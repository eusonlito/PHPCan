/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
};

CKEDITOR.on('instanceReady', function (ev) {
	ev.editor.dataProcessor.htmlFilter.addRules({
		elements: {
			$: function (element) {
				if (element.name == 'img') {
					delete element.attributes.style;
				}

				return element;
			}
		}
	});
});
