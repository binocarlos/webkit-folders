<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Admin Controller
 *
 * Controller for generic application requests such as authentication
 *
 */
 
// ------------------------------------------------------------------------ 

class Admin_Controller extends Controller
{
	// --------------------------------------------------------------------

	/**
	* 	The authentication controller - use this to prompt an error response are success depending on
	*	whether the user is logged in or has supplied correct login details
	*
	* 	@url		/admin/
	* 	@return		JSON login response
	*/	
	public function index()
	{
		$this->apprequest = new Apprequest;

		if(!$this->apprequest->login()) { return; }
		
		$this->adminmodel = new Admin_Model;
		$this->treemodel = new Tree_Model;
		
		// lets make sure they have a system to look at!
		installation::ensure_system();
		
		$system_item = $this->treemodel->load_system_item('all');
		
		$root_folder_info = array();
		
		// there are multiple system folders so we need to make a ghost root
		if(is_array($system_item))
		{
			$root_folder_info = array(
				'id' => 'root',
				'item_type' => 'installation',
				'name' => 'Installation'
			);
		}
		// there is only one system folder so we can make it the root
		else
		{
			$root_folder_info = array(
				'id' => $system_item->id,
				'item_type' => $system_item->item_type,
				'name' => $system_item->name
			);
		}
		
		$return_data = array(
		
			'status' => 'ok',
			
			'iconsFolder' => Kohana::config('webkitfolders.icon_folder'),
			
			'uploadFolder' => Kohana::config('webkitfolders.upload_folder'),
			
			'installationName' => installation::name(),
			
			'installation_id' => installation::id(),
			
			'rootFolderInfo' => $root_folder_info,
			
			'schemas' => $this->adminmodel->get_schemas()
		);
		
		$this->apprequest->do_response($return_data);
		
	}

	public function schema()
	{
		$this->apprequest = new Apprequest;

		if(!$this->apprequest->login()) { return; }
		
		$this->adminmodel = new Admin_Model;
		$this->treemodel = new Tree_Model;
		
		$return_data = array(
		
			'status' => 'ok',
			
			'schemas' => $this->adminmodel->get_schemas()
		);
		
		$this->apprequest->do_response($return_data);
	}	

	public function list_all_icons()
	{
		$this->apprequest = new Apprequest;
		
		$local_icons_folder = Kohana::config('webkitfolders.icon_folder').'/default/16';
		
		$search = $this->input->post('search', '');
		
		$icon_array = glob($local_icons_folder.'/'.$search.'*.png');
		
		$icons = array();
		
		$start = $this->input->post('start', 0);
		$limit = $this->input->post('limit', 40);
		
		if(!empty($search))
		{
			$start = 0;
			$limit = count($icon_array);	
		}
		
		for($i=$start; $i<$start+$limit; $i++)
		{
			if($i<count($icon_array))
			{
				$icon_path = $icon_array[$i];
			
				preg_match("/([\w-]+)\.png$/", $icon_path, $matches);
			
				$icons[] = array(
					'id' => $matches[1],
					'icon' => $matches[1]
				);
			}
		}
		
		$metadata = array(
			'idProperty' => 'id',
			'root' => 'icons',
			'totalProperty' => 'results',
			'successProperty' => 'success',
			
			// This is the base level ItemRecord definition
			// The schema for each item maps its keywords onto other fields
			
			'fields' => array(
				array(
					'name' => 'icon',
					'type' => 'string' )
			)

		);
				
		$response = array(
			'success' => TRUE,
			'results' => count($icon_array),
			'metaData' => $metadata,
			'icons' => $icons );
			
		$this->apprequest->do_response($response);
	}

	// --------------------------------------------------------------------

	/**
	* 	Logout - this destroys the session
	*
	* 	@url		/admin/logout
	* 	@return		JSON logout response
	*/

	public function logout()
	{
		$this->apprequest = new Apprequest;
		
		if(!$this->apprequest->login()) { return; }
		
		$this->apprequest->logout();
		
		$this->apprequest->do_status_response();
	}


	


}
?>