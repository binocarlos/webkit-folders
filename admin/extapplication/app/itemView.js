////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// ItemView.js
//
//
// the main interface for looking at the contents of an item
// it deals with different templating selections for different types of item
// at the moment only really deals with folders
//
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

Webkit.Folders.ItemView = Ext.extend(Ext.util.Observable, {
	
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// INIT
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// initial variables
	
	templates: {},
	
	schemas: {},
	
	currentItem: null,
	
	history: [],
	
	currentHistoryIndex: 0,
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// required params:
	//		id		= the id of this component
	//		name	= the title this component
	//		uri		= the data uri this component will use
		
    constructor: function(config)
    {	
    	Ext.apply(this, config);
    	
		this.addEvents(
			'loaditem',				// a request has been made internally to load an item
									// so we will now trigger events to get that done from above
									
			'edititem',				// a request has been made internally to edit an item using its form
									// again - time to delegate									
									
			'deleteitems',			// a request has been made internally to delete some items
									// we will delegate this request providing this as a callback
									
									
			'itemnamechanged',		// an items name has been changed internally - lets tell everyone
									
			'moveitems',			// items have been requested to move to another
			
			'clipboardcut',				// items have been cut from the dataview
			'clipboardcopy',			// items have been copied from the dataview
			'clipboardghost',			// items have been ghosted from the dataview
			'clipboardpaste',			// items have been pasted into the dataview
			
			'clipboardrequested'	// the clipboard data has been requested - we shall see if there is anything that will give it to us!			
		);

        Webkit.Folders.ItemView.superclass.constructor.call(config);
        
        this.createGUI();
	},
	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
// Main Item Events

	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// a call to open an item - opening means 'viewing' it
	// therefore - we don't want to open the itemForm but a view
	//
	// the path array is an array of items that form the path to this one
	
	displayItemChildren: function(item, pathArray)
	{
		if(!item) { return; }
		
		var shouldAddHistory = true;

		if(item.historyFlag)
		{
			item.historyFlag = false;
			shouldAddHistory = false;
		}
			
		if(shouldAddHistory)
		{
			this.addHistory(item);
		}
			
		this.disableMenus();
			
		this.currentItem = item;
		
		this.dataView.currentItem = item;
		
		var records = [];
		
		var children = item.getChildren();
		
		for(var i=0; i<children.length; i++)
		{
			var childItem = children[i];
			
			records.push(childItem);
		}
		
		this.store.removeAll();
		this.store.add(records);
			
		this.panel.setTitle(item.get('name'));
			
		this.setItemPath(item.path, pathArray);
			
		this.toggleHistoryButtons();
	
		this.rebuildMenus();
		this.reflowMenus();
		
		this.dataView.resizePanelToContent.defer(10, this.dataView);
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// a call to add a new item - you must provide the item
	
	editItem : function(item)
	{
		if(item==null) { return; }
		
		this.fireEvent('edititem', item, this);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// a call to add a new item - you must provide the item
	
	deleteItems: function(items)
	{
		if(items==null) { return; }
		
		this.fireEvent('deleteitems', items, this);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// a call to add a new item - you must provide the item_type
	
	addItem : function(itemType)
	{
		if(itemType==null) { return; }
		
		var newItem = Webkit.Folders.Item.prototype.createBlank(itemType, this.currentItem.id);
			
		this.fireEvent('edititem', newItem, this);
	},
	
	
	
	
	reloadItem: function()
	{
		this.fireEvent('loaditem', this.currentItem, this);
		//this.openItem(this.currentItem);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// responds to a double click by checking what type of item it is and triggering the 'viewitem' event
	// if its a folder it opens that folder in the view 
	// if its not a folder it will open the editor for that type of item
	
	dataViewDoubleClick : function(dataView, index, node, e)
	{
		var selectedItemRecord = dataView.getRecord(node);
		
		this.fireEvent('loaditem', selectedItemRecord, this);
		
		//this.openItem(selectedItem);
	},

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// responds to a change of the selection - this will reflow the menus

	dataViewSelectionChanged: function(dataView, selections)
	{
		this.reflowMenus();
	},
	
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// MENU
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// responds to a logout request
	
	fileMenuClicked: function(menuItem, e)
	{
		
	},
	
	selectionOpenMenuClicked: function()
	{
		var currentItems = this.createItemsFromSelection();
			
		if(currentItems.length==1)
		{
			this.fireEvent('loaditem', currentItems[0], this);
			//this.openItem(currentItems[0]);
		}
	},
	
	selectionEditMenuClicked: function()
	{
		var currentItems = this.createItemsFromSelection();
			
		if(currentItems.length==1)
		{
			this.fireEvent('edititem', currentItems[0], this);
			
			//this.editItem(currentItems[0]);
		}
	},
	
	selectionDeleteMenuClicked: function()
	{
		var currentItems = this.createItemsFromSelection();
			
		this.fireEvent('deleteitems', currentItems, this);
		//this.deleteItems(currentItems);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// responds to the add menu by opening the appropriate form
	
	fileAddMenuClicked : function(menuItem, e)
	{
		this.addItem(menuItem.item_type);
	},

	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// responds to the view menu changing by changing the template and itemSelector for the dataview
	
	templateMenuClicked : function(menuItem, e)
	{
		if(menuItem.group != 'templateMenu') { return; }
		
		this.dataView.setItemSelector('div.' + menuItem.view + '-item');
		this.dataView.setTemplate(menuItem.view);
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// responds to the group menu changing by changing the grouping field for the dataView
	
	groupByMenuClicked: function(menuItem, e)
	{
		if(menuItem.group != 'groupByMenu') { return; }
		
		var groupField = menuItem.groupBy;
		
		if(menuItem.groupBy == 'none')
		{
			groupField = null;
		}
		
		this.dataView.setGroupBy(groupField);
	},
	
	selectionMenuClicked: function(menuItem, e)
	{
		if(menuItem.id == 'selection-selectall')
		{
			this.dataView.selectAllItems();
		}
		else if(menuItem.id == 'selection-cut')
		{
			this.cutItems();
		}
		else if(menuItem.id == 'selection-copy')
		{
			this.copyItems();
		}
		else if(menuItem.id == 'selection-ghost')
		{
			this.ghostItems();
		}
		else if(menuItem.id == 'selection-paste')
		{
			this.pasteItems();
		}
		else if(menuItem.id=='selection-open')
		{
			this.selectionOpenMenuClicked();
		}
		else if(menuItem.id=='selection-edit')
		{
			this.selectionEditMenuClicked();
		}
		else if(menuItem.id=='selection-delete')
		{
			this.selectionDeleteMenuClicked();
		}		
	},
	
	contextMenuClicked: function(menuItem, e)
	{		
		if(menuItem.id == 'context-paste')
		{
			this.pasteItems();
		}
		else if(menuItem.id == 'context-open')
		{
			this.selectionOpenMenuClicked();
		}
		else if(menuItem.id == 'context-edit')
		{
			this.selectionEditMenuClicked();
		}
		else if(menuItem.id == 'context-delete')
		{
			this.selectionDeleteMenuClicked();
		}
		else if(menuItem.id == 'context-cut')
		{
			this.cutItems();
		}
		else if(menuItem.id == 'context-copy')
		{
			this.copyItems();
		}
		else if(menuItem.id == 'context-ghost')
		{
			this.ghostItems();
		}
	},
	
	contextAddMenuClicked: function(menuItem, e)
	{
		this.addItem(menuItem.item_type);
	},
  
    
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// the context menu has been hidden so lets reshow all nodes
	
	contextMenuHidden: function()
    {
    	this.dataView.unGhostAllNodes();
    	
    	this.contextMenu.destroy();
    },
    	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// CLIPBOARD
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	ghostItems: function()
	{
		var currentItems = this.createItemsFromSelection();
		
		if(currentItems.length<=0) { return; }
		
		this.fireEvent('clipboardghost', currentItems, this);
		
		this.reflowMenus();
	},
	
	cutItems: function()
	{
		var currentItems = this.createItemsFromSelection();
		
		if(currentItems.length<=0) { return; }
		
		this.fireEvent('clipboardcut', currentItems, this);
		
		this.reflowMenus();
	},
	
	copyItems: function()
	{
		var currentItems = this.createItemsFromSelection();
		
		if(currentItems.length<=0) { return; }
		
		this.fireEvent('clipboardcopy', currentItems, this);
		
		this.reflowMenus();
	},
	
	pasteItems: function()
	{
		this.fireEvent('clipboardpaste', this.currentItem, this);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if you are allowed to paste what is in the clipboard into the current folder
	
	canPaste: function()
	{
		this.fireEvent('clipboardrequested', this);
		
		var clipboardData = this.clipboardData.data;
		var clipboardMode = this.clipboardData.mode;
		
		if(clipboardData == null)
		{
			clipboardData = [];	
		}
		
		if(clipboardData.length<=0) { return false; }
		
		for(var i=0; i<clipboardData.length; i++)
		{
			var pasteItem = clipboardData[i];
			
			if(!this.currentItem.canAddItemOfType(pasteItem.getType()))
			{
				return false;	
			}

			// can we make a copy of this thing in the same place?
			if(clipboardMode!='copy')
			{
				if(this.currentItem.id == pasteItem.get('parent_id'))
				{
					return false;
				}
			}
			
			if(this.isItemInCurrentItemPath(pasteItem))
			{
				return false;
			}
		}
		
		return true;
	},
	
	clipboardDataCleared: function()
	{
		this.reflowMenus();
	},	
	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// History
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// manages history info (back / forward etc)
	
	addHistory: function(item)
	{
		this.history.push(item);
		
		this.currentHistoryIndex = this.history.length-1;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you the item in the history representing the 'current' page
	// if you currentHistoryIndex - it means this page is different
	
	getHistoryItem: function()
	{
		var itemIndex = this.currentHistoryIndex;	
		
		return this.history[itemIndex];
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if there is anything left in the history
	
	canGoBack: function()
	{
		if(this.history.length<=1) { return false; }
		
		if(this.currentHistoryIndex>0) { return true; }
		
		return false;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// go backwards through the history
	
	goBack: function()
	{
		if(!this.canGoBack()) { return; }
		
		this.currentHistoryIndex--;
		
		var historyItem = this.getHistoryItem();
		
		historyItem.historyFlag = true;
		
		this.fireEvent('loaditem', historyItem, this);
		
		//this.openItem(historyItem, true);
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if there is anything left in the history
	
	canGoForward: function()
	{
		if(this.history.length<=1) { return false; }
		
		if(this.currentHistoryIndex < this.history.length - 1) { return true; }
		
		return false;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// go forwards through the history
	
	goForward: function()
	{
		if(!this.canGoForward()) { return; }
		
		this.currentHistoryIndex++;
		
		var historyItem = this.getHistoryItem();
		
		historyItem.historyFlag = true;
		
		this.fireEvent('loaditem', historyItem, this);
		
		//this.openItem(historyItem, true);
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if there is an item above the current one
	
	canGoUp: function()
	{
		if(this.itemPathArray.length<=1) { return false; }
		
		return true;
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// navigate one up from here
	
	goUp: function()
	{
		if(!this.canGoUp()) { return; }
		
		var parentItem = this.itemPathArray[this.itemPathArray.length-2];
		
		this.fireEvent('loaditem', parentItem, this);
		
		//this.openItem(parentItem);
	},
	
	

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// activate / deactivate the history buttons
	
	toggleHistoryButtons : function()
	{
		if(this.canGoBack())
		{
			this.backButton.enable();
		}
		else
		{
			this.backButton.disable();	
		}
		
		if(this.canGoForward())
		{
			this.forwardButton.enable();
		}
		else
		{
			this.forwardButton.disable();	
		}
		
		if(this.canGoUp())
		{
			this.upButton.enable();
		}
		else
		{
			this.upButton.disable();	
		}
	},	


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// Tools
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// removes the given items from the store
	// it makes sure it gets the items by id first to avoid copy problems
	
	removeItemsFromStore: function(itemArray)
	{
		if(itemArray==null) { return; }
		
		for(var i=0; i<itemArray.length; i++)
		{
			var record = this.store.getById(itemArray[i].id);
			
			this.store.remove(record);
		}
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you the item being currently edited
	
	getCurrentItem: function()
	{
		return this.currentItem;
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// sets the info about the current items path
	// itemArray is an array of Item objects
	// the array represents each item on the way to the current item from the top
	
	setItemPath: function(itemPath, itemArray)
	{
		this.locationTextField.setValue(itemPath);
		
		if(itemArray==null)
		{
			return '';
		}
		
		this.itemPathArray = itemArray;
		
		/*
		var pathTitleArray = [];
		var driveTitle = '';
		
		var systemIndex = 0;
		var systemItem = itemArray[systemIndex];
		
		// do we have an installation as our root item?
		// if so - we must start at the system level
		if(systemItem.get('item_type')=='installation')
		{
			systemIndex = 1;
			systemItem = itemArray[systemIndex];
		}

		// this means we only have the system folder
		if(itemArray.length<=1 + systemIndex)
		{
			driveTitle = 'system:/';
		}
		// this means we are looking at somewhere in the top level folder
		else
		{
			var topLevelItem = itemArray[1 + systemIndex];
			
			// if we have a diskdrive - we need to see if it is the default one
			// in which case the drive is '/'
			
			if(topLevelItem.isOfType('disk'))
			{
				driveTitle = '/';
			}
			else
			{
				driveTitle = topLevelItem.get('item_type') + ':/';
			}
		}
		
		var pathTitleArray = [];
		
		for(var i=2 + systemIndex; i<this.itemPathArray.length; i++)
		{
			var part = this.itemPathArray[i].get('name').toLowerCase();		
			
			part = part.replace(/[^\w ]/g, '');
			part = part.replace(/ +/g, ' ');
			part = part.replace(/ /g, '_');
			
			pathTitleArray.push(part);
		}
		
		// the root folder will have a :/ so lets not add another one
		var pathTitle = driveTitle + pathTitleArray.join('/');
		
		this.locationTextField.setValue(pathTitle);
		
		*/
	},	
	
	setItemTitle: function(titleSt)
	{
		this.panel.setTitle(titleSt);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if the given item is in the current items path (i.e. is a parent of)
	
	isItemInCurrentItemPath: function(checkItem)
	{
		for(var i=0; i<this.itemPathArray.length; i++)
		{
			var theItem = this.itemPathArray[i];
			
			if(theItem.id==checkItem.id)
			{
				return true;
			}
		}

		return false;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you an array of items based on the current selection
	
	createItemsFromSelection: function()
	{
		var currentSelections = this.dataView.getSelectedRecords();
		
		return currentSelections;
		
		/*
		var ret = [];
		
		for(var i=0; i<currentSelections.length; i++)
		{
			var item = Webkit.Folders.Item.prototype.createFromRecord(currentSelections[i]);
			
			ret.push(item);
		}
		
		return ret;
		
		*/
	},
	
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// REFLOW
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// this will rebuild the menus based on the current item and/or selection
	
	rebuildMenus: function()
	{
		this.buildAddMenu(this.fileAddMenu);
	},
	
	disableMenus: function()
	{
		
	},
	
	reflowMenus: function()
	{
		var currentSelections = this.dataView.getSelectedRecords();
		
		var openButton = this.selectionMenu.getComponent('selection-open');
		var editButton = this.selectionMenu.getComponent('selection-edit');
		var deleteButton = this.selectionMenu.getComponent('selection-delete');
		
		var cutButton = this.selectionMenu.getComponent('selection-cut');
		var copyButton = this.selectionMenu.getComponent('selection-copy');
		var ghostButton = this.selectionMenu.getComponent('selection-ghost');
		var pasteButton = this.selectionMenu.getComponent('selection-paste');
		
		openButton.disable();
		editButton.disable();
		deleteButton.disable();
		
		cutButton.disable();
		copyButton.disable();
		ghostButton.disable();
		pasteButton.disable();
		
		// if there is more than one then we can only delete
		if(currentSelections.length>=1)
		{
			cutButton.enable();
			copyButton.enable();
			ghostButton.enable();
		
			deleteButton.enable();
		}
		
		// if there is only one then we can open or edit
		if(currentSelections.length==1)
		{
			openButton.enable();
			editButton.enable();
		}
		
		if(this.canPaste())
		{
			pasteButton.enable();
		}
		
		this.fileMenuButton.enable();
		this.selectionMenuButton.enable();
		this.viewMenuButton.enable();
	},


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// BUILDING
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// builds the toolbar to go above the view
	// includes a menu to change the view by property
	
	createMenus : function()
	{
		this.toolbar = this.createLocationToolbar();
		
	},
	
	createSecondToolbar: function()
	{
		this.createFileToolbar(this.panel.tbar);
		this.createMenuListeners();
	},
	
	createLocationToolbar: function(renderTo)
	{
		this.locationTextField = new Ext.form.TextField({
    		id:'location',
    		width:550
    	});
    		
    	// the go up button
		this.locationOpenButton = new Ext.Button({
			id: 'location-open-button',
			width: 80,
			disabled:true,
			icon:Webkit.Folders.IconFactory.makeIconURI({name:'book_open'}),
			style: 'margin-left: 10px;',
			text: 'Open Location' });
			
		// the go up button
		this.locationCopyButton = new Ext.Button({
			id: 'location-copy-button',
			width: 80,
			disabled:true,
			icon:Webkit.Folders.IconFactory.makeIconURI({name:'paperclip'}),
			style: 'margin-left: 10px;',
			text: 'Copy Location' });
			
	// the go up button
		this.upButton = new Ext.Button({
			id: 'up-button',
			width: 30,
			disabled:true,
			icon:Webkit.Folders.IconFactory.makeIconURI({name:'navigate_up'}),
			style: 'margin-left: 10px;' });
		
		// the go back button
		this.backButton = new Ext.Button({
			id: 'back-button',
			width: 30,
			disabled:true,
			icon:Webkit.Folders.IconFactory.makeIconURI({name:'navigate_left'}),
			style: 'margin-left: 10px;' });
		
		// the go forwards button
		this.forwardButton = new Ext.Button({
			id: 'forward-button',
			width: 30,
			disabled:true,
			icon:Webkit.Folders.IconFactory.makeIconURI({name:'navigate_right'}),
			style: 'margin-left: 10px;' });

    	this.locationToolbar = new Ext.Toolbar({
    		border:false,
    		renderTo:renderTo,
    		items: [
    			this.locationTextField,
    			{xtype: 'tbspacer', width: 20},
				'-',
    			this.backButton,
    			this.forwardButton,
    			this.upButton
    		]
		});
		
		return this.locationToolbar;
	},
	
	createFileToolbar: function(renderTo)
    {
		this.createFileMenu();
		this.createSelectionMenu();
		this.createViewMenu();
		
		this.fileMenuButton = new Ext.Button({
			text: Webkit.Folders.Language.getLabel('submenu_file'),
          	width: 50,
           	style: 'margin-left: 10px; margin-right: 10px;',
           	disabled:true,
           	menu: this.fileMenu
       	});
        
       	this.selectionMenuButton = new Ext.Button({
            text: Webkit.Folders.Language.getLabel('submenu_edit'),
           	width: 50,
           	style: 'margin-left: 10px; margin-right: 10px;',
           	disabled:true,
           	menu: this.selectionMenu
        });
        
       	this.viewMenuButton = new Ext.Button({
            text: Webkit.Folders.Language.getLabel('submenu_view'),
           	width: 50,
           	style: 'margin-left: 10px; margin-right: 10px;',
           	disabled:true,
           	menu: this.viewMenu
       	});
       	
       
   		
   		// the root toolbar which will hold the top level buttons
		this.fileToolbar = new Ext.Toolbar({
			renderTo: renderTo,
			border:false,
			items:[
				this.fileMenuButton,
				this.selectionMenuButton,
				this.viewMenuButton
			]
		});
   		
   		return this.fileToolbar;
    },
    
 
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// builds the file menu to go into the toolbar
	
	createFileMenu: function()
	{
		// the add menu lets you add items to the current item
		// this will become contextual depending on item definitions
    	
		this.fileAddMenu = new Ext.menu.Menu({
        	id:this.id + '-file-add-menu',
        	items: []
    	});
    	
    	this.fileMenu = new Ext.menu.Menu({
        	id:this.id + '-file-menu',
        	items: [{
        		id: 'file-add',
	        	text: Webkit.Folders.Language.getLabel('new1'),
        		menu: this.fileAddMenu
        	},'-',{
        		id: 'file-edit',
        		disabled:true,
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'window_dialog'}),
				text: Webkit.Folders.Language.getLabel('edit')
			}]
    	});		
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// builds the edit menu to go into the toolbar
	
	createSelectionMenu: function()
	{
		this.selectionMenu = new Ext.menu.Menu({
        	id:this.id + '-selection-menu',
        	items: [{
        		id: 'selection-selectall',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'elements_selection'}),
        		text: Webkit.Folders.Language.getLabel('selectall')
        	},'-',{
        		id: 'selection-open',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'eye'}),
        		disabled:true,
				text: Webkit.Folders.Language.getLabel('view')
			},{
        		id: 'selection-edit',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'edit'}),
        		disabled:true,
				text: Webkit.Folders.Language.getLabel('edit')
			},{
        		id: 'selection-delete',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'garbage_add'}),
        		disabled:true,
				text: Webkit.Folders.Language.getLabel('delete1')
			},'-',{
        		id: 'selection-cut',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'cut'}),
        		disabled:true,
				text: Webkit.Folders.Language.getLabel('cut')
			},{
        		id: 'selection-copy',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'copy'}),
        		disabled:true,
				text: Webkit.Folders.Language.getLabel('copy')
			},{
        		id: 'selection-ghost',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'ghost'}),
        		disabled:true,
				text: Webkit.Folders.Language.getLabel('ghost')
			},'-', {
        		id: 'selection-paste',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'paste'}),
        		disabled:true,
				text: Webkit.Folders.Language.getLabel('paste')
			}]
        });		
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// builds the viewBy menu to go into the toolbar
	
	createViewMenu: function()
	{
		// the group by menu lets you change the grouping field of the dataView
        this.groupByMenu = new Ext.menu.Menu({
        	id: this.id + '-group-by-menu',
        	items: [{
        		id: 'groupby-none',
        		groupBy:'none',
        		text: Webkit.Folders.Language.getLabel('nothing'),
        		checked: true,
        		group: 'groupByMenu'
        	},{
        		id: 'groupby-item_type',
        		groupBy: 'item_type',
        		text: Webkit.Folders.Language.getLabel('itemtype'),
        		checked: false,
        		group: 'groupByMenu'
        	}]
        });
        
        
        // the template menu that changes the dataView template (icons, details etc)
        this.templateMenu = new Ext.menu.Menu({
        	id: this.id + '-template-menu',
        	items: [{
        		id: 'template-icons',
        		view: 'icons',
				text: Webkit.Folders.Language.getLabel('icons'),
				checked: true,
				group: 'templateMenu'
			},{
				id: 'template-details',
				view: 'details',
				text: Webkit.Folders.Language.getLabel('details'),
				checked: false,
				group: 'templateMenu'
			}]
        });
    	
    	// the view by menu lets you change the template being used in the view
    	// this lets you view by icons or details
    	
		this.viewMenu = new Ext.menu.Menu({
        	id:this.id + '-view-menu',
        	items: [{
        		id: this.id + '-group-by-menu-button',
        		text: Webkit.Folders.Language.getLabel('groupitemsby'),
        		menu: this.groupByMenu
        	},'-',{
        		id: this.id + '-template-menu-button',
        		text: Webkit.Folders.Language.getLabel('template'),
        		menu: this.templateMenu
        	}]
    	});
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// builds the menu items for an add menu based on the schema data from the server for the current item
	// it will add the menu to the pro
	
	buildAddMenu : function(theMenu)
	{
		theMenu.removeAll();
		
		var childTypes = this.currentItem.getAllowedChildTypes();
		
		var buttonData = [];
		
		for(var i=0; i<childTypes.length; i++)
		{
			var itemType = childTypes[i];
			
			if(Webkit.Folders.Schema.isItemTypePublic(itemType))
			{
				var theIconName = Webkit.Folders.Schema.getItemTypeIcon(itemType);
				
				var theIconURI = Webkit.Folders.IconFactory.makeIconURI({
					name:theIconName,
					status:'new'
				});
				
				// lets see if display_title is defined in the schema for this
				var title = Webkit.Folders.Schema.getItemTypeSchemaProperty(itemType, 'display_title');
				
				if(!title)
				{
					title = Webkit.Folders.Schema.getItemTypeTitle(itemType);
				}
				
				buttonData.push({
					id: theMenu.id + '-' + itemType,
					item_type: itemType,
					icon: theIconURI,
					text: title
				});
			}
		}
		
		theMenu.add(buttonData);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// the context menu needs to be shown for a node
	
	buildContextMenu: function(e)
	{
		var node = this.dataView.getNodeFromEvent(e);
		
		if(node)
		{
			if(!this.dataView.isSelected(node))
			{
    			this.dataView.select(node, e.ctrlKey, true);	
    		}
    	}
    	else
    	{
    		this.dataView.clearSelections(true);
    	}
    	
    	var theRecords = this.dataView.getSelectedRecords();
    	
    	var ghostMap = {};
    	
    	for(var i=0; i<theRecords.length; i++)
    	{
    		ghostMap[theRecords[i].id] = 1;
    	}
    	
    	this.dataView.ghostNodes(ghostMap);
    	
    	menuDefs = [];
    	
		this.contextMenu = new Ext.menu.Menu({
        	id:this.id + '-context-menu',
        	items: []
    	});
    	
    	this.contextAddMenu = new Ext.menu.Menu({
        	id:this.id + '-context-add-menu',
        	items: []
    	});
    	
    	this.contextMenu.addListener('hide', this.contextMenuHidden, this);
    	this.contextMenu.addListener('itemclick', this.contextMenuClicked, this);
    	this.contextAddMenu.addListener('itemclick', this.contextAddMenuClicked, this);
    	
    	this.contextMenu.removeAll();
    	this.buildAddMenu(this.contextAddMenu);
    	
    	if(theRecords.length==0)
    	{    		
    		menuDefs.push({
        		id: 'context-add',
        		text: Webkit.Folders.Language.getLabel('new1'),
        		menu: this.contextAddMenu
        	});
        	
        	menuDefs.push('-');
        	
        	menuDefs.push({
        		id: 'context-editthis',
        		text: Webkit.Folders.Language.getLabel('editthis') + this.currentItem.getTypeTitle(),
        		disabled:true,
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'window_dialog'})
        	});
        	
        	if(this.canPaste())
        	{
        		menuDefs.push('-');
        		menuDefs.push({
        			id: 'context-paste',
        			icon:Webkit.Folders.IconFactory.makeIconURI({name:'paste'}),
					text: Webkit.Folders.Language.getLabel('paste')
				});
        	}
    	}
    	else 
    	{
    		if(theRecords.length==1)
    		{
    			menuDefs.push({
	        		id: 'context-open',
	        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'eye'}),
        			text: Webkit.Folders.Language.getLabel('view')
        		});
        	
        		menuDefs.push({
	        		id: 'context-edit',
	        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'edit'}),
        			text: Webkit.Folders.Language.getLabel('edit')
        		});
        	}
        	
        	menuDefs.push({
        		id: 'context-delete',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'garbage_add'}),
        		text: Webkit.Folders.Language.getLabel('delete1')
        	});
        	
        	menuDefs.push('-');
        	
        	menuDefs.push({
        		id: 'context-cut',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'cut'}),
        		text: Webkit.Folders.Language.getLabel('cut')
        	});
        	
        	menuDefs.push({
        		id: 'context-copy',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'copy'}),
        		text: Webkit.Folders.Language.getLabel('copy')
        	});

        	menuDefs.push({
        		id: 'context-ghost',
        		icon:Webkit.Folders.IconFactory.makeIconURI({name:'ghost'}),
        		text: Webkit.Folders.Language.getLabel('ghost')
        	});
    	}
    	
    	this.contextMenu.add(menuDefs);
    	
    	if(node)
    	{
    		this.contextMenu.show(Ext.fly(node), 'c');
    	}
    	else
    	{
    		this.contextMenu.showAt(e.getXY());
    	}
    	
    	e.stopEvent();
	},		
	
	
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// creates the actual view to list an items children
	
	createPanel : function()
	{
		
		// the store that will load JSON item data from the server
		// the server will configure the JsonReader with the fields
		// so you don't need to change this if you add a field to item
		this.store = new Webkit.Folders.ItemViewStore({
			//proxy: new Ext.data.HttpProxy({
			//	url: this.uri }),

			//reader: new Ext.data.JsonReader(),
			
			sortInfo: {
				field: 'name',
				direction: 'ASC'
			},

			remoteSort: false });
		
		this.dragSelector = new Webkit.Folders.ItemViewDragSelector({
			dragSafe:true
		});
		
		var autoHeight = false;
		
		if(this.renderConfig)
		{
			autoHeight = true;
		}

		// the actual dataView object - prepareData works out what icon the item should use (depends on item_type)
		this.dataView = new Webkit.Folders.ItemViewDataView({
			store: this.store,
			autoHeight:autoHeight,
            multiSelect: true,
			simpleSelect: false,
			//style:'background-color:#ffcccc;',
			//autoScroll:true,
        	overClass:'itemViewOver',
        	selectedClass:'itemViewSelected',
        	itemSelector:'div.icons-item',
			loadingText: 'loading data...',
        	emptyText: '',
        	anchor:'-23 -23',
			
			plugins: [
				this.dragSelector
			],
			
			currentTemplate: 'icons',
			
			// we default to the item_type group_by
			//groupBy: 'item_type',
			groupBy: null,
			
			prepareData: function(data)
			{
				var groupByIconMap = {
					details:true,
					icons:true };
					
				var tempItem = new Webkit.Folders.Item(data, data.id);
				
				var itemFields = tempItem.getFields();
				
				var foundImagePath = null;
				
				for(var i=0; i<itemFields.length; i++)
				{
					var fieldDef = itemFields[i];
					
					if(fieldDef.type=='image' && foundImagePath == null)
					{
						var imageValue = tempItem.get(fieldDef.name);
						
						if(imageValue)
						{
							foundImagePath = '/uploaded_files/_square_48/' + imageValue.folder + '/' + imageValue.file;
						}
					}
					else if(fieldDef.type=='video' && foundImagePath == null)
					{
						var imageValue = tempItem.get(fieldDef.name);
						
						if(imageValue)
						{
							foundImagePath = '/uploaded_files/_thumbnail_size_48x48/' + imageValue.folder + '/' + imageValue.file;
						}
					}
				}
					
				for(var prop in groupByIconMap)
				{
					var iconURI = foundImagePath;
					
					if(iconURI==null)
					{
						iconURI = Webkit.Folders.IconFactory.makeItemIconURI(tempItem, {
							destination:'item_view_' + prop
						});
					}
					
					data[prop + '_icon_url'] = iconURI;
				}

				return data;
			},
			
			templates: {
				
				icons:	''
					+	'<div class="icons-item" id="{id}">'
       	    		+		'<div class="icons-thumbnail"><img src="{icons_icon_url}" width=48 height=48 /></div>'
           			+		'<span class="icons-title">{name}</span>'
           			+	'</div>',
           			
           		
           		details:''
       				+	'<div class="details-item" id="{id}">'
       	    		+		'<img src="{details_icon_url}" align="absbottom" width=16 height=16 /> {name}'
           			+	'</div>'
           	}
		});
		
		this.containerPanel = new Ext.Panel({
			layout:'anchor',
			style:'cursor:default;',
			autoScroll:true,
			autoHeight:autoHeight,
			title:'Default View',
			items:[this.dataView]
		});
		
		this.tabPanel = new Ext.TabPanel({
			tabPosition:'bottom',
			activeTab:0,
			border:false,
			autoHeight:autoHeight,
			items:[
				this.containerPanel/*,
				{
					title:'+ Add View',
					tabTip:'Click here create new order & grouping instructions for this folder...'
				}*/
			]
		});
		
		var panelConfig = {
			id:this.id,
			title:this.name,
			tbar:this.toolbar,
      		border:false,
      		autoHeight:autoHeight,
      		//style:'border-left: #99bbe8 1px solid;',
			bodyStyle:'background-color: #ffffff;',
			region:'center',
			layout:'fit',
			margins: '0 0 0 0',
       		items: [this.tabPanel]
		};

		// the final panel to hold the dataView
		this.panel = new Ext.Panel(panelConfig);
    },
  
  	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// adds the context menu listener
	
	dataViewRendered : function()
	{
		this.dataView.getEl().addListener('contextmenu', this.buildContextMenu, this);
		
		this.createSecondToolbar();
	},
    
    //////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// maps component events onto functions
	
	createEventListeners : function()
	{
		//////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////
		// Data View listeners
		
		this.dataView.addListener('dblclick', this.dataViewDoubleClick, this);
		
		this.dataView.addListener('selectionchange', this.dataViewSelectionChanged, this);
		
		// creates the context menu for the dataview
		this.dataView.addListener('render', this.dataViewRendered, this);
		
		var itemView = this;
		
		// the inline title editor has been used in the dataview
		this.dataView.addListener('itemnamechanged', 
			function(item)
			{
				itemView.fireEvent('itemnamechanged', item, itemView);
			}, 
			
			this);
			
		// items have been dropped on other items
		this.dataView.addListener('dropitems', 
			function(dropItem, dragItems)
			{
				itemView.fireEvent('moveitems', dropItem, dragItems, itemView);
			}, 
			
			this);
	},

	createMenuListeners : function()
	{
		
		//////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////
		// Menu listeners
		
		this.fileMenu.addListener('itemclick', this.fileMenuClicked, this);
		this.fileAddMenu.addListener('itemclick', this.fileAddMenuClicked, this);
		
		this.groupByMenu.addListener('itemclick', this.groupByMenuClicked, this);		
		this.templateMenu.addListener('itemclick', this.templateMenuClicked, this);		
		
		this.selectionMenu.addListener('itemclick', this.selectionMenuClicked, this);
		
		this.backButton.addListener('click', this.goBack, this);
		this.forwardButton.addListener('click', this.goForward, this);
		this.upButton.addListener('click', this.goUp, this);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// main GUI building method
	
	createGUI : function()
	{
		this.createMenus();
		this.createPanel();
		
		this.createEventListeners();
	}
});