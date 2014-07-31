<?php defined('SYSPATH') OR die('No direct access allowed.');

class Thumbnailcache
{
	private $enable_cache = true;
	
	private $quality_settings = array(
		'jpg' => 90,
		'gif' => 0,
		'png' => 0
	);
	
	private $anchor_settings = array(
		"NorthWest",
		"North",
		"NorthEast",
		"West",
		"Center",
		"East",
		"SouthWest",
		"South",
		"SouthEast"
	);
	
	public static $button_tags = array(
		'button' => 'button',
		'text' => 'text',
		'color' => 'color',
		'bgcolor' => 'bgcolor',
		'outline' => 'outline',
		'font' => 'font',
		'pointsize' => 'pointsize',
		'width' => 'width',
		'height' => 'height',
		'size' => 'size',
		'anchor' => 'anchor',
		'shadow' => 'shadow'
	);
	
	public static $video_tags = array(
		'thumbnail_size' => 'thumbnail_size',
		'thumbnail_start' => 'thumbnail_start',
		'thumbnail_count' => 'thumbnail_count'
	);
	
	public static $argument_tags = array(
		'size' => 'size',
		'width' => 'width',
		'height' => 'height',
		'fit' => 'fit',
		'thumbnail' => 'size',
		'square' => 'square',
		'squareanchor' => 'squareanchor',
		'crop' => 'crop',
		'cropanchor' => 'cropanchor',
		'rectangle' => 'rectangle',
		'rectangleanchor' => 'rectangleanchor',
 		'reflect' => 'reflect',
 		'rotate' => 'rotate',
 		'background' => 'background',
 		'polaroid' => 'polaroid',
 		'polaroid2' => 'polaroid2',
 		'polaroidstack' => 'polaroidstack',
 		'contentscale' => 'contentscale',
 		'auto' => 'auto',
 		'sharpen' => 'sharpen',
 		'level' => 'level',
 		'negative' => 'negative',
		'overlay' => 'overlay',
 		'overlayposition' => 'overlayposition',
 		'convertto' => 'convertto',
 		'border' => 'border',
 		'colorspace' => 'colorspace',
 		'flop' => 'flop',
 		'tile' => 'tile'
 	);
 	
 	// if any of these arguments are in the image url - there will be no 404 check
 	// this is so the handler here can look after it
 	public static $ignore_file_exists_tags = array(
 		'tile' => 'tile'
 	);
		
 	public static function add_size_fields_to_upload_data($upload_data)
 	{
 		$full_path = statictools::get_full_file_path($upload_data['relative_folder'], $upload_data['file_name']);
 		
 		$dimensions = Thumbnailcache::get_file_dimensions($full_path, $upload_data['file_type']);
 		
 		$upload_data['width'] = $dimensions['width'];
 		$upload_data['height'] = $dimensions['height'];
 		
 		return $upload_data;
 	}
 	
 	public static function get_file_dimensions($filepath, $filetype)
 	{		
 		$dimensions = array();
 		
 		// its an image
 		if(preg_match('/^image/', $filetype))
 		{	
 			$imageinfo = getimagesize($filepath);
 			
 			$dimensions['width'] = $imageinfo[0];
 			$dimensions['height'] = $imageinfo[1];
 		}
 		// it a video
 		else if(preg_match('/^video/', $filetype))
 		{
 			$cache = new Thumbnailcache();
 			
 			$video_info = $cache->get_video_info($filepath);
 			
 			$dimensions['width'] = $video_info['width'];
 			$dimensions['height'] = $video_info['height'];
 		}
 		
 		return $dimensions;
 	}
 	
 	public function process_button($uri, $arguments)
 	{
 		$ext = '';
 		
 		$cache_folder = Kohana::config('webkitfolders.thumbnail_cache_folder').$_SERVER['DOCUMENT_ROOT'];
		
		$path_parts = explode('/', $original_image_path);
 		
 		if(preg_match('/\.(\w+)$/', $uri, $match))
		{
			$ext = strtolower($match[1]);
		}
		
		$quality = $this->quality_settings[$ext];
		
		foreach(Thumbnailcache::$button_tags as $tag => $realtag)
		{
			if(!empty($arguments[$tag]))
			{
				$path_parts[] = $realtag.'_'.urlencode($arguments[$tag]);
			}
		}
		
		$folder = implode('/', $path_parts);
		
		$cache_folder = preg_replace('/\/$/', '', $cache_folder);
		
		$full_cache_folder = $cache_folder.$folder;
		$destination_filename = $full_cache_folder.'/button.png';
		
		if($this->enable_cache)
		{
			// does this image already exist in the cache?	
			if(is_file($destination_filename))
			{
				return $destination_filename;
			}
		}
		
		if(!is_file($full_cache_folder))
		{
			// lets make sure the directory exists in the cache
			mkdir($full_cache_folder, 0777, true);
		}
	
		$escaped_destination_folder = escapeshellcmd($full_cache_folder);
		$escaped_destination_filename = escapeshellcmd($destination_filename);
		  
       	$font_folder = Kohana::config('webkitfolders.font_cache_folder');      	
       	
      	$button_label = str_replace("&#039;", "'", $arguments['text']);
      	$button_label = str_replace("\"", "\\\"", $button_label);

       	$args = array();
       	
       	$size = '';
		$pointsize = '';
		
		$fill = '';
		$background = '';
		$outline = '';
		$shadow = '';
		$label = '';
		
		$binary_path = $this->get_convert_path();
		$font = "-font $font_folder/tahoma.ttf";
		$gravity = "-gravity ".$this->get_anchor($arguments['anchor']);

       	$color = $this->extract_arg($arguments, 'color', '000000');
       	$bgcolor = $this->extract_arg($arguments, 'bgcolor', 'ffffff');
       	$outlinecolor = $this->extract_arg($arguments, 'outline', '');
       	
       	if(preg_match('/^([0-9a-f]){6}$/i', $color))
       	{
       		$fill = "-fill '#$color'";
       	}
       	
       	if(preg_match('/^([0-9a-f]){6}$/i', $bgcolor))
       	{
       		$background = "-background \"#$bgcolor\"";
       	}
       	
       	if(preg_match('/^([0-9a-f]){6}$/i', $outlinecolor))
       	{
       		$outline = "-stroke \"#$outlinecolor\"";
       	}
       	
		if(!empty($arguments['size']))
		{
			$size = "-size {$arguments['size']}";
		}
		else if(!empty($arguments['width']))
		{
			$size = "-size {$arguments['width']}x";
		}
		else if(!empty($arguments['height']))
		{
			$size = "-size x{$arguments['height']}";
		}
		
		if(!empty($arguments['pointsize']))
		{
			$pointsize = "-pointsize {$arguments['pointsize']}";
		}
		
		if(!empty($arguments['font']))
		{
			$font = "-font $font_folder/{$arguments['font']}";
		}
		
		$label = "label:\"$button_label\"";
		
		$button_command = "$binary_path $size $background $fill $outline $gravity $font $pointsize $label $shadow $escaped_destination_filename";
		
		if(!empty($arguments['shadow']))
		{
			$shadow_color = '#e5e5e5';
			
			if(preg_match('/^([0-9a-f]){6}$/i', $arguments['shadow']))
			{
				$shadow_color = '#'.$arguments['shadow'];
			}

			$button_command = "$binary_path $size -background none $fill $outline $font $pointsize label:\"$button_label\" -trim \( +clone -background \"#$shadow_color\" -shadow 80x3 \) +swap +repage -gravity center -geometry -3-3 -composite $escaped_destination_filename";

		}

      	#echo $button_command;
		#exit;
		exec($button_command);
			
		$escaped_original_image_path = $escaped_destination_filename;
		
		return $escaped_original_image_path;
 	}
 	
 	private function extract_arg($args, $key, $default = null)
 	{
 		$ret = $args[$key];
 		
 		if(empty($ret))
 		{
 			$ret = $default;
 		}
 		
 		return $ret;	
 	}
 	
 	public function process_video($original_video_path, $arguments)
 	{
 		$original_image_path = $original_video_path;
 		
 		if(preg_match('/\.jpg$/', $original_video_path))
 		{
 			$original_video_path = substr($original_video_path, 0, strlen($original_video_path) - 4);
 		}
 		else
 		{
 			$original_image_path .= '.jpg';
 		}
 		
 		if(count($arguments)<=0)
		{
			return $original_video_path;
		}
		
		$cache_folder = Kohana::config('webkitfolders.thumbnail_cache_folder').'/';
		
		$path_parts = explode('/', $original_image_path);
		
		$filename = array_pop($path_parts);
		$tilefilename = '';
		$ext = '';
		
		if(preg_match('/\.(\w+)$/', $original_image_path, $match))
		{
			$ext = strtolower($match[1]);
		}
		
		$quality = $this->quality_settings[$ext];
		
		$this->quality = $quality;
		
		$convert_path = $this->get_convert_path();
		$composite_path = $this->get_composite_path();
		
		$this->convert_path = $convert_path;
		
		$thumbnail_size = empty($arguments['thumbnail_size']) ? '' : $arguments['thumbnail_size'];
		$thumbnail_count = empty($arguments['thumbnail_count']) ? 1 : $arguments['thumbnail_count'];
		$thumbnail_start = empty($arguments['thumbnail_start']) ? 0 : $arguments['thumbnail_start'];

		if($thumbnail_count>=5)
		{
			$thumbnail_count -= 2;
		}
		else
		{
			$thumbnail_count = 1;
		}
		
		if(!preg_match('/^\d+(\.\d+)?$/', $thumbnail_start))
		{
			$thumbnail_start = 0;
		}
		
		foreach(Thumbnailcache::$video_tags as $tag => $realtag)
		{
			if(!empty($arguments[$tag]))
			{
				$path_parts[] = $realtag.'_'.$arguments[$tag];		
			}
		}
		
		$folder = implode('/', $path_parts);
		
		$cache_folder = preg_replace('/\/$/', '', $cache_folder);
		
		$full_cache_folder = $cache_folder.$folder;
		$destination_filename = $full_cache_folder.'/'.$filename;
		
		if($this->enable_cache)
		{
			// does this image already exist in the cache?	
			if(is_file($destination_filename))
			{
				return $destination_filename;
			}
		}
		
		if(!is_file($full_cache_folder))
		{
			// lets make sure the directory exists in the cache
			mkdir($full_cache_folder, 0777, true);
		}
	
		$escaped_original_image_path = escapeshellcmd($original_image_path);
		$escaped_original_video_path = escapeshellcmd($original_video_path);
		
		$escaped_destination_folder = escapeshellcmd($full_cache_folder);
		$escaped_destination_filename = escapeshellcmd($destination_filename);
		
		$video_info = $this->get_video_info($original_video_path);

		$thumbnail_count = 1;
		
		if(empty($thumbnail_size))
		{
			$thumbnail_size = $video_info['size'];
		}
		
		if($thumbnail_count>1)
		{
			$this->output_video_thumbnails(array(
				'video' => $escaped_original_video_path,
				'output_folder' => $full_cache_folder,
				'start' => $thumbnail_start,
				'size' => $video_info['size'],
				'count' => $thumbnail_count
			));
		
			$filearr = array();
		
			if ($handle = opendir($full_cache_folder))
			{
	    		while (false !== ($file = readdir($handle)))
    			{
	    			if(preg_match('/\.jpg$/', $file))
    				{
						$filearr[] = escapeshellcmd($full_cache_folder.'/'.$file);
    				}
        		}
    		}
    	
    		sort($filearr);
    	
    		$file_st = implode(' ', $filearr);
    	
    		$convert_path = $this->get_convert_path();
    		
    		foreach($filearr as $file)
    		{
    			$escfile = escapeshellcmd($file);
    			
    			$this->apply_rectangle($escfile, $escfile, $thumbnail_size);
    		}
    	
    		exec("$convert_path $file_st +append $escaped_destination_filename");
    	
    		foreach($filearr as $file)
    		{
	    		unlink($file);	
    		}
    	}
    	else
    	{
    		$this->output_video_thumbnail(array(
				'video' => $escaped_original_video_path,
				'output_path' => $destination_filename,
				'start' => $thumbnail_start,
				'size' => $video_info['size']
			));
			
			$this->apply_rectangle($escaped_destination_filename, $escaped_destination_filename, $thumbnail_size);
    	}
    	
    	return $escaped_destination_filename;
 	}
 	
 	private function output_video_thumbnail($config)
 	{
 		$video_path = $config['video'];
 		$size = $config['size'];
 		$start = $config['start'];
 		
 		$output_path = $config['output_path'];
		
		$command = "ffmpeg -i $video_path -ss $start -vframes 1 -s $size -f image2 $output_path";


		exec($command);
 	} 	
 	
 	private function output_video_thumbnails($config)
 	{
 		$video_path = $config['video'];
 		$size = $config['size'];
 		$start = $config['start'];
 		$count = $config['count'];
 		$output_path = $config['output_folder'].'/image%03d.jpg';
 		
		$video_info = $this->get_video_info($video_path);
		
		$video_seconds = $video_info['duration'] - $start;

		$thumbnail_per_seconds = $video_seconds / $count;
		
		$thumbnail_framerate = 1 / $thumbnail_per_seconds;
		
		$command = "ffmpeg -i $video_path -r $thumbnail_framerate -ss $start -s $size -bt 16000000 -f image2 $output_path";
		
		exec($command);
 	} 	
 	
 	private function get_video_info($video_path)
 	{
 		if($this->_video_info)
 		{
 			return $this->_video_info;
 		}
 		
		$results = array();
		
		exec("ffmpeg -i $video_path 2>&1", $results);
		
		$results = implode("\n", $results);

		preg_match('/Duration: (.*?),/', $results, $matches);
		$duration = $matches[1];
		
		preg_match('/Video: (.*?), ([\d\.]+) tbr?,/', $results, $matches);
		$framerate = $matches[2];
		
		preg_match('/Video: (.*?), (\d+)x(\d+)/', $results, $matches);
		$width = $matches[2];
		$height = $matches[3];
		
		$realwidth = $width;
		$realheight = $height;
		
		if($width % 2 > 0)
		{
			$width += 1;	
		}
		
		if($height % 2 > 0)
		{
			$height += 1;	
		}
				
		$size = $width.'x'.$height;
		
		$duration_array = explode(':', $duration);
		$duration = $duration_array[0] * 3600 + $duration_array[1] * 60 + $duration_array[2];
		
		$ret = array(
			'duration' => $duration,
			'size' => $size,
			'width' => $realwidth,
			'height' => $realheight,
			'framerate' => $framerate
		);

		$this->_video_info = $ret;
		
		return $ret;
 	}
 	
 	private function apply_rectangle($escaped_original_image_path, $escaped_destination_filename, $size, $anchor)
 	{
		$rectanglewidth = 0;
		$rectangleheight = 0;
			
		$imageinfo = getimagesize($escaped_original_image_path);

		$imagewidth = $imageinfo[0];
		$imageheight = $imageinfo[1];
			
		if(preg_match('/^(\d+)x(\d+)$/', $size, $matches))
		{
			$rectanglewidth = $matches[1];
			$rectangleheight = $matches[2];
		}

		if($rectanglewidth==0 || $rectangleheight==0)
		{
			return;
		}
		
		$cropgeometry = $rectanglewidth.'x'.$rectangleheight;
		
		if(empty($anchor))
		{
			$anchor = 'Center';
		}
			
		$crop_args = "-gravity $anchor -crop {$cropgeometry}+0+0";
		$resize_args = "-resize {$cropgeometry}\^";
		
		exec("{$this->convert_path} $escaped_original_image_path $resize_args -quality {$this->quality} $escaped_destination_filename");			
		
		$cmd = "{$this->convert_path} $escaped_destination_filename $crop_args -quality {$this->quality} $escaped_destination_filename";
		
		exec($cmd);
		
		return $escaped_destination_filename;	
 	}
 	
 	private function apply_size($escaped_original_image_path, $escaped_destination_filename, $size)
 	{
		$resize_args = "-resize {$size}x{$size}\>";
			
		exec("{$this->convert_path} $escaped_original_image_path $resize_args -quality {$this->quality} $escaped_destination_filename");
		
		return $escaped_destination_filename;
 	}

 	public function convert_size($arg, $double){
 		if(!$double){
 			return $arg;
 		}

 		if(!preg_match('/x/', $arg)){
 			return $arg * 2;
 		}

 		$parts = explode('x', $arg);
 		$new_parts = array();

 		foreach($parts as $part){
 			$new_parts[] = $part * 2;
 		}

 		$size = implode('x', $new_parts);

 		return $size;
 	}
 	
	public function process_image($original_image_path, $arguments)
	{
		if(count($arguments)<=0)
		{
			return $original_image_path;
		}

		$is_2x = $arguments['2x'];
		
		$cache_folder = Kohana::config('webkitfolders.thumbnail_cache_folder').'/';
		
		$path_parts = explode('/', $original_image_path);
		
		$filename = array_pop($path_parts);
		$tilefilename = '';
		$ext = '';
		
		if(preg_match('/\.(\w+)$/', $original_image_path, $match))
		{
			$ext = strtolower($match[1]);
		}
		
		$quality = $this->quality_settings[$ext];
		
		$this->quality = $quality;
		
		if(!empty($arguments['tile']))
		{
			if(preg_match('/\/.*?\.jpg\/.*?\.jpg/i', $original_image_path))
			{
				$tilefilename = $filename;
			
				$filename = array_pop($path_parts);
			}
		}
		
		foreach(Thumbnailcache::$argument_tags as $tag => $realtag)
		{
			if(!empty($arguments[$tag]))
			{
				$path_parts[] = $realtag.'_'.$arguments[$tag];		
			}
		}
		
		if(!empty($tilefilename))
		{
			$original_image_path = preg_replace('/\/'.$tilefilename.'/', '', $original_image_path);
			$path_parts[] = $tilefilename;
		}
		
		$folder = implode('/', $path_parts);
		
		$cache_folder = preg_replace('/\/$/', '', $cache_folder);
		
		$full_cache_folder = $cache_folder.$folder;
		$destination_filename = $full_cache_folder.'/'.$filename;
		
		if(!empty($arguments['convertto']))
		{
			$destination_filename = preg_replace('/\.\w+$/', '.'.$arguments['convertto'], $destination_filename);
		}

		if(!empty($arguments['2x']))
		{
			$parts = explode('.', $destination_filename);
			$ext = array_pop($parts);
			$filename = array_pop($parts);

			$filename .= '@2x';

			$parts[] = $filename;
			$parts[] = $ext;

			$destination_filename = implode('.', $parts);
		}
		
		if($this->enable_cache)
		{
			// does this image already exist in the cache?	
			if(is_file($destination_filename))
			{
				return $destination_filename;
			}
		}
		
		if(!is_file($full_cache_folder))
		{
			// lets make sure the directory exists in the cache
			mkdir($full_cache_folder, 0777, true);
		}
	
		$escaped_original_image_path = escapeshellcmd($original_image_path);
		$escaped_destination_folder = escapeshellcmd($full_cache_folder);
		$escaped_destination_filename = escapeshellcmd($destination_filename);
		
		$convert_path = $this->get_convert_path();
		$composite_path = $this->get_composite_path();
		
		$this->convert_path = $convert_path;

		
		if(!empty($arguments['auto']))
		{
			$auto = escapeshellcmd($arguments['auto']);
			
			$auto_args = "-auto-{$auto}";
			
			exec("$convert_path $escaped_original_image_path $auto_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}
		
		if(!empty($arguments['rotate']))
		{
			$angle = escapeshellcmd($arguments['rotate']);
			
			if(!empty($arguments['background']))
			{
				$bgcol = escapeshellcmd($arguments['background']);
			}
			else
			{
				$bgcol = "ffffff";
			}
				
			$rotate_args = "-background '#{$bgcol}' -rotate {$angle}";
			
			exec("$convert_path $escaped_original_image_path $rotate_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}

		if(!empty($arguments['square']))
		{
			$size = escapeshellcmd($arguments['square']);

			$size = $this->convert_size($size, $is_2x);
			$anchor = escapeshellcmd($arguments['squareanchor']);
			
			$imageinfo = getimagesize($escaped_original_image_path);
			
			$imagewidth = $imageinfo[0];
			$imageheight = $imageinfo[1];
			
			$cropgeometry = $imagewidth.'x'.$imagewidth;
			
			// if the height is less we will use that for the crop
			if($imageheight < $imagewidth)
			{
				$cropgeometry = $imageheight.'x'.$imageheight;
			}
			
			if(empty($anchor))
			{
				$anchor = 'Center';
			}
			
			$crop_args = "-gravity $anchor -crop ".$cropgeometry."+0+0";
			
			exec("$convert_path $escaped_original_image_path $crop_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
			
			$arguments['size'] = $size;
		}
		
		if(!empty($arguments['rectangle']))
		{
			$size = escapeshellcmd($arguments['rectangle']);

			$size = $this->convert_size($size, $is_2x);

			$anchor = escapeshellcmd($arguments['rectangleanchor']);
			
			$escaped_original_image_path = $this->apply_rectangle($escaped_original_image_path, $escaped_destination_filename, $size, $anchor);
		}
		
		if(!empty($arguments['size']))
		{
			$size = escapeshellcmd($arguments['size']);
			$size = $this->convert_size($size, $is_2x);
			$escaped_original_image_path = $this->apply_size($escaped_original_image_path, $escaped_destination_filename, $size);
		}
		
		if(!empty($arguments['fit']))
		{
			$size = escapeshellcmd($arguments['fit']);
			
			$resize_args = "-resize {$size}\>";
			
			exec("$convert_path $escaped_original_image_path $resize_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}		
		
		if(!empty($arguments['width']))
		{
			$size = escapeshellcmd($arguments['width']);
			$size = $this->convert_size($size, $is_2x);			
			$resize_args = "-resize {$size}x\>";
			
			exec("$convert_path $escaped_original_image_path $resize_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}
		
		if(!empty($arguments['height']))
		{
			$size = escapeshellcmd($arguments['height']);

			$size = $this->convert_size($size, $is_2x);
			
			$resize_args = "-resize x{$size}\>";

			$cmd = "$convert_path $escaped_original_image_path $resize_args -quality $quality $escaped_destination_filename";

			exec($cmd);
			
			$escaped_original_image_path = $escaped_destination_filename;
		}
		
		if(!empty($arguments['contentscale']))
		{
			$size = escapeshellcmd($arguments['contentscale']);
			
			// Need to install liblqr and re-compile imagemagick (http://liblqr.wikidot.com/en:installation-instructions)  
			$scale_args = "-liquid-rescale {$size}x100%\!";
			
			exec("$convert_path $escaped_original_image_path $scale_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}

		if(!empty($arguments['sharpen']))
		{
			if (escapeshellcmd($arguments['sharpen']) == "low")
			{
				$sharpen = "1.0x1.0+1.0+0.02";
			}
			else
			{
				$sharpen = "1.5x1.0+1.5+0.02";
			}
			
			$sharpen_args = "-unsharp {$sharpen}";
			
			exec("$convert_path $escaped_original_image_path $sharpen_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}

		if(!empty($arguments['negative']))
		{			
			exec("$convert_path $escaped_original_image_path -negate -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}

		if(!empty($arguments['flop']))
		{			
			exec("$convert_path $escaped_original_image_path -flop -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}
		
		if(!empty($arguments['level']))
		{
			$amount = escapeshellcmd($arguments['level']);
			
			if(preg_match('/^(\d+)-(\d+)$/', $amount, $matches))
			{
				$darkvalue = $matches[1];
				$lightvalue = $matches[2];
			}
			
			$level_args = "-level {$darkvalue}%,{$lightvalue}%";
			
			exec("$convert_path $escaped_original_image_path $level_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
			
		}		
		
		if(!empty($arguments['polaroid']))
		{
			$bordercolor = escapeshellcmd($arguments['polaroid']);
		
			$imageinfo = getimagesize($escaped_original_image_path);
			
			$imagewidth = $imageinfo[0];
			$imageheight = $imageinfo[1];	
			
			if(!empty($arguments['background']))
			{
				$bgcol = escapeshellcmd($arguments['background']);
			}
			else
			{
				$bgcol = "ffffff";
			}											
				
			$polaroid_args = "-bordercolor '#{$bordercolor}' -border 1 -background gray40 +polaroid -background '#{$bgcol}' -flatten -resize {$imagewidth}x\>";
			
			exec("$convert_path $escaped_original_image_path $polaroid_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}		

		if(!empty($arguments['polaroid2']))
		{
			$bordercolor = escapeshellcmd($arguments['polaroid2']);
		
			$imageinfo = getimagesize($escaped_original_image_path);
			
			$imagewidth = $imageinfo[0];
			$imageheight = $imageinfo[1];	
			
			if(!empty($arguments['background']))
			{
				$bgcol = escapeshellcmd($arguments['background']);
			}
			else
			{
				$bgcol = "ffffff";
			}											
				
			$polaroid2_args = "-bordercolor '#{$bordercolor}' -border 3% -bordercolor grey80 -border 1 -background  none  -rotate ".rand(-15, 15)." -background  black  \( +clone -shadow 60x2+2+4 \) +swap -background '#{$bgcol}'  -flatten -resize {$imagewidth}x\>";
			
			exec("$convert_path $escaped_original_image_path $polaroid2_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}

		if(!empty($arguments['polaroidstack']))
		{
			$bordercolor = escapeshellcmd($arguments['polaroidstack']);
		
			$imageinfo = getimagesize($escaped_original_image_path);
			
			$imagewidth = $imageinfo[0];
			$imageheight = $imageinfo[1];	
			
			if(!empty($arguments['background']))
			{
				$bgcol = escapeshellcmd($arguments['background']);
			}
			else
			{
				$bgcol = "ffffff";
			}											
				
			$polaroidstack_args = "-bordercolor '#{$bordercolor}'  -border 3% -bordercolor grey80 -border 1 -bordercolor none  -background  none \( -clone 0 -rotate ".rand(-15, 15)." \) \( -clone 0 -rotate ".rand(-15, 15)." \) \( -clone 0 -rotate ".rand(-15, 15)." \) \( -clone 0 -rotate ".rand(-15, 15)." \) \( -clone 0 \) -delete 0 -border 1000x1000 -flatten -trim +repage -background black \( +clone -shadow 60x2+2+4 \) +swap -background '#{$bgcol}' -flatten -resize {$imagewidth}x\>";
			
			exec("$convert_path $escaped_original_image_path $polaroidstack_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}

		if(!empty($arguments['reflect']))
		{
			$size = escapeshellcmd($arguments['reflect']);

			$size = $this->convert_size($size, $is_2x);

			$imageinfo = getimagesize($escaped_original_image_path);
			
			$imagewidth = $imageinfo[0];
			$imageheight = $imageinfo[1];
			
			$reflectheight = $imageheight+$size;
			
			if(!empty($arguments['background']))
			{
				$bgcol = escapeshellcmd($arguments['background']);
			}
			else
			{
				$bgcol = "ffffff";
			}
						
			$reflection_args = "-alpha on \( +clone -flip -size {$imagewidth}x{$size} gradient:gray20-black -alpha off -compose CopyOpacity -composite \) -append -gravity North -background '#{$bgcol}' -compose Over -flatten -crop {$imagewidth}x{$reflectheight}+0+0";
									
			exec("$convert_path $escaped_original_image_path $reflection_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}

		if(!empty($arguments['overlay']))
		{
			$overlayimage = escapeshellcmd($arguments['overlay']);
			
			if(!empty($arguments['overlayposition']))
			{
				$overlayposition = escapeshellcmd($arguments['overlayposition']);
			}
			else
			{
				$overlayposition = "SouthEast";
			}
			
			$overlaylocation = "/usr/local/uploaded_files/overlays/";
			
			$overlay_args = "-gravity {$overlayposition} {$overlaylocation}{$overlayimage}.png";
			
			exec("$composite_path $overlay_args $escaped_original_image_path -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}

		if(!empty($arguments['colorspace']))
		{
			$colortype = escapeshellcmd($arguments['colorspace']);
			
			$colorspace_args = "-colorspace {$colortype}";
			
			exec("$convert_path $escaped_original_image_path $colorspace_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}

		if(!empty($arguments['border']))
		{
			$percent = escapeshellcmd($arguments['border']);

			if(!empty($arguments['background']))
			{
				$bgcol = escapeshellcmd($arguments['background']);
			}
			else
			{
				$bgcol = "ffffff";
			}
			
			$border_args = "-bordercolor '#{$bgcol}' -border {$percent} -transparent '#{$bgcol}'";
			
			exec("$convert_path $escaped_original_image_path $border_args -quality $quality $escaped_destination_filename");
			
			$escaped_original_image_path = $escaped_destination_filename;
		}
		
		if(!empty($arguments['tile']))
		{
			if(preg_match('/^(\d+)_(\d+)$/', $arguments['tile'], $match))
			{
				$tileinfo = Thumbnailcache::calculate_tiles($escaped_original_image_path, $match[1], $match[2]);
				
				$cropgeometry = $tileinfo['cropwidth'].'x'.$tileinfo['cropheight'];
				
				$crop_args = "-gravity Center -crop ".$cropgeometry."+0+0";
		
				exec("$convert_path $escaped_original_image_path $crop_args -quality $quality $escaped_destination_filename");
				
				$escaped_original_image_path = $escaped_destination_filename;

				if(!empty($tilefilename))
				{
					if(preg_match('/(\d+)\.jpg$/i', $tilefilename, $match))
					{
						$tilenumber = $match[1];
						
						$testrows = 1;
						$testcols = 1;
						
						$tilerow = 0;
						$tilecol = 0;
						
						for($i=0; $i<=$tilenumber; $i++)
						{
							$testnumber = (($testrows - 1) * $tileinfo['cols']) + $testcols;
							
							if($testnumber == $tilenumber)
							{
								$tilerow = $testrows;
								$tilecol = $testcols;
								
								break;
							}
							else
							{
								$testcols++;
								
								if($testcols>$tileinfo['cols'])
								{
									$testcols = 1;
									$testrows++;	
								}	
							}
						}
						
						$cropx = ($tilecol - 1) * $tileinfo['cellwidth'];
						$cropy = ($tilerow - 1) * $tileinfo['cellheight'];
						
						$cropgeometry = $tileinfo['cellwidth'].'x'.$tileinfo['cellheight'];
						
						$crop_args = "-gravity NorthWest -crop ".$cropgeometry."+".$cropx."+".$cropy;
						
						$tilecommand = "$convert_path $escaped_destination_filename $crop_args -quality $quality $escaped_destination_filename";

						exec($tilecommand);
					}
				}
			}
		}
		
		return $escaped_destination_filename;
	}
	
	public static function calculate_tiles($image_path, $rows, $cols)
	{
		$rows = escapeshellcmd($rows);	
		$cols = escapeshellcmd($cols);
					
		$imageinfo = getimagesize($image_path);
			
		$imagewidth = $imageinfo[0];
		$imageheight = $imageinfo[1];
		
		$widthremainder = $imagewidth % $cols;
		$heightremainder = $imageheight % $rows;
			
		if(empty($widthremainder))
		{
			$widthremainder = 0;
		}
				
		if(empty($heightremainder))
		{
			$heightremainder = 0;
		}
				
		$newimagewidth = $imagewidth - $widthremainder;
		$newimageheight = $imageheight - $heightremainder;
		
		$cellwidth = $newimagewidth / $cols;
		$cellheight = $newimageheight / $rows;
		
		$info = array(
			'cells' => $rows * $cols,
			'rows' => $rows,
			'cols' => $cols,
			'imagewidth' => $imagewidth,
			'imageheight' => $imageheight,
			'cropwidth' => $newimagewidth,
			'cropheight' => $newimageheight,
			'cellwidth' => $cellwidth,
			'cellheight' => $cellheight
		);
		
		return $info;
	}
	
	private function get_anchor($anchor)
	{
		return $anchor;
	}
	
	// gives you the path to the image magick convert executable
	private function get_convert_path()
	{
		$path = Kohana::config('webkitfolders.image_magick_bin');
		
		return $path.'/convert';
	}
	private function get_composite_path()
	{
		$path = Kohana::config('webkitfolders.image_magick_bin');
		
		return $path.'/composite';
	}
}
?>