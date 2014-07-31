<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * FolderParser - scans a local folder and makes items from it.
 */
class FolderParser
{
	public function __construct($folder, $model, $field, $foldermodel, $mode)
	{
		$this->foldermodel = $foldermodel;
		$this->folder = $folder;
		$this->model = $model;
		$this->field = $field;
		$this->mode = $mode;
		
		$this->gavin_image_store = array();
		
		if(empty($this->foldermodel))
		{
			$this->foldermodel = 'folder';
		}
		
		if(empty($this->mode))
		{
			$this->mode = 'normal';
		}
	}
	
	// scans a local directory and builds the item create info from it
	public function get_item_defs()
	{
		$this->process_folder();
  
  		if($this->mode == 'gavin')
  		{
  			foreach($this->top_items as $top_item)
  			{
  				$this->check_for_child_images($top_item);	
  			}
  		}
  		
  		return $this->top_items;
    	//$this->item->auto_create_children($this->top_items);
	}
	
	public function check_for_child_images($item)
	{
		foreach($item->children as $child)
		{
			$this->check_for_child_images($child);
		}

		if(!empty($item->namekey))
		{
			$childimages = $this->gavin_image_store[$item->namekey];
			
			if(isset($childimages))
			{
				$num = 1;
				
				foreach($childimages as $childimage)
				{
					$childimage->name .= ' '.$num;
					$item->children[] = $childimage;
					
					$num++;
				}	
			}
		}
		
		usort($item->children, array('FolderParser', '_cmpAscA'));
		
		$position = 10;
		
		foreach($item->children as $child)
		{
			$child->fields['position'] = $position;
			
			$position += 10;
		}
	}
	
	static function _cmpAscA($m, $n)
	{
		if(empty($m->image_number))
		{
			if ($m->name == $n->name) {
	        	return 0;
	    	}

	    	return ($m->name < $n->name) ? -1 : 1;
		}
		else
		{
    		if ($m->image_number == $n->image_number) {
		        return 0;
	    	}

	    	return ($m->image_number < $n->image_number) ? -1 : 1;
	    }
 	}
	
	private function process_folder()
	{
		$site_folder = $this->folder;
		
		$file_lister =  new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($site_folder),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		
		$this->top_items = array();	
		$this->folder_map = array();
		
		$tools = new Tools();

   		foreach( $file_lister as $file_path => $file_object ) 
    	{
    		$full_folder_path = $file_object->getPath();
    		$relative_folder_path = str_replace($site_folder, '', $full_folder_path);
    		$relative_folder_path = preg_replace('/^\//', '', $relative_folder_path);
    		$filename = $file_object->getFilename();
    		
    		$relative_file_path = $relative_folder_path.'/'.$filename;
    		$full_file_path = $full_folder_path.'/'.$filename;
    		
    		if(preg_match('/\.(\w+)$/i', $filename))
    		{
    			$page_uri = '/'.$relative_file_path;
    			
    			$page_uri = preg_replace('/^\/\//', '/', $page_uri);
    			$item_name = preg_replace('/\.\w+$/i', '', $filename);

    			$img_info = new StdClass();
    			$img_info->name = $item_name;
    			$img_info->item_type = $this->model;
    			$img_info->check_url = $base_path.$page_uri;
    			$img_info->children = array();
    			
				if($this->mode == 'gavin')
				{
					if(preg_match('/(\d+)/', $item_name, $match))
					{
						$img_info->image_number = intval($match[1]);	
					}
					
					$name = preg_replace('/\W+G.*$/', '', $item_name);
					
					$name = preg_replace('/_\d+/', '', $name);
					
					$name = preg_replace('/_/', ' ', $name);
					
					$namekey = strtolower(preg_replace('/\W/', '', $name));
					
					$img_info->name = $name;
					$img_info->namekey = $namekey;
				}    			
    			
    			//list($img_w,$img_h) = getimagesize($full_file_path); 
    			$filesize = filesize($full_file_path);
    			
    			$upload_folder_info = $tools->make_upload_folder();
    			
    			$upload_folder = $upload_folder_info['full_folder'];
				$relative_folder = $upload_folder_info['relative_folder'];
				
				$filename = str_replace(' ', '_', $filename);
				
				$full_output_path = $upload_folder.'/'.$filename;
				
				copy($full_file_path, $full_output_path);
    			
    			$img_info->fields[$this->field] = array(
    				'size' => $filesize,
    				'type' => mime_content_type($full_output_path),
    				'file' => $filename,
    				'folder' => $relative_folder
    			);

    			// this is a top level webpage
    			if(empty($relative_folder_path))
    			{
    				$this->top_items[] = $img_info;
    			}
    			// this webpage is inside of a folder
    			else
    			{
    				if($this->mode == 'gavin')
    				{
    					if(empty($img_info->image_number) || $img_info->image_number == 1)
    					{
    						$folder_info = $this->ensure_folder($relative_folder_path);
    				
    						$folder_info->children[] = $img_info;
    					}
    					else
    					{
    						if(!isset($this->gavin_image_store[$img_info->namekey]))
    						{
    							$this->gavin_image_store[$img_info->namekey] = array();
    						}
    						
    						$this->gavin_image_store[$img_info->namekey][] = $img_info;
    					}
    				}
    				else
    				{
    					$folder_info = $this->ensure_folder($relative_folder_path);
    				
    					$folder_info->children[] = $img_info;
    				}
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
    	$folder_info->item_type = $this->foldermodel;
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