////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// App.js
//
// Base class for Ext applications - anything that is useful to any application goes in here
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// CONFIG
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


Ext.IMAGE_DIR = '/ext/resources/images',

Ext.BLANK_IMAGE_URL = Ext.IMAGE_DIR + '/default/s.gif';
Ext.ADD_IMAGE_URL = Ext.IMAGE_DIR + '/icons/page_add.png';
Ext.DELETE_IMAGE_URL = Ext.IMAGE_DIR + '/icons/page_delete.png';
Ext.EDIT_IMAGE_URL = Ext.IMAGE_DIR + '/icons/page_edit.png';

Ext.namespace('Webkit.Folders');
Ext.namespace('Webkit.Folders.Fields');

Webkit.Folders.App = Ext.extend(Ext.util.Observable, {

	/// data stubs
	session_id : null,

	loginURI : null,

	userData : null,

	accountData : null,

	loggedIn : false,
	
	constructor: function(config)
    {	
    	Ext.apply(this, config);
    	
		this.addEvents(
		
		);

        Webkit.Folders.App.superclass.constructor.call(config);
        
        Ext.onReady(this.initApp, this);
	},	


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// INIT
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


	initApp : function()
	{
		Ext.QuickTips.init();
		Ext.form.Field.prototype.msgTarget = 'qtip';

		this.init();
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// init will build the interface and disable right clicks

	init : function()
	{
		//this.createMainInterface();

		this.disableRightClicks();
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// holding place for the main interface building

	createMainInterface : function()
	{

	},


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// NETWORK
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// generic function for making a direct ajax call to the server
	// you provide the url and params for the request
	// for the response, the 'callback' method is called in the 'scope'
	// any data returned from the server is given to this callback method
	// as a parsed javascript variable (i.e. not just a string)
	
	appRequest : function(cfg)
	{
		if(!cfg.params) { cfg.params = {}; }
		
		cfg.params.appId = 'Xara Administration';
		
		Webkit.Folders.App.prototype.addLoadingMessage();

		Ext.Ajax.request({
			url:cfg.url,
			params:cfg.params,
			callback:this.appResponse,
			scope:this,
			postParams:cfg.returnParams,
			postCallback:cfg.callback,
			postScope:cfg.scope || this
		});
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// this is the corresponding method to appRequest - it does the parsing of the
	// returned JSON string and executes the callback method (if provided)

	appResponse : function(options, success, responseObj)
	{
		if(!success)
		{
			this.networkError();

			return;
		}

		var dataObj = Ext.decode(responseObj.responseText);

		if(options.postCallback!=null)
		{
			options.postCallback.call(options.postScope, dataObj, options.postParams);
		}
		
		Webkit.Folders.App.prototype.removeLoadingMessage();
	},
	
	//////////////////////////////////////////////////////////////
	// displays an error message

	networkError : function()
	{
		Ext.MessageBox.alert('Network Error', 'There was a network problem - please contact the administrator.');
	},


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// LOGIN
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// shows a login screen with username & password fields
	// this overwrites any gui initially built by createMainInterface
	
	showPasswordScreen : function()
	{						
		this.userNameField = new Ext.form.TextField({
			fieldLabel:'Email address',
			anchor:'100%',
			validationEvent: false,
			name:'username',
			tabIndex:1,
			allowBlank:false
		});
		
		this.passwordField = new Ext.form.TextField({
			fieldLabel:'Password',
			anchor:'100%',
			name:'password',
			validationEvent: false,
			inputType:'password',
			tabIndex:2,
			allowBlank:false
		});
		
		this.loginForm = this.createFormPanel({
			formItems:[
				this.userNameField,
				this.passwordField
			] 
		});
		
		this.loginWindow = this.createDialog({
			title:'Please enter your login details...',
			width:400,
			height:170,
			modal: true,
			panel:this.loginForm,
			buttons:[{
				text:'Login',
				handler:this.doLogin,
				scope:this }] });
				
		this.loginWindow.addListener('render', this.loginFormRendered, this);
		
		this.userNameField.addListener('specialkey', this.loginFormSpecialKey, this);
		this.passwordField.addListener('specialkey', this.loginFormSpecialKey, this);

		this.loginWindow.show();
		
		this.loginFormRendered.defer(100, this);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// focuses the login form once it has been rendered
	
	loginFormRendered: function()
	{
		this.userNameField.focus();
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// submits the login form if the enter key was pressed
	
	loginFormSpecialKey: function(field, e)
	{
		if (e.getKey() == e.ENTER)
		{
			this.doLogin();
		}
	},

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// event handler for the 'login' button having been pressed
	// this triggers doLoginRequest with the form values
	
	doLogin : function()
	{
		var formObj = this.loginForm.getForm();

		if(!formObj.isValid())
		{
			this.loginForm.showError();
			return;
		}

		this.loginForm.hideError();
		
		this.showLoadingPanel();

		this.doLoginRequest(formObj.getValues().username, formObj.getValues().password);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// performs a server request to perform a login with the details supplied
	// loginResponse is the callback

	doLoginRequest : function(username, password)
	{
		this.appRequest({
			url:this.loginURI,
			params:{
				username:username,
				password:password },
			callback:this.loginResponse,
			scope:this });
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// responds to a login request with a 'status' property
	// it hides the loginForm and triggers postLogin if status == "ok"

	loginResponse : function(responseData)
	{
		this.postLoginResponse = responseData;
		
		if(responseData.status != 'ok')
		{
			if(this.loginForm)
			{
				this.removeLoadingPanel();
				this.loginForm.showError(responseData.desc);
			}
		}
		else
		{
			if(this.loginWindow)
			{
				this.loginWindow.hide();
			}

			this.loggedIn = true;

			this.removeLoadingPanel();
			this.createMainInterface();
		}
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// holding function for post user authentication methods

	postLogin : function()
	{

	},


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// GUI
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// creates a dialog based on the given config

	createDialog : function(cfg)
	{
    	var window = new Ext.Window({
        		title: cfg.title || 'Form',
        		width: cfg.width || 500,
        		height: cfg.height || 300,
        		layout: 'fit',
		        plain:true,
			modal: cfg.modal,
			closable: cfg.closable,
			resizable: false,
        		bodyStyle:'padding:5px;padding-top:10px;',
        		buttonAlign:'right',
        		items: cfg.panel || new Ext.Panel({title:'panel'}),
	
        		buttons: cfg.buttons || []
    		});

    		return window;
	},
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// creates a viewport based on the given config

	createViewport : function(cfg)
	{
		if(cfg.titlePanel==null)
		{
			Webkit.Folders.App.prototype.pageTitle = cfg.pageTitle || 'Page Title';
			
			var html = 	''
			+			'<h1 id="pageTitle" class="x-panel-header">'
			+			'	<span id="pageTitle">'
			+					Webkit.Folders.App.prototype.pageTitle
			+			'	</span>'
			+			'	<span id="clipboardTitle">'
			+			'	</span>'			
			+			'	<span id="loadingTitle">'
			+			'	</span>'
			+			'</h1>';

			cfg.titlePanel = new Ext.Panel({
        			region: 'north',
        			html: html,
        			autoHeight: true,
        			border: false,
        			margins: '0 0 5 0'
    			});
		}

		if(cfg.contentPanel==null)
		{
			cfg.contentPanel = new Ext.TabPanel({
      				region: 'center',
        			items: {
            				title: 'Content',
            				html: ''
        			}
			});
		}
		
		if(cfg.menuPanel==null)
		{
			cfg.menuPanel = new Ext.Panel({
      				region: 'west',
      				title: 'Menu'
			});
		}

		this.viewPort = new Ext.Viewport({
    			layout: 'border',
    			items: [cfg.menuPanel, cfg.titlePanel, cfg.contentPanel]
		});

		return this.viewPort;
	},
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// creates a form based on the given config
	
	createFormPanel : function(cfg)
	{
		var itemCfgs = cfg.itemCfgs || [];
		var formItems = cfg.formItems || [];

		for(var i=0; i<itemCfgs.length; i++)
		{
			var cfg = itemCfgs[i];

			if(cfg.allowBlank==null)
			{
				cfg.allowBlank = true;
			}

			formItems.push({
				xtype:cfg.xtype || 'textfield',
				vtype:cfg.vtype || null,
				inputType:cfg.inputType || 'text',
				allowBlank:cfg.allowBlank,
				anchor:'100%',
				name:cfg.name,
				text:cfg.title,
				fieldLabel:cfg.title });
		}

		var errorLabel = null;

		if(!cfg.errorLabel)
		{
			var spaceLabel = new Ext.form.Label({
				height:25 });

			var errorLabel = new Ext.form.Label({
				id:'error-label',
				cls:'error-label',
				xtype:'label',
				text:'',
				anchor:'100%' });

			formItems.push(spaceLabel);
			formItems.push(errorLabel);
		}

		var form = new Ext.form.FormPanel({
			baseCls: 'x-plain',
			defaultType: 'textfield',
			labelWidth: 100,
			labelPad: 10,
			fileUpload: cfg.fileUpload || false,
			errorLabel:cfg.errorLabel || errorLabel,
			showError: function(errorText)
			{
				this.errorLabel.getEl().update(errorText || 'please check the fields with a red underline...');
			},
			hideError: function()
			{
				this.errorLabel.getEl().update('');
			},
			items: formItems
    		});

		return form;
	},
	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// creates a form based on the given config
	
	
	createDataGrid : function(cfg)
	{
		if(cfg.fieldList==null)
		{
			cfg.fieldList = [{
				dataIndex:'id',
				header:'ID'
			}];
		}

		var propArray = [];
		var fieldList = [];

		for(var i=0; i<cfg.fieldList.length; i++)
		{
			propArray.push(cfg.fieldList[i].dataIndex);

			if(cfg.fieldList[i].header!=null)
			{
				fieldList.push(cfg.fieldList[i]);
			}
		}

		// Here is the JSON reader for the list of files
		var jsonReader = new Ext.data.JsonReader({
			totalProperty: cfg.totalField || "itemCount",
			root: cfg.rootField || "items",
			id: cfg.idField || "id"
		}, propArray);

		// Here is the proxy for the file load request
		var proxy = new Ext.data.HttpProxy({
			url: cfg.url
		});

		// Here is the column model for the file grid
		var gridColModel = new Ext.grid.ColumnModel(fieldList);

    	gridColModel.defaultSortable = true;

		var gridView;
		var gridStore;
		var sortOrder = "ASC";
		
		if(cfg.sortOrder)
		{
			sortOrder = cfg.sortOrder;	
		}

		if(cfg.gridView!=null)
		{
			if(cfg.gridView=='none')
			{
				gridView = new Ext.grid.GridView({
	            			forceFit:true });
			}
			else
			{
				gridView = cfg.gridView;
			}

			gridStore = new Ext.data.Store({
	      			proxy: proxy,
        			reader: jsonReader,
					sortInfo:{field:cfg.sortField || "id", direction: sortOrder}
    			});
		}
		else
		{
			gridView = new Ext.grid.GroupingView({
	            		forceFit:true,
            			groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
        		});

			// Here is the data store for the list of files
			gridStore = new Ext.data.GroupingStore({
	      			proxy: proxy,
        			reader: jsonReader,
					groupField:cfg.groupField || "id",
					sortInfo:{field:cfg.sortField || "id", direction: sortOrder}
    			});
		}

		var gridCfg = {
			id: cfg.id || Ext.id(),
			title: cfg.title || 'Grid',
			tbar: cfg.toolbar,
			//autoExpandColumn: cfg.autoExpandColumn || 'id',
			store: gridStore,
			colModel: gridColModel,
			selModel: new Ext.grid.RowSelectionModel({singleSelect: true}),
        	view: gridView
		};
		
		if(cfg.pageSize>0)
		{
			var pagingToolbar = new Ext.PagingToolbar({
		        pageSize: cfg.pageSize,
        		store: gridStore,
        		displayInfo: true,
        		displayMsg: 'Displaying items {0} - {1} of {2}',
        		emptyMsg: "No items to display"
    		});
    		
    		gridCfg.bbar = pagingToolbar;
    	}		

		var useCfg = cfg.useCfg || {};

		for(var prop in gridCfg)
		{
			useCfg[prop] = gridCfg[prop];
		}

		// This is the actual grid
		var grid = new Ext.grid.GridPanel(useCfg);

		return grid;
	},	
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// creates a toolbar based on the given config	
	
	createToolbar : function(cfg)
	{
		var items = [];
		var afterItems = cfg.afterItems || [];

		var itemTitle = cfg.itemTitle || 'Item';

		var beforeItems = cfg.items || [];

		var toolbarId = cfg.toolbar_id || '';

		for(var i=0; i<beforeItems.length; i++)
		{
			beforeItems[i].handler = cfg.handler;
			beforeItems[i].scope = cfg.scope;
			beforeItems[i].id = toolbarId + '-' + beforeItems[i].id;

			items.push(beforeItems[i]);
		}

		if(cfg.noAdd==null)
		{
			items.push({
				id:toolbarId + '-add',
				text: 'Add ' + itemTitle,
				icon: Ext.ADD_IMAGE_URL,
				cls: 'x-btn-text-icon',
				handler: cfg.handler,
				scope: cfg.scope });
		}

		if(cfg.noEdit==null)
		{
			items.push({
				id:toolbarId + '-edit',
				text: 'Edit ' + itemTitle,
				icon: Ext.EDIT_IMAGE_URL,
				cls: 'x-btn-text-icon',
				handler: cfg.handler,
				scope: cfg.scope });
		}

		if(cfg.noDelete==null)
		{
			items.push({
				id:toolbarId + '-delete',
				text: 'Delete ' + itemTitle,
				icon: Ext.DELETE_IMAGE_URL,
				cls: 'x-btn-text-icon',
				handler: cfg.handler,
				scope: cfg.scope });
		}

		for(var i=0; i<afterItems.length; i++)
		{
			afterItems[i].handler = cfg.handler;
			afterItems[i].scope = cfg.scope;
			afterItems[i].id = toolbarId + '-' + afterItems[i].id;

			items.push(afterItems[i]);
		}

		var newItems = [];

		for(var i=0; i<items.length; i++)
		{
			var item = items[i];

			newItems.push(item);

			if(i<items.length-1)
			{
				newItems.push(new Ext.Toolbar.Separator());
			}
		}

		var tb = new Ext.Toolbar(newItems);

		return tb;
	},	
	
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// TOOLS
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// disableRightClicks
	
	disableRightClicks : function()
	{
		Ext.get(document.body).on('contextmenu', function(e) { 
			e.stopEvent();
		});
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// removeLoadingPanel

	removeLoadingPanel : function()
	{
		// lets get rid of the loading bar
	
		var loading = Ext.get('loading');
		loading.hide();

		var mask = Ext.get('loading-mask');
		mask.hide();
	},
	
	showLoadingPanel : function()
	{
		// lets show the loading bar
	
		var loading = Ext.get('loading');
		loading.show();

		var mask = Ext.get('loading-mask');
		mask.show();
	}	
});

Webkit.Folders.App.prototype.addLoadingMessage = function(message)
{
	if(message==null) { message = " - loading..."; }
	
	Webkit.Folders.App.prototype.setLoadingTitle(message);
};

Webkit.Folders.App.prototype.removeLoadingMessage = function()
{
	Webkit.Folders.App.prototype.setLoadingTitle();	
};

Webkit.Folders.App.prototype.setClipboardTitle = function(title)
{
	if(title==null) { title = ''; }
		
	var titleElem = Ext.get('clipboardTitle');
	
	if(titleElem==null) { return; }
	
	titleElem.update(title);	
};

Webkit.Folders.App.prototype.setLoadingTitle = function(title)
{
	if(title==null) { title = ''; }
		
	var titleElem = Ext.get('loadingTitle');
	
	if(titleElem==null) { return; }
	
	titleElem.update(title);	
};
	
Webkit.Folders.App.prototype.setPageTitle = function(title)
{
	if(title==null) { title = Webkit.Folders.App.prototype.pageTitle; }
		
	var titleElem = Ext.get('pageTitle');
	
	if(titleElem==null) { return; }
	
	titleElem.update(title);	
};