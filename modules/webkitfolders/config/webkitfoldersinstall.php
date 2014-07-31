<?php defined('SYSPATH') OR die('No direct access allowed.');

// Webkit Folders Install Config
//
// sets the overall behaviour for the whole server
// (although of course you can override this file - thank you Kohana : )


// turns on/off all of the dev mode stuff - like dumping sql as it happens
// you can fine tune this mode for more specific behaviour
$config['dev_mode'] = false;

$config['json_dev_mode'] = false;


$config['html_parser_dev_mode'] = false;

// the password for managing installations (/root/)
$config['root_password'] = 'apples';

// the default system layout - new installations will create their layout like this:
$config['system_layouts'] = array(
	'default' => array(
		'name' => 'System',
		'type' => 'system',
		'items' => array(
			array(
				'name' => 'Disk',
				'path' => '/',
				'type' => 'disk' ),
			array(
				'name' => 'Models',
				'path' => 'models:/',
				'type' => 'models' ),		
			array(
				'name' => 'Users',
				'path' => 'users:/',
				'type' => 'users' ),
			array(
				'name' => 'Recycle Bin',
				'path' => 'bin:/',
				'type' => 'bin' )
		)
	)
);