<?php
/* Uninstall file
 *
 * Things to delete when the plugin is deinstalled
*/
if( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

delete_option( 'wadmw_options' );
