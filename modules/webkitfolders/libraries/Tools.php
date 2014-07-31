<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Tools Library
 *
 * Useful functions used across the system
 *
 */
 
// ------------------------------------------------------------------------ 

class Tools
{
	
	// --------------------------------------------------------------------

	/**
	* 	Quick accessor for input parameters
	*	If the input has been given Kohana style (i.e. /controller/method/param)
	*	Then the passed variable will be the params
	*
	* 	@access	public
	* 	@return	boolean
	*/		
	
	function param($existing_value = NULL, $name = NULL)
	{
		if(isset($existing_value))
		{
			return $existing_value;
		}
		
		if(isset($name))
		{
			$input = Input::instance();
		
			return $input->get($name);
		}
		else if(preg_match("/[\&\=]/", $_SERVER['QUERY_STRING'])<=0)
		{
			return $_SERVER['QUERY_STRING'];
		}
		else
		{
			return NULL;
		}
	} 
	
	function xara_template_output($content = NULL, $use_layout = 'layout.tmpl')
	{
		require_once('template.php');
		
		TplProcessPageHeader(NULL, $use_layout);
		
		$content = str_replace('$', '\\$', $content);
 	
 		$tpl->setCurrentBlock('CONTENT');
		$tpl->setVariable("CONTENT", $content);
		$tpl->parseCurrentBlock();

		TplProcessPageFooter();	
	}
	
	function save_uploaded_content($uploadcontent, $uploadfilename, $uploadsize, $uploadtype)
	{
		$fileinfo = array(
		
			'name' => $uploadfilename,
			
			'size' => $uploadsize,
			
			'type' => $uploadtype
		);
		
		if(!$fileinfo['size']>0)
		{			
			return null;	
		}
		
		$upload_folder_info = $this->make_upload_folder();

		$upload_folder = $upload_folder_info['full_folder'];
		$relative_folder = $upload_folder_info['relative_folder'];

		//$config['upload_path'] = $upload_folder;
		//$config['allowed_types'] = 'jpg|gif|png';
		
		//$filename = upload::save($uploadname, $fileinfo['name'], $upload_folder);
		
		$fullpath = $upload_folder.'/'.$uploadfilename;
		
		file_put_contents($fullpath, $uploadcontent);

		$fileinfo['name'] = str_replace(' ', '_', $fileinfo['name']);
		
		$filenameparts = explode('.', $fileinfo['name']);
		
		$upload_data = array(
			'file_name' => $fileinfo['name'],
			'file_size' => $fileinfo['size'],
			'file_type' => $fileinfo['type'],
			'relative_folder' => $relative_folder,
			'upload_folder' => $upload_folder,
			'extension' => array_pop($filenameparts)
		);
		
		$upload_data = Thumbnailcache::add_size_fields_to_upload_data($upload_data);
		
		return $upload_data;
	}

	function save_uploaded_file($uploadname)
	{
		$fileinfo = $_FILES[$uploadname];	
		
		if(!$fileinfo['size']>0)
		{			
			return null;	
		}
		
		$upload_folder_info = $this->make_upload_folder();
		
		$upload_folder = $upload_folder_info['full_folder'];
		$relative_folder = $upload_folder_info['relative_folder'];

		//$config['upload_path'] = $upload_folder;
		//$config['allowed_types'] = 'jpg|gif|png';
		
		$filename = upload::save($uploadname, $fileinfo['name'], $upload_folder);

		$fileinfo['name'] = str_replace(' ', '_', $fileinfo['name']);
		
		$filenameparts = explode('.', $fileinfo['name']);
		
		$upload_data = array(
			'file_name' => $fileinfo['name'],
			'file_size' => $fileinfo['size'],
			'file_type' => $fileinfo['type'],
			'relative_folder' => $relative_folder,
			'upload_folder' => $upload_folder,
			'extension' => array_pop($filenameparts)
		);
		
		$upload_data = Thumbnailcache::add_size_fields_to_upload_data($upload_data);
		
		return $upload_data;
	}
	
	function recursive_remove_directory($directory, $empty=FALSE)
	{
		// if the path has a slash at the end we remove it here
		if(substr($directory,-1) == '/')
		{
			$directory = substr($directory,0,-1);
		}
	
		// if the path is not valid or is not a directory ...
		if(!file_exists($directory) || !is_dir($directory))
		{
			// ... we return false and exit the function
			return FALSE;
	
		// ... if the path is not readable
		}elseif(!is_readable($directory))
		{
			// ... we return false and exit the function
			return FALSE;
	
		// ... else if the path is readable
		}else{
	
			// we open the directory
			$handle = opendir($directory);
	
			// and scan through the items inside
			while (FALSE !== ($item = readdir($handle)))
			{
				// if the filepointer is not the current directory
				// or the parent directory
				if($item != '.' && $item != '..')
				{
					// we build the new path to delete
					$path = $directory.'/'.$item;
	
					// if the new path is a directory
					if(is_dir($path)) 
					{
						// we call this function with the new path
						$this->recursive_remove_directory($path);
	
					// if the new path is a file
					}else{
						// we remove the file
						unlink($path);
					}
				}
			}
			// close the directory
			closedir($handle);
	
			// if the option to empty is not set to true
			if($empty == FALSE)
			{
				// try to delete the now empty directory
				if(!rmdir($directory))
				{
					// return false if not possible
					return FALSE;
				}
			}
			// return success
			return TRUE;
		}
	}
	
	function make_upload_folder()
	{
		$date = getdate();
		
		$base_dir = Kohana::config('webkitfolders.full_upload_folder');
		
		$random_dir = $this->generateRandStr(8);
		
		$extra_dirs = array($date['year'], $date['mon'], $date['mday'], $random_dir);
		
		$current_dirs = array($base_dir);
		$relative_dirs = array();
		
		foreach($extra_dirs as $extra_dir)
		{
			$current_dirs[] = $extra_dir;
			$relative_dirs[] = $extra_dir;
			
			$check_dir = join('/', $current_dirs);
			
			if(!file_exists($check_dir))
			{
				mkdir($check_dir, '0755');
			}
		}
		
		$final_dir = join('/', $current_dirs);
		$relative_dir = join('/', $relative_dirs);
		
		$ret = array(
			'full_folder' => $final_dir,
			'relative_folder' => $relative_dir
		);
		
		return $ret;
	}
	
	function generateRandStr($length){ 
      $randstr = ""; 
      for($i=0; $i<$length; $i++){ 
         $randnum = mt_rand(0,61); 
         if($randnum < 10){ 
            $randstr .= chr($randnum+48); 
         }else if($randnum < 36){ 
            $randstr .= chr($randnum+55); 
         }else{ 
            $randstr .= chr($randnum+61); 
         } 
      } 
      return $randstr; 
   } 
	
}
?>