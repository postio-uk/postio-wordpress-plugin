/* Editor script for wp:postio/address-finder.
 * No JSX — uses wp.element.createElement directly so the block ships
 * without a build step. */
(function (blocks, element, blockEditor, components, i18n) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	var outputFields = [
		['outputLine1',    __('Address line 1 selector', 'postio-address-validation')],
		['outputLine2',    __('Address line 2 selector', 'postio-address-validation')],
		['outputTown',     __('Town selector',           'postio-address-validation')],
		['outputPostcode', __('Postcode selector',       'postio-address-validation')]
	];

	blocks.registerBlockType('postio/address-finder', {
		edit: function (props) {
			var attrs = props.attributes;
			var setAttrs = props.setAttributes;

			var blockProps = blockEditor.useBlockProps({
				className: 'postio-address-finder-block'
			});

			var preview = el(
				'div',
				blockProps,
				attrs.inputLabel
					? el('label', { className: 'postio-address-finder-label' }, attrs.inputLabel)
					: null,
				el('input', {
					type: 'text',
					placeholder: attrs.inputPlaceholder,
					disabled: true,
					value: '',
					readOnly: true
				})
			);

			var searchPanel = el(
				components.PanelBody,
				{ title: __('Search field', 'postio-address-validation') },
				el(components.TextControl, {
					label: __('Label', 'postio-address-validation'),
					value: attrs.inputLabel,
					onChange: function (v) { setAttrs({ inputLabel: v }); }
				}),
				el(components.TextControl, {
					label: __('Placeholder', 'postio-address-validation'),
					value: attrs.inputPlaceholder,
					onChange: function (v) { setAttrs({ inputPlaceholder: v }); }
				}),
				el(components.TextControl, {
					label: __('Custom field id', 'postio-address-validation'),
					help: __('Leave blank for an auto-generated id.', 'postio-address-validation'),
					value: attrs.inputId,
					onChange: function (v) { setAttrs({ inputId: v }); }
				})
			);

			var outputControls = outputFields.map(function (pair) {
				var key = pair[0];
				var label = pair[1];
				return el(components.TextControl, {
					key: key,
					label: label,
					placeholder: '#field-id',
					value: attrs[key],
					onChange: function (v) {
						var update = {};
						update[key] = v;
						setAttrs(update);
					}
				});
			});

			var outputPanel = el(
				components.PanelBody,
				{ title: __('Output mappings', 'postio-address-validation'), initialOpen: false },
				el('p', null, __('CSS selectors for fields elsewhere on the page that should be filled when an address is picked.', 'postio-address-validation')),
				outputControls
			);

			var inspector = el(
				blockEditor.InspectorControls,
				null,
				searchPanel,
				outputPanel
			);

			return [preview, inspector];
		},
		save: function () {
			return null;
		}
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
