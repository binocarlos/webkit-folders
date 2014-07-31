<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Apprequest Library
 *
 * Library object for dealing with common application issues such as authentication and json_output
 *
 */
 
// ------------------------------------------------------------------------ 

class Apprequest
{
	// --------------------------------------------------------------------

	/**
	* 	Performs the login sequence first checking if the PHP session is set then if username,password params are supplied
	*	This will result in the PHP session being logged in if succesful
	*
	* 	@access	public
	* 	@return	boolean
	*/	
	function login()
	{
		if(!$this->is_logged_in())
		{
			if(!$this->try_login())
			{
				$this->do_error_response();
			
				return FALSE;
			}
		}

		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	* 	Attempts to login using the username,password params against the admins table
	*
	* 	@access	public
	* 	@return	boolean
	*/		
	
	function try_login()
	{					
		if(!$this->try_item_login())
		{
			if(!$this->try_db_login())
			{
				if(installation::is_pageloc())
				{
					return $this->try_pageloc_login();
				}
				else
				{
					return false;
				}
			}
			else
			{
				return true;
			}
		}
		else
		{
			return true;
		}
	}
	
	function try_pageloc_login()
	{
		$input = Input::instance();
			
		$username = $input->post('username');
		$password = $input->post('password');
		
		if(empty($username) || empty($password))
		{
			return false;
		}
		
		$factory = new Itemfactory_Model();
		
		$accounts = $factory->item_types('account')->
		keyword_query('email', $username)->
		keywords()->
		load();

		foreach($accounts->asarray as $account)
		{
			if(strtolower($account->password) == md5(strtolower($password)))
			{
				$_SESSION['login_id'] = $account->database_id();
        		$_SESSION['login_name'] = $account->email;
        		$_SESSION['installation_id'] = $account->account_installation_id;
        		
        		installation::switch_installations($account->account_installation_id);
        		
        		return true;
			}
		}
		
		return false;		
	}
	
	function try_item_login()
	{
		$input = Input::instance();
			
		$username = $input->post('username');
		$password = $input->post('password');
		
		if(empty($username) || empty($password))
		{
			return false;
		}
		
		$factory = new Itemfactory_Model();
		
		$users = $factory->item_types('user')->
		keyword_query('email', $username)->
		keywords()->
		load();
		
		foreach($users->asarray as $user)
		{
			if(strtolower($user->password) == strtolower($password))
			{
				$_SESSION['login_id'] = $user->database_id();
        		$_SESSION['login_name'] = $user->email;
        		
        		return true;
			}
		}
		
		return false;
	}
	
	// this will login users based on the admins table (xara legacy)
	function try_db_login()
	{
		$input = Input::instance();
		$db = statictools::database_instance();
			
		$username = $input->post('username');
		$password = $input->post('password');
		
		if(!isset($username) || !isset($password) || !preg_match("/^[A-Za-z0-9_@*]+$/",$username))
		{
			return FALSE;
		}
		
		$md5 = md5($password);
		
		$result = $db->getwhere('admins', array('login_name' => $username, 'password' => $md5), 1);
		
		if($result->count()<=0)
		{
			return FALSE;
		}
		
		$user = $result->current();

        $_SESSION['login_id'] = $user->admin_id;
        $_SESSION['login_name'] = $user->login_name;
		$_SESSION['permissions'] = $user->permissions;
		
		return TRUE;
	}
	
	
	// --------------------------------------------------------------------

	/**
	* 	Destroys the session being used for the current login
	*
	* 	@access	public
	* 	@return	boolean
	*/	
	
	function logout()
	{
		session_destroy();
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	* 	Tells you whether there is currently an active logged in session
	*
	* 	@access	public
	* 	@return	boolean
	*/	
	
	function is_logged_in()
	{
		if (!isset ($_SESSION)) session_start();
		
		if (	!isset($_SESSION['login_id']) ||
      			!isset($_SESSION['login_name']) ||
      			!preg_match("/^[0-9]+$/",$_SESSION['login_id']))
  		{
  			return FALSE;
		}
		else
		{
			if(installation::is_pageloc())
			{
				installation::id($_SESSION['installation_id']);
			}
			
			return TRUE;
		}
	}
	
	// --------------------------------------------------------------------

	/**
	* 	Shortcut for do_response with an error code and optional message
	*
	* 	@access	public
	* 	@return NULL
	*/	
	
	function do_error_response($message = 'Incorrect Login Details')
	{
		$this->do_response(array('status' => 'error', 'desc' => $message));	
	}
	
	// --------------------------------------------------------------------

	/**
	* 	Shortcut for do_response with a status message
	*
	* 	@access	public
	* 	@return NULL
	*/	
	
	function do_status_response($message = 'Request Completed')
	{
		$ret = array('status' => 'ok');
		
		if(is_string($message))
		{
			$ret['desc'] = $message;	
		}
		else if(is_array($message))
		{
			foreach($message as $key => $value)
			{
				$ret[$key] = $value;
			}
		}
		
		$this->do_response($ret);
	}
	
	// --------------------------------------------------------------------

	/**
	* 	Outputs the given data structure as JSON to the browser - this should be the last thing the controller does
	*
	* 	@access	public
	* 	@return NULL
	*/		
	
	function do_response($data)
	{
		$response = json_encode($data);
		
		$devmode = Kohana::config('webkitfoldersinstall.json_dev_mode');
		
		if($devmode != false)
		{
			$response = $this->jsonReadable($response);
		}

		echo $response;
	}
	
	/**
	* 	gives you a JSON array of the given item array
	*	the JSON object returned is populated with meta-data for the ext.js Store
	*	this is so you can change field names of the item table without messing up the javascript
	*
	* 	@url		/item/
	* 	@return		JSON item array
	*/
	
	function get_item_json_packet($item_array)
	{
		$metadata = array(
			'idProperty' => 'id',
			'root' => 'items',
			'totalProperty' => 'results',
			'successProperty' => 'success',
			
			// This is the base level ItemRecord definition
			// The schema for each item maps its keywords onto other fields
			
			'fields' => array(
				array(
					'name' => 'name',
					'type' => 'string' ),
						
				array(
					'name' => 'parent_id',
					'type' => 'float' ),
					
				array(
					'name' => 'item_type',
					'type' => 'string' ),
					
				array(
					'name' => 'keywords' )
					
			)

		);
				
		$response = array(
			'success' => TRUE,
			'results' => count($item_array),
			'metaData' => $metadata,
			'items' => $item_array );
			
		return $response;
	}
	
	
	// --------------------------------------------------------------------

	/**
	* 	Converts the given JSON string into a much nicer and readable format
	*	Not required for production servers
	*
	* 	@access	public
	* 	@return string
	*/	
	
	function jsonReadable($json, $html=FALSE)
	{ 
		$tabcount = 0; 
    	$result = ''; 
    	$inquote = false; 
    	$ignorenext = false; 
    
    	if ($html)
    	{ 
        	$tab = "&nbsp;&nbsp;&nbsp;"; 
        	$newline = "<br/>"; 
    	}
    	else
    	{ 
        	$tab = "\t"; 
        	$newline = "\n"; 
    	} 
    
    	for($i = 0; $i < strlen($json); $i++)
    	{ 
        	$char = $json[$i]; 
        
        	if ($ignorenext)
        	{ 
            	$result .= $char; 
            	$ignorenext = false; 
        	}
        	else
        	{ 
            	switch($char)
            	{ 
                	case '{': 
	                    $tabcount++; 
                    	$result .= $char . $newline . str_repeat($tab, $tabcount); 
                    	break; 
                	case '}': 
	                    $tabcount--; 
                    	$result = trim($result) . $newline . str_repeat($tab, $tabcount) . $char; 
                    	break; 
                	case ',': 
	                    $result .= $char . $newline . str_repeat($tab, $tabcount); 
                    	break; 
                	case '"': 
	                    $inquote = !$inquote; 
                    	$result .= $char; 
                    	break; 
                	case '\\': 
	                    if ($inquote) $ignorenext = true; 
                    	$result .= $char; 
                    	break; 
                	default: 
	                    $result .= $char; 
            	} 
        	} 
    	} 
    
    	return $result; 
	}
	
	function make_upload_folder()
	{
		$tools = new Tools();
		
		return $tools->make_upload_folder();
	}
	
	
	
}
?>