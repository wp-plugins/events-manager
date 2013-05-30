<?php
$wp_content_folder = dirname(dirname(dirname(dirname(dirname(__FILE__))))); //finds the wp-content folder
if( file_exists($wp_content_folder.'/em-timthumb-config.php') ) include($wp_content_folder.'/em-timthumb-config.php');

//applied a fix https://code.google.com/p/timthumb/issues/detail?id=120#c28, but reverted as it causes more problems and only fixes rare cases, still here for usage if needbe
if( !defined('FILE_CACHE_DIRECTORY') ) define ('FILE_CACHE_DIRECTORY', '../../../../uploads/em-cache/');