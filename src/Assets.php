<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation;

defined( 'ABSPATH' ) || exit;

final class Assets {

	public const HANDLE = 'postio-address-finder';

	/** @var Assets|null */
	private static $instance = null;

	/** @var array<int,array<string,mixed>> */
	private $setups = [];

	/** @var array<int,string> */
	private $scanners = [];

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function register(): void {
		// wp_footer runs after the_content, so detectors that filter form
		// rendering mid-body (Gravity's gform_pre_render, etc.) have already
		// pushed their setups by the time we enqueue. Priority 1 keeps us
		// ahead of wp_print_footer_scripts (priority 20).
		add_action( 'wp_footer', [ $this, 'enqueue' ], 1 );
	}

	/**
	 * Queue a Postio.AddressFinder.setup() call for the current page render.
	 *
	 * @param string                $input  CSS selector for the search input.
	 * @param array<string,string>  $output Map of API field name → CSS selector.
	 * @param array<string,mixed>   $extras Optional extra options forwarded to setup().
	 */
	public function add_setup( string $input, array $output, array $extras = [] ): void {
		$input = trim( $input );
		if ( '' === $input || empty( $output ) ) {
			return;
		}
		foreach ( $this->setups as $existing ) {
			if ( $existing['input'] === $input ) {
				return;
			}
		}
		$this->setups[] = array_merge(
			$extras,
			[
				'input'  => $input,
				'output' => $output,
			]
		);
	}

	/**
	 * Add a self-contained client-side scanner. Each scanner runs after the
	 * bundle has loaded and may call window.PostioWPMount({ input, output })
	 * for any form it identifies.
	 */
	public function add_scanner( string $js ): void {
		$js = trim( $js );
		if ( '' === $js ) {
			return;
		}
		$this->scanners[] = $js;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function setups(): array {
		return $this->setups;
	}

	public function enqueue(): void {
		if ( empty( $this->setups ) && empty( $this->scanners ) ) {
			return;
		}

		$api_key = Settings::api_key();
		if ( '' === $api_key ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Postio: bundle skipped — no API key configured.' );
			}
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			Settings::bundle_url(),
			[],
			// The bundle now ships with the plugin, so version it for
			// cache-busting. Previously null, which suited an immutable
			// CDN path but leaves a locally-served file stale on upgrade.
			Settings::BUNDLE_VERSION,
			true
		);

		// The bundle expects baseUrl to include the /v1 path segment —
		// it concatenates baseUrl + '/connect' / '/address/search' etc.
		// Settings::api_url() returns the host root, so append /v1 here.
		$payload = [
			'apiKey'     => $api_key,
			'baseUrl'    => rtrim( Settings::api_url(), '/' ) . '/v1',
			'mode'       => Settings::lookup_mode(),
			'maxResults' => Settings::max_results(),
			'labels'     => [
				'postcodeHint'     => __( 'Enter your full postcode to see every address, e.g. BA2 4QT', 'postio-address-validation' ),
				'postcodeLoading'  => __( 'Finding addresses…', 'postio-address-validation' ),
				/* translators: {count} and {postcode} are filled in by the script — keep them as written. */
				'postcodeCount'    => __( '{count} addresses at {postcode}', 'postio-address-validation' ),
				/* translators: {postcode} is filled in by the script — keep it as written. */
				'postcodeCountOne' => __( '1 address at {postcode}', 'postio-address-validation' ),
				/* translators: {postcode} is filled in by the script — keep it as written. */
				'postcodeNone'     => __( 'No addresses found at {postcode}. Check the postcode, or type your address in.', 'postio-address-validation' ),
			],
			'setups'     => $this->setups,
		];

		$json = wp_json_encode(
			$payload,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);

		// Boot script:
		// - Defines window.PostioWPMount(setup) for scanners to call.
		// - Wraps setup() so onSelect restores the search input to
		//   address_line_1 when the input element is the same as line_1's
		//   target — otherwise the bundle's formatPicked summary clobbers
		//   the user-friendly Line 1 in builder fields.
		// - Closes the dropdown again after that write-back. See below.
		// - Runs all pre-built setups at DOMContentLoaded.
		//
		// The write-back has to dispatch `input`, because React- and
		// Vue-backed checkouts (WooCommerce's block checkout especially)
		// only take a value into their own state when they see the event.
		// But the finder is listening on that same input, so the event
		// reads as fresh typing: it debounces, searches for the address we
		// just picked, and reopens the list over the fields the customer
		// is trying to read. A feedback loop, not a rendering artefact.
		//
		// The finder writes `input.value` directly with no event, so this
		// only ever bites where our own onSelect fires — i.e. wherever the
		// search box IS line 1. That includes Woo and Fluent.
		//
		// Fixed with the finder's own public API: onSearch runs after
		// renderResults(), so closing there lands after the reopen and
		// the browser never paints the intermediate state. `justPicked`
		// is cleared on real keystrokes so a customer who carries on
		// typing still gets their results.
		$boot = '(function(){'
			. 'var cfg=' . $json . ';'
			. 'function mount(s){'
			. 'if(!window.Postio||!window.Postio.AddressFinder)return;'
			. 'var inp=(typeof s.input==="string")?document.querySelector(s.input):s.input;'
			. 'if(!inp)return;'
			// Site-wide lookup settings first, so a single setup can still
			// override them.
			. 'var opts=Object.assign({apiKey:cfg.apiKey,baseUrl:cfg.baseUrl,mode:cfg.mode,maxResults:cfg.maxResults,labels:cfg.labels},s);'
			. 'var line1=s.output&&s.output.address_line_1;'
			. 'var handle=null,justPicked=false;'
			// React installs a value tracker on every controlled input. A
			// plain `el.value = x` updates that tracker too, so the input
			// event we fire next looks like "no change" and React drops it
			// — the DOM shows the value but the framework store never gets
			// it, and the next re-render wipes it. Going through the
			// prototype's own setter leaves the tracker stale, so React
			// sees a real change. Verified against WooCommerce's block
			// checkout: plain assignment leaves wc/store/cart empty,
			// this lands the value.
			. 'var nativeSet=(function(){'
			. 'try{var d=Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype,"value");'
			. 'return d&&d.set?d.set:null;}catch(e){return null;}'
			. '})();'
			. 'function setValue(el,val){'
			. 'if(!el)return;'
			. 'try{if(nativeSet&&el instanceof window.HTMLInputElement){nativeSet.call(el,val);}else{el.value=val;}}'
			. 'catch(e){el.value=val;}'
			. 'el.dispatchEvent(new Event("input",{bubbles:true}));'
			. 'el.dispatchEvent(new Event("change",{bubbles:true}));'
			. '}'
			. 'opts.onSelect=function(addr){'
			. 'if(!addr)return;'
			. 'try{'
			// Re-resolve the search box rather than trusting the element
			// captured at mount. React-backed checkouts replace their input
			// nodes on re-render, so the original reference goes stale and
			// detached — writes to it are invisible and identity checks
			// against it silently fail.
			. 'var cur=(typeof s.input==="string")?document.querySelector(s.input):inp;'
			// Re-apply every mapped output through setValue. The bundle has
			// already written them with a plain assignment, which is enough
			// for a jQuery or plain-HTML form but invisible to a React one.
			. 'var out=s.output||{};'
			. 'for(var k in out){if(!Object.prototype.hasOwnProperty.call(out,k))continue;'
			. 'var v=addr[k];if(v==null)continue;'
			. 'var tgt=(typeof out[k]==="string")?document.querySelector(out[k]):out[k];'
			. 'if(tgt)setValue(tgt,String(v));}'
			// The search box doubles as line 1 on several builders. The
			// bundle leaves its formatted summary there; put the plain
			// line 1 back last, so it wins regardless of key order, and
			// mark the state as settled so the resulting input event does
			// not reopen the list.
			. 'var l1=line1?((typeof line1==="string")?document.querySelector(line1):line1):null;'
			. 'if(cur&&l1&&cur===l1&&typeof addr.address_line_1==="string"){'
			. 'justPicked=true;setValue(cur,addr.address_line_1);'
			. '}}catch(e){if(window.console)console.warn("Postio: onSelect failed",e);}'
			. '};'
			. 'var prevOnSearch=opts.onSearch;'
			. 'opts.onSearch=function(q,r){'
			. 'if(justPicked){justPicked=false;'
			. 'try{if(handle&&handle.close)handle.close();}catch(e){}}'
			. 'if(typeof prevOnSearch==="function")prevOnSearch(q,r);'
			. '};'
			. 'inp.addEventListener("keydown",function(e){'
			. 'if(e.key&&e.key.length===1||e.key==="Backspace"||e.key==="Delete")justPicked=false;'
			. '});'
			. 'try{handle=window.Postio.AddressFinder.setup(opts);}'
			. 'catch(e){if(window.console)console.warn("Postio: setup failed",e,s);}'
			. '}'
			. 'window.PostioWPMount=mount;'
			. 'window.PostioWPConfig=cfg;'
			. 'function go(){'
			. 'if(!window.Postio||!window.Postio.AddressFinder)return;'
			. 'for(var i=0;i<cfg.setups.length;i++)mount(cfg.setups[i]);'
			. '}'
			. 'if(document.readyState!=="loading"){go();}'
			. 'else{document.addEventListener("DOMContentLoaded",go);}'
			. '})();';

		wp_add_inline_script( self::HANDLE, $boot, 'after' );

		foreach ( $this->scanners as $scanner_js ) {
			wp_add_inline_script( self::HANDLE, $scanner_js, 'after' );
		}
	}
}
