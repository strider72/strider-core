<?php
// This file should be include()ed from the main plugin uninstall.php file.

if ( ! defined( 'ABSPATH' ) ) exit();	// sanity check

function uninstall_strider_core_plugin() {
	//	TODO:	needs an uninstall (not deactivate) function that removes current plugin from "plugin exists" array.  If array is then empty (no other SC plugins, whether active or not), delete strider_core_data and strider_core_options
}

uninstall_strider_core_plugin();

?>