/* Elementor Forms detector — Elementor Pro Forms has no composite
 * address field, just individual Text fields. Users opt in by adding
 * Custom CSS Classes under each field's Advanced tab:
 *
 *   postio-search   (optional) dedicated lookup input. If absent, line 1
 *                   doubles as the search field.
 *   postio-line-1, postio-line-2, postio-town,
 *   postio-postcode — the output targets.
 *
 * The class lands on the wrapper div; we dig for the input inside each.
 */
(function () {
	'use strict';

	if (!window.PostioWPMount) return;

	var counter = 0;
	var mounted = new WeakSet();

	function inputIn(node) {
		if (!node) return null;
		if (node.tagName === 'INPUT' || node.tagName === 'SELECT' || node.tagName === 'TEXTAREA') return node;
		return node.querySelector('input, select, textarea');
	}

	function bind(form, cssClass) {
		var node = form.querySelector(cssClass);
		var el = inputIn(node);
		if (!el) return null;
		if (!el.id) el.id = 'postio-elem-' + cssClass.replace(/[^a-z0-9]/gi, '') + '-' + (++counter);
		return '#' + el.id;
	}

	function tryMount() {
		var forms = document.querySelectorAll('form.elementor-form');
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
