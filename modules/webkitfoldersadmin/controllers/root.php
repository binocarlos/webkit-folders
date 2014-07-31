<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Admin Controller
 *
 * Controller for generic application requests such as authentication
 *
 */
 
// ------------------------------------------------------------------------ 

class Root_Controller extends Controller
{
	// --------------------------------------------------------------------
	
	public function index()
	{
		$this->check_login();

		$model = new Root_Model();
		
		$results = $model->load_installations();
		
		$rows = '';
		
		$current_id = NULL;
		
		foreach($results->asarray as $system_obj)
		{
			$installation_name = $system_obj->installation_name;
			$system_name = $system_obj->system_name;
			$installation_id = $system_obj->installation_id;
			$system_id = $system_obj->system_id;
	
			if($installation_id!=$current_id)
			{

				$rows .= <<<EOT
<tr>
<td colspan=4 height=15 bgcolor=#cccccc>&nbsp;</td>
</tr>					
EOT;
				
				$current_id = $installation_id;
				
				$rows .= <<<EOT
<tr>
<td colspan=2><b>$installation_name ($installation_id)</b></td>
<td colspan=2>
<!--<a href="/app/root/fix_installation/$installation_id">fix</a> - -->
<a href="/app/root/delete_installation/$installation_id">delete</a>
</td>
</tr>				
<tr>
<td colspan=3><form method="POST" action="/app/root/add_system/$installation_id">
<input name="name"><input type="submit" value="add system">
</form>
</td>
</tr>

EOT;
			}
				
			$rows .= <<<EOT
<tr bgcolor=#ffcccc>
<td>&nbsp;</td>			
<td>$system_name</td>
<!--<td><a href="/app/root/create_urls/$installation_id/$system_id">urls</a> - <a href="/app/root/rebuild_system/$installation_id/$system_id">rebuild</a> - <a href="/app/root/fix_system/$installation_id/$system_id">fix</a> - <a href="/app/root/old_fix_system/$system_id">old fix</a></td>-->
<td><a href="/app/root/create_urls/$installation_id/$system_id">urls</a></td>
<td><a href="/app/root/dump_models/$installation_id">dump_models</a> - <a href="/app/root/upload_files/$installation_id/$system_id">upload</a> - <a href="/app/root/insert_xml/$installation_id/$system_id">insert xml</a></td>
<td><a href="/app/root/delete_system/$installation_id/$system_id">delete</a></td>
</tr>			
EOT;
		}
		
		$uri = $this->uri->string();
		
		$page = <<<EOT
<a href="/app/root/fix_files">Fix Files</a><hr>

<form method="POST" action="/app/root/add_installation">
<input name="name">
<input type="submit" value="add installation">
</form>
<hr>
<table border=1 cellpadding=10>	
<tr>
<td>Installation</td>
<td>System</td>
<td colspan=2>&nbsp;</td>
</tr>
$rows
</table>
EOT;
		
		echo $page;
		
	}
	
	private function check_login()
	{
		session_start();
		
		if($_SESSION['root_logged_in'] == 'yes')
		{
			return TRUE;
		}
		
		if($this->input->post('password')!=Kohana::config('webkitfoldersinstall.root_password'))
		{
			$this->print_password_form();
			exit;	
		}
		
		$_SESSION['root_logged_in'] = 'yes';
	}
	
	public function dump_models($installation_id, $new_installation_id)
	{
		if(empty($new_installation_id))
		{
			echo 'append move to installation id';
			return;
		}
		
		installation::switch_installations($installation_id);
		
		$models = new Item_Model('models:/', true);
		
		$children = $models->load_children();
		$tree_model = new Tree_Model();
		
		foreach($children->asarray as $child)
		{
			$child->load_keywords();	
		}
		
		installation::switch_installations($new_installation_id);
		
		$new_models = new Item_Model('models:/', true);

		foreach($children->asarray as $child)
		{
			$child->create_carbon_copy();
			
			$tree_model->copy_child_to_parent($child, $new_models);
		}
		
		echo 'done';
	}
	
	private function print_password_form()
	{
		$html = <<<EOT
	<form method="POST" action="/app/root">
	<input name="password">
	<input type="submit" value="enter password">
	</form>	
EOT;

		echo $html;
	}

	public function insert_xml($installation_id, $system_id)
	{
		$this->check_login();
		
		$html = <<<EOT
	<form method="POST" action="/app/root/insert_xml_submit">
	<input type="hidden" name="installation_id" value="$installation_id">
	<input type="hidden" name="system_id" value="$system_id">
	mode: <input type="radio" name="runmode" value="test" checked> test - <input type="radio" name="runmode" value="real"> real<br>
	node name (translation_item): <input name="node_name"><br>
	item id attribute (id): <input name="id_attribute"><br>
	keyword name attribute (keyword): <input name="keyword_attribute"><br>
	content node name (content): <input name="content_node_name"><br><br>
	XML: <textarea style="width:400px;height:200px;" name="xml_content"></textarea><br><br>
	<input type="submit">
	</form>	
EOT;

		echo $html;
	}
	
	public function insert_xml_submit()
	{
		$this->check_login();
		
		$installation_id = $this->input->post('installation_id');
		$system_id = $this->input->post('system_id');
		
		installation::$installation_id = $installation_id;
		
		$runmode = $this->input->post('runmode');
		$node_name = $this->input->post('node_name');
		$id_attribute = $this->input->post('id_attribute');
		$keyword_attribute = $this->input->post('keyword_attribute');
		$content_node_name = $this->input->post('content_node_name');
		$xml_content = $this->input->post('xml_content');
		
		$xml = new SimpleXMLElement($xml_content);

		foreach($xml->$node_name as $itemnode)
		{
			$id = (String)$itemnode[$id_attribute];
			$keyword = (String)$itemnode[$keyword_attribute];
			$value = (String)$itemnode->$content_node_name;
			
			$item = Itemfactory_Model::instance($id, true);
			
			if($runmode=='real')
			{
				$item->replace_field($keyword, $value);
			}
			
			echo "Item = {$item->name} - <b style='color:Red;'>{$item->id}</b><br>";
			echo "Set <b style='color:Red;'>$keyword</b> = $value<p>";
		}
		/*
		$item = Itemfactory_Model::instance($id, true);
		
		$item->replace_field($field_name, $value);
		*/
		
		echo 'done';
		//echo $xml_content;
	}
	
	public function fix_files()
	{
		$this->check_login();
		
		$root_model = new Root_Model();
		
		$root_model->fix_files();
		
		echo "Finish";
	}
	
	public function upload_files($installation_id, $system_id)
	{
		$this->check_login();
		
		$html = <<<EOT
	<form method="POST" action="/app/root/upload_files_submit">
	<input type="hidden" name="installation_id" value="$installation_id">
	<input type="hidden" name="system_id" value="$system_id">
	insert into path: <input name="path"><br>
	physical folder: <input name="folder"><br>
	folder model: <input name="foldermodel" value="folder"><br>
	model: <input name="model"><br>
	field: <input name="field"><br>
	mode: <input name="mode" value="normal"><br>
	<input type="submit">
	</form>	
EOT;

		echo $html;
	}
	
	public function upload_files_submit()
	{
		$this->check_login();
		
		$installation_id = $this->input->post('installation_id');
		$system_id = $this->input->post('system_id');
		
		installation::$installation_id = $installation_id;
		
		$path = $this->input->post('path');
		$folder = $this->input->post('folder');
		$foldermodel = $this->input->post('foldermodel');
		$model = $this->input->post('model');
		$field = $this->input->post('field');
		$mode = $this->input->post('mode');
		
		$parent_item = Itemfactory_Model::instance($path);
		
		$parser = new FolderParser($folder, $model, $field, $foldermodel, $mode);
		
		$defs = $parser->get_item_defs();
		
		$parent_item->auto_create_children($defs);
		
		url::redirect('/app/root');
		
	}
	
	public function upload_filesauctionhtml_submit()
	{
		//$this->check_login();
		
		$this->tools = new Tools();
		
		$installation_id = 46;
		$system_id = 45;
		
		installation::$installation_id = $installation_id;
		
		$path = $this->input->post('path');
		$model = $this->input->post('model');
		$field = $this->input->post('field');
		$name = $this->input->post('name');
		$redirect = $this->input->post('redirect');

		$parent_item = Itemfactory_Model::instance($path);
		
		$upload_data = $this->tools->save_uploaded_file('file');
		
		$img_info = new StdClass();
    	$img_info->name = $name;
    	$img_info->item_type = $model;
    	$img_info->children = array();		
    	$img_info->fields[$field] = array(
			'size' => $upload_data['file_size'],
    		'type' => $upload_data['file_type'],
    		'file' => $upload_data['file_name'],
    		'folder' => $upload_data['relative_folder']
		);	
		
		$top_items = array($img_info);
		
		$parent_item->auto_create_children($top_items);
		
		url::redirect($redirect);
		
	}	

	public function upload_multi_submit()
	{
		//$this->check_login();
		
		$this->tools = new Tools();
		
		$installation_id = $this->input->post('installation_id');
		$system_id = $this->input->post('system_id');
		
		installation::$installation_id = $installation_id;
		
		$path = $this->input->post('path');
		$model = $this->input->post('model');
		$field = $this->input->post('field');
		$name = $this->input->post('name');
		$redirect = $this->input->post('redirect');

		$parent_item = Itemfactory_Model::instance($path);
		
		$upload_data = $this->tools->save_uploaded_file('file');
		
		$img_info = new StdClass();
    	$img_info->name = $name;
    	$img_info->item_type = $model;
    	$img_info->children = array();		
    	$img_info->fields[$field] = array(
			'size' => $upload_data['file_size'],
    		'type' => $upload_data['file_type'],
    		'file' => $upload_data['file_name'],
    		'folder' => $upload_data['relative_folder']
		);	
		
		$top_items = array($img_info);
		
		$parent_item->auto_create_children($top_items);
		
		url::redirect($redirect);
		
	}
	
	public function fix_up_news_titles()
	{
		$this->check_login();
		
		$factory = new Itemfactory_Model();
		
		$items = $factory->item_types('news_release')->keywords()->load();
		
		foreach($items->asarray as $item)
		{
			if(empty($item->title))
			{
				$item->create_field('title', $item->name);
			}
		}
	}
	
	public function add_system($installation_id)
	{
		$this->check_login();
		
		$installation = installation::instance($installation_id);
		
		installation::build_system($this->input->post('name'));
		
		url::redirect('root');
	}
	
	public function add_installation()
	{
		$this->check_login();
		
		$name = $this->input->post('name');
		
		$root_model = new Root_Model();
		
		$id = $root_model->create_installation($name);
		
		url::redirect('root');
	}
	
	function info()
	{
		phpinfo();
	}
	
	function fix_installation($installation_id)
	{
		$this->check_login();
		
		$root_model = new Root_Model();
		
		$root_model->fix_installation($installation_id);
		
		url::redirect('root');
	}
	
	function create_all_urls()
	{
		$this->check_login();
		
		$this->rootmodel = new Root_Model;
		
		$results = $this->rootmodel->load_installations();
		
		foreach($results->asarray as $system_obj)
		{
			$installation_id = $system_obj->installation_id;
			$system_id = $system_obj->system_id;		
		
			$this->rootmodel->create_urls($installation_id, $system_id);
		}
		
		url::redirect('root');
	}
	
	function create_urls($installation_id, $system_id)
	{
		$this->check_login();
		
		$this->rootmodel = new Root_Model;
		
		$this->rootmodel->create_urls($installation_id, $system_id);
		
		url::redirect('root');
	}
	
	public function old_fix_system($system_id)
	{
		$this->check_login();
		
		$this->rootmodel = new Root_Model();
		
		$this->rootmodel->fix_old_tree($system_id);
		
		url::redirect('root');
	}
	
	public function fix_system($installation_id, $system_id)
	{
		$this->check_login();
		
		$this->rootmodel = new Root_Model();
		
		$this->rootmodel->fix_tree($system_id);
		
		url::redirect('root');
	}
	
	function rebuild_system($installation_id, $system_id)
    {
    	$this->check_login();
    	
    	$root_model = new Root_Model();
		
		$root_model->rebuild_installation($installation_id, $system_id);
		
		url::redirect('root');
    }
    
	function delete_installation($id)
    {
    	$this->check_login();
    	
    	$installation = installation::instance($id);
		
		if(!isset($installation))
		{
			throw new Kohana_User_Exception("No installation found", "cannot delete installation - cannot find it!");
		}
		
		$html = <<< EOT
		
Are you sure?<p>
<a href="/app/root/confirm_delete_installation/$id">Yes</a><p>
<a href="/app/root/">No</a>		
EOT;

		echo $html;
    }
    
    public function confirm_delete_installation($id = NULL)
	{
		$this->check_login();
		
		if(!isset($id))
		{
			$id = $this->input->post('id');
		}
		
		$model = new Root_Model();
		
		$model->delete_installation($id);
		
		url::redirect('root');
	}
	
	function delete_system($installation_id, $system_id)
    {
    	$this->check_login();
    	
    	$installation = installation::instance($installation_id);
    	$system = foldersystem::instance($system_id);
		
		if(!isset($system))
		{
			throw new Kohana_User_Exception("No system found", "cannot delete system - cannot find it! - $id");
		}
		
		$html = <<< EOT
		
Are you sure?<p>
<a href="/app/root/confirm_delete_system/$installation_id/$system_id">Yes</a><p>
<a href="/app/root/">No</a>		
EOT;

		echo $html;
    }
    
    public function confirm_delete_system($installation_id, $system_id)
	{
		$this->check_login();
		
		if(!isset($id))
		{
			$id = $this->input->post('id');
		}
		
		$model = new Root_Model();
		
		installation::instance($installation_id);
		$model->delete_system($system_id);
		
		url::redirect('root');
	}	
	


}
?>