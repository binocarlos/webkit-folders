////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// language.js
//
//
// responsible for providing language alternatives
// for application level items
//
// anywhere in the application you need a language reliant
// string - you must ask this class for it
//
// the data will be eventually supplied by the server
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


Webkit.Folders.Language = new function Language()
{
	this.languageDB = {
		US:{
			icon:'flag_us',
			mainmenu_user:'User',
			mainmenu_user_fullscreen:'Fullscreen',
			mainmenu_user_logout:'Log out',
			mainmenu_clipboard:'Clipboard',
			mainmenu_clipboard_clearitems:'Clear Items',
			mainmenu_clipboard_emptyclipboard:'Empty Clipboard',
			mainmenu_help:'Help',
			mainmenu_help_about:'About',
			mainmenu_language:'Language',
			mainmenu_language_de:'German',
			mainmenu_language_us:'English',
			clipboard_empty:'The clipboard is empty',
			clipboard_item_added:'Item added',
			clipboard_item_added_message:'was added to the clipboard',
			clipboard_items_added:'Items added',
			clipboard_items_added_message:'items were added to the clipboard',
			keywords:'Keywords',
			keyword_add:'Add keyword',
			keyword_delete:'Delete keyword',
			submenu_file:'File',
			submenu_edit:'Edit',
			submenu_view:'View',
			save:'Save',
			ok:'OK',
			cancel:'Cancel',
			new1:'New',
			edit:'Edit',
			openlocation:'Open Location',
			copylocation:'Copy Location',
			selectall:'Select All',
			view:'View',
			delete1:'Delete',
			cut:'Cut',
			copy:'Copy',
			ghost:'Ghost',
			paste:'Paste',
			nothing:'Nothing',
			itemtype:'Item Type',
			icons:'Icons',
			details:'Details',
			groupitemsby:'Group Items By',
			template:'Template',
			editthis:'Edit this ',			
			choose_item:'Choose Item'
		},
		DE:{
			icon:'flag_de',
			mainmenu_user:'User',
			mainmenu_user_fullscreen:'Vollbild',
			mainmenu_user_logout:'Log out',
			mainmenu_clipboard:'Zwischenablage',
			mainmenu_clipboard_clearitems:'Klare Positionen',
			mainmenu_clipboard_emptyclipboard:'Empty Merkliste',
			mainmenu_help:'Hilfe',
			mainmenu_help_about:'Über',
			mainmenu_language:'Sprache',
			mainmenu_language_de:'Deutsch',
			mainmenu_language_us:'Englisch',
			clipboard_empty:'Die Zwischenablage ist leer',
			clipboard_item_added:'Artikel hinzugefügt',
			clipboard_item_added_message:'wurde hinzugefügt, um die Zwischenablage',
			clipboard_items_added:'Artikel, die hinzugefügt',
			clipboard_items_added_message:'Gegenstände wurden in die Zwischenablage eingefügt',
			keywords:'Stichwort',
			keyword_add:'Stichwort add',
			keyword_delete:'Stichwort löschen',
			submenu_file:'Datei',
			submenu_edit:'Edit',
			submenu_view:'Anzeigen',
			save:'Sichern',
			ok:'OK',
			cancel:'Abbrechen',
			new1:'Neu',
			edit:'Edit',
			openlocation:'Ort öffnen',
			copylocation:'Kopieren Lage',
			selectall:'Alle auswählen',
			view:'Anzeigen',
			delete1:'Löschen',
			cut:'Schneiden',
			copy:'Kopieren',
			ghost:'Ghost',
			paste:'Paste',
			nothing:'Nichts',
			itemtype:'Item Type',
			icons:'Icons',
			details:'Details',
			groupitemsby:'Elemente gruppieren nach',
			template:'Template',
			editthis:'Diese',			
			choose_item:'Wählen Sie Objekt'
		}
	};
	
	this.currentLanguage = 'US';

	this.getLabel = function(labelName)
	{
		var languageSet = this.getLanguageSet();
		return languageSet[labelName];
	}
	
	this.getLanguageSet = function(whichLanguage)
	{
		if(whichLanguage==null)
		{
			whichLanguage = this.currentLanguage;
		}
		return this.languageDB[whichLanguage];
	}
	
};