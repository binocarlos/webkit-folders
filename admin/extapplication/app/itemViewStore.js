////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// ItemViewStore.js
//
//
// This is a custom store object for the itemView
// It allows the grouping of items such the ordering isn't mucked up
//
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

Webkit.Folders.ItemViewStore = function(config)
{
    Webkit.Folders.ItemViewStore.superclass.constructor.call(this, config);
    
    this.fields = new Ext.util.MixedCollection(false, function(field){
        return field.name;
    });
    
    var skeletonFields = [{
    	name:'name' },{
    	name:'item_type' },{
    	name:'parent_id' },{
    	name:'r' },{
    	name:'l' }];
    
    for(var i=0; i<skeletonFields.length; i++)
    {
    	this.fields.add(new Ext.data.Field(skeletonFields[i]))
    }
};


Ext.extend(Webkit.Folders.ItemViewStore, Ext.data.Store, {
	
	groupBy: null,
	
	
	
	setGroupBy: function(groupField)
	{
		this.groupBy = groupField;
		
		var sortState = this.getSortState();
		
		this.sort(sortState.field, sortState.direction);
	},
	
	resortData: function()
	{
		var sortState = this.getSortState();
		
		this.sortData(sortState.field, sortState.direction);
	},
	
	sortData: function(f, direction)
	{
		this.lastSortInfo = {
			f:f,
			d:direction
		};
		
       	direction = direction || 'ASC';
        	
       	var st = this.fields.get(f).sortType;
       	
       	// This puts everything in default order
        
       	var fn = this.comparator || function(r1, r2)
       	{       		
       		var r1P = Webkit.Folders.Schema.getItemTypeSortPriority(r1.data.item_type);
       		var r2P = Webkit.Folders.Schema.getItemTypeSortPriority(r2.data.item_type);
        		
       		var priorityCompare = r2P - r1P;
           		
       		if(priorityCompare==0)
       		{
       			var v1 = st(r1.data[f]), v2 = st(r2.data[f]);
           		return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);
       		}
       		else
       		{
       			return priorityCompare;
       		}
       	};
        
       	this.data.sort(direction, fn, this);
        	
       	if(this.snapshot && this.snapshot != this.data)
       	{
			this.snapshot.sort(direction, fn, this);
       	}
       	
       	// we have no group so we are done...
       	if(this.groupBy == null) { return; }
       	
       	var sortValues = [];
       	
       	for(var i=0; i<this.data.items.length; i++)
       	{
            sortValues.push({
            	key: this.data.keys[i],
            	value: this.data.items[i],
            	index: i
            });
        }
       	
		var groupArray = [];
		var groupMap = {};
		
		for(var i=0; i<sortValues.length; i++)
		{
			var sortValue = sortValues[i];
			
			var groupValue = sortValue.value.get(this.groupBy);
			
			if(groupValue==null)
			{
				groupValue = 'None';
			}
			
			var existingGroup = groupMap[groupValue];
			
			if(!existingGroup)
			{
				existingGroup = {
					group: groupValue,
					items: [] };
					
				groupArray.push(existingGroup);
				groupMap[groupValue] = existingGroup;
			}
			
			existingGroup.items.push(sortValue);
		}
		
		var groupSorter = null;
		
		if(this.groupBy == 'item_type')
		{
			groupSorter = function(a, b)
			{
				if(a.group=='None') { return -1; }
				else if(b.group=='None') { return -1; }
					
				var aCheck = Webkit.Folders.Schema.getItemTypeSortPriority(a.items[0].value.get('item_type'));
				var bCheck = Webkit.Folders.Schema.getItemTypeSortPriority(b.items[0].value.get('item_type'));
				
				return bCheck - aCheck;
			}
		}
		else
		{
			groupSorter = function(a, b)
			{
				var v1 = a.group;
				var v2 = b.group;
				
				return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);
			}
		}

		groupArray = groupArray.sort(groupSorter);
		
		var finalSorted = [];
		
		for(var i=0; i<groupArray.length; i++)
		{
			var group = groupArray[i];
			
			for(var j=0; j<group.items.length; j++)
			{
				finalSorted.push(group.items[j]);
			}
		}
		
		var finalItems = [];
		var finalKeys = [];
		
		for(var i=0; i<finalSorted.length; i++)
		{
			finalItems.push(finalSorted[i].value);
			finalKeys.push(finalSorted[i].key);
		}
		
		this.data.items = finalItems;
		this.data.keys = finalKeys;
		
		this.snapshot = this.data;
    }
    
});