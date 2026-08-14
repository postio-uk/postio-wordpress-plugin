/* Ninja Forms detector — NF doesn't ship a composite address field, just
 * a single text "address" field plus city/zip/country. Users opt in by
 * adding Display CSS Classes on the relevant fields:
 *
 *   postio-search   (optional) dedicated lookup input. If absent, line 1
 *                   doubles as the search field.
 *   postio-line-1, postio-line-2, postio-town,
 *   postio-postcode — the output targets.
 *
 * NF renders forms client-side via Backbone, so we re-scan on DOM
 * mutations (and also listen for nfFormReady when jQuery is present).
 */
(function () {
	'use strict';

	if (!window.PostioWPMount) return;

	var counter = 0;
	var mounted = new WeakSet();

	function inputIn(el) {
		if (!el) return null;
		if (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') return el;
		return el.querySelector('input, select, textarea');
	}

	function bind(form, cssClass) {
		var node = form.querySelector(cssClass);
		var el = inputIn(node);
		if (!el) return null;
		if (!el.id) el.id = 'postio-nf-' + cssClass.replace(/[^a-z0-9]/gi, '') + '-' + (++counter);
		return '#' + el.id;
	}

	function tryMount() {
		var forms = document.querySelectorAll('.nf-form-cont');
		for (var i = 0; i < forms.length; i++) {
			var form = forms[i];
			if (mounted.has(form)) continue;

			var line1 = bind(form, '.postio-line-1');
			if (!line1) continue;

			var search = bind(form, '.postio-search');

			var output = { address_line_1: line1 };
			var pairs = [
				['.postio-line-2', 'address_line_2'],
				['.postio-town', 'post_town'],
				['.postio-postcode', 'postcode']
			];
			for (var j = 0; j < pairs.length; j++) {
				var sel = bind(form, pairs[j][0]);
				if (sel) output[pairs[j][1]] = sel;
			}

			window.PostioWPMount({ input: search || line1, output: output });
			mounted.add(form);
		}
	}

	tryMount();

	if (window.jQuery) {
		window.jQuery(document).on('nfFormReady', tryMount);
	}

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
