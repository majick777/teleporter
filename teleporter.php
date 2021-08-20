<?php

/*
Plugin Name: Teleporter
Plugin URI: http://wordquest.org/plugins/teleporter/
Author: Tony Hayes
Description: Seamless fading Page Transitions via the Browser History API
Version: 1.0.0
Author URI: http://wordquest.org
GitHub Plugin URI: majick777/teleporter
*/

if ( !function_exists( 'add_action' ) ) {
	exit;
}

// --- Add Plugin Loader Action
// --- Open Plugin Loader Wrapper
// === WordQuest Menus ===
// - Add WordQuest Submenu
// - WordQuest Submenu Icon Fix
// - Add WordQuest Sidebar Settings
// - Load WordQuest Admin Helper
// === Plugin Setup ===
// - Define Constants
// - Plugin Options
// - Plugin Loader Settings
// - Set Plugin Option Globals
// - Start Plugin Loader Instance
// === Teleporter ===
// - Enqueue Teleporter Scripts
// - Localize Script Settings
// - Add History API Support
// - Add Teleporter Styles
// - Minify Development Script
// - Link Test Shortcode
// --- Close Plugin Loader Wrapper
// --- Add Plugin Loader Action

// Development TODOs
// -----------------
// - test display page on page load timeout
// - check for event handlers on a link tags ?



// --------------------------------
// --- Add Plugin Loader Action ---
// --------------------------------
add_action( 'plugins_loaded', 'teleporter_load_plugin', 9 );

// ----------------------------------
// --- Open Plugin Loader Wrapper ---
// ----------------------------------
if ( !function_exists( 'teleporter_load_plugin' ) ) {
	function teleporter_load_plugin() {

		// --- load prefixed settings functions ---
		add_action( 'plugins_loaded', 'teleporter_load_prefixed_functions', 11 );

// ==================================================

// -----------------------
// === WordQuest Menus ===
// -----------------------
// note: these actions must be added before loader is initiated

// ---------------------
// Add WordQuest Submenu
// ---------------------
add_filter( 'teleporter_admin_menu_added', 'teleporter_add_admin_menu', 10, 2 );
function teleporter_add_admin_menu( $added, $args ) {

	// --- bug out if no wordquest helper ---
	$wqhelper = dirname( __FILE__ ) . '/wordquest.php';
	if ( !file_exists( $wqhelper ) ) {
		return false;
	}
	
	// --- filter menu capability early ---
	$capability = apply_filters( 'wordquest_menu_capability', 'manage_options' );

	// --- maybe add Wordquest top level menu ---
	global $admin_page_hooks;
	if ( empty( $admin_page_hooks['wordquest'] ) ) {
		$icon = plugins_url( 'images/wordquest-icon.png', $args['file'] );
		$position = apply_filters( 'wordquest_menu_position', '3' );
		add_menu_page( 'WordQuest Alliance', 'WordQuest', $capability, 'wordquest', 'wqhelper_admin_page', $icon, $position );
	}

	// --- check if using parent menu ---
	// (and parent menu capability)
	if ( isset( $args['parentmenu']) && ( $args['parentmenu'] == 'wordquest' ) && current_user_can( $capability ) ) {

		// --- add WordQuest Plugin Submenu ---
		add_submenu_page( 'wordquest', $args['pagetitle'], $args['menutitle'], $args['capability'], $args['slug'], $args['namespace'] . '_settings_page' );

		// --- add icons and styling fix to the plugin submenu :-) ---
		add_action( 'admin_footer', 'teleporter_wordquest_submenu_fix' );

		return true;
	}

	return false;
}

// --------------------------
// WordQuest Submenu Icon Fix
// --------------------------
function teleporter_wordquest_submenu_fix() {
	$args = teleporter_loader_instance()->args;
	$icon_url = plugins_url( 'images/icon.png', $args['file'] );
	if ( isset( $_REQUEST['page'] ) && ( $_REQUEST['page'] == $args['slug'] ) ) {$current = '1';} else {$current = '0';}
	echo "<script>jQuery(document).ready(function() {if (typeof wordquestsubmenufix == 'function') {
	wordquestsubmenufix('" . esc_js( $args['slug'] ) . "', '" . esc_url( $icon_url ) . "', '" . esc_js( $current ) . "');} });</script>";
}

// ------------------------------
// Add WordQuest Sidebar Settings
// ------------------------------
add_action( 'teleporter_add_settings', 'teleporter_add_settings' , 10, 1 );
function teleporter_add_settings( $args ) {
	if ( isset( $args['settings'] ) ) {
		$adsboxoff = 'checked';
		if ( file_exists( $args['dir'] . '/updatechecker.php' ) ) {
			$adsboxoff = '';
		}
		$sidebaroptions = array(
			'installdate'		=> date( 'Y-m-d' ),
			'donationboxoff'	=> '',
			'subscribeboxoff'	=> '',
			'reportboxoff' 		=> '',
			'adsboxoff'		=> $adsboxoff,
		);
		add_option( $args['settings'] . '_sidebar_options', $sidebaroptions );
	}
}

// ---------------------------
// Load WordQuest Admin Helper
// ---------------------------
add_action( 'teleporter_loader_helpers', 'teleporter_load_wordquest_helper', 10, 1 );
function teleporter_load_wordquest_helper( $args ) {
	if ( is_admin() && ( version_compare( PHP_VERSION, '5.3.0') >= 0 ) ) {
		$wqhelper = dirname( __FILE__ ) . '/wordquest.php';
		if ( file_exists( $wqhelper ) ) {
			include $wqhelper;
			global $wordquestplugins; $slug = $args['slug'];
			$wordquestplugins[$slug] = $args;
		}
	}
}


// --------------------
// === Plugin Setup ===
// --------------------

// ----------------
// Define Constants
// ----------------
define( 'TELEPORTER_FILE', __FILE__ );
define( 'TELEPORTER_DIR', dirname( __FILE__ ) );
define( 'TELEPORTER_HOME_URL', 'https://wordquest.org/plugins/teleporter/' );

// --------------
// Plugin Options
// --------------
// 1.0.0: added plugin options
$options = array(

	// === General ===

	// --- Teleporter Switch ---
	'page_fade_switch' => array(
		'type'    => 'checkbox',
		'label'   => __( 'Enable Teleporter', 'teleporter' ),
		'default' => 'yes',
		'value'   => 'yes',
		'helper'  => __( 'Switch for enabling or disabling Teleporter.', 'teleporter' ),
		'section' => 'basic',
	),

	// --- Page Fade Time ---
	'page_fade_time' => array(
		'type'    => 'number',
		'label'   => __( 'Page Fade Time', 'teleporter' ),
		'default' => 2000,
		'min'     => 0,
		'step'    => 100,
		'max'     => 10000,
		'helper'  => __( 'Number of milliseconds over which to fade in new Pages. Use 0 for instant display.', 'teleporter' ),
		'section' => 'basic',
	),

	// --- Page Load Timeout ---
	// 1.0.0: add page load timeout
	'page_load_timeout' => array(
		'type'    => 'number',
		'label'   => __( 'Page Load Timeout', 'teleporter' ),
		'default' => 7000,
		'min'     => 0,
		'step'    => 500,
		'max'     => 20000,
		'helper'  => __( 'Number of milliseconds to wait for new Page to load before fading in anyway. Use 0 for instant display.', 'teleporter' ),
		'section' => 'basic',
	),
	
	// === Loading Bar ===

	// --- Loading Bar Position ---
	'loading_bar_position'        => array(
		'type'    => 'select',
		'label'   => __( 'Loading Bar Position', 'teleporter' ),
		'default' => 'top',
		'options' => array(
			'top'     => __( 'Top', 'teleporter' ),
			'bottom'  => __( 'Bottom', 'teleporter' ),
			'none'    => __( 'None', 'teleporter' ),
		),
		'helper'  => __( 'Loading bar position when fading in new pages.', 'teleporter' ),
		'section' => 'loadingbar',
	),

	// --- Loading Bar Color ---
	'loading_bar_color'        => array(
		'type'    => 'coloralpha',
		'label'   => __( 'Loading Bar Color', 'teleporter' ),
		'default' => 'rgba(0,204,0,0.9)',
		'helper'  => __( 'Loading bar color used when fading in new pages.', 'teleporter' ),
		'section' => 'loadingbar',
	),
	
	// === Advanced ===

	// --- Ignore Link Classes ---
	'ignore_link_classes' => array(
		'type'    => 'csv',
		'label'   => __( 'Ignore Link Classes', 'teleporter' ),
		'default' => 'no-teleporter,no-transition',
		'helper'  => __( 'Any links with these classes will not be transitioned. (Comma separated list of classes to ignore.)', 'teleporter' ),
		'section' => 'advanced',
	),

	// --- Section Titles ---
	'sections' => array(
		'basic'      => __( 'General', 'teleporter' ), 
		'loadingbar' => __( 'Loading Bar', 'teleporter' ),
		'advanced'   => __( 'Advanced', 'teleporter' ),
	),
);

// ----------------------
// Plugin Loader Settings
// ----------------------
$slug = 'teleporter';
$settings = array(
	// --- Plugin Info ---
	'slug'         => $slug,
	'file'         => __FILE__,
	'version'      => '0.0.1',

	// --- Menus and Links ---
	'title'        => 'Teleporter',
	'parentmenu'   => 'wordquest',
	'home'         => TELEPORTER_HOME_URL,
	// 'docs'         => TELEPORTER_DOCS_URL,
	// 'support'		=> 'https://wordquest.org/quest-category/teleporter/',
	'support'      => 'https://github.com/majick777/teleporter/issues/',
	'ratetext'     => __( 'Rate on WordPress.org', 'teleporter' ),
	'share'        => 'https://wordquest.org/plugins/teleporter/#share',
	'sharetext'    => __( 'Share the Plugin Love', 'teleporter' ),
	'donate'       => 'https://wordquest.org/contribute/?plugin=teleporter',
	'donatetext'   => __( 'Support this Plugin', 'teleporter' ),
	'readme'       => false,
	'settingsmenu' => false,

	// --- Options ---
	'namespace'    => 'teleporter',
	'settings'     => 'tp',
	'option'       => 'teleporter',
	'options'      => $options,

	// --- WordPress.Org ---
	'wporgslug'    => 'teleporter',
	'wporg'        => true,
	'textdomain'   => 'teleporter',

	// --- Freemius ---
	// TODO: add Freemius integration 
	// 'freemius_id'  => '',
	// 'freemius_key' => '',
	// 'hasplans'     => false,
	//  'hasaddons'    => false,
	// 'plan'         => 'free',
);

// -------------------------
// Set Plugin Option Globals
// -------------------------
global $teleporter_data;
$teleporter_data['options'] = $options;
$teleporter_data['settings'] = $settings;

// ----------------------------
// Start Plugin Loader Instance
// ----------------------------
require TELEPORTER_DIR . '/loader.php';
$instance = new teleporter_loader( $settings );


// ------------------
// === Teleporter ===
// ------------------

// --------------------------
// Enqueue Teleporter Scripts
// --------------------------
add_action( 'wp_enqueue_scripts', 'teleporter_enqueue_scripts' );
function teleporter_enqueue_scripts() {

	// --- check settings ---
	// 1.0.0: added plugin settings 
	$enabled = teleporter_get_setting( 'page_fade_switch' );
	if ( 'yes' != $enabled ) {
		return;
	}
	
	// --- enqueue find event handlers script ---
	// TODO: check for click event handlers in teleporter.js using findEventHandlers
	// ref: https://stackoverflow.com/questions/2518421/jquery-find-events-handlers-registered-with-an-object
	// ref: https://www.blinkingcaret.com/2014/01/17/quickly-finding-and-debugging-jquery-event-handlers/
	// ref: http://findhandlersjsexample.azurewebsites.net/
	// ref: https://stackoverflow.com/questions/446892/how-to-find-event-listeners-on-a-dom-node-when-debugging-or-from-the-javascript/22841712#22841712
	// 1.0.0: disabled event handler script until implemented
	// $event_handlers_url = plugins_url( 'js/findEventHandlers.js', __FILE__ );
	// $version = filemtime( dirname( __FILE__ ) . '/js/findEventHandlers.js' );
	// wp_enqueue_script( 'find-event-handlers', $event_handlers_url, array(), $version, false );

	// --- enqueue history js for browser compatibility ---
	// ref: https://github.com/browserstate/history.js/
	$version = filemtime( dirname( __FILE__ ) . '/js/history.js' );
	$history_js_url = plugins_url( 'js/history.js', __FILE__ );
	wp_enqueue_script( 'historyjs', $history_js_url, array(), $version, false );

	// --- enqueue teleporter script ---
	// 0.9.7: fix to debug mode via querystring
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$suffix = '';
	} elseif ( isset( $_REQUEST['teleporter-debug'] ) && ( '1' == $_REQUEST['teleporter-debug'] ) ) {
		$suffix = '';
	} else {
		$suffix = '.min';
	}
	$teleporter_url = plugins_url( 'js/teleporter' . $suffix . '.js', __FILE__ );
	$version = filemtime( dirname( __FILE__ ) . '/js/teleporter' . $suffix . '.js' );
	wp_enqueue_script( 'teleporter', $teleporter_url, array( 'jquery' ), $version, false );

	// --- localize script settings ---
	teleporter_localize_settings();
}

// ------------------------
// Localize Script Settings
// ------------------------
function teleporter_localize_settings() {

	// --- set debug mode ---
	$debug = 'false';
	if ( isset( $_REQUEST['teleporter-debug'] ) && ( '1' == $_REQUEST['teleporter-debug'] ) ) {
		$debug = 'true';
	}

	// --- set iframe fadein time ---
	// 1.0.0: get page fade time from setting
	$fade_time = teleporter_get_setting( 'page_fade_time' );
	$fade_time = apply_filters( 'teleporter_fade_time', $fade_time );
	if ( !$fade_time ) {
		$fade_time = 'false';
	}

	// --- set iframe fadein timeout ---
	// 1.0.0: get page fade timeout from setting
	$timeout = teleporter_get_setting( 'page_load_timeout' );
	$timeout = apply_filters( 'teleporter_load_timeout', $timeout );
	if ( !$timeout ) {
		$timeout = 'false';
	}

	// --- set ignore classes ---
	// 1.0.0: get ignore classes from setting
	// $ignore_classes = array( 'no-teleporter', 'no-transition' );
	$ignore_classes = teleporter_get_setting( 'ignore_link_classes' );
	$ignore_classes = apply_filters( 'teleporter_ignore_classes', $ignore_classes );
	$ignore = '[';
	if ( is_string( $ignore_classes ) ) {
		$ignore_classes = explode( ',', $ignore_classes );
	}
	if ( is_array( $ignore_classes ) && ( count( $ignore_classes > 0 ) ) ) {
		foreach ( $ignore_classes as $i => $ignore_class ) {
			if ( $i > 0 ) {
				$ignore .= ',';
			}
			$ignore .= "'" . esc_js( $ignore_class ) . "'";
		}
	}
	$ignore .= ']';

	// --- set iframe class ---
	$iframe = apply_filters( 'teleporter_iframe_class', 'teleporter-iframe' );
	if ( !$iframe || !is_string( $iframe ) ) {
		$iframe = 'false';
	} else {
		$iframe = "'" . esc_js( $iframe ) . "'";
	}
	
	// --- set loading element ID ---
	$loading = apply_filters( 'teleporter_loading_id', 'teleporter-loading' );
	if ( !$loading || !is_string( $loading ) ) {
		$loading = 'false';
	} else {
		$loading = "'" . esc_js( $loading ) . "'";
	}
	// 1.0.0: if no loading bar position
	$bar_position = teleporter_get_setting( 'loading_bar_position' );
	if ( 'none' == $bar_position ) {
		$loading = 'false';
	}

	// --- set site URL ---
	// 0.9.8: added for subdomain install checks
	$siteurl = get_option( 'siteurl' );
	if ( !$siteurl || !is_string( $siteurl ) ) {
		// 1.0.0: fix to variable typo
		$siteurl = 'false';
	} else {
		$siteurl = "'" . esc_js( $siteurl ) . "'";
	}

	// --- output script settings object ---
	// 1.0.0: added timeout setting
	$js = "var teleporter = {";
		$js .= "debug: " . esc_js( $debug ) . ", ";
		$js .= "fadetime: " . esc_js( $fade_time ) . ", ";
		$js .= "timeout: " . esc_js( $timeout ) . ", ";
		$js .= "ignore: " . $ignore . ", ";
		$js .= "iframe: " . $iframe . ", ";
		$js .= "loading: " . $loading . ", ";
		$js .= "siteurl: " . $siteurl;
	$js .= "}" . PHP_EOL;

	// --- ignore WordPress admin links ---
	// 0.9.6: fix for admin bar links
	if ( count( $ignore_classes ) > 0 ) {
		$js .= "document.addEventListener('teleporter-check-links', function(event,params) {";
		$js .= "ignoreclass = '" . esc_js( $ignore_classes[0] ) . "'; ";
		if ( is_admin_bar_showing() ) {
			// --- add ignore class to wp admin bar links ---
			// (code only added if admin bar will be showing)
			$js .= "if (document.getElementById('wpadminbar')) {";
				$js .= "ablinks = document.getElementById('wpadminbar').getElementsByTagName('a'); ";
				$js .= "for (var i = 0; i < ablinks.length; i++) {";
					$js .= "if (!ablinks[i].classList.contains(ignoreclass)) {";
						$js .= "ablinks[i].classList.add(ignoreclass);";
					$js .= "}";
				$js .= "}";
			$js .= "} ";
		}
		// --- links to WordPress admin area ---
		// 1.0.0: added href attribute typeof check
		// 1.0.0: added check for wp-login.php in URL
		$js .= "adlinks = document.getElementsByTagName('a'); ";
		$js .= "for (var i = 0; i < adlinks.length; i++) {";
			$js .= "link = adlinks[i]; ";
			$js .= "if (typeof link.href != 'undefined') {";
				$js .= "if ((link.href.indexOf('/wp-admin/') > -1) || (link.href.indexOf('wp-login.php') > -1)) {";
					$js .= "if (link.classList.contains(ignoreclass)) {";
						$js .= "link.classList.add(ignoreclass);";
					$js .= "}";
				$js .= "}";
			$js .= "}";
		$js .= "}";

		$js .= "});" . PHP_EOL;
	}
	
	// --- filter extra script and add to teleporter ---
	$js = apply_filters( 'teleporter_script_settings', $js );
	wp_add_inline_script( 'teleporter', $js );

}

// -----------------------
// Add History API Support
// -----------------------
// note: for consistency, history support always is loaded and given preferred use
// add_action( 'wp_head', 'teleporter_history_support' );
function teleporter_history_support() {
	// --- maybe conditionally load history.js ---
	// ref: https://github.com/browserstate/history.js/
	// https://raw.githubusercontent.com/browserstate/history.js/master/scripts/bundled/html4%2Bhtml5/native.history.js */
	$version = filemtime( dirname( __FILE__ ) . '/js/history.js' );
	$history_js_url = plugins_url( 'js/history.js', __FILE__ ) . '?v=' . $version;
	echo "<script>";
	/* if ( isset( $_REQUEST['history-js-test'] ) && ( '1' == $_REQUEST['history-js-test'] ) ) {
		// --- disable native history to test History.js ---
		echo "window.history = {}" . PHP_EOL;
		echo "historyjs = document.createElement('script');";
		echo "historyjs.setAttribute('src', '" . $history_js_url . "');";
		echo "document.getElementsByTagName('head')[0].appendChild(historyjs);";
	} else {
		echo "if (window.history == 'undefined') {"; */
			// 0.9.7: added esc_url to history js URL
			echo "historyjs = document.createElement('script');";
			echo "historyjs.setAttribute('src', '" . esc_url( $history_js_url ) . "');";
			echo "document.getElementsByTagName('head')[0].appendChild(historyjs);";
		/* echo "}";
	} */
	echo "</script>";
}

// -----------------------------
// Add Teleporter Dynamic Styles
// -----------------------------
add_action( 'wp_footer', 'teleporter_dynamic_styles' );
function teleporter_dynamic_styles() {

	// --- check settings ---
	// 1.0.0: added plugin settings 
	$enabled = teleporter_get_setting( 'page_fade_switch' );
	if ( 'yes' != $enabled ) {
		return;
	}
	$page_fade_time = teleporter_get_setting( 'page_fade_time' );
	$page_load_timeout = teleporter_get_setting( 'page_load_timeout' );
	$loading_bar_position = teleporter_get_setting( 'loading_bar_position' );
	$loading_bar_color = teleporter_get_setting( 'loading_bar_color' );

	// --- get iframe class and loading div ID ---
	// 1.0.0: force back to string if incorrectly filtered
	$iframe = apply_filters( 'teleporter_iframe_class', 'teleporter-iframe' );
	if ( !$iframe || !is_string( $iframe ) ) {
		$iframe = 'teleporter-iframe';
	}
	$loading = apply_filters( 'teleporter_loading_id', 'teleporter-loading' );
	if ( !$iframe || !is_string( $iframe ) ) {
		$iframe = 'teleporter-loading';
	}

	// --- output page transition iframe ---
	// note: actual iframes are added dynamically via script
	// ref: https://stackoverflow.com/questions/3982422/full-screen-iframe
	// echo '<iframe src="javascript:void(0);" id="' . esc_attr( $iframe ) . '" name="' . esc_attr( $iframe ) . '" width="100%" height="100%" frameborder="0" scrolling="auto" allowfullscreen="true" style="display:none;"></iframe>';

	// --- output loading div ---
	if ( 'none' != $loading_bar_position ) {
		echo '<div id="' . esc_attr( $loading ) . '"></div>';
	}

	// --- iframe and loading styles ---
	// 0.9.7: added esc_attr to iframe class and loading IDs
	echo "<style>." . esc_attr( $iframe ) . " {";
		echo "background: #FFF; overflow: hidden; z-index: 999999; ";
		echo "position: fixed; border: none; margin: 0; padding: 0; top: 0; left: 0; bottom: 0; right: 0;";
	echo "}" . PHP_EOL;

	// --- loading bar styles ---
	// 1.0.0: add check for non-zero page fade time and loading bar position
	if ( ( $page_fade_time > 0 ) && ( 'none' != $loading_bar_position ) ) {
		echo "#" . esc_attr( $loading ) . " {position: fixed; left: 0; right: 0; margin: 0; padding: 0; ";
			echo "border: none; height: 7px; width: 0; max-width: 5000px; overflow: hidden; ";
			echo "opacity: 0; transition: none; background: " . $loading_bar_color . ";";
		if ( 'top' == $loading_bar_position ) {
			echo " top: 0;";
		} else {
			echo " bottom: 0;";
		}
		echo "}" . PHP_EOL;

		// 1.0.0: use page load timeout for loading bar animation
		$timeout = (string) round( $page_load_timeout / 1000, 3 );
		echo "#" . esc_attr( $loading ) . ".loading {transition: width " . $timeout . "s ease-in-out; width: 100%; opacity: 1;}" . PHP_EOL;
		echo "#" . esc_attr( $loading ) . ".reset {transition: none; width: 0; opacity: 0;}" . PHP_EOL;

		// --- maybe shift top position for admin bar ---
		if ( 'none' != $loading_bar_position ) {
			echo "body.admin-bar #" . esc_attr( $loading ) . " {top: 32px;}" . PHP_EOL;
			echo "@media screen and (max-width: 782px) {";
				echo "body.admin-bar #" . esc_attr( $loading ) . "{top: 46px;}";
			echo "}" . PHP_EOL;
		}
	}

	echo "</style>";

}

// ------------------------------
// Minify from Development Script
// ------------------------------
// 0.9.6: working code to minify scripts
add_action( 'init', 'teleporter_script_minifier' );
function teleporter_script_minifier() {

	// --- check trigger conditions ---	
	if ( !isset( $_REQUEST['teleporter-minify'] ) || !in_array( $_REQUEST['teleporter-minify'], array( '1', 'yes' ) ) ) {
		return;
	}
	if ( !current_user_can( 'manage_options' ) ) {
		return;
	}

	// --- set script paths ---
	$devscript = TELEPORTER_DIR . '/js/teleporter.dev.js';
	$script = TELEPORTER_DIR . '/js/teleporter.js';
	$minscript = TELEPORTER_DIR . '/js/teleporter.min.js';
	$contents = implode( '', file( $devscript ) );

	// --- strip comments from min script ---
	$mincontents = $contents;
	while ( strstr( $mincontents, '/*' ) ) {
		$pos = strpos( $mincontents, '/*' );
		$before = substr( $mincontents, 0, $pos );
		$remainder = substr( $mincontents, $pos, strlen( $mincontents ) );
		$posb = strpos( $remainder, '*/' ) + 2;
		$after = substr( $remainder, $posb, strlen( $remainder ) );
		$mincontents = $before . $after;
	}

	// --- strip empty lines ---
	$newlines = array();
	$lines = explode( "\n", $mincontents );
	foreach ( $lines as $line ) {
		if ( '' != trim( $line ) ) {
			$newlines[] = $line;
		}
	}
	$mincontents = implode( "\n", $newlines );

	// --- remove tabs (and line breaks?) ---
	$mincontents = str_replace( "\t", '', $mincontents );
	$mincontents = str_replace( "\r", ' ', $mincontents );
	$mincontents = str_replace( "\n", ' ', $mincontents );
	
	// --- write minified script ---
	$fh = fopen( $minscript, 'w' );
	fwrite( $fh, $mincontents );
	fclose( $fh );
	// echo '<br>----- Minified Script -----<br>';
	// echo $mincontents;

	// --- keep debugs for non-minified version ---
	$contents = str_replace( '/* if (teleporter.debug) {', 'if (teleporter.debug) {', $contents );
	$contents = str_replace( '} */', '}', $contents );

	// --- strip comments from dev script ---
	while ( strstr( $contents, '/*' ) ) {
		$pos = strpos( $contents, '/*' );
		$before = substr( $contents, 0, $pos );
		$remainder = substr( $contents, $pos, strlen( $contents ) );
		$posb = strpos( $remainder, '*/' ) + 2;
		$after = substr( $remainder, $posb, strlen( $remainder ) );
		$contents = $before . $after;
	}

	// --- strip empty lines ---
	$newlines = array();
	$lines = explode( "\n", $contents);
	foreach ( $lines as $line ) {
		if ( '' != trim( $line ) ) {
			$newlines[] = $line;
		}
	}
	$contents = implode( "\n", $newlines );
	
	// --- write comment stripped script ---
	$fh = fopen( $script, 'w' );
	fwrite( $fh, $contents );
	fclose( $fh );
	// echo '<br>----- Comment Free Script -----<br>';
	// echo $contents;
	
}

// -------------------
// Link Test Shortcode
// -------------------
add_shortcode( 'teleporter-test', 'teleporter_test_shortcode' );
function teleporter_test_shortcode() {

	if ( isset( $_GET['next'] ) ) {
		if ( '2' == $_GET['next'] ) {
			$title = '1'; $text = "This is a PAGE 2!"; $nextvalue = '3';
		} elseif ( '3' == $_GET['next'] ) {
			$title = '2'; $text = "This is a PAGE 3!"; $nextvalue = '4';
		} elseif ( '4' == $_GET['next'] ) {
			$title = '3'; $text = "This is a PAGE 4!"; $nextvalue = '';
		} elseif ( '' == $_GET['next'] ) {
			$title = '1'; $text = "Back to the First Page."; $nextvalue = '2';
		}
	} else {
		$title = '0'; $text = "This is Page 1."; $nextvalue = '2';
	}

	ob_start();
	
	// 0.9.7: added esc_html to outputs
?>

<title>Transition <?php echo esc_html( $title ); ?></title>

<?php echo esc_html( $text ); ?><br><br>

<div id='links'>

	<a href='?next=<?php echo esc_attr( $nextvalue ); ?>'>Link to Next Page.</a><br><br>
	<a href=''>Link to Page 1.</a><br><br>
	<a href='?next=2'>Link to Page 2.</a><br><br>
	<a href='?next=3'>Link to Page 3.</a><br><br>
	<a href='?next=3'>Link to Page 4.</a><br><br>
	<a href='' onclick='something();'>with OnClick Attribute.</a><br><br>
	<a href='' onclick=''>with empty OnClick Attribute.</a><br><br>
	<a href='' target='someframe'>with Target Attribute.</a><br><br>
	<a href='' target=''>with empty Target Attribute.</a><br><br>
	<a href='' class='no-transition'>with 'no-transition' Class.</a><br><br>
	<a href='#anchor'>with Hash in Href Attribute.</a><br><br>

</div>

<!-- Add Dynamic Links -->
<script>
/* Add Javascript Links */
newlinka = document.createElement('a');
newlinka.innerHTML = 'JS Added Link A';
newlinka.href = 'somepage.php';
newlinka.onclick = function() {alert("JS Added Link A"); return false;};
document.getElementById('links').appendChild(newlinka);
bra = document.createElement('br');
brb = document.createElement('br');
document.getElementById('links').appendChild(bra);
document.getElementById('links').appendChild(brb);
newlinkb = document.createElement('a');
newlinkb.innerHTML = 'JS Added Link B';
newlinkb.setAttribute('href', 'somepage.php');
newlinkb.setAttribute('onclick', 'alert("JS Added Link B"); return false;');
document.getElementById('links').appendChild(newlinkb);
brc = document.createElement('br');
brd = document.createElement('br');
document.getElementById('links').appendChild(brc);
document.getElementById('links').appendChild(brd);

/* Add jQuery Links */
if (typeof jQuery !== 'undefined') {

}
</script>
<?php

	$html = ob_get_contents();
	ob_end_clean();

	return $html;
}

// -----------------------------------
// --- Close Plugin Loader Wrapper ---
// ==================================================
	// close teleporter_load_plugin function
	}
// close function_exists check
}
