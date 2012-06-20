/**
 * Basic sample plugin inserting abbreviation elements into CKEditor editing area.
 * Updated to add context menu support and possibility to edit a previously added abbreviation element.
 */

// Register the plugin with the editor.
// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.plugins.html
CKEDITOR.plugins.add( 'abbr',
{
	// The plugin initialization logic goes inside this method.
	// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.pluginDefinition.html#init
	init: function( editor )
	{
		// Place the icon path in a variable to make it easier to refer to it later.
		// "this.path" refers to the directory where the plugin.js file resides.
		var iconPath = this.path + 'images/icon.png';

		// Define an editor command that inserts an abbreviation. 
		// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#addCommand
		editor.addCommand( 'abbrDialog',new CKEDITOR.dialogCommand( 'abbrDialog' ) );
		
		// Create a toolbar button that executes the plugin command. 
		// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.ui.html#addButton
		editor.ui.addButton( 'Abbr',
		{
			// Toolbar button tooltip.
			label: 'Insert Abbreviation',
			// Reference to the plugin command name.
			command: 'abbrDialog',
			// Button's icon file path.
			icon: iconPath
		} );
		
		// Add context menu support.
		if ( editor.contextMenu )
		{
			// Register a new context menu group.
			editor.addMenuGroup( 'myGroup' );
			// Register a new context menu item.
			editor.addMenuItem( 'abbrItem',
			{
				// Item label.
				label : 'Edit Abbreviation',
				// Item icon path using the variable defined above.
				icon : iconPath,
				// Reference to the plugin command name.
				command : 'abbrDialog',
				// Context menu group that this entry belongs to.
				group : 'myGroup'
			});
			// Enable the context menu only for an <abbr> element.
			editor.contextMenu.addListener( function( element )
			{
				// Get to the closest <abbr> element that contains the selection.
				// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.node.html#getAscendant
				if ( element )
					element = element.getAscendant( 'abbr', true );
				// Return a context menu object in an enabled, but not active state.
				// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.html#.TRISTATE_OFF
				if ( element && !element.isReadOnly() && !element.data( 'cke-realelement' ) )
		 			return { abbrItem : CKEDITOR.TRISTATE_OFF };
				// Return nothing if the conditions are not met.
		 		return null;
			});
		}
		
		// Add a dialog window definition containing all UI elements and listeners.
		// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.html#.add
		CKEDITOR.dialog.add( 'abbrDialog', function ( editor )
		{
			return {
				// Basic properties of the dialog window: title, minimum size.
				// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.dialogDefinition.html
				title : 'Abbreviation Properties',
				minWidth : 400,
				minHeight : 200,
				// Dialog window contents.
				// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.definition.content.html
				contents :
				[
					{
						// Definition of the Basic Settings dialog window tab (page) with its id, label and contents.
						// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.contentDefinition.html
						id : 'tab1',
						label : 'Basic Settings',
						elements :
						[
							{
								// Dialog window UI element: a text input field for the abbreviation text.
								// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.ui.dialog.textInput.html
								type : 'text',
								id : 'abbr',
								// Text that labels the field.
								// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.ui.dialog.labeledElement.html#constructor
								label : 'Abbreviation',
								// Validation checking whether the field is not empty.
								validate : CKEDITOR.dialog.validate.notEmpty( "Abbreviation field cannot be empty" ),
								// Function to be run when the setupContent method of the parent dialog window is called.
								// It can be used to initialize the value of the field.
								// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.element.html#setValue
								// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.element.html#getText
								setup : function( element )
								{
									this.setValue( element.getText() );
								},
								// Function to be run when the commitContent method of the parent dialog window is called.
								// Set the element's text content to the value of this field.
								// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.element.html#setText
								// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.element.html#getValue
								commit : function( element )
								{
									element.setText( this.getValue() );
								}
							},
							{
								// Another text input field for the explanation text with a label and validation.
								type : 'text',
								id : 'title',
								label : 'Explanation',
								validate : CKEDITOR.dialog.validate.notEmpty( "Explanation field cannot be empty" ),
								// Function to be run when the setupContent method of the parent dialog window is called.
								// It can be used to initialize the value of the field.
								// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.element.html#getAttribute
								setup : function( element )
								{
									this.setValue( element.getAttribute( "title" ) );
								},
								// Function to be run when the commitContent method of the parent dialog window is called.
								// Set the element's title attribute to the value of this field.
								// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.element.html#setAttribute
								commit : function( element )
								{
									element.setAttribute( "title", this.getValue() );
								}
							}	 
						]
					},
					{
						// Definition of the Advanced Settings dialog window tab with its id, label, and contents.
						id : 'tab2',
						label : 'Advanced Settings',
						elements :
						[
							{
								// Yet another text input field for the abbreviation ID.
								// No validation added since this field is optional.
								type : 'text',
								id : 'id',
								label : 'Id',
								// Function to be run when the setupContent method of the parent dialog window is called.
								// It can be used to initialize the value of the field. 
								setup : function( element )
								{
									this.setValue( element.getAttribute( "id" ) );
								},
								// Function to be run when the commitContent method of the parent dialog window is called.
								commit : function ( element )
								{
									var id = this.getValue();
									// If the field is non-empty, use its value to set the element's id attribute.
									if ( id )
										element.setAttribute( 'id', id );
									// If on editing the value was removed by the user, the id attribute needs to be removed.
									// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.element.html#removeAttribute
									else if ( !this.insertMode )
										element.removeAttribute( 'id' );
								}
							}
						]
					}
				],
				// This method is invoked once a dialog window is loaded. 
				onShow : function()
				{
					// Get the element selected in the editor.
					// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#getSelection
					var sel = editor.getSelection(),
					// Assigning the element in which the selection starts to a variable.
					// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.selection.html#getStartElement
						element = sel.getStartElement();
					
					// Get the <abbr> element closest to the selection.
					if ( element )
						element = element.getAscendant( 'abbr', true );
					
					// Create a new <abbr> element if it does not exist.
					// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.document.html#createElement
					// For a new <abbr> element set the insertMode flag to true.
					if ( !element || element.getName() != 'abbr' || element.data( 'cke-realelement' ) )
					{
						element = editor.document.createElement( 'abbr' );
						this.insertMode = true;
					}
					// If an <abbr> element already exists, set the insertMode flag to false.
					else
						this.insertMode = false;
					
					// Store the reference to the <abbr> element in a variable.
					this.element = element;
					
					// Invoke the setup functions of the element.
					this.setupContent( this.element );
				},				
				// This method is invoked once a user closes the dialog window, accepting the changes.
				// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.dialogDefinition.html#onOk
				onOk : function()
				{
					// A dialog window object.
					// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.html 
					var dialog = this,
						abbr = this.element;
					
					// If we are not editing an existing abbreviation element, insert a new one.
					// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#insertElement
					if ( this.insertMode )
						editor.insertElement( abbr );
					
					// Populate the element with values entered by the user (invoke commit functions).
					this.commitContent( abbr );
				}
			};
		} );
	}
} );