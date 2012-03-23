<?php
/* 
 * WARNING - This file is only useful if you're using MultiSite Global Mode. 
 * If not, you want to create custom post type templates, as described here - http://codex.wordpress.org/Post_Types#Template_Files
 * Your file would be named single-location.php
 */
/*
 * This page displays a single event, called during the em_content() if this is an event page.
 * You can override the default display settings pages by copying this file to yourthemefolder/plugins/events-manager/templates/ and modifying it however you need.
 * You can display events however you wish, there are a few variables made available to you:
 * 
 * $args - the args passed onto EM_Events::output() 
 */
global $EM_Location;
/* @var $EM_Location EM_Location */
echo  $EM_Location->output_single();
?>