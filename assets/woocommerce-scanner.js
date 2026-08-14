/* WooCommerce detector — wires both classic checkout (#billing_address_1, …)
 * and block checkout (#billing-address_1, …). Block checkout renders via
 * React after page load, so we re-scan on DOM mutations until each section
 * has been mounted (or until 30s elapses).
 *
 * `country` is intentionally not mapped: WC's country field is a <select>
 * keyed by ISO 3166-1 alpha-2 (GB), but Postio returns the constituent
 * country name (e.g. "England"). Setting an unmatched value would clear
 * the dropdown, which is worse than leaving it on the existing default.
 */
(function () {
	'use strict';

	if (!window.PostioWPMount) return;

	var mappings = [
		// Classic checkout — billing.
		{
			input: '#billing_address_1',
			output: {
				address_line_1: '#billing_address_1',
				address_line_2: '#billing_address_2',
				post_town: '#billing_city',
				postcode: '#billing_postcode'
			}
		},
		// Classic checkout — shipping.
		{
			input: '#shipping_address_1',
			output: {
				address_line_1: '#shipping_address_1',
				address_line_2: '#shipping_address_2',
				post_town: '#shipping_city',
				postcode: '#shipping_postcode'
			}
		},
		// Block checkout — billing.
		{
			input: '#billing-address_1',
			output: {
				address_line_1: '#billing-address_1',
				address_line_2: '#billing-address_2',
				post_town: '#billing-city',
				postcode: '#billing-postcode'
			}
		},
		// Block checkout — shipping.
		{
			input: '#shipping-address_1',
			output: {
				address_line_1: '#shipping-address_1',
				address_line_2: '#shipping-address_2',
				post_town: '#shipping-city',
				postcode: '#shipping-postcode'
			}
		}
	];

	var mounted = {};

	function tryMount() {
		for (var i = 0; i < mappings.length; i++) {
			var m = mappings[i];
			if (mounted[m.input]) continue;
			if (!document.querySelector(m.input)) continue;
			window.PostioWPMount(m);
			mounted[m.input] = true;
		}
	}

	function allMounted() {
		for (var i = 0; i < mappings.length; i++) {
			if (document.querySelector(mappings[i].input) && !mounted[mappings[i].input]) {
				return false;
			}
		}
		return true;
	}

	tryMount();

	if (typeof MutationObserver === 'undefined') return;

	var pending = false;
	var observer = new MutationObserver(function () {
		if (pending) return;
		pending = true;
		setTimeout(function () {
			pending = false;
			tryMount();
		}, 50);
	});

	observer.observe(document.body, { childList: true, subtree: true });

	// Stop observing after 30 s — block checkout React mount is well under
	// that, and we don't want a long-lived observer.
	setTimeout(function () { observer.disconnect(); }, 30000);
})();
