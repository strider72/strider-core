<?php
/**
 * Strider Core is a framework for WordPress plugins
 *
 * Does a lot of the common stuff I kept copy/paste repeating in all my plugins.
 * I'd rather write once!  In the future may also include version checking
 *
 * @link https://github.com/strider72/strider-core
 *
 * @package WP Strider Core
 */

 $this_strider_core_b2 = array(
	'version' => '0.2-beta-2',
	'date' => '2015-05-23',
	'file' => __FILE__
 );

// The licensing here is a bit unusual. Please see licensing info in readme.md
// Strider Core -- Copyright 2009 by Stephen Rider
// http://striderweb.com/nerdaphernalia/features/strider-core

// To Do:
/*	* Fix activate/deactivate hook problem.  Probably can hook a simple
		"container" function from the main plugin file.
		* see core_activate function

Each Admin page should have a second tab for "Strider Core" universal settings:
	* Toggle Version Check (default true)
		* Toggle - include compatible non-Strider Core plugins
	* Toggle menu icons

Version Check routine:
	* TEST INPUT of "Version Check URI" w/ wp_kses();
	* fetch latest versions --> TEST INPUT of returned string w wp_kses()
	* after_line_$plugin hook:
		* "skip this version" button ? (use nonces)
		* "Disable updates for this plugin" button ? (use nonces)
	* update_plugin may be written twice per check (in WP) - once for update 
		time, and once for actual results.  Only run our update routine once!
*/

if ( function_exists( 'find_and_load_newest_strider_core_b2' ) && ! isset( $all_strider_core_b2 ) && ! class_exists( 'strider_core_b2' )  ) {

	$strider_core_b2_firstrundone = false;

	abstract class strider_core_b2 {

		public $sc_data_name = 'plugin_strider_core_b2_data';
		public $sc_option_name = 'plugin_strider_core_b2_options';
		/* "Main" plugin file should also set...
		public $option_name : name of option in wp_options, e.g. 'plugin_xxx_settings'
		public $option_version : updated any time option structure changes
		public $option_bools : array of all user-settable boolean options
		public $text_domain : domain for l18n. Currently also use as broader "plugin tag"
		public $plugin_file : __FILE__ of the base plugin file
		*/
		public $menu_icon_url = null; // overwrite if you want a menu icon (optional)

		// FIXME: This should run for each strider_core_b2 plugin without main file having to call it.
		function core_init() {

			global $strider_core_b2_firstrundone;

			$plugin_base_dir = dirname( plugin_basename( $this->plugin_file ) );
			load_plugin_textdomain( $this->text_domain, null, $plugin_base_dir . '/lang' );
			load_plugin_textdomain( 'strider_core_b2', null, $plugin_base_dir . '/strider-core/lang' );

// testing.  I think this can be safely moved to version_check()
//			add_filter( 'pre_update_option_update_plugins', array( &$this, 'filter_set_update_plugins' ) );

			// the rest only runs once
			if ( $strider_core_b2_firstrundone ) return true;
			$strider_core_b2_firstrundone = true;

			$this->core_activate();
			//$this->version_check();

			if ( ! defined( 'WP_CONTENT_URL' ) )
				define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
			if ( ! defined( 'WP_CONTENT_DIR' ) )
				define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
			if ( ! defined( 'WP_PLUGIN_URL' ) )
				define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
			if ( ! defined( 'WP_PLUGIN_DIR' ) )
				define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
		}

		function core_activate() {
			$sc_data = get_option( $this->sc_data_name );

			// mock activation hook
			// If the options aren't in the DB, it must have just been activated
			if ( ! $sc_data || ! $sc_data['plugins'][plugin_basename($this->plugin_file)] ) {
				$this_plugin = $this->get_plugin_data();
				$sc_data['plugins'][plugin_basename( $this->plugin_file )]['name'] = $this_plugin['Name'];
				$sc_data['plugins'][plugin_basename( $this->plugin_file )]['version'] = $this_plugin['Version'];
				if ( get_option( $this->sc_data_name ) )
					update_option( $this->sc_data_name, $sc_data );
				else
					add_option( $this->sc_data_name, $sc_data );
			}
		}

		function core_deactivate() {
			// FIXME: Still trying to figure out how to hook this. Maybe just recommend using uninstall.php
		}

		function get_plugin_data( $param = null, $plugin_file = null ) {
			// You can optionally pass a specific value to fetch, e.g. 'Version' -- but it's inefficient to do that multiple times
			// WP 2.7: 'Name', 'PluginURI', 'Description', 'Author', 'AuthorURI', 'Version', 'TextDomain', 'DomainPath', 'Title'
			static $plugin_data;
			if ( ! $plugin_data ) {
				if ( ! $plugin_file ) $plugin_file = $this->plugin_file;
	 			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$plugin_data = get_plugin_data( $this->plugin_file );
			}

			$output = $plugin_data;
			// error_log(print_r($output, true));
			if ( $param ) {
				foreach ( (array) $plugin_data as $key => $value ) {
					if ( $param == $key ) {
						$output = $value;
						break;
					}
				}
			}
			return $output;
		}

		// abstracting l18n functions so I don't have to pass domain each time
		// also allows poedit to distinguish plugin-specific strings
		function sc__( $text ) {
			return __( $text, 'strider-core' );
		}
		function sc_e( $text ) {
			_e( $text, 'strider-core' );
		}

		function set_default_options ( $mode = 'merge', $curr_options = null ) {
			if ( $mode == 'reset') { delete_option( $this->option_name ); };
			return update_option( $this->option_name, $this->get_default_options( $mode, $curr_options ) );
		}

		protected function _get_default_options( $def_options, $mode = 'merge', $curr_options = null ) {
			switch( $mode ) {
				case 'merge' :
					if ( ! $curr_options ) $curr_options = $this->get_options();
					if ( $curr_options ) {
					// Merge existing prefs with new or missing defaults
						$def_options = array_merge( $def_options, $curr_options );
						$def_options['last_opts_ver'] = $this->option_version;
					}
					break;
				case 'reset' :
				case 'default' :
					break;
			}

			return $def_options;
		}

		function get_options( $refresh = false ) {
			// normally "caches" data as static.  $refresh tells it to check again
			static $options;
			if ( ! $options || $refresh ) {
				$options = $this->_get_options();
			}
			return $options;
		}
		protected function _get_options() {
			$options = get_option( $this->option_name );
			if ( ! $options['last_opts_ver'] ) {
				$options = $this->set_default_options( 'reset' );
			} elseif ( version_compare( $this->option_version, $options['last_opts_ver'], '>' ) ) {
				$options = $this->set_default_options( 'merge', $options );
			}
			return $options;
		}

		/**
		 * Updates a single member of the plugin's Options array.
		 *
		 * Plugins commonly (should) put options in an array and save the array as a single entry in the
		 * WP Options table.  This plugin eases updating a single member of that array.
		 *
		 * @param $option
		 * @param $value
		 *
		 * @return bool
		 */
		function update_option( $option, $value ) {
			$options = get_option( $this->option_name );
			$options[$option] = $value;
			return update_option( $this->option_name, $options );
		}

		//***********************
		// Admin Page Stuff
		//***********************

		function add_admin_page() {
			$name = $this->get_plugin_data( 'Name' );
			$param = array( 'options-general.php', $name, $name, 'manage_options', $this->text_domain, 'admin_page', $this->menu_icon_url );
			return $this->_add_admin_page( $param );
		}
		protected function _add_admin_page( $param ) {
		// $param == 0'basefile.php', 1'Title', 2'Menu Text', 3'permission', 4'page_tag', 5'function', 6'menu_icon'
			if ( current_user_can( $param[3] ) ) {
				if ( isset( $param[6] ) )
					$icon = $this->add_adminmenu_icon( $param[6] ) . ' ';
				else
					$icon = '';
				$page = add_submenu_page( $param[0], $param[1], $icon . $param[2], $param[3], $param[4], array( &$this, $param[5] ) );

				$this->admin_link = $param[0] . '?page=' . $param[4];

				$plugin = plugin_basename( $this->plugin_file );
				add_filter( "plugin_action_links_$plugin", array( &$this, 'filter_plugin_actions' ), 10, 2 );
				return $page;
			}
			return false;
		}

		function admin_footer() {
			global $strider_core_b2_info;
		// Add homepage link to settings page footer
			$pluginfo = $this->get_plugin_data();
			printf( $this->sc__('%1$s plugin | <span title="Strider Core version %2$s">Version %3$s</span> | by %4$s<br />'), $pluginfo['Title'], $strider_core_b2_info['version'], $pluginfo['Version'], $pluginfo['Author'] );
		}

		function filter_plugin_actions( $links, $file ){
			$param = func_get_args();
			return $this->_filter_plugin_actions( $param, $this->admin_link );
		}
		protected function _filter_plugin_actions( $param, $this_link, $link_name = 'Settings' ) {
			// $param == $links, $file
			$this_link = "<a href=\"$this_link\">" . __( $link_name ) . '</a>';
			array_unshift( $param[0], $this_link ); // before other links
			return $param[0];
		}

		function add_adminmenu_icon( $icon = null ) {
			if( function_exists('wp_ozh_adminmenu') ) {
				// if Ozh plugin running
				add_filter( 'ozh_adminmenu_icon', array( &$this, 'add_ozh_adminmenu_icon' ) );
			} else {
				if ( ! $icon && $this->menu_icon_url ) 
					$icon = $this->menu_icon_url;
				if ( $icon )
					return '<img src="' . $icon . '" style="height: 1em; fill: currentColor;" alt="" title="" />';
			}
			return '';
		}

		function add_ozh_adminmenu_icon( $hook ) {
			$param = func_get_args();
			return $this->_add_ozh_adminmenu_icon( $param, $this->text_domain );
		}
		protected function _add_ozh_adminmenu_icon( $param, $hook ) {
			$icon = WP_CONTENT_URL . '/plugins/' . basename( dirname( $this->plugin_file ) ) . '/resources/menu_icon.png';
			if ($param[0] == $hook) return $icon;
			return $param[0];
		}

		// this is used to display existing options in the admin form
		function checktext( $options, $optname, $optdefault = '' ) {
		// for text boxes or textarea
			return $options[$optname] ? htmlspecialchars(stripslashes($options[$optname])) : htmlspecialchars($optdefault);
		}

		function process_form() {
			if ( isset( $_POST['save_settings'] ) )
				return $this->_process_form();
			else
				return $this->get_options();
		}
		protected function _process_form( $message = '', $extra_options = null ) {
			check_admin_referer( $this->text_domain . '-update-options' );
			$options = $_POST[$this->option_name];
			if( $extra_options !== null ) {
				$options = array_merge( $extra_options, $options );
			}
			foreach( (array) $this->option_bools as $bool ) { 
				// explicitly set all checkboxes true or false
				$options[$bool] = isset( $options[$bool] ) ? true : false;
			}
			$options['last_opts_ver'] = $this->option_version; // always update
			update_option( $this->option_name, $options );
			echo '<div id="message" class="updated fade"><p><strong>' . __('Settings saved.') . '</strong></p>' . $message . '</div>';
			return $options;
		}

		function core_admin_page() {
		// dummy code -- not active
		// FIXME: find a way to programatically get "basepage.php?page=myplugin"
			add_action( 'in_admin_footer', array( &$this, 'admin_footer' ), 9 );

			$options = $this->sc_process_form();
	?>
	<div class="wrap">
		<h2><?php $this->p_e( 'Strider Core Settings' ); ?></h2>
		<form action="plugins.php?page=deprecated" method="post">
			<?php
			if ( function_exists( 'wp_nonce_field' ) )
				wp_nonce_field( 'strider_core_b2-update-options' );
			?>
			<table class="form-table">
				<tbody>

				</tbody>
			</table>
			<div class="submit">
				<input type="submit" name="save_settings" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" /></div>
		</form>
	</div><!-- wrap -->
	<?php
		}

		//***********************
		//  Version Checking
		//***********************

		function version_check() {
			// FIXME: User should be able to control if version check is for all SC plugins or just this one.

			// TODO: What happens if branched core uses same system with different variable names?  Duplicate calls?

			add_filter( 'extra_plugin_headers', array( &$this, 'register_version_check_header' ) );
			add_filter( 'pre_update_option_update_plugins', array( &$this, 'filter_set_update_plugins' ) );
			add_filter( 'option_update_plugins', array( &$this, 'filter_get_update_plugins' ) );
			add_filter( 'transient_update_plugins', array( &$this, 'filter_get_update_plugins' ) ); // for cached data
			add_action( 'admin_head-plugins.php', array( $this, 'plugin_update_rows' ) );

	// test code -- trigger the pre_update action/filter:
	/*
			$x = get_option('update_plugins');
			update_option( 'update_plugins', $x );
			error_log( print_r( $x, true ) );
			$x = get_option( $this->sc_data_name );
			error_log( print_r( $x, true ) );
	/* */
		}

		function register_version_check_header( $extra_headers ) {
			// requires WP 2.9
			$extra_headers[] = 'Version Check URI';
			return $extra_headers;
		}

		function filter_set_update_plugins( $data ) {
			// TODO: option so user can turn checking off

		/* TODO: "hard coded" test data for now.  For each Strider Core plugin, we need to...
				* fetch the content of the Version-check URL
				* assign to $newitem as in test data
				* unset $data->response for this plugin
				* run $sc_data code below
			probably going to use 	$content = wp_remote_fopen();
			or simply get_file_data()
		*/

			$sc_data = get_option( $this->sc_data_name );

			// start "each Strider Core plugin" loop...
				// start test data
				$plugin = 'log_deprecated_calls/log_deprecated_calls.php';
				$newitem = new stdClass;
				$newitem->new_version = '0.7';
				//$test = $this->get_plugin_data( 'Version Check URI' );
				$newitem->url = 'http://striderweb.com/nerdaphernalia/features/wp-log-deprecated-calls/';
				// end test data

				//	error_log('comparing versions');
				if ( ! isset( $sc_data['updates'] ) ) {
					$sc_data['updates'] = [];
				}
				if ( version_compare( $newitem->new_version, $this->get_plugin_data( 'Version', WP_PLUGIN_DIR . '/' . $plugin ) ) == 1 ) {
					$sc_data['updates'][$plugin] = $newitem;
					//	error_log('new version');
				} else {
					unset( $sc_data['updates'][$plugin] );
					//	error_log('same version');
				}

				// We never actually want to write the SC changes to the database -- 
				// only filter it when called
				unset( $data->response[$plugin] );
			// end "each SC plugin" loop

			update_option( $this->sc_data_name, $sc_data );
			unset( $newitem );

			return $data;
		}

		function filter_get_update_plugins( $data ) {
			static $sc_data;
			if ( ! $sc_data ) {
				$sc_data = get_option( $this->sc_data_name );
			}
			if ( is_array($sc_data['updates']) ) {
			//	FIXME: we need to loop the object's name as well!
			//	error_log(print_r($sc_data['updates'], true));
				foreach( $sc_data['updates'] as $key=>$object ) {
					$data->response[$key] = $object;
				}
			}
			return $data;
		}

		function plugin_update_rows() {
			$sc_data = (array) get_option( $this->sc_data_name );
			$names = array_keys( (array) $sc_data['updates'] );
			foreach( $names as $file ) {
				if ( version_compare( $sc_data['updates'][$file]->new_version, $this->get_plugin_data('Version') ) != 1 ) {
					unset($sc_data['updates'][$file]);
					update_option( $sc_data_name, $sc_data );
				} else {
					add_action( "after_plugin_row_$file", array( &$this, 'plugin_update_row' ), 10, 3 );
				}
			}
		}

		function plugin_update_row( $plugin_file, $plugin_data, $context ) {
			$plugin = get_option( $this->sc_data_name );
			$plugin = $plugin['updates'][$plugin_file];

			echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">';
			printf( $this->sc__('Version %1$s of %2$s is available for <a href="%3$s" title="%4$s">download</a>.'), $plugin->new_version, $plugin_data['Name'], $plugin_data['PluginURI'], $this->sc__('visit plugin homepage') );
			echo '</div></td></tr>';
		}
		
		//***********************
		//  Deprecated
		//***********************

		function p__( $text ) {
			_deprecated_function( __FUNCTION__, 'Strider Core 0.1 beta 5', '__()' );
			return __( $text, $this->text_domain );
		}
		function p_e( $text ) {
			_deprecated_function( __FUNCTION__, 'Strider Core 0.1 beta 5', '_e()' );
			_e( $text, $this->text_domain );
		}
		function checkflag( $options, $optname ) {
			_deprecated_function( __FUNCTION__, 'Strider Core 0.1 beta 5.1', 'checked()' );
			return $options[$optname] ? ' checked="checked"' : '';
		}
		function checkcombo( $options, $optname, $thisopt, $is_default = false ) {
			_deprecated_function( __FUNCTION__, 'Strider Core 0.1 beta 5.1', 'selected()' );
			return (
				( $is_default && ! $options[$optname] ) ||
				$options[$optname] == $thisopt
			) ? ' selected="selected"' : '';
		}
		function _set_defaults( $def_options, $mode = 'merge', $curr_options = null ) {
			_deprecated_function( __FUNCTION__, 'Strider Core 0.2 beta 1', '_set_default_options()' );
			return $this->_set_default_options($def_options, $mode, $curr_options);
		}
		protected function _set_default_options( $def_options, $mode = 'merge', $curr_options = null ) {
			_deprecated_function( __FUNCTION__, 'Strider Core 0.2 beta 2', 'get_default_options()  (NOTE: See changes to get/set_default_options in the code. Probably rename your function to get_default_actions)' );
			if ( $mode == 'reset') { delete_option( $this->option_name ); };
			return update_option( $this->option_name, $this->_get_default_options( $def_options, $mode, $curr_options ) );
		}

	} // end class

} // end if

// This code is called the first time that it is encountered
if ( ! function_exists( 'find_and_load_newest_strider_core_b2' ) ) {

	function find_and_load_newest_strider_core_b2() {
		global $strider_core_b2_plugins;
		global $all_strider_core_b2;
		global $strider_core_b2_info;

		$best_file = $all_strider_core_b2[0];
		foreach( (array) $all_strider_core_b2 as $this_core ) {
			$best_file = version_compare( $best_file['version'], $this_core['version'] ) == 1 ? $best_file : $this_core;
		}

		unset( $all_strider_core_b2 );
		require( $best_file['file'] );  // strider_core_b2 class is created
		$strider_core_b2_info = array_pop( $all_strider_core_b2 );
		unset( $all_strider_core_b2 );
		// FIXME: somehow strider_core_b2s is being recreated later on.  Doesn't seem to hurt anything, just messy.

		foreach( array_keys( $strider_core_b2_plugins ) as $key ) {
			include_once( $strider_core_b2_plugins[$key]['core file'] );
		}
	}
	add_action( 'plugins_loaded', 'find_and_load_newest_strider_core_b2', 3 );

} // end if

$all_strider_core_b2[] = $this_strider_core_b2;
unset( $this_strider_core_b2 );

?>