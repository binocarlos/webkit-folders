<?php
//=======================================
//###################################
// Kayako Web Solutions
//
// Source Copyright 2001-2004 Kayako Web Solutions
// Unauthorized reproduction is not allowed
// License Number: $%LICENSE%$
// $Author: vshoor $ ($Date: 2005/06/23 00:23:48 $)
// $RCSfile: default.login.php,v $ : $Revision: 1.5 $ 
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//                   www.kayako.com
//###################################
//=======================================


if (!defined("INSWIFT")) {
	trigger_error("Unable to process $PHP_SELF", E_USER_ERROR);
}

/**
* Initialization function. You can connect to your database etc over here.
*/
function loginShareInit()
{
	global $loginshare;

	$loginshare->moduleloaded = true;
}

function CheckMagix($email, $password)
{
	// Check if login is valid at xaraonline.
        $ch = curl_init();
        $url = "https://site.magix.net/mgx_external/extcrs2/services.php";
        $posting = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE extcrs2 SYSTEM "request_check_customer_login_10.dtd">
<extcrs2>
<request id="check_customer_login" version="1.0">
<access_user>XaraOnline</access_user>
<access_pass>Fb2s8Rtc12SyxNWq</access_pass>
<email>'.$email.'</email>
<password>'.md5('lecd3xsw'.$password).'</password>
</request>
</extcrs2>';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $posting);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response=curl_exec($ch);
        curl_close($ch);
//	echo "Curl complete, --[".$response."]--<br>";

	if (strpos($response, ' status="0" ') != false)
		return true;
	else
		return false;
}

/**
* Authorize a user based on email and password
*/
function loginShareAuthorize($email, $password)
{
	global $dbCore, $_SWIFT, $loginshare, $settings;

	$_loginshare = $settings->getSection("loginshare");

	// Check if login is valid at xaraonline.
	$ch = curl_init();
        $url = "https://secure.xaraonline.com/_stratus/utils/verifyuser.aspx?user=".$email."&password=".$password;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response=curl_exec($ch);
	curl_close($ch);

//	 echo "Curl complete, --[".$response."]--<br>";
	$response_lines = preg_split('/[\n\r]+/',$response,5);
	$login_valid = substr($response_lines[0],0,2);
//	echo "login_valid[".$login_valid."]--<br>";

	if ($login_valid != "OK")
	{
		// xaraonline login failed, lets try magix.
		$magix = CheckMagix($email, $password);

		if ($magix == false)
			return false;
		else
			$xol_email = $email;
	}
	else
	{
		$xol_email = substr($response_lines[0],3);
	//	echo "xol_email[".$xol_email."]--<br>";
	}


	$userpassword = md5($password);
	$regpassword = substr(buildHash(),0,8);

	// Check if user is registered.
	$userid = getLoginShareUser(LOGINAPI_XARAONLINE, $xol_email);
//	echo "userid is [".$userid."]<br>";
	if (!$userid)
	{
		// Register the user.
	//	echo "We should register this user.<br>";
		$userid = insertUser(true, $xol_email, $regpassword, $_SWIFT["tgroup"]["regusergroupid"], LOGINAPI_XARAONLINE, $xol_email, $xol_email, $_SWIFT["tgroup"]["languageid"], 0, false, 1, true); 
	}

//	echo "userid is [".$userid."]<br>";
	if (!$userid)
	{
	   	
		$conn = mysql_connect('localhost','kayako_user','support');
		mysql_select_db('kayako_suite');
	//	$query ="select fullname,loginapi_userid from swusers where userid='54'";
	//	echo $query;
		$query = "update swusers s, swuseremails e set s.loginapi_userid = e.email where s.userid=e.userid and s.loginapi_moduleid=1;";
		$query1= "update swusers set loginapi_moduleid=118 where loginapi_moduleid=1;";
		
		$result = mysql_query($query);
		$result = mysql_query($query1);
		
	  	echo "Please try again"; 
	//	echo "Return false, because still no userid<br>";
		return false;
	}

	$_swiftuser = $loginshare->loadSWIFTUser($userid);
	if (!$_swiftuser)
	{
		return false;
	}

	$_SWIFT["user"] = $_swiftuser;

	return $_swiftuser["userid"];
}

/**
* Return the Unique User ID of the current user
*/
function loginShareUserID()
{
	global $_SWIFT;

	if (empty($_SWIFT["user"]["userid"]))
	{
		return false;
	} else {
		return $_SWIFT["user"]["userid"];
	}
}

/**
* Logout the current user
*/
function loginShareLogout()
{
	global $session, $_SWIFT;

	$session->updateSession($_SWIFT["session"]["sessionid"], 0);

	return true;
}

/**
* Load the user credentials into current workspace. The following variables should be declared for proper working:
* userid - User id that is set in the "users" table
* fullname
* email - Array
* password (MD5 Hashed)
* usergroupid - If this is not set, then it will use the default registered user group for this template group
*/
function loginShareLoadUser()
{
	global $dbCore, $_SWIFT, $loginshare;

	if (empty($_SWIFT["session"]["typeid"]))
	{
		$_SWIFT["user"]["loggedin"] = false;
		return false;
	}

	$_user = $loginshare->loadSWIFTUser($_SWIFT["session"]["typeid"]);
	if (!$_user)
	{
		$_SWIFT["user"]["loggedin"] = false;

		return false;
	}

	$_SWIFT["user"] = $_user;

	return true;
}

/**
* Render Login Share
*/
function renderLoginShareForm()
{
	global $_SWIFT;

	return array();
}
?>
