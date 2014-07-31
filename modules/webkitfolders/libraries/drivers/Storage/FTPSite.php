<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * FTPSite Storage Driver.
 */
class Storage_FTPSite_Driver extends Storage_Driver
{
	// scans a local directory and builds the item create info from it
	public function do_create()
	{
		$this->process_ftp_folder();
  
    	$this->item->auto_create_children($this->top_items);
	}
	
	public function do_save()
	{
		return;
		
    	$this->item->load_children(true, true, true);
    	
    	$this->process_ftp_folder($this->item->path);
    	
    	$this->item->auto_create_children($this->top_items);		
	}
	
	private function process_ftp_folder($base_path)
	{
		$local_folder = Kohana::config('ftp_site.website_folder');
		
		$site_folder = $local_folder.'/'.$this->item->folder.'/www';
		
		$file_lister =  new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($site_folder),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		
		$this->top_items = array();	
		$this->folder_map = array();

   		foreach( $file_lister as $file_path => $file_object ) 
    	{
    		$full_folder_path = $file_object->getPath();
    		$relative_folder_path = str_replace($site_folder, '', $full_folder_path);
    		$relative_folder_path = preg_replace('/^\//', '', $relative_folder_path);
    		$filename = $file_object->getFilename();
    		
    		$relative_file_path = $relative_folder_path.'/'.$filename;
    		$full_file_path = $full_folder_path.'/'.$filename;
    		
    		if(preg_match('/\.html?$/i', $filename))
    		{
    			$page_uri = '/'.$relative_file_path;
    			
    			$page_uri = preg_replace('/^\/\//', '/', $page_uri);
    			
    			$page_info = new StdClass();
    			$page_info->name = $filename;
    			$page_info->item_type = 'ftp_webpage';
    			$page_info->check_url = $base_path.$page_uri;
    				
    			$parser = new HTMLParser($full_file_path);
		
    			$page_info->children = $parser->get_item_definitions();
    			
    			foreach($page_info->children as $component)
    			{
    				$this->assign_path($component, $base_path.$page_uri);
    			}

    			// this is a top level webpage
    			if(empty($relative_folder_path))
    			{
    				$this->top_items[] = $page_info;
    			}
    			// this webpage is inside of a folder
    			else
    			{
    				$folder_info = $this->ensure_folder($relative_folder_path);
    				
    				$folder_info->children[] = $page_info;
    			}
    		}
    		else if(preg_match('/\.jpg$/i', $filename))
    		{
    			$page_uri = '/'.$relative_file_path;
    			
    			$page_uri = preg_replace('/^\/\//', '/', $page_uri);
    			
    			$img_info = new StdClass();
    			$img_info->name = $filename;
    			$img_info->item_type = 'photo';
    			$img_info->check_url = $base_path.$page_uri;
    			$img_info->children = array();
    			
    			list($img_w,$img_h) = getimagesize($full_file_path); 
    			$filesize = filesize($full_file_path);
    			
    			$img_info->fields['width'] = $img_w;
    			$img_info->fields['height'] = $img_h;
    			$img_info->fields['image'] = array(
    				'size' => $filesize,
    				'type' => 'image/jpeg',
    				'file' => $filename,
    				'folder' => '/'.$relative_folder_path
    			);

    			// this is a top level webpage
    			if(empty($relative_folder_path))
    			{
    				$this->top_items[] = $img_info;
    			}
    			// this webpage is inside of a folder
    			else
    			{
    				$folder_info = $this->ensure_folder($relative_folder_path);
    				
    				$folder_info->children[] = $img_info;
    			}
    		}
    	}
	}
	
	private function assign_path($component, $base_path)
	{
		$use_url = $component->fields['name'];
		
		if(empty($use_url))
		{
			$use_url = $component->fields['title'];
		}
		
		$use_url = statictools::get_url_from_string($use_url);
		
		$component->check_url = $base_path.'/'.$use_url;
		
		foreach($component->children as $child)
		{
			$this->assign_path($child, $component->check_url);	
		}
	}
	
	private function ensure_folder($folder_path)
	{
		$folder_info = $this->folder_map[$folder_path];
		
		if(isset($folder_info))
		{
			return $folder_info;
		}
		
		$folder_parts = explode('/', $folder_path);
		
		$current_folder_name = array_pop($folder_parts);
		
		$folder_info = new StdClass();
    	$folder_info->name = $current_folder_name;
    	$folder_info->item_type = 'folder';
    	$folder_info->check_url = $base_path.$folder_path;
    	$folder_info->fields = array();
    	$folder_info->children = array();
    	
    	$this->folder_map[$folder_path] = $folder_info;
    	
    	$parent_folder_path = implode('/', $folder_parts);
    			
    	if(!empty($parent_folder_path))
    	{
			$parent_folder = $this->ensure_folder($parent_folder_path);
			$parent_folder->children[] = $folder_info;
		}
		else
		{
			$this->top_items[] = $folder_info;
		}

		return $folder_info;
	}
	
}