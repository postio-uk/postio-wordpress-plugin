/* CF7 detector — finds CF7 forms with at least one postio-* opt-in class
 * and wires the address fields. CF7 has no native composite address
 * field, so users compose it from individual [text] tags with class
 * attributes:
 *
 *   class:postio-search   (optional) dedicated search input — kept clean,
 *                         shows the formatted picked address.
 *   class:postio-line-1   address line 1 (also serves as the search input
 *                         if .postio-search is absent).
 *   class:postio-line-2   address line 2.
 *   class:postio-town     town / city.
 *   class:postio-postcode postcode.
 *   class:postio-country  country (rarely useful — see API docs).
 *
 * CF7's class: shortcode attribute lands on the <input> directly, not a
 * wrapper, so `form.querySelector('.postio-line-1')` returns the input.
 * We don't rely on form.id (CF7 puts the id on the wrapper div, not the
 * form element) — instead we assign DOM ids on the fly within each
 * form's scope.
 */
(function () {
	'use strict';

	if (!window.PostioWPMount) return;

	var counter = 0;

	function ensureId(el, prefix) {
		if (!el.id) el.id = prefix + '-' + (++counter);
		return '#' + el.id;
	}

	function go() {
		var forms = document.querySelectorAll('form.wpcf7-form');
		for (var i = 0; i < forms.length; i++) {
			var form = forms[i];

			var line1 = form.querySelector('.postio-line-1');
			if (!line1) continue;
			var line1Sel = ensureId(line1, 'postio-cf7-line1');

			var search = form.querySelector('.postio-search');
			var searchSel = search ? ensureId(search, 'postio-cf7-search') : null;

			var output = { address_line_1: line1Sel };
			var pairs = [
				['address_line_2', '.postio-line-2'],
				['post_town', '.postio-town'],
				['postcode', '.postio-postcode'],
				['country', '.postio-country']
			];
			for (var j = 0; j < pairs.length; j++) {
				var el = form.querySelector(pairs[j][1]);
				if (!el) continue;
				output[pairs[j][0]] = ensureId(el, 'postio-cf7-' + pairs[j][1].replace(/[^a-z0-9]/gi, ''));
			}

			window.PostioWPMount({ input: searchSel || line1Sel, output: output });
		}
	}

	if (document.readyState !== 'loading') go();
	else document.addEventListener('DOMContentLoaded', go);
})();
