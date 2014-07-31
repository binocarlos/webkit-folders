////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// item.js
//
//
// represents one item in the database
// this class wraps a Ext.data.Record and provides an abstract way of integrating an items
// keywords into its properties
//
// this allows the js app the treat an item as though it were merged with its keywords
// to make it easier to ask questions of the item
//
//
//
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

Webkit.Folders.Item = function(data, id){

    this.id = (id || id === 0) ? id : Ext.data.Record.id(this);
    this.data = data || {};
    
    this.name = this.data.name;
    this.children = this.data.children;
    this.keywords = this.data.keywords;
    this.parent_id = this.data.parent_id;
    this.item_type = this.data.item_type;
    this.l = this.data.l;
    this.r = this.data.r;
    this.link_type = this.data.link_type;
	this.path = this.data.path;
	this.website_path = this.data.website_path;
	this.website_hostname = this.data.website_hostname;
};

Ext.extend(Webkit.Folders.Item, Ext.data.Record, {
	
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
	// this is an important method
	//
	// once an item has been created - it has a standard item record with a keyword array
	//
	// this function transforms the keyword array into a Record (from the schema) with the correct definition
	//
	// it loops through each keyword - and if the keyword is defined in the schema - it is added to the record
	//
	// each item has a 'keyword_map' this is an index of every keyword
	// each entry is an array because one 'word' may have several values
	// 
	
	constructKeyword: function(k)
	{
		var ret = {
			id:k[0],
			keyword_type:k[1],
			name:k[2],
			value:k[3]
		};
		
		return ret;	
	},
	
	databaseId: function()
	{
		var idSt = '' + this.id;
		var parts = idSt.split('.');
		
		return parts[0];
	},
	
	linkId: function()
	{
		var idSt = '' + this.id;
		var parts = idSt.split('.');
		
		return parts[1];
	},
	
	parentDatabaseId: function()
	{
		var idSt = '' + this.parent_id;
		var parts = idSt.split('.');
		
		return parts[0];
	},
	
	parentLinkId: function()
	{
		var idSt = '' + this.parent_id;
		var parts = idSt.split('.');
		
		return parts[1];
	},

	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// LOGIC INTERFACE
//
// more for questions than get/set
//
// This will change considerably when we can get the GUI to listen to the server
// for item definitions
//
// until then we are hardcoding properties based on item_type
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if the given item is an ancestor of this one
	
	isAncestor: function(ancestorItem)
	{
		if((ancestorItem.l < this.l) && (ancestorItem.r > this.r))
		{
			return true;
		}
		else
		{
			return false;
		}
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// used to determine if this item can be moved around (ghosts cannot for example)
	
	canMove: function(parentItem)
	{
		if(this.isGhost() && parentItem.isGhost()) { return false; }
		
		return true;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// used to determine whether the provided item - itended to be a child of this one
	// can in fact be a child - for instance - you cannot add a folder to a file (for example)
	
	canAddItem : function(childItem)
	{
		if(this.isGhost()) { return false; }
		
		if(this.isOfType('bin')) { return true; }
		
		if(!this.areChildrenAllowed()) { return false; }
		
		
		if(childItem)
		{
			if(!this.canAddItemOfType(childItem.getType())) { return false; }
			
			if(this.isAncestor(childItem)) { return false; }
		
			if(this.databaseId()==childItem.databaseId()) { return false; }
			//if(this.databaseId()==childItem.parentDatabaseId()) { return false; }
			if(childItem.parentDatabaseId()==this.databaseId()) { return false; }
		}
		
		return true;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if this items childFilter contains '*'
	
	canAddItemOfAnyType: function()
	{
		return this.canAddItemOfType();
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if this items parentFilter contains '*'
	
	canAddToItemOfAnyType: function()
	{
		return this.canAddToItemOfType();
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// checks the definition rules to see if we are allowed add an an X to a Y
	
	canAddItemOfType : function(childType)
	{
		if(!this.areChildrenAllowed()) { return false; }
		
		if(childType)
		{
			var parentFilter = Webkit.Folders.Schema.getItemTypeParentFilter(childType);
		
			var hasFoundParentMatch = false;
		
			for(var i=0; i<parentFilter.length; i++)
			{
				var allowedParentType = parentFilter[i];
				
				if(allowedParentType == '*')
				{
					hasFoundParentMatch = true;
				}
			
				if(allowedParentType == this.getType())
				{
					hasFoundParentMatch = true;
				}
			}
		
			if(!hasFoundParentMatch) { return false; }
		}
			
		
		var filter = this.getChildFilter();
		
		for(var i=0; i<filter.length; i++)
		{
			var allowedType = filter[i];
			
			if(allowedType == '*') { return true; }
			
			if(childType!=null)
			{
				if(allowedType == childType) { return true; }
			}
		}
		
		return false;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// checks the definition rules to see if we are allowed add this to a given type (checks parentFilter)
	
	canAddToItemOfType : function(parentType)
	{
		var filter = this.getParentFilter();
		
		for(var i=0; i<filter.length; i++)
		{
			var allowedType = filter[i];
			
			if(allowedType = '*') { return true; }
			
			if(parentType!=null)
			{
				if(allowedType == parentType) { return true; }
			}
		}
		
		return false;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns an array of item types that are allowed to be children of this one
	//
	// it populates this with every type if the filter is '*'
	
	getAllowedChildTypes: function()
	{
		
		if(!this.areChildrenAllowed()) { return []; }
		
		var childTypes = [];
		var ret = [];
		
		if(this.canAddItemOfAnyType())
		{
			childTypes = Webkit.Folders.Schema.getAllItemTypes();
		}
		else
		{
			childTypes = Webkit.Folders.Schema.sortItemTypes(this.getChildFilter());
		}
		
		for(var i=0; i<childTypes.length; i++)
		{
			var childType = childTypes[i];
			
			if(this.canAddItemOfType(childType))
			{
				ret.push(childType);
			}
		}
		
		return ret;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if this is item not allowed ANY children items
	
	areChildrenAllowed : function()
	{
		var filter = this.getChildFilter();
		
		if(!filter) { return false; }
		if(filter.length <= 0) { return false; }
		
		return true;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns the keywords that are not used as part of this items schema
	//
	// i.e. these are keywords that have just been 'added' manually
	
	getPlainKeywords : function()
	{
		return this.get('keywords');
	},	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// DATA INTERFACE
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// because one word may have several keywords - keywords are kept in arrays
	//
	// this function gives you the value of the first value in the array
	
	getKeywordValue: function(fieldName)
	{
		var keywordArray = this.keywordMap[fieldName];
		
		if(keywordArray==null) { return null; }
		
		return keywordArray[0];
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you the type of this item
	
	getType: function()
	{
		return this.get('item_type');
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns the data object from the record
	
	getData: function()
	{
		return this.data;
	},
	
	// tells you if this item can be opened in a view
	// or if it will trigger an edit if you open it 
	// (i.e. bottom level things that cannot contain children
	
	canOpen: function()
	{
		if(this.isOfType('folder'))
		{
			return true;
		}
		else
		{
			return false;
		}
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if this item inherits from the provided schema type
	
	isOfType: function(checkType)
	{
		if(checkType==this.getType()) { return true; }
		
		var schemaPath = this.getSchemaPath();
		
		for(var i=schemaPath.length-1; i>=0; i--)
		{
			var schemaType = schemaPath[i];
			
			if(schemaType==checkType)
			{
				return true;
			}
		}
		
		return false;
	},
	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// checks to see if the given property is a multiple
	// if so it returns an array of values
	// otherwise - it returns the first value
	
	getKeywordValue: function(fieldName)
	{
		var keywordArray = this.keywordMap[fieldName];
		
		if(keywordArray==null)
		{
			return null;
		}
		
		if(this.isFieldMultiple(fieldName))
		{
			var returnArray = [];
			
			for(var i=0; i<keywordArray.length; i++)
			{
				returnArray.push(keywordArray[i].value);
			}
			
			return returnArray;
		}
		else
		{
			var returnScalar = null;
			
			var firstElem = keywordArray[0];
			
			if(firstElem)
			{
				returnScalar = firstElem.value;
			}
			
			return returnScalar;
		}
	},
	
	
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// SCHEMA INTERFACE
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
		
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you the schema definition for this item
	
	getSchema: function()
	{
		return Webkit.Folders.Schema.getItemSchema(this.getType());
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// accessor for any property of this items schema
	
	getSchemaProperty : function(propName)
	{
		return Webkit.Folders.Schema.getItemTypeSchemaProperty(this.getType(), propName);
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if this type of item has precedence over the default sorting
	// useful for grouping folders before other items
	
	getSortPriority : function()
	{
		return Webkit.Folders.Schema.getItemTypeSortPriority(this.getType());
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns the name of the icon this item should use
	
	getIcon: function()
	{
		var schemaIcon = Webkit.Folders.Schema.getItemTypeIcon(this.getType());
		
		if(this.get('foldericon')!=null)
		{
			schemaIcon = this.get('foldericon');
		}
		
		return schemaIcon;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns the field definitions for this item as an array
	
	getFields: function()
	{
		return Webkit.Folders.Schema.getItemTypeFields(this.getType());
	},
	
	doeSchemaInheritFrom: function(checkType)
	{
		if(checkType==this.getType())
		{
			return true;
		}
		
		var path = this.getSchemaPath();
		
		for(var i=0; i<path.length; i++)
		{
			if(path[i]==checkType) { return true; }
		}
		
		return false;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns the inheritance path of this items schema definition
	
	getSchemaPath: function()
	{
		return Webkit.Folders.Schema.getItemTypePath(this.getType());
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns the field definitions for this item as a named map
	
	getFieldMap: function()
	{
		return Webkit.Folders.Schema.getItemTypeFieldMap(this.getType());
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns a single field definition for this item
	
	getField: function(fieldName)
	{
		var fieldMap = this.getFieldMap();
		
		return fieldMap[fieldName];
	},	
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns the title for ths type of item
	
	getTypeTitle: function()
	{
		return Webkit.Folders.Schema.getItemTypeTitle(this.getType());
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns the children or an empty array
	
	getChildren: function()
	{
		var ret = this.get('children');
		
		if(ret == null)
		{
			ret = [];
		}
		
		return ret;
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// returns the child filter for this item
	
	getChildFilter: function()
	{
		return Webkit.Folders.Schema.getItemTypeChildFilter(this.getType());
	},
	
	getParentFilter: function()
	{
		return Webkit.Folders.Schema.getItemTypeParentFilter(this.getType());
	},
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if a field represents a list of values or just one
	
	isFieldMultiple: function(fieldName)
	{
		var field = this.getField(fieldName);
		
		return field.multiple;	
	},
	
	isGhost: function()
	{
		return this.link_type=='ghost' ? true : false;
	},
	
	isSystemObject: function()
	{
		if(this.getSchemaProperty('access')=='system')
		{
			return true;	
		}
		else
		{
			return false;
		}	
	},
	
	shouldAutoExpand: function()
	{		
		// lets auto expand their disk
		if(this.isOfType('disk'))
		{
			return true;
		}
		else
		{
			return false;
		}
	},
	
	exists: function()
	{
		var testString = '' + this.id;
		
		return testString.match(/\d+\.\d+/);
	}
	
});

//////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////
// factory for creating a blank item

Webkit.Folders.Item.prototype.createBlank = function(itemType, itemId)
{	
	var item = new Webkit.Folders.Item({
    	id: 0,
    	name: '',
    	parent_id: itemId,
    	item_type: itemType,
    	keywords: []
    }, 0);
    
    return item;
};