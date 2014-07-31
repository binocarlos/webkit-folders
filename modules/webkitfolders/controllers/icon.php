<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Icon Controller
 *
 * Controller for the icon factory
 *
 *
 * icon URLs are in no particular order - the controller will look for the various arguments
 * like:
 *
 *	size (16, 24, 32, 48 or 256)
 *	icon_set (default or customset8)
 *	transformations (overlay_ghost, greyscale)
 *	icon name (document_blank)
 *
 *	any argument that starts with an underscore is NOT an icon name
 * 	any argument that dosn't start with an underscore IS an icon name
 *
 *	/app/icon/document
 *		this will return the default size (16), default set document.png with no transformations
 *
 *	/app/icon/_64/document OR /app/icon/document/_64.png
 *		both will return the document icon @ 64 pixels (note that the order does not matter)
 *		also note that .png is not neccessary (but dosn't matter if you include it)
 *
 *	/app/icon/_overlay_ghost/_overlay_shortcut/_greyscale/_64/_customset65/computer.png
 *		this will return the computer icon from the customset65 at size 64 with 2 overlays and in grey
 *
 *
 */
 
// ------------------------------------------------------------------------ 

class Icon_Controller extends Controller
{	
	/**
	* 	main icon gateway - accepts parameters in the form of the URL
	*
	*	this will accept the name of an icon, its size and any of the other
	*	params listed and will transform the original icon file accordingly
	*
	*/
	
	function __call($method, $arguments)
	{
		// lets prepare the cache
		$this->iconcache = new Iconcache();
		
		// this is the folder that contains the actual icon files
		// split into sets and then sizes (e.g. set4/256/iconname)
		$icons_folder = Kohana::config('webkitfolders.icon_folder').'/';
		$cache_folder = Kohana::config('webkitfolders.icon_cache_folder').'/';
		
		// if the icon you are after is not a png - then the extension you specify
		// must match the format you want
		// NOTE - at the moment, tranformations are only supported for PNG's
		$icon_extension = 'png';
		
		// lets get rid of the png on the end 
		// (i.e. we are not assuming that the last argument is the name of the actual icon)
		if(count($arguments)>0)
		{	
			$last_elem = array_pop($arguments);
			
			if(preg_match('/\.(\w{3})$/', $last_elem, $match))
			{
				$icon_extension = $match[1];
				
				$last_elem = preg_replace('/\.(\w{3})$/', '', $last_elem);
			}
			
			$arguments[] = $last_elem;
		}
		
		// the list of UN-ORDERED parameters sent to the controller
		// in other words we need to examine each parameter to determine its meaning
		array_unshift($arguments, $method);

		// so now we have the list of arguments - lets examine them to see what we
		// are looking for - the variables below are the defaults that will be used
		// if there is nothing specified (about.png is the default icon)
		$icon_name = 'document_plain';
		$icon_set = 'default';
		$icon_size = 16;
		$source_icon_size = $icon_size;
		$icon_transforms = array();
		
		// this will hold the parts of the url but will ommit rebuild
		$path_items = array();
		$icon_cache_path = null;
		$should_rebuild = false;
		
		foreach($arguments as $arg)
		{
			if($arg == '_rebuild')
			{
				$should_rebuild = true;
			}
			else
			{
				$path_items[] = $arg;	
			}
			
			// is the argument an instruction (i.e. starts with underscore)
			if(preg_match('/^_(.*+)$/', $arg, $match))
			{
				$arg = $match[1];
				
				// if the argument is all numbers, it is an icon size
				if(preg_match('/^\d+$/', $arg))
				{
					$icon_size = $arg;
					$source_icon_size = $arg;
					
					// lets see if the size is an official one or a rescale one
					if(!$this->iconcache->is_icon_size($arg))
					{
						$source_icon_size = $this->iconcache->get_closest_icon_size($arg);
						
						$icon_transforms[] = 'resize_'.$icon_size;
					}
				}
				// the argument is an icon set
				else if($this->iconcache->is_icon_set($arg))
				{
					$icon_set = $arg;	
				}
				// the argument is a transformation
				else
				{
					$icon_transforms[] = $arg;
				}
			}
			// nope - the argument is the actual name of the icon
			else
			{
				// if the icon name is index then we assume default
				// (although this does mean you cannot have an icon called index)
				if($arg!='index')
				{
					$icon_name = $arg;
				}
			}
		}
		
		// now we know what we are after - lets create the path to the physical (original) icon
		$icon_path = $icons_folder.$icon_set.'/'.$source_icon_size.'/'.$icon_name;
		
		if(!preg_match('/\.\w{3}$/', $icon_path))
		{
			$icon_path .= '.'.$icon_extension;
		}
		
		// so - there is no such icon and we need to do a 404
		if(!file_exists($icon_path))
		{
			Event::run('system.404');
			return;
		}
		
		// if there are no transformations then all we need to do is return the icon!
		if(count($icon_transforms)<=0)
		{
			$this->render_image_to_browser($icon_path);

			return;
		}
		
		// lets see if there is a cache version of the icon - in which case we will return that one
		// (providing rebuild has not been specified
		$icon_cache_folder = implode('/', $path_items);
		
		$full_cache_folder_path = $cache_folder.$icon_cache_folder;
		$full_cache_file_path = $full_cache_folder_path.'/icon.png';
		
		if(file_exists($full_cache_file_path))
		{
			if($should_rebuild)
			{
				unlink($full_cache_file_path);
			}
			else
			{
				$this->render_image_to_browser($full_cache_file_path);

				return;
			}
		}
		
		// the properties needed by the icon cache
		$icon_props = array(
			'set' => $icon_set,
			'cache_folder' => $full_cache_folder_path,
			'source_size' => $source_icon_size,
			'size' => $icon_size,
			'name' => $icon_name
		);
		
		// so - lets give the cache everything it needs
		// this includes the path to the original icon, the calculated id and any transformations
		// that need to happen
		//
		// it will look after the caching internally so you don't need to worry from this point on
		$cache_image_path = $this->iconcache->make_icon($icon_path, $icon_props, $icon_transforms);

		// now we have the cache path - lets render it to the browser!!!
		$this->render_image_to_browser($cache_image_path);
	}
	
	// outputs the contents of a file path to the browser
	function render_image_to_browser($image_path)
	{
		header('Cache-Control: max-age=7200, must-revalidate');
		header("Content-Type: image/png");
		header("Content-Length: " . filesize($image_path));
		
		$fp = fopen($image_path, 'rb');

		// dump the picture and stop the script
		fpassthru($fp);

//		$image_obj = new Image($image_path);
//		$image_obj->render();
	}
	
	function run_icon_display()
	{
		$icons_folder = Kohana::config('webkitfolders.icon_folder').'/default/48';
		
		$col_count = 0;
		
		$html = <<<EOT
<script>
	var currentName = null;
	
	function makeIcon(name)
	{
		if(name!=null)
		{
			currentName = name;
		}
		
		var urlParts = [];
		
		var checkBoxArray = ['flip', 'flop', 'blur', 'grayscale'];
		var overlayArray = ['ghost', 'shortcut', 'new'];
		
		var customOverlays = {
			'selection':'selection:1:t,l',
			'add':'add:0.4:b,l',
			'left':'arrow_left_blue:0.4:c,l',
			'breakpoint':'breakpoint:0.4:b,l',
			'play':'bullet_triangle_glass_green:0.4:b,r',
			'warning':'sign_warning:0.4:b,r',
			'delete':'delete:0.4:c,r'
		};
		
		for(var i=0; i<checkBoxArray.length; i++)
		{
			var transformName = checkBoxArray[i];
			
			if(document.getElementById(transformName).checked)
			{
				urlParts.push('_' + transformName);	
			}
		}
		
		for(var i=0; i<overlayArray.length; i++)
		{
			var overlayName = overlayArray[i];
			
			if(document.getElementById(overlayName).checked)
			{
				urlParts.push('_overlay_' + overlayName);	
			}
		}
		
		for(var customOverlay in customOverlays)
		{
			if(document.getElementById('preset_' + customOverlay).checked)
			{
				urlParts.push('_overlay_' + customOverlays[customOverlay]);	
			}
		}
		
		if(document.getElementById('tint').checked)
		{
			var tintColor = document.getElementById('tint_color').value;
			
			urlParts.push('_tint_' + tintColor);	
		}
		
		if(document.getElementById('rotate').checked)
		{
			var rotateDegrees = document.getElementById('rotate_degrees').value;
			
			urlParts.push('_rotate_' + rotateDegrees);	
		}
		
		var customOverlay = document.getElementById('custom_overlay').value;
		
		if(customOverlay!='')
		{
			var cSize = document.getElementById('custom_overlay_size').value;
			var cPos = document.getElementById('custom_overlay_position').value;
			
			urlParts.push('_overlay_' + customOverlay + ':' + cSize + ':' + cPos);
		}
		
		urlParts.push(currentName + '.png');
		
		var size = document.getElementById('sz').value;
		
		var rebuild = '';
		
		if(document.getElementById('rebuild').checked)
		{
			rebuild = '_rebuild/';
		}
		
		var finalURL = '/app/icon/' + rebuild + '_' + size + '/' + urlParts.join('/');
		
		document.getElementById('final_icon_url').value = finalURL;
		
		loadIcon();
	}
	
	function loadIcon()
	{
		finalURL = document.getElementById('final_icon_url').value;
		
		document.getElementById('finalImage').innerHTML = '<table border=0 cellpadding=0 cellspacing=0><tr><td bgcolor=#cccccc><img src="' + finalURL + '"></td></tr></table>';
	}
	
	var helpMode = false;
	
	function toggleHelp()
	{
		helpMode = !helpMode;
		
		var helpDisplay = 'none';
		var helpTitle = 'Show Help';
		
		if(helpMode)
		{
			helpDisplay = 'block';
			helpTitle = 'Hide Help';	
		}
		
		document.getElementById('helpSpan').style.display = helpDisplay;
		document.getElementById('helpTitle').innerHTML = helpTitle;
	}
	
</script>	
<span id="finalImage"></span><br>
<span id="finalURL"></span>
<hr>	
flip: <input type="checkbox" id="flip"><br>
flop: <input type="checkbox" id="flop"><br>
blur: <input type="checkbox" id="blur"><br>
greyscale: <input type="checkbox" id="grayscale"><br>
tint: <input type="checkbox" id="tint">  <input type="text" id="tint_color" value="ff0000"><br>
rotate: <input type="checkbox" id="rotate">  <input type="text" id="rotate_degrees" value="90"><br><br>
hand made overlays:<br>
<input type="checkbox" id="ghost"> Ghost<br>
<input type="checkbox" id="shortcut"> Shortcut<br>
<input type="checkbox" id="new"> New<br><br>

custom overlays: <a href="javascript:toggleHelp();"><span id="helpTitle">Show Help</span></a><br>
<span id="helpSpan" style="display:none;">
A custom overlay takes the following form:<br><br>
icon_name:size:position (e.g. selection:1:t,l - alarmclock:0.4:b,r)<br><br>
Positioning:<br>

c = center - can be used for X and Y<br>
l = left, r = right - only for X<br>
t = top, b = bottom - only for Y<br><br>

If ambigious - it assumes X then Y - e.g. c,c+5 is center X and offset 5 from center Y<br><br>

c,t = center X, top Y<br>
b,l = bottomY, left X<br>
b-10,r-5 = 10 off bottom, 5 off right<br><br>

size is percentage size of the original icon (0 -> 0.5 -> 1)<br><br>
</span>

any icon: <input type="text" id="custom_overlay" value=""> - size: <input type="text" id="custom_overlay_size" value="0.4" size="3"> - position: <input type="text" id="custom_overlay_position" value="b,l" size="3"><br>
presets:<br>

selection: <input type="checkbox" id="preset_selection"><br>
add: <input type="checkbox" id="preset_add"><br>
delete: <input type="checkbox" id="preset_delete"><br>
warning: <input type="checkbox" id="preset_warning"><br>
play: <input type="checkbox" id="preset_play"><br>
left: <input type="checkbox" id="preset_left"><br>
breakpoint: <input type="checkbox" id="preset_breakpoint"><br>
<br>


size: <input type="text" id="sz" value="48"><br><br>

rebuild: <input type="checkbox" id="rebuild"><br><br>

<input type="button" onClick="makeIcon();" value="remake url and icon"><br>


<input type="text" id="final_icon_url" style="width:400px;"><br>
<input type="button" onClick="loadIcon();" value="make icon from url">
<hr>		
<table border=0 cellpadding=5 cellspacing=0>
<tr>
EOT;
		
		//$limit = null;
		$limit = 100;
		$current = 0;
		
		foreach (glob($icons_folder.'/*.png') as $file)
		{
			$current++;
			
			if(($limit)&&($current>$limit))
			{
				break;	
			}
			
			if(preg_match('/(\w+)\.png$/', $file, $match))
			{
				$icon_name = $match[1];
				
				$html .= <<<EOT
	<td><a href="#" onClick="makeIcon('$icon_name');"><img src="/app/icon/_32/$icon_name.png" border=0></a></td>
EOT;

				$col_count++;
				
				if($col_count>24)
				{
					$col_count = 0;
					
					$html .= <<<EOT
</tr>
<tr>					
EOT;
				}
			}
		}
		
		$html .= <<<EOT
</tr>
</table>
EOT;

		echo $html;
	}
}
?>