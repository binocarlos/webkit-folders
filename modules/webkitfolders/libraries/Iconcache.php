<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Iconcache Library
 *
 * Responsible for caching the icons that are automatically created
 *
 * the cache is a lightweight class - it accepts an id - which is remapped into a folder path
 * by remapping underscores into slashes - and stores icon.png in that folder
 *
 * so default_48_document will become default/48/document/icon.png
 *
 */
 
// ------------------------------------------------------------------------ 

class Iconcache
{	
	// the different sizes of icon allowed - this array is used to determine when
	// we are being asked for a size
	private $icon_sizes = array(
		'16' => true,
		'24' => true,
		'32' => true,
		'48' => true,
		'256' => true
	);
	
	// the different icon sets we have - this is used to see if an argument is 
	// to specify a different set
	// at present we only work with default but as new sets are added this array needs
	// to be populated (either manually or by scanning folders)
	private $icon_sets = array(
		'default' => true,
		'webpics' => true
	);
	
	// these are tranformations that act as instructions rather than should be saved in the cache
	// for example rebuild means make the icon again - this should not be part of the filename
	private $non_cached_transformations = array(
		'rebuild' => true
	);
	
	// adds an icon to the cache - applies all of the neccessary transformations to the object
	// before it adds the image to the cache
	public function make_icon($original_icon_path, $icon_info, $transforms)
	{
		// we need to apply all of the overlays first so we will split the overlays
		// from other transformations so they overlays can be done first
		$nonoverlays = array();
		$overlays = array();

		foreach($transforms as $transform)
		{
			if(preg_match('/^overlay_(.*+)$/', $transform, $match))
			{
				$overlays[$match[1]] = true;
			}
			else
			{
				$nonoverlays[$transform] = true;
			}
		}

		/////////////////////////////////////////////////////////////////////////////////////
		/////////////////////////////////////////////////////////////////////////////////////
		// Time to do the actual building of the icon
		
		// now lets get the cache path which is based on the id
		$icon_cache_folder = $icon_info['cache_folder'];
		$icon_cache_path = $icon_cache_folder.'/icon.png';
		
		if(!is_file($icon_cache_folder))
		{
			// lets make sure the directory exists in the cache
			mkdir($icon_cache_folder, 0777, true);
		}
		
		// New ImageMagick Method
		$icon_object = NewMagickWand();
		MagickReadImage($icon_object, $original_icon_path);
		
		// these are the transformations that should happen before we do the overlays
		if($nonoverlays['grayscale'])
		{
			MagickSetImageType($icon_object, MW_GrayscaleMatteType);
		}
		
		if($nonoverlays['flip'])
		{
			MagickFlipImage($icon_object);
		}
		
		if($nonoverlays['flop'])
		{
			MagickFlopImage($icon_object);
		}
		
		if($nonoverlays['blur'])
		{
			MagickBlurImage($icon_object, 3, 3);
		}
		
		// So - some transformations require us to use the command-line (like tint)
		// we are splitting the whole into 2 parts - i.e. the stuff we do first (like above)
		// then the command line stuff
		// then the stuff we do after the command line
		
		$command_line_transformations = array();
		
		// this is used in case we are actually after a custom size of icon
		// this means that last thing we should do is resize it to the final size
		$final_icon_size = null;

		// so - now we will loop because someof the tranformations will include arguments
		// and so will not have a fixed name
		foreach($nonoverlays as $tranformation => $ignore)
		{
			if(preg_match('/^tint_(\w+)$/', $tranformation, $match))
			{
				$command_line_transformations[$tranformation] = $ignore;
			}
			
			if(preg_match('/^color_(\w+)_(\w+)$/', $tranformation, $match))
			{
				$command_line_transformations[$tranformation] = $ignore;
			}
			
			if(preg_match('/^rotate_(\d+)$/', $tranformation, $match))
			{
				$degrees = $match[1];				
				
				MagickRotateImage($icon_object, $this->get_transparent_color(), $degrees);
			}
			
			if(preg_match('/^resize_(\d+)$/', $tranformation, $match))
			{
				// this will trigger a resize as the last action
				$final_icon_size = $match[1];
			}
		}
		
		// now we write out the image because everything else is done on the command line
		MagickWriteImage($icon_object, $icon_cache_path);
		
		ClearMagickWand($icon_object);
		DestroyMagickWand($icon_object);
		
		// time for the command line tranformations	
		if(count($command_line_transformations)>0)
		{
			foreach($command_line_transformations as $tranformation => $ignore)
			{
				if(preg_match('/^tint_(\w+)$/', $tranformation, $match))
				{
					$color = $match[1];
					
					$this->tint_icon($icon_cache_path, $color);
				}
				
				if(preg_match('/^color_(\w+)_(\w+)$/', $tranformation, $match))
				{
					$blackcolor = $match[1];
					$whitecolor = $match[2];
			
					$this->color_icon($icon_cache_path, $blackcolor, $whitecolor);
				}
			}
		}
		
		// the image itself has now been transformed and its time to overlay things on top of it
		// obviously we don't want the transformations to occur to the overlays - this is why
		// they happen at the end
		
		// this is the folder that contains the various size of overlays
		$overlay_folder = MODPATH.'webkitfolders/media/icon_overlays/';
		$icons_folder = Kohana::config('webkitfolders.icon_folder').'/';

		// this should always be png (until they invent 24-bit gifs or transparent jpegs : )
		$overlay_extension = 'png';
	
		// now lets loop each of the overlays and apply them
		foreach($overlays as $overlay => $ignore)
		{
			// with the overlays - we will first attempt to see if there is a customized overlay
			// that already exists in the overlays folder - i.e. overlays that have been prepared by hand
			// if the custom overlay does not exist - we will assume that they want to overlay another icon

			// this points to the filename of the PNG overlay - lets check if it exists
			$overlay_filename = $overlay_folder.$icon_info['size'].'/'.$overlay.'.'.$overlay_extension;
			
			if(file_exists($overlay_filename))
			{
				$this->overlay_icon($icon_cache_path, $overlay_filename, 0, 0);
			}
			else
			{
				// the overlay does not exist! so we must see if there is an icon that we can use
				// for the overlay instead
				
				// so the overlay information is encoded into a colon delimted string
				// lets split it so we know what we are after and apply the defaults if no info present
				$overlay_info = explode(':', $overlay);
				
				$overlay = $overlay_info[0];
				$overlay_size = $overlay_info[1];
				$overlay_position = $overlay_info[2];
				
				if(!$overlay_size)
				{
					$overlay_size = 0.4;
				}
				
				if(!$overlay_position)
				{
					$overlay_position = 'b,l';
				}
				
				// we dont want an overlay bigger than the icon!
				if($overlay_size>1)
				{
					$overlay_size = 1;
				}

				// now we have the size as a percentage - lets work out the pixel size we
				// want the overlay to be
				$desired_overlay_size = round($icon_info['source_size'] * $overlay_size);
				
				// now lets get the size of icon that we will work with to create the overlay
				// this will always be the same size or bigger than the desired size
				$overlay_icon_size = $this->get_closest_icon_size($desired_overlay_size);
				
				// now we can grab the actual file
				$overlay_filename = $icons_folder.$icon_info['set'].'/'.$overlay_icon_size.'/'.$overlay.'.'.$overlay_extension;
				
				$temp_overlay_filename = $this->getTemporaryCacheName();
				
				// do we need to rescale the overlay?
				// in which case the position comes into play
				if($desired_overlay_size != $overlay_icon_size)
				{
					$this->resize_icon($overlay_filename, $desired_overlay_size, $temp_overlay_filename);
					
					$overlay_filename = $temp_overlay_filename;
				}
				
				// what is the size of the icon we are working with?
				$icon_size = $icon_info['source_size'];
				
				// the initial starting point for the overlay position (top left)
				// if the overlay is the same size as the icon - this default is what we want
				$overlay_x = 0;
				$overlay_y = 0;
				
				// if the overlay a different size to the icon?
				// in which case we will need to position the overlay depending on the position config
				if($icon_size != $desired_overlay_size)
				{
					$overlay_position = $this->get_overlay_xy($icon_size, $desired_overlay_size, $overlay_position);

					$overlay_x = $overlay_position['x'];
					$overlay_y = $overlay_position['y'];
				}
				
				$this->overlay_icon($icon_cache_path, $overlay_filename, $overlay_x, $overlay_y);

				if(is_file($temp_overlay_filename))
				{
					unlink($temp_overlay_filename);	
				}
			}
		}
		
		// finally lets check if we have to resize the icon we are working with to the final desired size
		if($final_icon_size)
		{
			$this->resize_icon($icon_cache_path, $final_icon_size);
		}
		
		// finally return the path to the cached icon
		return $icon_cache_path;
	}
	
	private function getTemporaryCacheName()
	{
		$cache_folder = Kohana::config('webkitfolders.icon_cache_folder');
		
		$file_name = statictools::rand_str(16).'.png';
		
		return $cache_folder.'/'.$file_name;
	}
	
	// calls the command line function to overlay one image on another @ x and y position
	private function overlay_icon($icon_path, $overlay_path, $x, $y)
	{
		$icon_path = escapeshellcmd($icon_path);
		$overlay_path = escapeshellcmd($overlay_path);
		$x = escapeshellcmd($x);
		$y = escapeshellcmd($y);
		
		$composite_args = "-geometry +{$x}+{$y}";
		
		$composite_path = $this->get_composite_path();
		
		exec("$composite_path $composite_args $overlay_path $icon_path $icon_path");
	}
	
	// calls the command line function to tint and icon with the given color
	private function tint_icon($icon_path, $color)
	{
		$color = escapeshellcmd($color);
		$icon_path = escapeshellcmd($icon_path);
		
		$tint_args = "-colorspace gray -fill \"#{$color}ff\" -sigmoidal-contrast 5,50% -tint 100";
		
		$convert_path = $this->get_convert_path();
		
		exec("$convert_path $icon_path $tint_args $icon_path");
	}

	// calls the command line function to color an icon
	private function color_icon($icon_path, $blackcolor, $whitecolor)
	{
		$blackcolor = escapeshellcmd($blackcolor);
		$whitecolor = escapeshellcmd($whitecolor);
		$icon_path = escapeshellcmd($icon_path);
		
		$color_args = "+level-colors \"#{$blackcolor}\",\"#{$whitecolor}\"";
		
		$convert_path = $this->get_convert_path();
		
//echo "$convert_path $icon_path $color_args $icon_path";
//exit;
		exec("$convert_path $icon_path $color_args $icon_path");
	}
	
	// calls the command line function to resize the given icon to a size#
	// outputs either to original or optional alternative (to make a copy)
	private function resize_icon($image_path, $size, $target_path = null)
	{
		if(!$target_path)
		{
			$target_path = $image_path;
		}
		
		$image_path = escapeshellcmd($image_path);
		$size = escapeshellcmd($size);
		$target_path = escapeshellcmd($target_path);
		
		$resize_args = "-resize {$size}x{$size}";
		$convert_path = $this->get_convert_path();
					
		exec("$convert_path $image_path $resize_args $target_path");
	}
	
	// gives you the path to the image magick convert executable
	private function get_convert_path()
	{
		$path = Kohana::config('webkitfolders.image_magick_bin');
		
		return $path.'/convert';
	}
	
	// gives you the path to the image magick composite executable
	private function get_composite_path()
	{
		$path = Kohana::config('webkitfolders.image_magick_bin');
		
		return $path.'/composite';
	}
	
	private function get_overlay_xy($icon_size, $overlay_size, $position)
	{
		$ret = array(
			'x' => 0,
			'y' => 0
		);
		
		$xpos = null;
		$ypos = null;
		
		$center_point = ($icon_size/2) - ($overlay_size/2);
		
		// so lets split the position into the 2 parts (we don't know which is x or y yet though)
		$pos_parts = explode(',', $position);
		
		foreach($pos_parts as $part)
		{
			// lets get rid of any extraneous stuff
			$part = preg_replace('/[^lrtbc\-\+\d]/', '', $part);
			
			// now lets match the anchor and any adjustment
			if(preg_match('/^(\w)(.*)$/', $part, $match))
			{
				$anchor = $match[1];	
				$adjustment = $match[2];
				
				if($anchor == 'l')
				{
					// if xpos is already set then its cos the center point is ambigous
					// left can only be x so the center already encountered was actually meant for y
					// same below
					if($xpos)
					{
						$ypos = $xpos;	
					}
					
					eval('$xpos = 0'.$adjustment.';');
				}
				else if($anchor == 'r')
				{
					if($xpos)
					{
						$ypos = $xpos;	
					}
					
					eval('$xpos = ($icon_size - $overlay_size)'.$adjustment.';');
				}
				else if($anchor == 't')
				{
					eval('$ypos = 0'.$adjustment.';');
				}
				else if($anchor == 'b')
				{
					eval('$ypos = ($icon_size - $overlay_size)'.$adjustment.';');
				}
				else if($anchor == 'c')
				{
					// the center anchor could mean either x or y
					// x takes precedence so if it is not set - we will use it for x
					if(!$xpos)
					{
						eval('$xpos = $center_point'.$adjustment.';');
					}
					else
					{
						eval('$ypos = $center_point'.$adjustment.';');
					}
				}
			}
		}
		
		if($xpos<0)
		{
			$xpos = $icon_size + $xpos;
		}
		
		if($xpos > ($icon_size - $overlay_size))
		{
			$xpos = $xpos - ($icon_size - $overlay_size);
		}
		
		if($ypos<0)
		{
			$ypos = $icon_size + $ypos;
		}
		
		if($ypos > ($icon_size - $overlay_size))
		{
			$ypos = $ypos - ($icon_size - $overlay_size);
		}
		
		if($xpos)
		{
			$ret['x'] = $xpos;
		}
		
		if($ypos)
		{
			$ret['y'] = $ypos;
		}

		return $ret;
	}
	
	private function get_transparent_color()
	{
		//MW_TransparentOpacity
		
		return NewPixelWand("transparent");
	}
	
	private function get_color($hex)
	{
		$color = NewPixelWand();
		PixelSetColor($color, '#'.$hex);
		return $color;
	}
	
	// gives you the icon size that is closest to the given size
	// will always return an icon BIGGER than what you are asking for (unless you ask for too big a size)
	// this is so you can resize but never upscale
	public function get_closest_icon_size($size)
	{
		$closest_size = 0;
		$smallest_gap = 0;
		
		// lets see if we have an icon size that is exactly what is being asked for
		if($this->icon_sizes[$size])
		{
			return $size;
		}
		
		foreach($this->icon_sizes as $iconsize => $ignore)
		{
			// if the size is smaller then it cant be used
			if($iconsize < $size)
			{
				continue;
			}
			
			$current_gap = $iconsize - $size;
			
			if(($current_gap < $smallest_gap) || ($smallest_gap <= 0))
			{
				$smallest_gap = $current_gap;
				$closest_size = $iconsize;
			}
		}
		
		return $closest_size;
	}
	
	// tells you if the given argument is an icon size
	public function is_icon_size($arg)
	{
		return $this->icon_sizes[$arg];	
	}
	
	// tells you if the given argument is an icon set
	public function is_icon_set($arg)
	{
		return $this->icon_sets[$arg];
	}
}
?>