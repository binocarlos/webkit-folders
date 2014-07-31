<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Tools Library
 *
 * Useful static functions used across the system
 *
 */
 
// ------------------------------------------------------------------------ 

class statictools
{
	public static $database_config = 'default';
	
	public static function set_database_config($database_config)
	{
		statictools::$database_config = $database_config;
	}
	
	public static function database_instance()
	{
		$db = Database::instance(statictools::$database_config);
		
		return $db;
	}
	
	public static function dev($text = null)
	{
		$dev_mode = Kohana::config('webkitfoldersinstall.dev_mode');
    	
    	if($dev_mode && !empty($text))
    	{
    		echo "\n\n<hr>$text\n\n";
    	}
	}
	
	public static function devsql($title = null)
	{
		$dev_mode = Kohana::config('webkitfoldersinstall.dev_mode');
    	
    	if($dev_mode)
    	{
    		if(empty($title))
			{
				$title = 'statement';
			}
			
    		$db = statictools::database_instance();
    		
    		echo "\n\n$title:\n\n";
			echo $db->last_query();
			echo "\n\n\n<hr>\n\n\n";
    	}
	}
	
	// --------------------------------------------------------------------

	/**
	* 	Quick accessor for input parameters
	*	If the input has been given Kohana style (i.e. /controller/method/param)
	*	Then the passed variable will be the params
	*
	* 	@access	public
	* 	@return	boolean
	*/		
	
	public static function param($existing_value = NULL, $name = NULL)
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
	
	public function SendEmail($from, $to, $subject, $body)
	{
		$header  = "From: $from\n";
		$header .= "Reply-To: $from\n";
		$header .= "X-Mailer: PHP5\n";

		ini_set ( "SMTP", "mail.wk1.net" );
		
		if(mail($to,$subject,$body,$header))
		{
			return true;
		}

		return false;
	}
	
	public static function get_now_date_string()
	{
		return date("d/m/Y");
	}
	
	public static function get_now_datetime_string()
	{
		return date("d/m/Y H:i:d");
	}	
	
	public static function xara_template_output($content = NULL, $use_layout = 'layout.tmpl')
	{
		require_once('template.php');
		
		TplProcessPageHeader(NULL, $use_layout);
		
		$content = str_replace('$', '\\$', $content);
 	
 		$tpl->setCurrentBlock('CONTENT');
		$tpl->setVariable("CONTENT", $content);
		$tpl->parseCurrentBlock();

		TplProcessPageFooter();	
	}
	
	public static function rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
	{
	    // Length of character list
	    $chars_length = (strlen($chars) - 1);

	    // Start our string
	    $string = $chars{rand(0, $chars_length)};
    
	    // Generate random string
	    for ($i = 1; $i < $length; $i = strlen($string))
	    {
        	// Grab a random character from our list
        	$r = $chars{rand(0, $chars_length)};
        
        	// Make sure the same two characters don't appear next to each other
        	if ($r != $string{$i - 1}) $string .=  $r;
    	}
	    
    	// Return the string
    	return $string;
	}
	
	public static function object_to_array($mixed)
	{
    	if(is_object($mixed)) $mixed = (array) $mixed;
   		if(is_array($mixed))
   		{
        	$new = array();
        	foreach($mixed as $key => $val)
        	{
            	$key = preg_replace("/^\\0(.*)\\0/","",$key);
            	$new[$key] = statictools::object_to_array($val);
        	}
    	} 
    	else $new = $mixed;
    	
    	return $new;        
	}
	
	function isAssoc($arr)
	{
	    return array_keys($arr) !== range(0, count($arr) - 1);
	}
	
	public static function get_url_from_string($string)
	{
		$url = strtolower($string);
		
		$url = preg_replace('/[^\w\. -]/', '', $url);
		$url = preg_replace('/ +/', ' ', $url);
		$url = preg_replace('/ /', '_', $url);
		
		return $url;
	}
	
	public static function add_days_to_date_string($st, $days)
	{
		$vals = explode('/', $st);
		
		$datetime = date_create($vals[2].'-'.$vals[1].'-'.$vals[0]);
		$datetimetimestamp = date_format($datetime, 'U');
		
		$datetimetimestamp += $days * 60 * 60 * 24;
		
		$gd_a = getdate( $datetimetimestamp );
		
		$new_date = statictools::get_padded_string($gd_a['mday']).'/'.statictools::get_padded_string($gd_a['mon']).'/'.statictools::get_padded_string($gd_a['year']);
		
		return $new_date;
	}
	
	public static function get_date_epoch($date_string)
	{
		$vals = explode('/', $date_string);
		
		$datetime = date_create($vals[2].'-'.$vals[1].'-'.$vals[0]);

		$datetimetimestamp = date_format($datetime, 'U');
		
		return $datetimetimestamp;
	}
	
	public static function get_date_days($date_string)
	{
		$epoch = statictools::get_date_epoch($date_string);

		return $epoch / (60 * 60 * 24);
	}
	
	
	public static function get_date_string_gap_milliseconds($date_string)
	{
		$datetime = strtotime($date_string);
		$nowtime = time();

		return $nowtime - $datetime;
	}
	
	public static function get_date_string_gap($d)
	{
		$vals = explode('/', $d);
		
		$datetime = date_create($vals[2].'-'.$vals[1].'-'.$vals[0]);
		$datetimetimestamp = date_format($datetime, 'U');
										
		$nowdatetime = date_create('now');
		$nowdatetimetimestamp = date_format($nowdatetime, 'U');
										
		$days = statictools::count_days($datetimetimestamp, $nowdatetimetimestamp);
		
		return $days;
	}
		
	public static function count_days( $a, $b )
	{
    	$gd_a = getdate( $a );
    	$gd_b = getdate( $b );
 
    	// Now recreate these timestamps, based upon noon on each day
    	// The specific time doesn't matter but it must be the same each day
    	$a_new = mktime( 12, 0, 0, $gd_a['mon'], $gd_a['mday'], $gd_a['year'] );
    	$b_new = mktime( 12, 0, 0, $gd_b['mon'], $gd_b['mday'], $gd_b['year'] );
 
    	// Subtract these two numbers and divide by the number of seconds in a
    	//  day. Round the result since crossing over a daylight savings time
    	//  barrier will cause this time to be off by an hour or two.
    	return round( ($a_new - $b_new ) / 86400 );
	}
	
	public static function generateRandStr($length){ 
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
   
    public static function get_percent($val)
   	{
   		return number_format($val, 0);
   	}
   	
   	public static function round_for_money($val)
   	{
   		$val = round($val * 100) / 100;
   		
   		return $val;
   	}
   	
   	public static function get_money($val)
   	{
   		$val = round($val * 100) / 100;
   		
   		return number_format($val, 2, ".", "");
   		
   		/*
   		$st = ''.$val;
   		
   		if(preg_match('/^(\d+\.\d\d)/', $st, $match))
   		{
   			$val = $match[1];
   		}
   		else if($padd)
   		{
	   		if(preg_match('/^(\d+\.\d)/', $st, $match))
   			{
   				$val = $match[1].'0';
	   		}
   		}   		
   		
   		return $val;
   		*/
   	}
   	
	public static function create_session_id()
	{
		$id = md5(uniqid(microtime()) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
		
		return $id;
	}
	
	public static function get_padded_string($string, $padding = '0', $length = 2)
	{
		$string = ''.$string;
		for($i=strlen($string); $i<$length; $i++)
		{
			$string = $padding.$string;	
		}
		
		return $string;
	}
	
	public static function alphanumericPass() 
	{    
	    // Do not modify anything below here 
	    $underscores = 0; // Maximum number of underscores allowed in password 
	    $length = 8; // Length of password 
	    
	    $p =""; 
	    for ($i=0;$i<$length;$i++) 
	    {    
        	$c = mt_rand(1,7); 
        	switch ($c) 
        	{ 
	            case ($c<=2): 
                	// Add a number 
                	$p .= mt_rand(0,9);    
            	break; 
            	/*
            	case ($c<=4): 
	                // Add an uppercase letter 
                	$p .= chr(mt_rand(65,90));    
            	break; 
            	*/
            	case ($c<=6): 
	                // Add a lowercase letter 
                	$p .= chr(mt_rand(97,122));    
            	break; 
            	case 7: 
                 	$len = strlen($p); 
                	if ($underscores>0&&$len>0&&$len<($length-1)&&$p[$len-1]!="_") 
                	{ 
	                    $p .= "_"; 
                    	$underscores--;    
                	} 
                	else 
                	{ 
	                    $i--; 
                    	continue; 
                	} 
            	break;        
        	} 
    	} 
    	
    	return $p; 
	} 
	
	public static function get_folder_contents($folder)
	{
		$arr = array();
		
		if ($handle = opendir($folder)) {
    	
    	while (false !== ($file = readdir($handle))) {
    		if(preg_match('/\w/', $file))
    		{
    			$arr[] = $file;
    		}
    	}

    	closedir($handle);
    	}
    	return $arr;
	}
	
	public static function get_full_file_path($folder, $file)
	{
		$base_dir = Kohana::config('webkitfolders.full_upload_folder');
		
		$ret = $base_dir.'/'.$folder.'/'.$file;
		
		return $ret;
	}
	
	public static function js_quote($val)
	{
		$val = str_replace('\'', '\\\'', $val);	
		
		return $val;
	}

}
?>