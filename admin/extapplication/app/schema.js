////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// itemLoader.js
//
//
// responsible for loading item data from the server
// putting all of the server-communication in here allows 
// us generic access to item data from the actual application
//
//
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


Webkit.Folders.Schema = new function Schema()
{
	this.itemSchemas = {};
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// should be called once with a server-side JSON dump of the processed schemas

	this.setItemSchemas = function(passedSchemas)
	{
		this.itemSchemas = {};
		
		for(var schemaName in passedSchemas)
		{
			var schema = passedSchemas[schemaName];

			this.addSchema(schemaName, schema);
		}
	}

	this.addSchema = function(schemaName, schema)
	{
		if(schema.id==null)
		{
			schema.id = schemaName;
		}
		
		var fieldMap = {};

		for(var i=0; i<schema.fields.length; i++)
		{
			var field = schema.fields[i];
		
			fieldMap[field.name] = field;
		}
	
		schema.field_map = fieldMap;
	
		this.itemSchemas[schemaName] = schema;
	}
	
	this.getSystemModels = function()
	{
		var ret = [];
		
		for(var schemaName in this.itemSchemas)
		{
			var theSchema = this.itemSchemas[schemaName];
			
			// these schemas are private to the system
			if(theSchema.access=='private')
			{
				
			}
			else if(theSchema.access=='system')
			{
				
			}
			else
			{
				ret.push(theSchema);
			}
		}
		
		return ret;
	}
	
	this.getItemSchemas = function()
	{
		return this.itemSchemas;
	}

	this.getItemSchema = function(item_type)
	{
		var ret = this.itemSchemas[item_type];
		
		if(!ret) { Ext.Msg.alert('Schema not found', item_type + ' not found in the schema'); }
		
		return ret;
	}

	this.getDefaultSchema = function()
	{
		return this.getItemSchema('default');
	}
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you the name of an icon used for an item type

	this.getItemTypeIcon = function(item_type)
	{
		var itemSchema = this.getItemSchema(item_type);
		
		var iconName = itemSchema.icon;
	
		if(iconName == 'item_type')
		{
			iconName = item_type;
		}
    		
    	return iconName;
	}
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if an item type can be added by the user or is private to other items

	this.isItemTypePublic = function(item_type)
	{
		var itemSchema = this.getItemSchema(item_type);

		var access = itemSchema.access;
	
		if(access == 'private')
		{
			return false;
		}
		else
		{
			return true;
		}	
	}
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you a list of all types of item in the schema

	this.getAllItemTypes = function()
	{
		var schemas = this.getItemSchemas();
	
		var ret = [];
	
		for(var schemaName in schemas)
		{
			ret.push(schemaName);
		}
	
		return this.sortItemTypes(ret);
	}
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// takes a list of item types and sorts them based on their sort_priority

	this.sortItemTypes = function(typeArray)
	{
		var sortFunction = function(b, a)
		{
			var v1 = Webkit.Folders.Schema.getItemTypeSortPriority(a);
			var v2 = Webkit.Folders.Schema.getItemTypeSortPriority(b);
		
			return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);	
		}
	
		typeArray = typeArray.sort(sortFunction);
	
		return typeArray;
	}
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you a list of the field definitions for an item type

	this.getItemTypeFields = function(item_type)
	{
		var itemSchema = this.getItemSchema(item_type);
		
		return itemSchema.fields;
	}
	
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you the inheritance path of an item type
	// this lets you know in order - what schemas this one inherits from
	// useful for telling if an item inherits from a particular type (polymorphism)

	this.getItemTypePath = function(item_type)
	{
		var itemSchema = this.getItemSchema(item_type);
	
		return itemSchema.path;
	}

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you an indexed map of field definitions for an item type

	this.getItemTypeFieldMap = function(item_type)
	{
		var itemSchema = this.getItemSchema(item_type);

		return itemSchema.field_map;
	}

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you a list of item types allowed to be added to an item type

	this.getItemTypeParentFilter = function(item_type)
	{
		var itemSchema = this.getItemSchema(item_type);
	
		return itemSchema.parent_filter;
	}

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you a list of item types allowed to be added to an item type

	this.getItemTypeChildFilter = function(item_type)
	{
		var itemSchema = this.getItemSchema(item_type);
	
		return itemSchema.child_filter;
	}

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you a list of item types allowed to be added to an item type

	this.getItemTypeSchemaProperty = function(item_type, propName)
	{
		var itemSchema = this.getItemSchema(item_type);
	
		return itemSchema[propName];
	}

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// tells you if an item type has priority over other items

	this.getItemTypeSortPriority = function(item_type)
	{
		var itemSchema = this.getItemSchema(item_type);
	
		if(!itemSchema)
		{
			//alert(item_type);
		}
		
		return itemSchema.sort_priority;
	}

	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////
	// gives you the normalized title for a given itemType

	this.getItemTypeTitle = function(value)
	{
		value = value.replace(/_/g, ' ');
    		
	    return Ext.capitalizeWords(value);
	}
};