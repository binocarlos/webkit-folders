<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Tree Controller
 *
 * Controller for the data methods of navigation trees
 *
 */
 
// ------------------------------------------------------------------------ 

class Tree_Controller extends Controller
{
	// --------------------------------------------------------------------

	/**
	* 	provides hierarchical JSON data of every folder in the system
	*
	* 	@url		/tree/
	* 	@return		JSON item tree
	*/
	
	function _setup()
	{
		$this->apprequest = new Apprequest;
		if(!$this->apprequest->login()) { return FALSE; }
		$this->treemodel = new Tree_Model;
		
		return TRUE;
	}
	
	function index($id = null, $system_id = null)
	{
		if(!isset($id))
		{
			$id = $this->input->post('id');
		}
		
		if(!isset($id))
		{
			$id = $this->input->get('id');
		}
		
		if(!$this->_setup()) { return; }
		
		$root_items = $this->treemodel->load_folder_tree($id, $system_id);
		
		// if there is only one item - we check to see if it has l == 1 (i.e. it is the system)
		// if so - we want to print back only its children!
		
		// this means if there is only one system - we want that back as the root
		if(count($root_items->astree)==1)
		{
			$first_child = $root_items->astree[0];
			
			// this is a system top-level object
			if(!$first_child->has_parent())
			{
				$new_tree_data = array();
				
				foreach($first_child->children as $child)
				{
					$new_tree_data[] = $child;
				}
			
				$root_items->astree = $new_tree_data;	
			}
		}

		foreach($root_items->asarray as $childitem)
		{
			$childitem->clean_for_output();
		}

		$this->apprequest->do_response($root_items->astree);
	}
}
?>