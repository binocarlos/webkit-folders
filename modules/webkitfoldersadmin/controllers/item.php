<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Item Controller
 *
 * Controller for the data methods concerning a single item
 * If you want to get information about an item (including its children) or update it this is where its done
 *
 */
 
// ------------------------------------------------------------------------ 

class Item_Controller extends Controller
{
	// --------------------------------------------------------------------

	/**
	* 	gives you a JSON array of items contained within a given item - if no id is given then the 'root' item is assumed
	*
	* 	@url		/item/
	*
	*	@params
	*		id = the id of the item for which to load the children items
	*
	* 	@return		JSON item array
	*/
	
	function _setup()
	{
		$this->apprequest = new Apprequest;
		if(!$this->apprequest->login()) { return FALSE; }
		$this->adminmodel = new Admin_Model;
		$this->treemodel = new Tree_Model;
		
		return TRUE;
	}
	
	function index()
	{
		$sql = $this->input->post('sql');
			
		$html = <<<EOT
	<form method="POST" action=".">
	<textarea name="sql" style="width:600px;height:200px;">$sql</textarea>
	<br>
	<input type="submit">
	</form>
	<hr>
	$sql
EOT;

		echo $html;
	}
	
	function item_data($id = NULL)
	{
		////////////////////////////////
		// variables

		if(!isset($id))
		{
			$id = $this->input->post('id');
		}
		
		if($id == 'root')
		{
			$id = NULL;
		}
		
		////////////////////////////////
		// login & libs
		
		if(!$this->_setup()) { return; }
		
		$item = new Item_Model($id, true);
		
		//print_r($item);
		//exit;
		//$item->load_keywords();

		//$item->load_path();
		
		$item->clean_for_output();
		
		// dump the cleaned item with its children back as JSON	
		$this->apprequest->do_response($item);
	}
	
	function children($id = NULL)
	{
		////////////////////////////////
		// variables
		
		if(!isset($id))
		{
			$id = $this->input->post('id');
		}
		
		if($id == 'root')
		{
			$id = NULL;
		}
		
		////////////////////////////////
		// login & libs
		
		if(!$this->_setup()) { return; }
		
		$item = new Item_Model($id);
		$item->load_keywords();
		/*
		$children = $item->load_children_with_keywords(false, array(
			'words' => array('foldericon')
		));
		*/
		
		$children = $item->load_children_with_keywords(false, array(
			'group' => array(
				'item_keyword.name' => 'foldericon',
				'item_keyword.field_type' => 'image'
			)
		));
		
		$item->clean_for_output();

		foreach($children->asarray as $childitem)
		{
			if($childitem->link_type=='ghost')
			{
				$childitem->name = $childitem->name.' (G)';
			}
			
			$childitem->clean_for_output();
		}
		
		// dump the cleaned item with its children back as JSON	
		$this->apprequest->do_response($item);
	}
	
	function sitemap($id = NULL)
	{
		////////////////////////////////
		// variables
		
		if(!isset($id))
		{
			$id = $this->input->post('id');
		}
		
		if($id == 'root')
		{
			$id = NULL;
		}
		
		$vertical = $this->input->get('vertical');
		
		////////////////////////////////
		// login & libs
		
		if(!$this->_setup()) { return; }
		
		$root_items = $this->treemodel->load_item_tree($id);
		
		if(!empty($vertical))
		{
			echo '<table border=0><tr><td>';
		}
		else
		{
			echo '<table border=0><tr>';
		}
		
		foreach($root_items->astree as $root_item)
		{
			echo $root_item->get_sitemap_div($vertical);
		}
		
		if(!empty($vertical))
		{
			echo '</td></tr></table>';
		}
		else
		{
			echo '</tr></table>';
		}
	}
	
	
	/**
	* 	moves items into the given destination item
	*
	* 	@url		/itemapp/moveitems/
	*
	*	@params
	*		destination_item_id = the id of the item to move things to
	*		source_item_ids = delimited string of item ids to move to the destination
	*
	* 	@return		status hash
	*/
	
	function moveitems($source_item_ids = null, $destination_item_id = null, $move_mode = null)
	{
		////////////////////////////////
		// variables
		
		if(empty($source_item_ids))
		{
			$source_item_ids = $this->input->post('source_item_ids');
		}
		
		if(empty($destination_item_id))
		{
			$destination_item_id = $this->input->post('destination_item_id');
		}
		
		$source_item_id_array = preg_split('/:/', $source_item_ids);
		
		if($destination_item_id == 'root' || $destination_item_id == '0')
    	{
    		$destination_item_id = NULL;
    	}
    	
    	if(empty($move_mode))
    	{
    		$move_mode = $this->input->post('move_mode');
    	}
    	
    	if(count($source_item_id_array)<=0)
    	{
    		$this->apprequest->do_error_response("No items given to move");
    		return;
    	}
		
		////////////////////////////////
		// login & libs
		
		if(!$this->_setup()) { return; }
		
		statictools::dev('Loading Source Items');
		$move_items = Itemfactory_Model::factory($source_item_id_array);
		
		statictools::dev('Loading Destination Item');
		$destination_item = Itemfactory_Model::instance($destination_item_id, true);
		
		if($move_mode == 'move')
		{
			$this->treemodel->move_children_to_parent($move_items->asarray, $destination_item);
		}
		else if($move_mode == 'copy')
		{
			$this->treemodel->copy_children_to_parent($move_items->asarray, $destination_item);
		}
		else if($move_mode == 'ghost')
		{
			$this->treemodel->ghost_children_to_parent($move_items->asarray, $destination_item);
		}
				
		$destination_item_parent = $destination_item->load_parent(true);
		
		$destination_item->load_children(true, true);
		$destination_item->assign_path($destination_item_parent, true, true);
			
		$this->apprequest->do_status_response();
	}
	
	/**
	* 	deletes items and their keywords
	*
	* 	@url		/itemapp/deleteitems/
	*
	*	@params
	*		ids = delimited string of item ids to delete
	*
	* 	@return		status hash
	*/
	
	function deleteitems($delete_item_ids = null)
	{
		////////////////////////////////
		// variables
		
		if(!isset($delete_item_ids))
		{
			$delete_item_ids = $this->input->post('ids');
		}
		
		$delete_item_id_array = preg_split('/:/', $delete_item_ids);
		
		////////////////////////////////
		// login & libs
		
		if(!$this->_setup()) { return; }
		
		$delete_items = Itemfactory_Model::factory($delete_item_id_array);
		$clear_cache_paths = array();
		
		$parent_id = NULL;
		
		foreach($delete_items->asarray as $delete_item)
		{
			if(!isset($parent_id))
			{
				$parent_id = $delete_item->parent_id;
				$clear_cache_paths[] = $delete_item->path;
			}
			else if($delete_item->parent_id != $parent_id)
			{
				throw new Kohana_User_Exception("Cannot delete items", "cannot delete items from 2 places at the same time");
			}
		}
		
		$parent_item = Itemfactory_Model::instance($parent_id);
		
		$moved_to = $this->treemodel->delete_items_from_parent($delete_items, $parent_item);
		
		$bin_id = null;
		
		if(isset($moved_to))
		{
			$bin_id = $moved_to->id;
		}
		
		foreach($clear_cache_paths as $clear_cache_path)
		{
			Itemtrigger_Model::clear_cache_for_item_path($clear_cache_path);
		}
			
		$this->apprequest->do_response(array(
			'status' => 'ok',
			'bin_id' => $bin_id
		));
	}
	
	/**
	* 	renames an item - i.e. updates its name field
	*
	* 	@url		/itemapp/renameitem/
	*
	*	@params
	*		item_id = the id of the item whos name to update
	*		name = the name to change it to
	*
	* 	@return		status hash
	*/
	
	function renameitem()
	{
		////////////////////////////////
		// variables
		
		$item_id = $this->input->post('id');
		$name = $this->input->post('name');
		
		////////////////////////////////
		// login & libs
		
		if(!$this->_setup()) { return; }
		
		$item = Itemfactory_Model::instance($item_id, true);
		
		$this->treemodel->rename_item($item, $name);
			
		$this->apprequest->do_status_response();
	}
	
	/**
	* 	saves an item
	*
	*	this converts the JSON string provided into keyword data ready for the item
	*
	* 	@url		/itemapp/saveitem/
	*
	*	@params
	*		id = the id of the item which is being saved
	*		json = a json encoded string representing the items data - this includes:
	*
	*			id
	*			fields = hash of name,value pairs representing the field data
	*			keywords = array of name,value pairs representing keywords
	*
	* 	@return		status hash
	*/
	
	function saveitem()
	{
		////////////////////////////////
		// variables
		
		$item_id = $this->input->post('id');
		$parent_id = $this->input->post('parent_id');
		$json_data = $this->input->post('json');
		
		////////////////////////////////
		// login & libs
		
		if(!$this->_setup()) { return; }
		
		////////////////////////////////
		// so lets decode the json data
		$item_data = json_decode($json_data, TRUE);
		
		if($item_id == 0)
		{
			unset($item_id);
		}
		
		$item = Itemfactory_Model::instance($item_id);
		
		$item->save_form_data($item_data);
		
		$tree_model = new Tree_Model();
		
		if(!$item->existed())
    	{
    		$parent_item = new Item_Model($parent_id, true);
    		
    		$tree_model->add_new_child_to_parent($item, $parent_item);
    		
    		// now the item has been inserted to the tree - lets activate the storage driver hook
			$item->storage_driver_create();
			
			//$item->create_path($parent_item);
    	}
    	else
    	{
    		// the item has been saved - lets tell the storage driver
    		$item->storage_driver_save();
    	}
    	
    	$item->update_linked_keywords();
    	//$item->assign_url();
    	
    	//$tree_model->create_paths($item->system_id);
    	$tree_model->create_item_paths($item);
    	
		$response = array(
			'id' => $item->database_id()
		);
		
		$reloaded_item = new Item_Model($item->id, true);
		
		Itemtrigger_Model::clear_cache_for_item_path($reloaded_item->path);
		
		$this->apprequest->do_status_response($response);
	}
	
	
/**
	* 	uploads a file into the filestore and gives you a path back to it
	*
	* 	@url		/item/uploadfile
	*
	*	@params
	*
	*		upload = file upload
	*
	* 	@return		JSON item array
	*/
	
	function uploadfile()
	{
		////////////////////////////////
		// variables
		
		////////////////////////////////
		// login & libs
		
		$this->tools = new Tools();
		$this->apprequest = new Apprequest();

		//if(!$this->apprequest->login()) { return; }
	
		$upload_data = $this->tools->save_uploaded_file('file');
			
		$response = array(
			'status' => 'ok',
			'upload_data' => $upload_data
		);
		
		$this->apprequest->do_response($response);	
	}	
	
}
?>