////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//
// iconFactory.js
//
//
// generic access for icons - requests icon from the server side icon factory
//
//
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


Webkit.Folders.IconFactory = new function IconFactory()
{	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	// Icon Config
	
	// internal id counter for new CSS class creation	
	this.cssID = 0;
	
	// map of destinations and sizes (to save hardcoding sizes everywhere)
	this.destinationSizes = {
		tree:16,
		item_view:48,
		item_view_icons:48,
		item_view_details:16,
		top_level_menu:24
	};
	
	this.scaleSizes = {
		small:16,
		medium:24,
		large:32
	};
	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	// Generic Icon Methods
	
	// does the actual building of the icon URI
	// everything should be channeled through here in order to generate an icon
	// the config contains:
	//
	//		name - the base name of the icon you are after (defaults to document)
	//
	//		set - the set of icons to look in (defaults to default)
	//
	//		size - size in pixels you want the icon
	//			can be any size - the standard sizes are 16, 24, 32, 48, 256
	//			anything that is not that size will be scaled down from the next biggest standard size
	//
	//		destination - what is this icon for
	//			tree or item_view
	//			this is the preferable method to ask for a size - we want to try and avoid
	//			hardcoding sizes in and around the application
	//
	//		status - any modes this icon should have
	//			new, ghost
	//			this is where you dictate overlays and/or other effects to denote icon status
	//			you should never ask for the status by trying to configure the actual properties
	//			that is the job of this function - rather add another keyword (like new or ghost)
	//			and this method will translate that onto config properties
	
	this.makeIconURI = function(config)
	{
		if(!config) { config = {}; }
		
		// here are the defaults - (i.e. if you don't ask for anything)
		var iconName = config.name || 'document';
		
		// if we have a destination - lets map it onto a size
		if(config.destination)
		{
			config.size = this.destinationSizes[config.destination];
		}
		
		if(config.scale)
		{
			config.size = this.scaleSizes[config.scale];
		}
				
		var instructions = config.instructions || [];
		
		if(config.set)
		{
			instructions.push(config.set);
		}
		
		if(config.size)
		{
			instructions.push(config.size);
		}
		
		if(config.link_type == 'ghost')
		{
			instructions.push('grayscale');
			instructions.push('overlay_ghost');
		}
		
		if (iconName == 'step')
		{
			instructions.push('tint_b2080b');
		}
		
		
		
		// here is where additional instructions will go
		
		// now we make a string out of the instructions
		var instructionString = instructions.join('/_');
		
		var iconURI = Webkit.Folders.AdminApp.prototype.iconURI + '/_' + instructionString + '/' + iconName + '.png';
		
		return iconURI;
	}
	
	// generates a new CSS style for the given iconURI
	// each class will be unique and this will return the className
	// this does not care about items - to make an item css class use the item methods below
	this.makeIconClass = function(config)
	{
		var iconURI = this.makeIconURI(config);
		
		this.cssID++;
		
		var cssText = ""
		+	".icon-class-" + this.cssID + " { "
		+	"background-image: url('" + iconURI + "')!important;"
		+	"background-repeat: no-repeat;"
		+	"}";
					
		Ext.util.CSS.createStyleSheet(cssText, 'icon-sheet-' + this.cssID);
		
		return 'icon-class-' + this.cssID;
	}	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	// Item Icon Methods
	
	// processes the given config and item into a final icon config
	this.getItemIconConfig = function(item, config)
	{
		if(!config) { config = {}; }
		
		config.instructions = [];
		config.name = item.getIcon();
		config.link_type = item.get('link_type');
		
		if (item.name.toLowerCase().indexOf('maps')==0)
		{
			config.instructions.push('overlay_compass:0.5:b,r');
		}
		if (item.name.toLowerCase().indexOf('shopping carts')==0)
		{
			config.instructions.push('overlay_shopping_cart_empty:0.5:b,r');
		}
		if (item.name.toLowerCase().indexOf('forms')==0)
		{
			config.instructions.push('overlay_form_blue:0.5:b,r');
		}
		if (item.name.toLowerCase().indexOf('music')==0)
		{
			config.instructions.push('overlay_document_music:0.5:b,r');
		}
		if (item.name.toLowerCase().indexOf('news')==0)
		{
			config.instructions.push('overlay_newspaper:0.5:b,r');
		}
		if (item.name.toLowerCase().indexOf('photos')==0)
		{
			config.instructions.push('overlay_photo_landscape:0.5:b,r');
		}
		if (item.name.toLowerCase().indexOf('videos')==0)
		{
			config.instructions.push('overlay_videotape:0.5:b,r');
		}
		if (item.name.toLowerCase().indexOf('tracking codes')==0)
		{
			config.instructions.push('overlay_chart_area:0.5:b,r');
		}
		if (item.name.toLowerCase().indexOf('rss')==0)
		{
			config.instructions.push('overlay_megaphone:0.5:b,r');
		}
												
		return config;
	}
	
	// this first creates the Icon URI for the item and config
	// and then creates a new CSS style for it
	this.makeItemIconClass = function(item, config)
	{
		var config = this.getItemIconConfig(item, config);
		
		return this.makeIconClass(config);
	}
	
	// so - this is what you use to make an icon for an actual item
	// it will interrogate the item in order to create a config that gets passed to the factory method above
	this.makeItemIconURI = function(item, config)
	{
		var config = this.getItemIconConfig(item, config);
		
		return this.makeIconURI(config);
	}
	
	
	
};