////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// itemFields.js
//
//
// contains component definitions for the various field types
//
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        
Webkit.Folders.Fields.list_editor = Ext.extend(Ext.Panel, {
	
	width:'98%',
	height:100,
	
	initComponent: function()
	{
		this.storeData = [];
    		
    	if(this.value)
    	{
    		for(var i=0; i<this.value.length; i++)
    		{
    			var theValue = this.value[i];
    			
    			if(Ext.isObject(theValue))
    			{
    				if(this.list_title_field)
    				{
    					theString = theValue[this.list_title_field];
    				}
    				else
    				{
    					var stParts = [];
    			
    					for(var prop in theValue)
    					{
	    					stParts.push(theValue[prop]);
    					}
    			
    					theString = stParts.join(', ');
    				}
    			
	    			this.storeData.push([theValue, theString]);
	    		}
	    		else
	    		{
	    			this.storeData.push([theValue, theValue]);
	    		}
    		}
    	}
    	else if(this.default_value)
    	{
    		this.storeData.push([this.default_value, this.default_value.name]);
    	}
    				
 		this.store = new Ext.data.ArrayStore({
			fields: ['value','text'],
			data:this.storeData
		});

		this.valueField = 'value';
		this.displayField = 'text';
		
		delete(this.title);
		
		if(!this.hide_toolbar)
		{
			this.makeToolbar();
		}
		
		this.view = new Ext.ListView({
            autoHeight: true,
            multiSelect:true,
            anchor:'l 95%',
            store: this.store,
            columns: [{ header: 'Value', width: 1, dataIndex: this.displayField }],
            hideHeaders: true
        });
        
        this.layout = 'anchor';
        this.autoScroll = true;
        this.items = [this.view];
		
		Webkit.Folders.Fields.list_editor.superclass.initComponent.call(this);	
	},
	
	makeToolbar: function()
	{		
		this.addButton = new Ext.Button({
	 		text:'Add'
	 	});

	 	this.addButton.addListener('click', this.addValue, this);
	 	
	 	this.editButton = new Ext.Button({
	 		text:'Edit'
	 	});

	 	this.editButton.addListener('click', this.editValue, this);
	 	
	 	this.deleteButton = new Ext.Button({
	 		text:'Delete'
	 	});

	 	this.deleteButton.addListener('click', this.deleteValue, this);
	 	
	 	this.upButton = new Ext.Button({
	 		text:'Move Up'
	 	});

	 	this.upButton.addListener('click', this.moveUp, this);
	 	
	 	this.downButton = new Ext.Button({
	 		text:'Move Down'
	 	});

	 	this.downButton.addListener('click', this.moveDown, this);
	 	
		var arr = [
			this.addButton,
			this.editButton,
			this.deleteButton
		];
				
		this.sepAdded = false;
		
		if(this.fieldDef.allow_clear)
		{
			var clear_title = 'Remove All';
			
			if(this.fieldDef.clear_title)
			{
				clear_title = this.fieldDef.clear_title;
			}
			
			this.clearButton = new Ext.Button({
	 			text:clear_title
	 		});

	 		this.clearButton.addListener('click', this.clearValues, this);
	 		
	 		if(!this.sepAdded)
	 		{
	 			this.sepAdded = true;
	 			arr.push('-');
	 		}
	 		
	 		arr.push(this.clearButton);
		}
		
		if(this.fieldDef.default_value)
		{
			var default_title = 'Reset';
			
			if(this.fieldDef.default_title)
			{
				default_title = this.fieldDef.default_title;
			}
			
			this.defaultButton = new Ext.Button({
	 			text:default_title
	 		});

	 		this.defaultButton.addListener('click', this.resetToDefault, this);
	 		
	 		if(!this.sepAdded)
	 		{
	 			this.sepAdded = true;
	 			arr.push('-');
	 		}
	 		
	 		arr.push(this.defaultButton);
		}
		
		arr.push('-');
		arr.push(this.upButton);
		arr.push(this.downButton);
		
		this.tbar = arr;
	},
	
	getValue: function()
	{
		var records = this.store.getRange();
		
		var array = [];
		
		for(var i=0; i<records.length; i++)
		{
			array.push(records[i].get('value'));
		}
		
		return array;
	},
	
	clearValues: function()
	{
		this.store.removeAll();
		
		var theRecord = new Ext.data.Record({
			text:'None',
			value:{
				id:'_none',
				name:'None'
			}
		});
		
		this.store.add(theRecord);
	},
	
	resetToDefault: function()
	{
		this.store.removeAll();
		
		var theRecord = new Ext.data.Record({
			text:this.fieldDef.default_value.name,
			value:this.fieldDef.default_value
		});
		
		this.store.add(theRecord);
	},
	
	moveUp: function()
	{
		var selectedIndexes = this.view.getSelectedIndexes();
		var theIndex = selectedIndexes[0];
		
		if(theIndex<=0) { return; }
		
		var theRecord = this.store.getAt(theIndex);
		
		if(!theRecord)
		{
			return;
		}
		
		this.store.remove(theRecord);
		this.store.insert(theIndex-1, [theRecord]);
		this.view.select(theIndex-1);
	},
	
	moveDown: function()
	{
		var selectedIndexes = this.view.getSelectedIndexes();
		var theIndex = selectedIndexes[0];
		
		if(theIndex>=this.store.getCount()-1) { return; }
		
		var theRecord = this.store.getAt(theIndex);
		
		if(!theRecord)
		{
			return;
		}
		
		this.store.remove(theRecord);
		this.store.insert(theIndex+1, [theRecord]);
		this.view.select(theIndex+1);
	},
	
	deleteValue: function()
	{
		var selectedRecords = this.view.getSelectedRecords();
		
		for(var i=0; i<selectedRecords.length; i++)
		{
			this.store.remove(selectedRecords[i]);	
		}
	},
	
	addValue: function()
	{
		this.editingRecord = null;
		this.showEditor();
	},
	
	editValue: function()
	{
		var selectedRecords = this.view.getSelectedRecords();
		
		var theRecord = selectedRecords[0];
		
		if(!theRecord)
		{
			return;
		}
		
		this.editingRecord = theRecord;
		
		this.showEditor(theRecord.get('value'));
	},
	
	showEditor: function(theValue)
	{
		this.theFields = this.form.getFieldComponents(this.fieldDef, true, true, theValue, this.getValue());
		
		this.formPanel = new Ext.form.FormPanel({
			id:'addFieldValue',
			border: false,
			labelAlign: 'right',
			//labelWidth: 130,
			labelPad: 10,
			defaultType: 'textfield',
			autoHeight: true,
			//hidden: true,
			//hideMode: 'visibility',
			style: 'padding:5px;background-color:#ffffff;',
			defaults: {
	    		anchor: '100%'
			},
			items: this.theFields
   		});
   			
		this.editWindow = new Ext.Window({
			title: 'Add Value',
        	width: 600,
			autoHeight:true,
        	layout: 'fit',
			plain:true,
			modal: true,
			closable: false,
			resizable: true,
        	items: [this.formPanel],
        	buttonAlign: 'right'
    	});
    	
    	this.windowApplyButton = this.editWindow.addButton({
			text:Webkit.Folders.Language.getLabel('ok'),
			height:30,
			icon: Webkit.Folders.IconFactory.makeIconURI({
      			name:'navigate_check'
      		})
		});

    	this.windowCancelButton = this.editWindow.addButton({
			text:Webkit.Folders.Language.getLabel('cancel'),
			height:30,
			icon: Webkit.Folders.IconFactory.makeIconURI({
      			name:'navigate_cross'
      		})
		});
		
		this.windowApplyButton.addListener('click', this.applyValue, this);
		this.windowCancelButton.addListener('click', this.cancelValue, this);
		
		this.editWindow.show();
	},
	
	applyValue: function()
	{
		var value = null;
		var text = '';
		
		if(this.fieldDef.default_value)
		{
			for(var i=0; i<this.store.getCount(); i++)
			{
				var theRecord = this.store.getAt(i);
				
				if(theRecord.get('text') == this.fieldDef.default_value.name)
				{
					this.store.remove(theRecord);	
				}
			}
		}
		
		if(this.theFields.length==1)
		{
			value = this.theFields[0].getValue();
			text = this.theFields[0].getRawValue();
			
			if(!value)
			{
				return;
			}
		}
		else
		{
			var texts = [];
			var objValue = {};
			
			for(var i=0; i<this.theFields.length; i++)
			{
				var theValue = this.theFields[i].getValue();
				
				if((theValue=='')&&(this.theFields[i].emptyValue))
				{
					theValue = this.theFields[i].emptyValue;
				}
				
				objValue[this.theFields[i].name] = theValue;
				
				texts.push(theValue);
			}
			
			text = texts.join(', ');
			value = objValue;
		}
		
		if(this.editingRecord)
		{
			this.editingRecord.set('value', value);
			this.editingRecord.set('text', text);
		}
		else
		{
			var record = new Ext.data.Record({
				value:value,
				text:text
			});
		
			this.store.add(record);
		}
		
		this.cancelValue();
	},
	
	cancelValue: function()
	{
		this.editWindow.destroy();
	}
});

Webkit.Folders.Fields.icon_field = Ext.extend(Ext.Container, {
	
	layout: 'column',
	itemCls: 'icon-choice-field',
	includeReset: true,
	
	initComponent: function()
	{		
		this.buildGUI();
	},
	
	getValue: function()
	{
		return this.value;
	},
	
	editValue: function()
	{
		
	},
	
	resetValue: function()
	{
		
	},
	
	getRawValue: function()
	{
		return this.getValueName();
	},
	
	getValueName: function()
	{
		
	},
	
	getIconName: function()
	{
		
	},
	
	getIconURI: function()
	{
		var iconName = this.getIconName();
		
		if(!iconName) { return null; }
		
		var iconURI = Webkit.Folders.IconFactory.makeIconURI({name:iconName});
		
		return iconURI;
	},
	
	applySummary: function()
	{
		this.iconInfoPanel.getEl().update(this.getSummaryHTML());
	},
	
	buildGUI: function()
	{
		var html = this.getSummaryHTML();		
		
		this.iconInfoPanel = new Ext.Panel({
			columnWidth:1,
	 		autoHeight:true,
	 		border:false,
	 		cls:'icon-choice-field-panel',
	 		html:html
		});
		
	 	this.changeButton = new Ext.Button({
	 		text:'Choose...',
	 		width:80
	 	});

	 	this.changeButton.addListener('click', this.editValue, this);
	 	
		this.items = [this.iconInfoPanel, this.changeButton];
		
		if(this.includeReset)
		{
			this.resetButton = new Ext.Button({
		 		text:'Reset...',
	 			width:80
	 		});
	 		
	 		this.resetButton.addListener('click', this.resetValue, this);
	 		
	 		this.items.push(this.resetButton);
	 	}
	 		
		Webkit.Folders.Fields.file.superclass.initComponent.apply(this, arguments);
	},
	
	getSummaryHTML: function()
	{
		var html = '<span class="icon-choice-field-blank-span">';

		var iconURI = this.getIconURI();
		var iconName = this.getValueName();
		
		if(iconURI)
		{
			html += '<img width="16" height="16" border=0 align="absbottom" src="' + iconURI + '" /> - ';
		}
		
		if(iconName)
		{
			html += iconName;
		}
		
		html += '</span>';
		
		return html;
	}

});

Webkit.Folders.Fields.icon_choice = Ext.extend(Webkit.Folders.Fields.icon_field, {
	
	getIconName: function()
	{
		var currentIcon = this.value;
		
		if(currentIcon == null)
		{
			currentIcon = this.item.getIcon();
		}
		
		return currentIcon;
	},

	getValueName: function()
	{
		return this.value ? this.value : 'default';
	},
	
	resetValue: function()
	{
		this.value = null;
		
		this.applySummary();
	},
	
	iconClicked: function(dataView, i, node, e)
	{
		var iconRecord = dataView.getRecord(node);
		
		this.value = iconRecord.get('icon');
		
		this.applySummary();
			
		this.cancelChoiceWindow();	
	},
	
	searchKeyPress: function(textField, eventObj)
	{
		this.store.load({
			params:{
				search:textField.getValue()
			}
		});
	},
	
	letterClick: function(buttonObj)
	{
		this.store.load({
			params:{
				search:buttonObj.text
			}
		});
	},

	showChoiceWindow: function()
	{
		this.choiceWindow.show();
		
		this.store.load({
			params:{
				search:'a'
			}
		});
	},
	
	cancelChoiceWindow: function()
	{
		this.choiceWindow.close();
		this.choiceWindow.destroy();
	},	
	
	editValue: function()
	{
		this.store = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: Webkit.Folders.AdminApp.prototype.iconListURI }),

			reader: new Ext.data.JsonReader(),

			remoteSort: true });

		 this.tpl = new Ext.XTemplate(
			'<tpl for=".">',
           		'<div class="icon-choice-item">',
		   			'<div class="icon-choice-thumbnail">',
		   				'<img width="48" height="48" src="{url}" title="{id}">',
		   			'</div>',
		   			'<span class="icon-choice-title">{title}</span>',
		   		'</div>',
        	'</tpl>' );
		
		this.dataView = new Ext.DataView({
            store: this.store,
            tpl: this.tpl,
            autoHeight:false,

            multiSelect: false,
			simpleSelect: true,
			            
            region:'center',
            
			overClass:'itemViewOver',
        	selectedClass:'itemViewSelected',
        	
			itemSelector:'div.icon-choice-item',

            prepareData: function(data)
			{	
				data.url = Webkit.Folders.IconFactory.makeIconURI({name:data.icon, destination:'item_view'});
				
				data.title = data.icon.replace(/_/g, '<br>');
				
				return data;
			}
			
			
        });
        
        this.dataView.addListener("click", this.iconClicked, this);
		
		this.searchTextField = new Ext.form.TextField({
			id:'iconsearch',
			name: 'iconsearch',
			enableKeyEvents: true
		});
		
		this.searchTextField.addListener('keyup', this.searchKeyPress, this);
		
		var topBarItems = [
    		{xtype: 'tbtext', text: 'Search:'},
    		this.searchTextField,
    		'-'
    	];
    	
    	var alphabet = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
    	
    	for(var i=0; i<alphabet.length; i++)
    	{
    		var letterButton = new Ext.Button({
    			text:alphabet[i]
    		});
    		
    		letterButton.addListener('click', this.letterClick, this);
    		topBarItems.push(letterButton);
    	}
		
		this.panel = new Ext.Panel({
			id:'icon-choice-dataview',
       		autoHeight:false,
			autoScroll:true,
			bodyStyle:'background-color: #ffffff',
			containerScroll:true,
			region:'center',
			layout:'border',
			margins: '0 0 0 0',
       		items: [this.dataView],
       		/*bbar: new Ext.PagingToolbar({
				store: this.store,       // grid and PagingToolbar using same store
				displayInfo: true,
				pageSize: 40,
				prependButtons: true,
				items: [
					'showing'
				]
    		}),*/
    		tbar: new Ext.Toolbar({
    			items: topBarItems
    		})
		});		
    
    	this.iconChoiceCancelButton = new Ext.Button({
			text:'Cancel'			
    	});
    	
    	this.iconChoiceCancelButton.addListener('click', this.cancelChoiceWindow, this);
    	
    	this.choiceWindow = new Ext.Window({
			title: 'Choose Icon',
        	width: 600,
        	height: 400,
        	layout: 'fit',
			plain:true,
			modal: true,
			closable: false,
			resizable: true,
        	
        	buttonAlign:'right',
        	items: this.panel,
        	buttons: [
        		this.iconChoiceCancelButton
        	]
    	});
    	
    	this.showChoiceWindow.defer(10, this);
	}
	
	
});

Webkit.Folders.Fields.item_pointer = Ext.extend(Webkit.Folders.Fields.icon_field, {
	
	id:'itemPointerTree',
	rootFolderName:'Items',
	rootIconName:'folder',
	chooseKey:'choose_item',
	includeReset: false,
	allowed_types:['*'],
	
	initComponent: function()
	{
		Webkit.Folders.Fields.item_pointer.superclass.initComponent.call(this);
		
		this.addEvents({
			'valuechanged': true
		});
	},	
	
	getValue: function()
	{
		return this.value;
	},	
	
	getValueName: function()
	{
		if(this.value)
		{
			return this.value.name;
		}
		else
		{
			return null;
		}
	},
	
	getIconName: function()
	{
		if(this.value)
		{
			return this.value.icon;
		}
		else
		{
			return null;
		}
	},
	
	editValue: function()
	{		
		var rootClass = Webkit.Folders.IconFactory.makeIconClass({
			name:this.rootIconName
		});
		
		this.folderTree = new Webkit.Folders.ItemTree({
			id:this.id,
			rootFolderName:this.rootFolderName,
			rootFolderId:this.base_url,
			rootNodeIconClass:rootClass,
			noHeader:true,
			name:' '
		});
		
		this.folderTree.addListener('loaditem', this.itemChoosen, this);
		
		this.choiceWindow = new Ext.Window({
			title: Webkit.Folders.Language.getLabel(this.chooseKey),
        	width: 300,
        	height: 400,
        	layout: 'fit',
			plain:true,
			modal: true,
			closable: false,
			resizable: true,
        	items: [this.folderTree.panel],
        	buttonAlign: 'right'
    	});
    	
    	this.windowApplyButton = this.choiceWindow.addButton({
			text:Webkit.Folders.Language.getLabel('ok'),
			height:30,
			disabled:true,
			icon: Webkit.Folders.IconFactory.makeIconURI({
      			name:'navigate_check'
      		})
		});

    	this.windowCancelButton = this.choiceWindow.addButton({
			text:Webkit.Folders.Language.getLabel('cancel'),
			height:30,
			icon: Webkit.Folders.IconFactory.makeIconURI({
      			name:'navigate_cross'
      		})
		});	
    	
    	this.windowApplyButton.addListener('click', this.acceptChoice, this);
    	this.windowCancelButton.addListener('click', this.cancelChoice, this);
    	
    	this.choiceWindow.show();
    	
    	if(!this.base_url)
    	{
    		this.base_url = 'disk:/';
    	}
    	
    	theApplication.loadTreeData(this.base_url, this.folderTree);
    	
    	this.postLoadTreeData();
	},
	
	postLoadTreeData: function()
	{
		
	},
	
	acceptChoice: function()
	{
		var itemId = null;
		
		if(this.choosenItem.get('manuallyAdded'))
		{
			itemId = this.choosenItem.id;
		}
		else
		{
			itemId = this.choosenItem.databaseId();
		}
		
		this.value = {
			name:this.choosenItem.name,
			id:itemId,
			icon:this.choosenItem.getIcon()
		};
		
		this.applySummary();
		
		this.cancelChoice();
		
		this.fireEvent('valuechanged', this.value);
	},
	
	cancelChoice: function()
	{
		this.choiceWindow.destroy();
	},
	
	itemChoosen: function(item)
	{
		if(!item) { return; }

		if(item.id==this.base_url) { return; }
		
		for(var i=0; i<this.allowed_types.length; i++)
		{
			if((this.allowed_types[i] == '*') || (item.isOfType(this.allowed_types[i])))
			{
				this.choosenItem = item;
				this.windowApplyButton.enable();
			}
			else
			{
				this.choosenItem = null;
				this.windowApplyButton.disable();
			}
		}
	}
	
});

Webkit.Folders.Fields.model_pointer = Ext.extend(Webkit.Folders.Fields.item_pointer, {
		
	id:'modelPointerTree',
	rootFolderName:'Models',
	rootIconName:'model',
	
	initComponent: function()
	{
		if(!this.value)
		{
			this.value = this.default_value;
		}
		
		this.buildGUI();
	},	

	postLoadTreeData: function()
	{
		this.buildSystemOptions();
	},
	
	getSystemTreeNode: function(config)
	{
		var attr = {
			id:config.id,
			name:config.name,
			leaf:config.leaf,
			expanded:true,
			manuallyAdded:true,
			text:config.name,
			item:new Webkit.Folders.Item({
				id:config.id,
				item_type:config.type,
				name:config.name,
				manuallyAdded:true,
				foldericon:config.foldericon
			}, config.id)
		};

		if(Ext.isIE)
		{
			var iconURI = Webkit.Folders.IconFactory.makeIconURI({
				name:config.icon,
				destination:'tree'
			});
			
			attr.icon = iconURI;
		}
		else
		{
			var iconClass = Webkit.Folders.IconFactory.makeIconClass({
				name:config.icon,
				destination:'tree'
			});
			
			attr.iconCls = iconClass;
		}
		
		var theNode = new Ext.tree.TreeNode(attr);
		
		return theNode;
	},
	
	buildSystemOptions: function()
	{
		var rootNode = this.folderTree.panel.getRootNode();
		
		var schemas = Webkit.Folders.Schema.getSystemModels();
		
		var topNode = this.getSystemTreeNode({
			id:'system_models',
			name:'System Models',
			type:'folder',
			leaf:false,
			icon:'folder'
		});
		
		rootNode.appendChild(topNode);
		
		for(var i=0; i<schemas.length; i++)
		{
			var schema = schemas[i];
			
			if(!schema.is_dynamic)
			{
				var schemaNode = this.getSystemTreeNode({
					id:'system_model:' + schema.id,
					name:schema.id,
					type:'model',
					leaf:true,
					icon:schema.icon,
					foldericon:schema.icon
				});
			
				topNode.appendChild(schemaNode);
			}
		}
	}

});

Webkit.Folders.Fields.file = Ext.extend(Ext.Container, {
	layout: 'column',

	itemCls: 'file-field',
	
	getValue: function()
	{
		return this.value;
	},
	
	getRawValue: function()
	{
		return this.value.file;
	},
	 
	initComponent: function()
	{
		if(this.fileTypeTitle==null)
		{
			this.fileTypeTitle = 'File';
		}
		
		this.uploadURI = Webkit.Folders.AdminApp.prototype.uploadFileURI;
		
		var html = this.getSummaryHTML();	
		
		this.fileInfoPanel = new Ext.Panel({
			columnWidth:1,
	 		autoHeight:true,
	 		border:false,
	 		cls:'file-field-panel',
	 		html:html
		});
	 	
	 	this.changeButton = new Ext.Button({
	 		text:'Upload...',
	 		width:80
	 	});
	 	
	 	this.changeButton.addListener('click', this.uploadFile, this);
	 	this.changeButton.addListener('render', this.panelRendered, this);
	 	
		this.items = [this.fileInfoPanel, this.changeButton];
	 	
		Webkit.Folders.Fields.file.superclass.initComponent.apply(this, arguments);
	},
	
	panelRendered: function()
	{
		this.fileInfoPanel.getEl().addListener('click', this.panelClicked, this);
	},
	
	panelClicked: function(e)
	{
		var linkComponent = e.getTarget('span.file-field-launch-link');
		var deleteComponent = e.getTarget('span.file-field-delete-link');
		
		if(linkComponent==null && deleteComponent==null) { return; }
		if(this.value==null) { return; }
		
		if(linkComponent)
		{	
			var fileURL = this.value.folder + '/' + this.value.file;
		
			if(fileURL.indexOf('/')!=0)
			{
				fileURL = Webkit.Folders.AdminApp.prototype.uploadFolder + '/' + fileURL;
			}
			else
			{
				fileURL = this.website_hostname + fileURL;
			}
		
			window.open(fileURL);
		}
		else if(deleteComponent)
		{
			this.value = null;
			
			this.fileInfoPanel.getEl().update(this.getSummaryHTML());
		}
	},
	
	
	getSummaryHTML: function()
	{
		var html = '<span class="file-field-blank-span">';
		
		if(this.value!=null)
		{
			html += '<span class="file-field-filename">' + this.value.file + '</span> uploaded...<br/>';
			
			html += this.value.type + ' (' + this.value.size + ' Kb) - ';
			
			html += '<span class="file-field-launch-link">view ' + this.fileTypeTitle.toLowerCase() + '</span>';
			html += ' - <span class="file-field-delete-link">delete ' + this.fileTypeTitle.toLowerCase() + '</span>';
		}
		else
		{
			html += 'no ' + this.fileTypeTitle.toLowerCase() + ' uploaded...';
		}
		
		html += '</span>';
		
		return html;
	},
	
	getExtraValuesFromForm: function(data)
	{
		return data;
	},
	
	processFileData: function(data)
	{
		data = this.getExtraValuesFromForm(data);
		
		this.value = data;
		
		this.fileInfoPanel.getEl().update(this.getSummaryHTML());
	},
	
	getUploadFormItems: function()
	{
		var emptyText = 'Select a';
		
		if(this.fileTypeTitle == 'image')
		{
			emptyText += 'n';	
		}
		
		emptyText += ' ' + this.fileTypeTitle;
				
		var ret = [{
            	xtype: 'fileuploadfield',
            	id: 'form-file',
            	emptyText: emptyText,
            	fieldLabel: this.fileTypeTitle,
            	name: 'file',
            	buttonText: 'Browse',
            	buttonCfg: {
					width:80
            	}
        	}];
        
        return ret;
	},
	
	uploadFile: function()
	{		
		this.uploadPanel = new Ext.FormPanel({
        	fileUpload: true,
        	width: 600,
        	frame: true,
        	autoHeight: true,
        	bodyStyle: 'padding: 10px 10px 0 10px;',
        	labelWidth: 180,
        	defaults: {
	            anchor: '95%',
            	allowBlank: false,
            	msgTarget: 'side'
        	},
        	items: this.getUploadFormItems(),
        	buttons: []
    	});
    	
    	this.uploadCancelButton = new Ext.Button({
			text:'Cancel'			
    	});
    	
    	this.uploadSubmitButton = new Ext.Button({
			text:'Upload'			
    	});
    	
    	this.uploadCancelButton.addListener('click', this.cancelUploadWindow, this);
    	this.uploadSubmitButton.addListener('click', this.submitUploadWindow, this);
    	
    	this.uploadWindow = new Ext.Window({
			title: 'Upload ' + this.fileTypeTitle,
        	width: 600,
        	height: 150,
        	layout: 'fit',
			plain:true,
			modal: true,
			closable: false,
			resizable: false,
        	bodyStyle:'padding:5px;padding-top:10px;',
        	buttonAlign:'right',
        	items: this.uploadPanel,
        	buttons: [
        		this.uploadCancelButton,
        		this.uploadSubmitButton
        	]
    	});
    	
    	this.uploadWindow.show.defer(10, this.uploadWindow);
	},
	
	submitUploadWindow: function()
	{		
		this.uploadPanel.getForm().submit({
			url: this.uploadURI,
			method: 'POST',
			waitMsg: 'Uploading your ' + this.fileTypeTitle.toLowerCase() + '...',
			success: this.uploadDone,
			failure: this.uploadDone,
			scope: this
		});    			
	},

	uploadDone: function(form, action)
	{
		var theResponse = Ext.decode(action.response.responseText);
		
		if(theResponse.status == "ok")
		{
			var uploadData = theResponse.upload_data;
			
			
			//console.log(uploadData);
			
			var fileData = {
				file: uploadData.file_name,
				folder:  uploadData.relative_folder,
				size: uploadData.file_size,
				type: uploadData.file_type,
				extension: uploadData.extension,
				width: uploadData.width,
				height: uploadData.height
			};
			
			this.processFileData(fileData);
			this.cancelUploadWindow();
		}
		else
		{
			Ext.MessageBox.alert('Upload Error', 'There was a problem uploading this file...');
		}
	},
	
	cancelUploadWindow: function()
	{
		this.uploadWindow.close();
		this.uploadWindow.destroy();
	}
});

Webkit.Folders.Fields.video = Ext.extend(Webkit.Folders.Fields.file, {
		
	getExtraValuesFromForm: function(data)
	{
		var formData = this.uploadPanel.getForm().getValues();
		
		data.thumbnailstart = formData.videouploadthumbnailstart;
		
		return data;
	},
	
	getUploadFormItems: function()
	{
		var arr = Webkit.Folders.Fields.video.superclass.getUploadFormItems.apply(this);
		
		arr.push({
			xtype:'textfield',
			id: 'videouploadthumbnailstart',
			name: 'videouploadthumbnailstart',
			fieldLabel: 'Thumbnail Start Seconds',
			allowBlank: true,
			value: this.value ? this.value.thumbnailstart : ''
		});
        
        return arr;
	}
		

});

Webkit.Folders.Fields.mce_preview = Ext.extend(Ext.Container, {
		 
	initComponent: function()
	{
		this.displayPanel = new Ext.Panel({
	 		border:false,
	 		html:this.getPreviewHTML(this.value)
		});
		
		this.cls = 'mcePreviewOverflow';
		this.autoWidth = true;
		//this.autoHeight = true;
	 	
	 	this.displayPanel.addListener('afterrender', this.setupClick, this);
	 	
		this.items = [this.displayPanel];
	 	
		Webkit.Folders.Fields.mce_preview.superclass.initComponent.apply(this, arguments);
	},
	
	getPreviewHTML: function(value)
	{
		var valueString = '';
		
		if(value!=null)
		{
			valueString = value;
		}
		
		var st = '<div class="mce_preview">' + valueString + '</div>';
		
		return st;
	},
	
	getValue: function()
	{
		return this.value;
	},
	
	setupClick: function()
	{
		this.displayPanel.getEl().addListener('click', this.elemClick, this);
	},
	
	elemClick: function()
	{
		this.openEditor();
	},
	
	openEditor: function()
	{
		this.mce = new Ext.ux.TinyMCE({
			id:this.id + '-editor',
			tinymceSettings: {
				theme: "advanced",
				plugins: "style,preview,searchreplace,contextmenu,advlink,paste,visualchars,xhtmlxtras",
				theme_advanced_buttons1: "code,preview,|,cut,copy,pastetext,pasteword,cleanup,|,replace,undo,redo",
				theme_advanced_buttons2: "styleselect,|,bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,forecolor,|,link,unlink,|,charmap",
				theme_advanced_buttons3: "",
				//external_link_list_url: "http://dev.xara.com/us/generatedscripts/links.js",
				forced_root_block : false,
    			force_p_newlines : false,
    			remove_linebreaks : false,
    			force_br_newlines : true,
    			remove_trailing_nbsp : false,
    			verify_html : false,
				theme_advanced_toolbar_location: "top",
				theme_advanced_toolbar_align: "left",
				theme_advanced_statusbar_location: "bottom",
				theme_advanced_resizing: false,
				extended_valid_elements: "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
				template_external_list_url: "example_template_list.js"
			},
			value: this.value
		});
		
		this.currentEditor = new Ext.Window({
			width: 600,
			height: 500,
			minWidth: 100,
			minHeight: 100,
			layout: "fit",
			modal: true,
			resizable: true,
			maximizable: true,
			closable: false,
			hideMode: "offsets",
			constrainHeader: true,
			buttons: [
			{
			    text: "Apply",
			    handler:this.applyEditor,
			    scope:this
			},
			{
			    text: "Cancel",
			    handler:this.closeEditor,
			    scope:this
			}
			],
			items: [
				this.mce			
			]
		});
		
		this.currentEditor.show();	
	},
	
	applyEditor: function()
	{
		this.value = this.mce.getValue();
		
		this.displayPanel.update(this.getPreviewHTML(this.value));
		
		this.closeEditor();
	},
	
	closeEditor: function()
	{
		this.currentEditor.hide();
		this.currentEditor.destroy();
	}
	
});