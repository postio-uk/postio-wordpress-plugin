/* Fluent Forms detector — finds .ff-name-address-wrapper blocks and wires
 * each sub-input by its data-key_name attribute. Multi-form-safe because
 * each address wrapper is its own DOM scope; we don't rely on form ids.
 *
 * `country` is intentionally not mapped: Fluent's select_country renders
 * a <select> keyed by ISO codes, but Postio returns the constituent
 * country name. Setting an unmatched value would clear the dropdown.
 */
(function () {
	'use strict';

	if (!window.PostioWPMount) return;

	var counter = 0;
	var mounted = new WeakSet();

	function bind(wrap, key) {
		var el = wrap.querySelector(
			'input[data-key_name="' + key + '"], select[data-key_name="' + key + '"]'
		);
		if (!el) return null;
		if (!el.id) el.id = 'postio-ff-' + key + '-' + (++counter);
		return '#' + el.id;
	}

	function tryMount() {
		var wraps = document.querySelectorAll('.ff-name-address-wrapper');
		for (var i = 0; i < wraps.length; i++) {
			var wrap = wraps[i];
			if (mounted.has(wrap)) continue;

			var line1 = bind(wrap, 'address_line_1');
			if (!line1) continue;

			var output = { address_line_1: line1 };
			var pairs = [
				['address_line_2', 'address_line_2'],
				['city', 'post_town'],
				['zip', 'postcode']
			];
			for (var j = 0; j < pairs.length; j++) {
				var sel = bind(wrap, pairs[j][0]);
				if (sel) output[pairs[j][1]] = sel;
			}

			window.PostioWPMount({ input: line1, output: output });
			mounted.add(wrap);
		}
	}

	tryMount();

	if (typeof MutationObserver === 'undefined') return;

	var pending = false;
	var observer = new MutationObserver(function () {
		if (pending) return;
		pending = true;
		setTimeout(function () { pending = false; tryMount(); }, 50);
	});
	observer.observe(document.body, { childList: true, subtree: true });

	setTimeout(function () { observer.disconnect(); }, 30000);
})();
