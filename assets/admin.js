(function () {
	'use strict';

	if (typeof window.PostioAVAdmin === 'undefined') {
		return;
	}

	var cfg = window.PostioAVAdmin;

	function el(id) { return document.getElementById(id); }

	// API key states.
	//
	// The saved key is carried on data-saved. Anything else in the box is
	// a candidate the site is not using yet, so the row switches to the
	// modified state: Save to apply it, Cancel to put the saved one back.
	// Cancel is what stops a bad paste or a failed test being a dead end.
	var keyWrap = document.querySelector('.postio-av-key');
	var keyField = el('postio-av-api-key');
	var revertBtn = el('postio-av-revert');

	function savedKey() {
		return keyField ? (keyField.getAttribute('data-saved') || '') : '';
	}

	function isModified() {
		return !!keyField && keyField.value.trim() !== savedKey().trim();
	}

	function syncKeyState() {
		if (!keyWrap) return;
		keyWrap.classList.toggle('is-modified', isModified());
	}

	if (keyField && keyWrap) {
		keyField.addEventListener('input', function () {
			syncKeyState();
			// A result from the previous key says nothing about this one.
			var result = el('postio-av-test-result');
			if (result) {
				result.className = 'postio-av-test-result';
				result.textContent = '';
			}
		});
		syncKeyState();
	}

	if (revertBtn && keyField) {
		revertBtn.addEventListener('click', function () {
			keyField.value = savedKey();
			syncKeyState();
			var result = el('postio-av-test-result');
			if (result) {
				result.className = 'postio-av-test-result';
				result.textContent = '';
			}
			keyField.focus();
		});
	}

	// Test connection.
	var testBtn = el('postio-av-test');
	if (testBtn) {
		testBtn.addEventListener('click', function () {
			var keyInput = el('postio-av-api-key');
			var result = el('postio-av-test-result');
			if (!keyInput || !result) return;

			if (!keyInput.value.trim()) {
				result.className = 'postio-av-test-result is-fail';
				result.textContent = cfg.i18n.test_empty;
				return;
			}

			result.className = 'postio-av-test-result';
			result.textContent = cfg.i18n.test_busy;
			testBtn.disabled = true;

			var body = new URLSearchParams();
			body.append('action', 'postio_av_test_connection');
			body.append('nonce', cfg.nonce);
			body.append('api_key', keyInput.value);

			fetch(cfg.ajax_url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString()
			}).then(function (r) {
				return r.json().catch(function () { return null; });
			}).then(function (json) {
				testBtn.disabled = false;
				if (json && json.success) {
					result.className = 'postio-av-test-result is-ok';
					// Be explicit that a passing test on an edited field
					// does not mean the site is using that key yet.
					result.textContent = (json.data && json.data.message ? json.data.message : '')
						+ (isModified() ? ' ' + cfg.i18n.test_unsaved : '');
				} else {
					result.className = 'postio-av-test-result is-fail';
					result.textContent = (json && json.data && json.data.message) || cfg.i18n.test_fail;
				}
			}).catch(function () {
				testBtn.disabled = false;
				result.className = 'postio-av-test-result is-fail';
				result.textContent = cfg.i18n.test_fail;
			});
		});
	}

	// Custom mappings repeater.
	var container = el('postio-av-mappings');
	var addBtn = el('postio-av-add-mapping');
	var template = el('postio-av-mapping-template');

	if (container && addBtn && template) {
		addBtn.addEventListener('click', function () {
			var i = parseInt(container.getAttribute('data-next-index') || '0', 10);
			container.setAttribute('data-next-index', String(i + 1));

			var html = template.innerHTML.replace(/__INDEX__/g, String(i));
			var wrap = document.createElement('div');
			wrap.innerHTML = html.trim();
			var row = wrap.firstElementChild;
			if (row) {
				container.appendChild(row);
			}
		});

		container.addEventListener('click', function (e) {
			var t = e.target;
			if (t && t.classList && t.classList.contains('postio-av-remove-mapping')) {
				var row = t.closest('[data-row]');
				if (row && row.parentNode) {
					row.parentNode.removeChild(row);
				}
			}
		});
	}
})();

// Grey out "Suggestions to show" while postcode lookup is picked — it only
// applies to type-ahead.
( function () {
	var row = document.querySelector( '.postio-av-results-row' );
	if ( ! row ) {
		return;
	}
	document.querySelectorAll( '.postio-av-modes input[type="radio"]' ).forEach( function ( radio ) {
		radio.addEventListener( 'change', function () {
			if ( radio.checked ) {
				row.setAttribute( 'data-mode', radio.value );
			}
		} );
	} );
} )();
