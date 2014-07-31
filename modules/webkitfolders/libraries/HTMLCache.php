<?php defined('SYSPATH') OR die('No direct access allowed.');

// HTMLParser - a library able to load a page and replace
// tags with values

class HTMLCache
{
	protected $cache_folder = '';
	protected $cache_name = '';
	protected $cache_url = '';
	protected $cache_contents = '';
		
	public function __construct($name, $paths)
	{
		Itemtrigger_Model::ensure_triggers_exist($name, $paths);
		
		$this->cache_folder = Kohana::config('htmlcache.cachefolder');
		$this->cache_name = $name;
		$this->cache_url = preg_replace('/\/$/', '', $_SERVER['SCRIPT_URL']);
		
		if(file_exists($this->cache_file_path()))
		{
			if(preg_match('/^y/i', $_GET['nocache']))
			{
				$this->remove_cache_file();
			}
			else
			{
				$this->cache_contents = file_get_contents($this->cache_file_path());
			}
		}
	}
	
	public function remove_cache_file()
	{
		unlink($this->cache_file_path());
	}
	
	public static function remove_cache_folder($folder)
	{
		if(empty($folder)) { return; }
		
		$cache_folder = Kohana::config('htmlcache.cachefolder').'/'.$folder;
		
		if(empty($cache_folder)) { return; }
		
		if($cache_folder=='/') { return; }
		
		$tools = new Tools();
		
		$tools->recursive_remove_directory($cache_folder);
	}
	
	public function set_cache_contents($content)
	{
		if(preg_match('/^y/i', $_GET['nocache'])) { return; }
		
		if(!file_exists($this->cache_folder_path()))
		{
			mkdir($this->cache_folder_path(), 0777, true);
		}
		
		file_put_contents($this->cache_file_path(), $content);
	}
	
	public function has_cache()
	{
		return !empty($this->cache_contents);
	}
	
	public function cache_contents()
	{
		return $this->cache_contents;	
	}
	
	public function cache_folder_path()
	{
		return $this->cache_folder().'/'.$this->cache_name().$this->cache_url();
	}
	
	public function cache_file_path()
	{
		$querystring = $_SERVER['QUERY_STRING'];
		
		if(empty($querystring))
		{
			$querystring = 'cache';
		}
		
		return $this->cache_folder_path().'/'.$querystring.'.htm';
	}
	
	public function cache_name()
	{
		return $this->cache_name;
	}
	
	public function cache_folder()
	{
		return $this->cache_folder;
	}
		
	public function cache_url()
	{
		return $this->cache_url;
	}
	
	
}
?>