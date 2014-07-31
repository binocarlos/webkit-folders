<?php defined('SYSPATH') OR die('No direct access allowed.');

// cache configuration for webkitfolders
$config['default'] = array(
  'driver' => 'file',
  'params' => '/usr/local/webkitfolderscache',
  'lifetime' => 0,
  'requests' => -1
);