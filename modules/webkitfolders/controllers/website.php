<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Website Controller
 *
 * Controller for website pages
 *
 *
 * This controller is activated by a website request - its basic role
 * is to serve htm(l) pages and to replace any tags it finds with the content
 * those tags point to
 *
 * The URL will determine what items will get loaded
 *
 *
 */
 
// ------------------------------------------------------------------------ 

class Website_Controller extends Controller
{
	private $mime_map = array(
		'jpg' => 'image/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png'
	);
	
	public function render_page()
	{
		$uri_parts = $this->uri->argument_array();

		$clean_uri_parts = array();
		$uri_args = array();
		
		$this->orig_uri = '/'.implode('/', $uri_parts);
		
		// instructions are things present in the uri that will be interpreted
		// and shouldn't be used to find a page / item
		$this->instructions = array();
		
		// render mode tells us what should happen to this uri
		// this can be one of:
		//
		// 404 - there is no such uri
		// parse - the content should be parsed before returned
		// file - the content should just be returned
		// image - the content should be interpreted as an image
		$this->render_mode = '404';
		
		$this->has_hit_thumbnail_cache = false;
		
		$found_page = false;
		$is_video = false;

		$is_uploaded_files = preg_match('/uploaded_files\//', $this->orig_uri);

		// this strips the instructions out of the uri
		foreach($uri_parts as $part)
		{
			$has_hit = false;
			
			foreach(Thumbnailcache::$argument_tags as $tag => $realtag)
			{	
				if(preg_match('/^_'.$tag.'_(.*)?$/', $part, $match))
				{
					$this->instructions[$realtag] = $match[1];
					$has_hit = true;
				}
			}
			
			foreach(Thumbnailcache::$video_tags as $tag => $realtag)
			{	
				if(preg_match('/^_'.$tag.'_(.*)?$/', $part, $match))
				{
					$is_video = true;
					$this->instructions[$realtag] = $match[1];
					$has_hit = true;
				}
			}
			
			foreach(Thumbnailcache::$button_tags as $tag => $realtag)
			{	
				if(preg_match('/^_'.$tag.'_(.*)?$/', $part, $match))
				{
					$this->instructions[$realtag] = urldecode($match[1]);
					
					if($realtag=='text')
					{
						$this->instructions[$realtag] = htmlspecialchars_decode($this->instructions[$realtag]);
					}
					
					$has_hit = true;
				}
			}

			if(preg_match('/@2x\./', $part) && $is_uploaded_files){
				$part = preg_replace('/@2x/', '', $part);

				$this->instructions['2x'] = true;
				$force_include = true;
				
			}
			
			
			if(!$has_hit)
			{
				if($found_page)
				{
					$uri_args[] = $part;	
				}
				else
				{
					$clean_uri_parts[] = $part;
				}
			}
			
			if(preg_match('/\.(htm|js|xml|manifest)$/', $part))
			{
				$found_page = true;
			}
		}
		
		$this->uri_path = '/'.implode('/', $uri_args);
		
		$uri = '/'.implode('/', $clean_uri_parts);
		
		// this gets a full path for the file
		if(preg_match('/^\/uploaded_files\//', $uri, $match))
		{
			$uri = str_replace('/uploaded_files', '', $uri);
	
			$full_path = Kohana::config('webkitfolders.full_upload_folder').$uri;
		}
		else
		{
			$full_path = $_SERVER['DOCUMENT_ROOT'].$uri;
		}
		
		$this->uri = $uri;
		$this->full_path = $full_path;

		// now lets get the real full path
		$this->adjust_full_path();
		
		$this->file_exists = $this->does_path_exist($this->full_path);
		$this->is_dir = is_dir($this->full_path);
		
		if($this->is_dir)
		{
			$this->render_mode = '404';
		}
		else if(!$this->file_exists)
		{
			// have we got a pointer to a method of the image factory?
			if($this->instructions['button']=='y')
			{
				$this->render_mode = 'button';
			}
			else
			{
				$this->render_mode = '404';
			}
		}
		else if(!preg_match('/\.(html?|xml|js|manifest)$/i', $this->full_path))
		{
			$this->render_mode = 'file';
		}
		else
		{
			$this->render_mode = 'parse';
		}
		
		if($is_video)
		{
			$this->render_mode = 'video';
		}
		
		// this means there is nothing for this uri
		if($this->render_mode == '404')
		{
			Event::run('system.404');
		}
		// this means we are returning the contents of a file
		else if($this->render_mode == 'file')
		{
			if(preg_match('/\.(jpg|png|gif)$/i', $this->full_path, $match))
			{
				$cache = new Thumbnailcache();

				$image_path = $cache->process_image($this->full_path, $this->instructions);
				
				$ext = strtolower($match[0]);
				
				$mime = $this->mime_map[$ext];
				
				$this->output_image_content($image_path, $mime);
				
				return;
			}
			else
			{
				if(file_exists($this->full_path))
				{
    				header('Content-Type: '.mime_content_type($this->full_path));
   					header('Content-Length: ' . filesize($this->full_path));
    				ob_clean();
    				flush();
    				readfile($this->full_path);
    				exit;
    			}
    			else
    			{    
					Event::run('system.404');
				}
			}
		}
		else if($this->render_mode == 'button')
		{
			$cache = new Thumbnailcache();
			
			$image_path = $cache->process_button($this->orig_uri, $this->instructions);
			
			$mime = $this->mime_map['png'];
			
			$this->output_image_content($image_path, $mime);
				
			return;
		}
		else if($this->render_mode == 'video')
		{
			$cache = new Thumbnailcache();
					
			$image_path = $cache->process_video($this->full_path, $this->instructions);
				
			$mime = $this->mime_map['jpg'];
				
			$this->output_image_content($image_path, $mime);
				
			return;
		}
		// this means we are parsing the result
		else
		{
			$filecontents = file_get_contents($this->full_path);
			
			$result = $this->parse_text($filecontents);
			
			if(preg_match('/\.xml$/i', $this->full_path))
			{
				$mime = 'text/xml';	
				header('Content-Type: '.$mime);
   				header('Content-Length: ' . strlen($result));
			}
			
			echo $this->process_render($result);
		}
	}
	
	protected function process_render($string)
	{
		return $string;	
	}
	
	private function output_image_content($image_path, $mime)
	{
		header('Cache-Control: max-age=7200, must-revalidate');
		header("Content-Type: $mime");
		header("Content-Length: " . filesize($image_path));
		
		$fp = fopen($image_path, 'rb');

		// dump the picture and stop the script
		fpassthru($fp);
	}
	
	// single entry method - this is what will serve all HTML
	function parse_text($page_content = null)
	{
		$params = $_GET;
		
		$params['path'] = $this->uri_path;
		
		$parser = new HTMLParser($page_content, $params);
		
		$html = $parser->render_item();
		
		return $html;
		
		/*
		else if($item)
		{
			if($item->item_type == 'photo')
			{
				$render_template = false;
				$render_content = false;
				
				$image_path = $_SERVER['DOCUMENT_ROOT'].'/'.$item->image->folder.'/'.$item->image->file;
				
				if(isset($this->instructions['thumbnail']))
				{
					$size = $this->instructions['thumbnail'];
						
					$cache = new Thumbnailcache();
					
					$image_path = $cache->get_thumbnail($image_path, $size);
				}

				$image_obj = new Image($image_path);
				$image_obj->render();
				
				return;
			}
			else
			{
				$item->load_path();
	
				$view = new View('item/default');
		
				$view->item = $item;
		
				$html = $view->render();
			}
		}
		else
		{
			if(preg_match('/\.(jpg|png|gif)$/i', $this->full_path))
			{
				$image_path = $this->full_path;
				
				if(isset($this->instructions['thumbnail']))
				{
					$size = $this->instructions['thumbnail'];
						
					$cache = new Thumbnailcache();
					
					$image_path = $cache->get_thumbnail($this->full_path, $size);
				}

				$image_obj = new Image($image_path);
				$image_obj->render();
				
				return;
			}
			else
			{
				Event::run('system.404');
			}
		}
		*/

		/*
		if($render_template)
		{
			if(isset($website->template))
			{
				$template = new Item_Model($website->template->id, true);
				$template->load_path();
					
				$template_path = $_SERVER['DOCUMENT_ROOT'].'/'.$template->website_path;
					
				$template_parser = new HTMLParser($template_path);
				
				$template_content = file_get_contents($template_path);
					
				if(preg_match('/\[xara\.template_content].*?\[\/xara\.template_content\]/si', $template_content, $match))
				{
					$html = str_replace($match[0], $html, $template_content);
				}
			}
		}
		*/
	}
	
	function load_website()
	{
		// where are the websites living?
		$local_folder = Kohana::config('ftp_site.website_folder');
		
		$local_folder_pattern = str_replace('/', '\\/', $local_folder);
		
		$site_id_pattern = '/^'.$local_folder_pattern.'\\/(\w+)\\/www/i';
		
		$website_id = '';
		
		// this is where we try to match the local document root to a website
		if(preg_match($site_id_pattern, $_SERVER['DOCUMENT_ROOT'], $match))
		{
			$website_id = $match[1];
		}
		else
		{
			//echo "cannot find website id in the document root";
			return null;
		}
		
		// we now have the 'folder' property of the website from the FTP folder
		// we need to load the website that has this property - this will be replaced
		// by checking the domain against the websites domain
		
		// first lets load up the website

		$website_factory = new Itemfactory_Model();
		$website = $website_factory->item_types('ftp_website')->keyword_words(true)->keyword_query('folder', $website_id)->load_single();

		if(!isset($website))
		{
			//echo "cannot find website object for: $website_id";
			return null;
		}
		
		$website->load_path();
		
		$this->website = $website;
		
		return $website;
	}
	
	public function adjust_full_path()
	{
		if(empty($this->full_path))
		{
			return;
		}
		
		// if the request is for a directory lets manually insert the index.htm(l)
		if(is_dir($this->full_path))
		{
			// lets try with /index.htm
			$test_full_path = $this->full_path.'/index.htm';
			
			if(file_exists($test_full_path))
			{
				// we have a hit so we assume /index.htm
				$this->full_path .= '/index.htm';
				$this->uri .= '/index.htm';
			}
			else
			{
				// we don't have a /index.htm so lets try /index.html
				$test_full_path .= 'l';
				
				if(file_exists($test_full_path))
				{
					$this->full_path .= '/index.html';
					$this->uri .= '/index.html';
				}
			}
		}

		// if the request is for a file - but there is an alternative index
		// (like .html not .htm) - then lets swap them...
		if(!$this->does_path_exist($this->full_path))
		{
			if(preg_match('/\.htm$/', $this->full_path))
			{
				$this->full_path .= 'l';
				$this->uri .= 'l';
			}
			else if(preg_match('/\.html$/', $this->full_path))
			{
				$this->full_path = preg_replace('/\.html$/', '.htm', $this->full_path);
				$this->uri = preg_replace('/\.html$/', '.htm', $this->uri);
			}
		}	
	}
	
	private function does_path_exist($path)
	{
		foreach(Thumbnailcache::$ignore_file_exists_tags as $tag => $realtag)
		{
			if(!empty($this->instructions[$tag]))
			{
				return true;	
			}
		}
		
		return file_exists($path);
	}
	
	private function default_item_view($item)
	{
		
	}
}
?>