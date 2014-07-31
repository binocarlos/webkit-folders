<?php defined('SYSPATH') OR die('No direct access allowed.');

class Session_Model extends Simpleorm_Model
{
	protected $_time_to_live = 86400;//60 * 60 * 24; // 24 hours
	protected $_id_field = 'session_id';
	protected $_table_name = 'item_session';
	protected $_fields = array('installation_id', 'session_id', 'ip_address', 'created', 'modified', 'expired', 'data');
    
    function __construct($id = NULL, $ttl = null)
    {
    	$this->clear_data();
    	
        parent::__construct($id);
        
        $this->setup_auto_save();
    }
    
    function setup_auto_save()
    {
		if($this->exists())
        {
			// Enable auto session saving
			Event::add('system.post_controller', array($this, 'do_auto_save'));
			Event::add('system.redirect', array($this, 'do_auto_save'));
		}
    }
    
    function do_auto_save()
    {
    	if($this->exists())
    	{
			$this->modified = date("Y-m-d H:i:s");
			$this->save();
		}
    }
    
    function create()
    {
    	$this->generate();
  
    	parent::create();
    	
    	$this->setup_auto_save();
    }
    
    function is_alive($ttl = null)
    {
    	if(empty($ttl))
    	{
    		$ttl = $this->_time_to_live;
    	}
    	
    	$current_gap = statictools::get_date_string_gap_milliseconds($this->modified);
    	
    	if($current_gap > $ttl)
    	{
    		return false;
    	}
    	else
    	{
    		return true;
    	}
    }
    
    function expire()
    {
    	$this->expired = date("Y-m-d H:i:s");
    }
    
    function generate()
    {
    	$this->session_id = md5(uniqid(microtime()) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    	$this->ip_address = $_SERVER['REMOTE_ADDR'];
    	$this->created = date("Y-m-d H:i:s");
    	$this->modified = date("Y-m-d H:i:s");
    }
       
    function set_data($new_data = NULL)
    {
    	parent::set_data($new_data);

    	if(!empty($this->data))
    	{
    		$this->data = unserialize($this->data);
    	}
    	else
    	{
    		$this->clear_data();
    	}
    	
    	if(!empty($this->expired) && $this->expired != '0000-00-00 00:00:00')
    	{
    		$this->clear_data();
    	}
    }
    
    function clear_data()
    {
    	$this->data = new StdClass();
    }
    
    function get_data()
    {
    	$data = parent::get_data();
    	
    	$data['data'] = serialize($data['data']);

    	return $data;
    }
    
    function session_get($prop)
    {
    	return $this->data->$prop;
    }
    
	function session_set($prop, $val)
    {
    	$this->data->$prop = $val;
    	
    	return $val;
    }
    
    function session_wipe($prop)
    {
    	$ret = $this->session_get($prop);
    	$this->session_delete($prop);
    	return $ret;
    }
    
    function session_delete($prop)
    {
    	unset($this->data->$prop);	
    }
}
?>