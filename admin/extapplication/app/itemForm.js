////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// ItemForm.js
//
//
// creates a form for the data input into an item
// what is generated depends entirely upon the item definition
// the definition says what data this item holds and in turn how that data is edited
// this file is responsible for interpreting the item definition and creating an
// interface from it
//
//
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

Webkit.Folders.ItemForm = Ext.extend(Ext.util.Observable, {
	
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
	// required params:
	//		item	= the item this form is for
		
    constructor: function(config)
    {
    	// we cant have an itemForm without an item
    	if(!config.item)
    	{
    		config.item = Webkit.Folders.Item.prototype.createBlank();
    	}
    	
    	Ext.apply(this, config);
    	
        this.addEvents(
        	'item_form_rendered',
        	'itemsaved',	// trigger for the item getting saved to the server
        	'destroy' 		// the window has gone
        );
        
        
        this.listeners = config.listeners;
        
        this.id = Ext.id();//this.item.id;

        Webkit.Folders.ItemForm.superclass.constructor.call(config);
        
        this.createGUI();
	},

		
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// EVENTS
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// maps component events onto functions
	
	createEventListeners : function()
	{
		// triggers the close window because the cancel button was clicked
		
		this.windowCancelButton.addListener("click", this.windowCancelClicked, this);
		
		
		// triggers the close window because the cancel button was clicked
		
		this.windowApplyButton.addListener("click", this.windowApplyClicked, this);
		
		
		// triggers when the window is closed
		
		this.window.addListener("close", this.windowClosed, this);
		
		
		this.addKeywordButton.addListener('click', this.addKeywordButtonClicked, this);
		this.deleteKeywordButton.addListener('click', this.deleteKeywordButtonClicked, this);
		
		
		this.tabPanel.addListener('tabchange', this.tabPanelChanged, this);
		
		this.keywordPanel.getSelectionModel().addListener('selectionchange', this.keywordGridSelectionChanged, this);
	},	
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// triggered when the window is closed either by the cancel button or the top right close button
	
	windowClosed : function()
	{
		//this.fireEvent('destroy', this);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// close (and therefore destroy) this form
	
	windowCancelClicked : function()
	{
		// close the window - this triggers the destroy method
		this.window.destroy();
		//this.window.hide(this.id, this.windowIsHidden, this);
	},
	
	windowIsHidden : function()
	{
		this.window.close();
	},	
	
	tabPanelChanged : function()
	{
		this.window.syncShadow();
	},
	
	keywordGridSelectionChanged : function()
	{
		var selectedCellInfo = this.keywordPanel.getSelectionModel().getSelectedCell();    	
    	
    	if(selectedCellInfo==null)
    	{
    		this.deleteKeywordButton.disable();
    	}
    	else
    	{
    		this.deleteKeywordButton.enable();
    	}
	},

    addKeywordButtonClicked : function()
    {
    	var index = this.keywordStore.getCount();
    	
		var p = new Ext.data.Record({
    		id:'new' + index,
    		item_id:this.item.id,
    		word:'',
    		value:''
    	});
    	
    	var selectedCellInfo = this.keywordPanel.getSelectionModel().getSelectedCell();
    	
    	var insertRow = 0;
    	
    	if(selectedCellInfo!=null)
    	{
    		insertRow = selectedCellInfo[0];
    	}
    	
		this.keywordPanel.stopEditing();
		this.keywordStore.insert(insertRow, p);
		this.keywordPanel.startEditing(insertRow, 0);
    },
    
	deleteKeywordButtonClicked : function()
    {
    	var selectedCellInfo = this.keywordPanel.getSelectionModel().getSelectedCell();    	
    	
    	if(selectedCellInfo==null)
    	{
    		return;
    	}
    	
    	this.keywordPanel.stopEditing();
    	
    	var deleteRow = selectedCellInfo[0];
    	
    	var deleteRecord = this.keywordStore.getAt(deleteRow);
    	
    	this.keywordStore.remove(deleteRecord);
    },  
    
    
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// apply the data in this form
	
	windowApplyClicked : function()
	{
		this.tabPanel.setActiveTab(0);
		
		this.hideError();
		
		if(!this.hasErrors())
		{
			var formDataJSON = this.getFormDataJSON();
		
			this.fireEvent('itemsaved', this.item, formDataJSON, this);
		}
	},
	
	showError: function(errorText)
	{
		this.errorLabel.getEl().update(errorText);
		
		this.errorLabel.getEl().addClass('error-label');
	},
	
	hideError: function()
	{
		this.showError('');
	},
	
	hasErrors: function()
	{		
		var formData = this.getFormData();
		
		var theValues = formData.fields;
		
		theValues.name = formData.name;
		
		var fields = this.item.getFields();
		
		for(var i=0; i<fields.length; i++)
		{
			var theField = fields[i];
			var theValue = theValues[theField.name];
			
			if(theField.required == "yes")
			{
				if((theValue==null)||(theValue==""))
				{
					this.showError('Please enter a ' + theField.title);
					return true;
				}
			}
		}
		
		return false;
	},
	
	setError: function(errorText)
	{
		Ext.Msg.alert('Error', errorText);
	},
	
	destroy: function()
	{
		this.window.destroy();
		//this.window.hide(this.id, this.windowIsHidden, this);
	},
    
    
    	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// PUBLIC INTERFACE
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	
	highlightWindow : function()
	{
		this.window.toFront();
		this.window.getEl().frame('#8db2e3', 1);
	},
	
	
	activateWindow : function()
	{
		//this.window.show(this.id);
		this.window.show();
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
	// main GUI building method	
	
	createGUI: function()
	{
		var formDefinition = this.buildFormDefinition();
		
		this.createForm(formDefinition);
		
		this.createEventListeners();
    },
    
   
    
    createKeywordPanel: function()
    {
    	this.keywordColumnModel = new Ext.grid.ColumnModel({
        	defaults: {
	            sortable: true
        	},
        	columns: [{
                id: 'name',
                header: 'Word',
                dataIndex: 'name',
                width: 250,
                editor: new Ext.form.TextField({
                    allowBlank: false
                })
            }, {
            	id: 'value',
                header: 'Value',
                dataIndex: 'value',
                width: 250,
                editor: new Ext.form.TextField({
                    allowBlank: false
                })
            }]
    	});
    	
		var keywordRecord = Ext.data.Record.create([{
    		name:'id' },{
    		name:'keyword_type' },{
    		name:'name',
    		type:'string' },{
    		name:'value',
    		type:'string' }]);   
    		
    	var reader = new Ext.data.JsonReader({
    		idProperty: 'id',
        	root: 'keywords',
        	fields: keywordRecord
        });
    	
    	this.keywordStore = new Ext.data.Store({
    		sortInfo: {field:'word', direction:'ASC'},
    		reader: reader,
    		remoteSort: false
    	});
    	
    	this.addKeywordButton = new Ext.Button({
    		text: Webkit.Folders.Language.getLabel('keyword_add')
    	});
    	
    	this.deleteKeywordButton = new Ext.Button({
    		text: Webkit.Folders.Language.getLabel('keyword_delete'),
    		disabled: true
    	});
    	
    	this.keywordPanel = new Ext.grid.EditorGridPanel({
    		id:this.id + 'keyword-panel',
        	store: this.keywordStore,
        	cm: this.keywordColumnModel,
        	title: Webkit.Folders.Language.getLabel('keywords'),
        	width: 500,
        	height: 220,
        	clicksToEdit: 1,
        	
			tbar: [
				this.addKeywordButton,
				this.deleteKeywordButton
			]
        });

        var keywords = this.item.getPlainKeywords();
        var keywordData = []
        
        for(var i=0; i<keywords.length; i++)
        {
        	var keyword = keywords[i];
        	
        	keywordData.push({
    			id:keyword.id,
    			keyword_type:'keyword',
    			name:keyword.name,
    			value:keyword.value
    		});
    	}
        
        for(var i=0; i<keywordData.length; i++)
        {
        	var record = new keywordRecord(keywordData[i]);
        	
        	this.keywordStore.add(record);
        }
    },
      
    
    showFileUploadWindow: function()
    {
    	
    },
    
    getFormDataJSON: function()
    {
    	var formData = this.getFormData();
    	
    	var JSON = Ext.encode(formData);
    	
    	return JSON;
    },
    
	getFormData: function()
    {
    	var parent_id = this.parentItem.id;
    	
    	if(parent_id == 'root')
    	{
    		parent_id = null;
    	}

    	var fieldDefs = this.item.getFields();
    	
    	var itemData = {
    		id:this.item.id,
			item_type:this.item.get('item_type'),
    		fields:{},
    		keywords:[]
    	};
    	
    	for(var i=0; i<fieldDefs.length; i++)
    	{
    		var fieldDef = fieldDefs[i];
    		
    		var componentId = this.id + '_' + fieldDef.name;
    		
    		var tabName = this.fieldTabMap[componentId];
    		var formPanel = this.tabMap[tabName];
    		
    		var fieldElem = formPanel.getComponent(componentId);
    		
    		if(fieldElem && !fieldDef.auto_value)
    		{
    			var theValue = fieldElem.getValue(); 
    			
    			if(fieldDef.type.indexOf('date')==0)
    			{
    				theValue = fieldElem.getRawValue();
    			}
    			
    			if(fieldDef.name == 'name')
    			{
	    			itemData.name = theValue;
    			}
    			else
    			{
	    			itemData.fields[fieldDef.name] = theValue;
    			}
    			
    			this.item.set(fieldDef.name, theValue);
    			//itemData[fieldDef.name] = theValue;
    		}
    	}
    	
    	var keywordRecords = this.keywordStore.getRange();
    	
    	for(var i=0; i<keywordRecords.length; i++)
    	{
    		var keywordRecord = keywordRecords[i];
    		
    		if((keywordRecord.get('name')==null)||(keywordRecord.get('name')==''))
    		{
    			
    		}
    		else
    		{
    			itemData.keywords.push({
	    			name:keywordRecord.get('name'),
    				value:keywordRecord.get('value') });
    		
    		}
    	}
    	
    	return itemData;
    },
    
    getFieldComponents: function(fieldDef, noList, overrideValue, valueToUse, listValues)
    {
    	var fieldCfg = {};
    	
    	for(var prop in fieldDef)
    	{
    		fieldCfg[prop] = fieldDef[prop];
    	}
    	
    	if((fieldDef.config!=null)&&(fieldDef.config!=''))
    	{
			var lineParts = fieldDef.config.split(/\r?\n/);
			
			for(var i=0; i<lineParts.length; i++)
			{
				var linePart = lineParts[i];
				
				if(linePart.indexOf('=')>0)
				{
					var lineValues = linePart.split('=');
					
					var theField = lineValues[0];
					var theValue = lineValues[1];
					
					if(theValue.match(/^\d+$/))
					{
						theValue = parseInt(theValue);
					}
					else if(theValue.match(/^\d+\.\d+$/))
					{
						theValue = parseFloat(theValue);
					}
					
					fieldCfg[theField] = theValue;	
				}
			}
    	}

    	fieldCfg.id = this.id + '_' + fieldDef.name;
    	fieldCfg.qtipText = fieldDef.name;
    	fieldCfg.readOnly = fieldDef.read_only;

		if(this.item)
		{
			fieldCfg.value = this.item.get(fieldDef.name);
		}
		
		if(overrideValue)
		{
			fieldCfg.value = valueToUse;
		}
    	
    	fieldCfg.fieldLabel = fieldDef.title;
    	
    	if(noList)
    	{
    		fieldCfg.id += '-list-editor';
    	}
    		
		var fieldObjects = [];
		var fieldObject = null;

    	if(fieldDef.list && !noList)
    	{
    		fieldCfg.form = this;
    		fieldCfg.fieldDef = fieldDef;
    			
    		var editor = new Webkit.Folders.Fields.list_editor(fieldCfg);
    		
    		fieldObjects.push(editor);
    	}
    	else if(fieldDef.type == 'field')
    	{
    		if(!fieldCfg.value)
    		{
    			fieldCfg.value = {};
    		}
    		
    		var titleField = new Ext.form.TextField({
				fieldLabel:'Title',
				name:'title',
				value:fieldCfg.value.title
			});
			
			fieldObjects.push(titleField);
    		
			var nameField = new Ext.form.TextField({
				fieldLabel:'Field Name',
				name:'name',
				value:fieldCfg.value.name
			});
			
			fieldObjects.push(nameField);
			
			var fieldValue = '';

			var fieldData = this.getFieldTypeData();
			
			for(var i=0; i<fieldData.length; i++)
			{
				if(fieldData[i][0]==fieldCfg.value.type)
				{
					fieldValue = fieldData[i][0];
				}
			}
			
			var typeField = new Ext.form.ComboBox({
        		store: this.getFieldTypeStore(),
        		name:'type',
        		displayField:'title',
        		editable:false,
        		valueField:'type',
        		mode: 'local',
        		forceSelection: true,
        		triggerAction: 'all',
        		emptyText:'Text',
        		emptyValue:'string',
        		fieldLabel:'Field Type',
        		selectOnFocus:true,
        		value:fieldValue
    		});
    		
			fieldObjects.push(typeField);
			
			var tabField = new Ext.form.ComboBox({
        		store: this.getTabNameStore(listValues),
        		name:'tab',
        		displayField:'name',
        		editable:true,
        		valueField:'name',
        		mode: 'local',
        		triggerAction: 'all',
        		emptyText:'default',
        		emptyValue:'default',
        		fieldLabel:'Tab',
        		selectOnFocus:true,
        		value:fieldCfg.value.tab
    		});
			
			/*
			var tabField = new Ext.form.TextField({
				fieldLabel:'Tab',
				name:'tab',
				value:fieldCfg.value.tab
			});
			*/
			
			fieldObjects.push(tabField);
			
			var configField = new Ext.form.TextArea({
				fieldLabel:'Config',
				name:'config',
				value:fieldCfg.value.config,
				height:60
			});
			
			fieldObjects.push(configField);
    	}
    	else if(fieldDef.type == 'selectlist')
    	{
    		var optionString = fieldDef.config.replace(/\r?\n/g, ",");
    		optionString = optionString.replace(/,+/g, ",");
    		
    		var optionParts = optionString.split(/,+/);

	    	var dataArr = [];
    	
    		for(var i=0; i<optionParts.length; i++)
    		{
	    		var listValue = optionParts[i];
	    		
	    		var displayValue = listValue;
	    		var actualValue = listValue;
	    		
	    		if(listValue.indexOf('=')>0)
	    		{
	    			var parts = listValue.split('=');
	    			
	    			displayValue = parts[0];
	    			actualValue = parts[1];
	    		}

    			dataArr.push([actualValue, displayValue]);
    		}
    	
    		var store = new Ext.data.ArrayStore({
        		fields: ['value','title'],
        		data: dataArr
    		});
    	
    		var optionField = new Ext.form.ComboBox({
    			id:fieldCfg.id,
        		store: store,
        		name:fieldDef.name,
        		displayField:'title',
        		editable:true,
        		valueField:'value',
        		mode: 'local',
        		forceSelection: true,
        		triggerAction: 'all',
        		emptyText:'',
        		emptyValue:'',
        		fieldLabel:fieldDef.title,
        		qtipText:fieldCfg.qtipText,
        		selectOnFocus:true,
        		value:fieldCfg.value
    		});
    		
    		fieldObjects.push(optionField);
    	}
    	else if(fieldDef.hidden)
    	{
    		fieldCfg.xtype = 'hidden';
    		
    		fieldObjects.push(fieldCfg);
    	}
    	else if(fieldDef.type == 'icon_choice')
    	{
    		fieldCfg.item = this.item;
    			
    		var iconField = new Webkit.Folders.Fields.icon_choice(fieldCfg);
    		
    		fieldObjects.push(iconField);
    	}
    	else if(fieldDef.type == 'string')
    	{	
    		fieldObjects.push(fieldCfg);
    	}
		else if(fieldDef.type == 'password')
    	{	
    		fieldObjects.push(fieldCfg);
    	}
    	else if(fieldDef.type == 'checkbox')
    	{	
    		fieldCfg.xtype = 'checkbox';
    		
    		if(fieldCfg.value!=null)
    		{
    			fieldCfg.checked = true;
    		}
    		
    		fieldObjects.push(fieldCfg);
    	}
    	else if(fieldDef.type == 'image')
    	{
    		fieldCfg.website_hostname = this.item.website_hostname;
    		fieldCfg.fileTypeTitle = 'Image';
    			
    		var fileField = new Webkit.Folders.Fields.file(fieldCfg);
    		
    		fieldObjects.push(fileField);
    	}
    	else if(fieldDef.type == 'video')
    	{
    		fieldCfg.website_hostname = this.item.website_hostname;
    		fieldCfg.fileTypeTitle = 'Video';
    			
    		var fileField = new Webkit.Folders.Fields.video(fieldCfg);
    		
    		fieldObjects.push(fileField);
    	}    	
    	else if(fieldDef.type == 'file')
    	{
    		fieldCfg.website_hostname = this.item.website_hostname;
    		fieldCfg.fileTypeTitle = 'Image';
    		
    		var fileField = new Webkit.Folders.Fields.file(fieldCfg);
    		
    		fieldObjects.push(fileField);
    	}
		else if(fieldDef.type.indexOf('date')==0)
    	{
    		fieldCfg.xtype = 'datefield';
    			
   			//if(fieldCfg.value==null)
   			//{
   			//	var dt = new Date();
   			//	dt.format('n/j/Y');
			//
   			//	fieldCfg.value = dt;
   			//}
    			
			fieldCfg.format = 'd/m/Y';
			
			if(fieldDef.read_only)
			{
				fieldCfg.xtype = 'textfield';
			}
			
			fieldObjects.push(fieldCfg);
   		}
   		else if((fieldDef.type == 'number')||(fieldDef.type == 'float')||(fieldDef.type == 'integer'))
   		{
   			fieldCfg.xtype = 'numberfield';
   			
   			fieldObjects.push(fieldCfg);
   		}
   		else if(fieldDef.type == 'comment')
   		{
   			fieldCfg.xtype = 'textarea';
   			
   			if(fieldCfg.height==null)
   			{
   				fieldCfg.height = 70;
   			}
   			
   			fieldObjects.push(fieldCfg);
   		}
   		else if(fieldDef.type == 'text')
   		{
   			fieldCfg.xtype = 'textarea';
   			
   			if(fieldCfg.height==null)
   			{
   				fieldCfg.height = 160;
   			}
   			
   			fieldObjects.push(fieldCfg);
   		}
   		else if(fieldDef.type == 'html')
   		{
   			if(fieldCfg.height==null)
   			{
   				fieldCfg.height = 200;
   			}
   			
   			var previewField = new Webkit.Folders.Fields.mce_preview(fieldCfg);
    		
    		fieldObjects.push(previewField);
    		
    		/*
   			fieldCfg.xtype = 'htmleditor';
   			
   			if(fieldCfg.height==null)
   			{
   				fieldCfg.height = 200;
   			}
   			fieldObjects.push(fieldCfg);   	
   				
   				
   			fieldCfg.xtype = 'tinymce';
   			
   			if(fieldCfg.height==null)
   			{
   				fieldCfg.height = 200;
   			}
   			
   			fieldCfg.value = 'apples';
   			
   			//fieldCfg.value = 'this is a test';
   			fieldCfg.tinymceSettings = {
				theme: "advanced",
				plugins: "pagebreak,style,layer,table,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
				theme_advanced_buttons1: "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
				theme_advanced_buttons2: "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
				theme_advanced_buttons3: "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|",
				theme_advanced_buttons4: "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
				theme_advanced_toolbar_location: "top",
				theme_advanced_toolbar_align: "left",
				theme_advanced_statusbar_location: "bottom",
				theme_advanced_resizing: false,
				extended_valid_elements: "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]"
				//template_external_list_url: "example_template_list.js"
			};
   			*/
					
   		}
   		else if(fieldDef.type == 'item_pointer')
   		{    			
   			var modelNameField = new Webkit.Folders.Fields.item_pointer(fieldCfg);
   			
   			fieldObjects.push(modelNameField);
   		}
   		else if(fieldDef.type == 'model_pointer')
   		{
   			var modelNameField = new Webkit.Folders.Fields.model_pointer(fieldCfg);
   			
   			if(fieldDef.value_changed_callback)
   			{
   				modelNameField.addListener('valuechanged', this[fieldDef.value_changed_callback], this);
   			}
   			
   			if(fieldDef.initialized_callback)
   			{
   				var initValue = fieldCfg.value;
   				
   				if(!fieldCfg.value && fieldCfg.default_value)
   				{
   					initValue = fieldCfg.default_value;
   				}
   				
   				this.initializeEvents.push({
   					method:fieldDef.initialized_callback,
   					value:initValue
   				});
   			}
   			
   			fieldObjects.push(modelNameField);
   		}
   		
		return fieldObjects;
    },
    
    //////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// builds the field definitions from the schema
    
    buildFormDefinition: function()
    {
    	var fieldDefs = this.item.getFields();
    	
   		var tabMap = {};
   		
   		this.fieldTabMap = {};
   		this.fieldMap = {};
   		this.initializeEvents = [];
    	
    	for(var i=0; i<fieldDefs.length; i++)
    	{
    		var fieldDef = fieldDefs[i];
    		
    		var fieldObjects = this.getFieldComponents(fieldDef);
			
			for(var j=0; j<fieldObjects.length; j++)
			{
				var fieldObject = fieldObjects[j];
				
				var tabName = 'default';
			
				if(fieldDef.tab!=null)
				{
					tabName = fieldDef.tab;
				}
			
				var tabFieldArray = tabMap[tabName];
			
				if(tabFieldArray == null)
				{
					tabFieldArray = [];
				}
			
				tabFieldArray.push(fieldObject);

				tabMap[tabName] = tabFieldArray;
			
				this.fieldTabMap[fieldObject.id] = tabName;
			}
			
			this.fieldMap[fieldDef.name] = fieldObjects;
    	}
    	
    	return tabMap;
    },
    
    //////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// folder GUI building method	
    
    createForm: function(tabDefinitions)
    {
		this.errorLabel = new Ext.Panel({
			id:this.id + 'error-label',
			bodyCssClass:'error-label',
			html:'' 
		});
			
		var defaultTabDefinition = tabDefinitions['default'];
		defaultTabDefinition.push(this.errorLabel);
		
		var tabNameArray = [];

		for(var tabName in tabDefinitions)
		{
			if(tabName != 'default')
			{
				tabNameArray.push(tabName);
			}
		}
		
		tabNameArray.sort();
		
		tabNameArray.unshift('default');
		
		this.formTabs = [];
		this.tabMap = {};
		
		var propertiesTab = null;
		
		for(var i=0; i<tabNameArray.length; i++)
		{
			var tabName = tabNameArray[i];
			
			var fields = tabDefinitions[tabName];
			
			var tabTitle = tabName;
			
			if(tabTitle == 'default')
			{
				tabTitle = this.item.getTypeTitle();
			}
			
			var nextFormPanel = new Ext.form.FormPanel({
				id:this.id + '-tab-' + i,
				title: tabTitle,
				border: false,
				labelAlign: 'right',
				//labelWidth: 130,
				labelPad: 10,
				defaultType: 'textfield',
				autoHeight: true,
				//hidden: true,
				//hideMode: 'visibility',
				style: 'padding:15px; background-color:#ffffff;',
				defaults: {
	    			anchor: '100%'
				},
				items: fields
   			});

   			this.tabMap[tabName] = nextFormPanel;
   			
   			if(tabTitle == 'Properties')
   			{
   				propertiesTab = nextFormPanel;
   			}
   			else
   			{
   				this.formTabs.push(nextFormPanel);
   			}
   		}
   		
   		if(propertiesTab!=null)
   		{
   			this.formTabs.push(propertiesTab);
   		}
   		
   		this.createKeywordPanel();
   		
   		this.formTabs.push(this.keywordPanel);
   		
   		var itemTitle = 'New ' + this.item.getTypeTitle();
   		
   		if(this.item.exists())
   		{
   			itemTitle = this.item.get('url');
   		}
   		
   		this.tabPanel = new Ext.TabPanel({
   			activeItem: 0,
   			autoHeight: true,
   			border: false,
   			items:this.formTabs
   		});
			
		this.window = new Ext.Window({
			//animateTarget: this.id,
			id:this.id + '-window',
			//title: itemTitle,
			title: this.item.getTypeTitle(),
			width: this.item.getSchemaProperty('form_width'),
			autoHeight: true,
			closable: true,
			resizable: true,
			y: 50,
        	buttonAlign: 'right',
        	items: this.tabPanel
    	});
    	
    	this.window.addListener("afterrender", this.itemFormRendered, this);
    	
    	
    	
		this.windowApplyButton = this.window.addButton({
			text:Webkit.Folders.Language.getLabel('save'),
			height:30,
			icon: Webkit.Folders.IconFactory.makeIconURI({
      			name:'navigate_check'
      		})
		});

    	this.windowCancelButton = this.window.addButton({
			text:Webkit.Folders.Language.getLabel('cancel'),
			height:30,
			icon: Webkit.Folders.IconFactory.makeIconURI({
      			name:'navigate_cross'
      		})
		});
		
		//this.itemFormRendered.defer(500, this);
    },
    
    itemFormRendered: function()
    {
    	var nameField = Ext.ComponentMgr.get(this.id + '_name');
    	
    	nameField.focus();
    	
    	for(var i=0; i<this.initializeEvents.length; i++)
    	{
    		var eventDesc = this.initializeEvents[i];
    		
    		this[eventDesc.method](eventDesc.value);
    	}
    	
    	this.fireEvent("item_form_rendered");
    },
    
    getTabNameData: function(listValues)
    {
    	var dataArr = [['default']];
    	
    	var hitValues = {};
    	
    	for(var i=0; i<listValues.length; i++)
    	{
    		var listValue = listValues[i];
    		
    		if((listValue.tab!=null)&&(listValue.tab!='')&&(listValue.tab!='Properties'))
    		{
    			if(hitValues[listValue.tab]==null)
    			{
    				dataArr.push([listValue.tab]);
    				
    				hitValues[listValue.tab] = true;
    			}
    		}
    	}
    	
    	dataArr.push(['Properties']);
    	
		return dataArr;
    },
    
    getTabNameStore: function(listValues)
    {
    	var theData = this.getTabNameData(listValues);
    	
    	var store = new Ext.data.ArrayStore({
        	fields: ['name'],
        	data: theData
    	});
    	
    	return store;
    },
    
    getFieldTypeData: function()
    {
    	return [
    		['string', 'Text'],
    		['password', 'Password'],
        	['number', 'Number'],
        	['date', 'Date'],
        	['datetime', 'Date/Time'],
        	['checkbox', 'Checkbox'],
        	['comment', 'Text Area'],
        	['text', 'HTML'],
        	['html', 'Rich Text'],
        	['image', 'Image'],
        	['video', 'Video'],
        	['file', 'File'],
        	['item_pointer', 'Item'],
        	['selectlist', 'List Of Options']
        ];
    },
    
	getFieldTypeStore: function()
    {
    	var theData = this.getFieldTypeData();
    	
    	var store = new Ext.data.ArrayStore({
        	fields: ['type', 'title'],
        	data: theData
    	});
    	
    	return store;
    },
    
    
    // These are plug in methods - i.e. one field changes and aother reacts
    
    inherits_from_changed: function(value)
    {
    	var newFields = Webkit.Folders.Schema.getItemTypeFields(value.name);
    	
    	var fieldList = this.fieldMap.inherited_fields[0];

    	fieldList.store.removeAll();
    	
    	for(var i=0; i<newFields.length; i++)
    	{
    		var newField = newFields[i];
    		
    		if(!newField.hidden)
    		{
    			var newValue = {
    				title:newField.title,
	    			name:newField.name,
    				type:newField.type
    			};
    			
    			var stParts = [newField.title, newField.name, newField.type];
    			
    			var st = stParts.join(', ');
    		
    			var newRecord = new Ext.data.Record({
	    			text:st,
    				value:newValue
    			});
    		
    			fieldList.store.add(newRecord);
    		}
    	}
    }
         
});