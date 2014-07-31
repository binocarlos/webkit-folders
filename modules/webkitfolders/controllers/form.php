<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Form Controller
 *
 * Controller for forms being submitted from the website
 *
 *
 */
 
// ------------------------------------------------------------------------ 

class Form_Controller extends Controller
{	
	function __call($method, $arguments)
	{
		$tools = new Tools();
		
		$destination = $this->input->post('destination');
		$model = $this->input->post('model');
		
		// this might be pointing to the dev database for xara
		$dbconfig = $this->input->post('dbconfig');
		
		if(!empty($dbconfig))
		{
			statictools::set_database_config($dbconfig);
		}
		
		$ajaxMode = $this->input->post('formModelSubmitAjaxMode')=='yes';
		
		//if($this->input->post('use_recaptcha')=='y')
		if(Kohana::config('recaptcha.use_recaptcha')=='y' || $_SERVER['USE_RECAPTCHA']=='y')
		{
			$recaptchalib = Kohana::config('recaptcha.recaptchalib');
			$privatekey = Kohana::config('recaptcha.recaptchaprivatekey');
			
			require_once($recaptchalib);
			
			$resp = recaptcha_check_answer ($privatekey,
                                $_SERVER["REMOTE_ADDR"],
                                $this->input->post("recaptcha_challenge_field"),
                                $this->input->post("recaptcha_response_field"));

			if (!$resp->is_valid)
			{
				if($ajaxMode)
				{
					$this->do_json_response(array(
						wasSubmitted => 'no',
						error => $resp->error
					));
				}
				else
				{
					$this->do_redirect();
				}
				return;
			}
		}
		
		$item = Itemfactory_Model::instance($this->input->post('id'), null);
		$parent_item = Itemfactory_Model::instance($destination, null);
		
		$existed = $item->exists();
		
		$item->item_type = $model;
		$item->name = $this->input->post('name');
		
		if($item->exists())
		{
			$item->remove_keywords_from_database();
		}
		
		$name_field = $this->input->post('name_field');
					
		if(!empty($name_field))
		{
			$item->name = $this->input->post($name_field);
		}
		
		$item->save_or_create();
		
		$schema = $item->ensure_schema_model();
		
		$fields = $schema->get_fields($model);
		
		foreach($fields as $field)
		{
			$field_name = $field['name'];
			
			if(($field['type']=='image')||($field['type']=='file'))
			{
				$file_data = $tools->save_uploaded_file($field_name);
				
				$field_value = array(				
					file => $file_data["file_name"],
					folder => $file_data["relative_folder"],
					size => $file_data["file_size"],
					type => $file_data["file_type"]
				);
			}
			else
			{
				$field_value = $this->input->post($field_name);
			}
			
			if((!empty($field_value))&&($field_name != 'name'))
			{
				$item->create_field($field_name, $field_value);
			}
		}
		
		$tree_model = new Tree_Model();
		
		if(!$existed)
		{
			$tree_model->add_new_child_to_parent($item, $parent_item);
			$item->storage_driver_create();	
		}
		else
		{
			$item->storage_driver_save();	
		}
		
		//$tree_model->create_paths($item->system_id);
		$tree_model->create_item_paths($item);
		
		if($ajaxMode)
		{
			$this->do_json_response(array(
				wasSubmitted => 'yes'
			));
		}
		else
		{
			$this->do_redirect();
		}
	}
	
	function do_json_response($data)
	{
		echo json_encode($data);
	}
	
	function do_redirect()
	{
		$redirect_to = $_SERVER['HTTP_REFERER'];
		
		$passed_redirect = $this->input->post('redirect');
		
		if($passed_redirect == 'json')
		{
			echo "{status:'ok'}";
			return;
		}
		
		if(!empty($passed_redirect))
		{
			$redirect_to = $passed_redirect;
			
			if(!preg_match('/^http:\/\/', $redirect_to))
			{
				$redirect_to = 'http://'.$_SERVER['HTTP_HOST'].$redirect_to;
			}
		}
		
		url::redirect($redirect_to);
	}
}
?>