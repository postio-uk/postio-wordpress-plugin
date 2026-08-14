/* Forminator detector — address subfields are wrapped in
 * <div id="{element_id}-{slug}"> blocks (street_address, address_line,
 * city, state, zip, country) and the input inside has class
 * forminator-input. Server-side input ids contain a per-render uid that
 * we can't predict, so the scanner walks the DOM by wrapper id instead.
 *
 * `country` is intentionally not mapped: Forminator's country field is
 * a <select> keyed by ISO codes; Postio returns the constituent country
 * name. Setting an unmatched value would clear the dropdown.
 */
(function () {
	'use strict';

	if (!window.PostioWPMount) return;

	var counter = 0;
	var mounted = new WeakSet();

	function findWrap(form, exactId) {
		var nodes = form.querySelectorAll('[id]');
		for (var i = 0; i < nodes.length; i++) {
			if (nodes[i].id === exactId) return nodes[i];
		}
		return null;
	}

	function inputIn(wrap) {
		if (!wrap) return null;
		return wrap.querySelector('input, select, textarea');
	}

	function tryMount() {
		var forms = document.querySelectorAll('form.forminator-ui');
		for (var i = 0; i < forms.length; i++) {
			var form = forms[i];
			if (mounted.has(form)) continue;

			var streetBox = form.querySelector('[id$="-street_address"]');
			if (!streetBox) continue;

			var line1 = inputIn(streetBox);
			if (!line1) continue;

			if (!line1.id) line1.id = 'postio-fmin-line1-' + (++counter);
			var idBase = streetBox.id.substring(0, streetBox.id.length - '-street_address'.length);

			var output = { address_line_1: '#' + line1.id };

			var pairs = [
				['-address_line', 'address_line_2'],
				['-city', 'post_town'],
				['-zip', 'postcode']
			];
			for (var j = 0; j < pairs.length; j++) {
				var wrap = findWrap(form, idBase + pairs[j][0]);
				var el = inputIn(wrap);
				if (!el) continue;
				if (!el.id) el.id = 'postio-fmin-' + pairs[j][0].replace(/[^a-z0-9]/gi, '') + '-' + (++counter);
				output[pairs[j][1]] = '#' + el.id;
			}

			window.PostioWPMount({ input: '#' + line1.id, output: output });
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
