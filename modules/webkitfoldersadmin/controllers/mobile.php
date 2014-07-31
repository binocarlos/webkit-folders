<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Mobile Controller
 *
 * Controller for the basic admin system accessed from phones (basically a simple flat HTML version of the admin app)
 *
 */
 
// ------------------------------------------------------------------------ 
//

class Mobile_Controller extends Controller
{
	// --------------------------------------------------------------------
	
	function _setup()
	{
		$this->apprequest = new Apprequest;
		$this->adminmodel = new Admin_Model;
		$this->treemodel = new Tree_Model;
		
		return TRUE;
	}
	
	function save($id)
	{
		$field_type_map = array(
			'string' => 'text',
			'date' => 'text',
			'text' => 'textarea',
			'comment' => 'textarea',
			'html' => 'textarea'
		);
		
		$item = new Item_Model($id);
		$fields = $item->get_fields();
		
		foreach($fields as $field)
		{
			$field_name = $field['name'];
			$field_type = $field['type'];
			
			$gui_type = $field_type_map[$field_type];
			
			if(isset($gui_type))
			{
				$save_value = $this->input->post($field_name);
				
				if($field_name == 'name')
				{
					$item->name = $save_value;
					$item->save();
				}
				else
				{
					$item->replace_field($field_name, $save_value);
				}
			}
		}
		
		$response = <<<EOT
<meta http-equiv="refresh" content="0;url=/app/mobile">
EOT;

		echo $response;
	}
	
	function index()
	{
		$this->_setup();
		
		$disk_item = foldersystem::load_top_level_item('default', 'disk');
		
		if(!isset($disk_item))
		{
			echo 'Sorry - there is no disk item';
			return;	
		}
		
		$root_items = $this->treemodel->load_mobile_tree($disk_item, $disk_item->system_id);
		
		$view = new View('mobile/iphone');
		
		$view->top_item = $disk_item;
		$view->root_items = $root_items->astree;
		
		$view->render(TRUE);
	}
}
?>