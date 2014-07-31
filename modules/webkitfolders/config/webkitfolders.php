<?php defined('SYSPATH') OR die('No direct access allowed.');

// the path to the image magick executables
$config['image_magick_bin'] = '/usr/bin';

// this can be overriden in the application config
// therefore this just serves as a default
$config['icon_folder'] = '/usr/local/webkitfoldersicons';

// this is where the icons are cached - must be world writable
$config['icon_cache_folder'] = '/usr/local/webkitfolderscache/icons';

$config['thumbnail_cache_folder'] = '/usr/local/webkitfolderscache/thumbnails';

$config['font_cache_folder'] = '/usr/local/webkitfolderscache/fonts';

// again - this is a default and can be overriden by the application config
$config['upload_folder'] = '/uploaded_files';
$config['full_upload_folder'] = '/usr/local/uploaded_files';

$config['form_controller_uri'] = '/app/form';

$config['installation_id'] = 1;