////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// Extras.js
//
// Helper class containing Ext plugins and the like
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
// DataView extension
//
// this allows you to dynamically change the template used by a dataview
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

Ext.override(Ext.DataView, {
    setTemplate: function(template)
    {
    	if(typeof template == "string")
    	{
            template = new Ext.XTemplate(template);
        }
        
        this.tpl = template;
    },
    
    setItemSelector: function(selector)
    {
    	this.itemSelector = selector;
    }
});


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// GridView extension
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

Ext.override(Ext.grid.GridView, {
    holdPosition: true,
    onLoad : function(){
        if (!this.holdPosition) this.scrollToTop();
        this.holdPosition = false
    }
});


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// Data Tools
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

Ext.capitalizeWords = function(value)
{
   	var parts = value.split(' ');
   	var ret = [];
    	
   	for(var i=0; i<parts.length; i++)
   	{
   		ret.push(Ext.capitalizeWord(parts[i]));
   	}
    	
   	return ret.join(' ');
};
    
Ext.capitalizeWord = function(value)
{
   	var parts = value.split('');	
   	var firstOne = parts.shift();
    	
   	return firstOne.toUpperCase() + parts.join('');
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// Dump Object
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

Ext.getAlertObjectSt = function(obj)
{
	var st = '';
	
	for(var prop in obj)
	{
		st += prop + " = " + obj[prop] + "\n";
	}
}
	
Ext.alertObject = function(obj)
{	
	alert(Ext.getAlertObjectSt(obj));
}

Ext.defaultSort = function(v1, v2)
{
	return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);	
}

Ext.getObjectDump = function(object, depth, max)
{
  depth = depth || 0;
  max = max || 2;

  if (depth > max)
    return false;

  var indent = "";
  for (var i = 0; i < depth; i++)
    indent += "      ";

  var output = "";  
  for (var key in object){
    output += "\n" + indent + key + ": ";
    switch (typeof object[key]){
      case "object": output += Ext.getObjectDump(object[key], depth + 1, max); break;
      case "function": output += "function"; break;
      default: output += object[key]; break;        
    }
  }
  return output;
};

Ext.quickMessage = function(){
    var msgCt;

    function createBox(t, s){
        return ['<div style="height:100px;"></div><div class="msg" style="padding:10px;">',
                '<b>', t, '</b><br/>', s,
                '</div>'].join('');
    }
    return {
        msg : function(title, format){
            if(!msgCt){
                msgCt = Ext.DomHelper.insertFirst(document.body, {id:'msg-div'}, true);
            }
            
            msgCt.alignTo(document, 't-t');
            var s = String.format.apply(String, Array.prototype.slice.call(arguments, 1));
            var m = Ext.DomHelper.append(msgCt, {html:createBox(title, s)}, true);

            m.slideIn('t').pause(2).ghost("t", {remove:true});
        }
    };
}();


Ext.lib.Event.resolveTextNode = Ext.isGecko ? function(node){
	if(!node){
		return;
	}
	var s = HTMLElement.prototype.toString.call(node);
	if(s == '[xpconnect wrapped native prototype]' || s == '[object XULElement]'){
		return;
	}
	return node.nodeType == 3 ? node.parentNode : node;
} : function(node){
	return node && node.nodeType == 3 ? node.parentNode : node;
};


Ext.override(Ext.form.Field, {
  afterRender : function() {            
        if(this.qtipText){

            var label = findLabel(this);
            if(label){
                Ext.QuickTips.register({
                    target:  label,
                    title: '',
                    text: this.qtipText,
                    enabled: true
                });
            }

			if(this.xtype=='checkbox')
			{
            	                Ext.QuickTips.register({
                target:  this.getEl(),
                title: '',
                text: this.qtipText,
                enabled: true
            });
        	}

          }
          Ext.form.Field.superclass.afterRender.call(this);
          this.initEvents();
		this.initValue();

  }
});

var findLabel = function(field) {
    
    var wrapDiv = null;
    var label = null
    //find form-element and label?
    wrapDiv = field.getEl().up('div.x-form-element');
    if(wrapDiv) 
    {
        label = wrapDiv.child('label');        
    }
    if(label) {
        return label;
    }
    
    //find form-item and label
    wrapDiv = field.getEl().up('div.x-form-item');
    if(wrapDiv) 
    {
        label = wrapDiv.child('label');        
    }
    if(label) {
        return label;
    }    
}  