<?php

/*
Plugin Name: Teleporter
Plugin URI: https://wordquest.org/plugins/teleporter/
Author: Tony Hayes
Description: Seamless fading Page Transitions via the Browser History API
Version: 1.0.8
Author URI: https://wordquest.org
GitHub Plugin URI: majick777/teleporter
*/

if ( !defined( 'ABSPATH' ) ) {
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
// - Dynamic Link iPhone Fix
// - Add History API Support
// - Add Teleporter Styles
// - Minify Development Script
// - Link Test Shortcode
// --- Close Plugin Loader Wrapper
// --- Add Plugin Loader Action

// Development TODOs
// -----------------
// - check for event handlers on a link tags z


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
			global $wordquestplugins;
			$slug = $args['slug'];
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

	// --- Dynamic Link Classes ---
	// 1.0.4: added dynamic link classes handling
	'dynamic_link_classes' => array(
		'type'    => 'csv',
		'label'   => __( 'Dynamic Link Classes', 'teleporter' ),
		'default' => '',
		'helper'  => __( 'Dynamic links are those added to the page after loading. Add their classes here include them in transitions. (Comma separated list of classes to include.)', 'teleporter' ),
		'section' => 'advanced',
	),

	// --- Always Refresh Pages ---
	// 1.0.8: added always refresh pages option
	'always_refresh' => array(
		'type'    => 'csv',
		'label'   => __( 'Always Refresh Pages', 'teleporter' ),
		'default' => 'cart,checkout',
		'helper'  => __( 'Pages to force refresh when clicked, instead of switching to if previously loaded. (Comma separated list of page slugs or IDs.)', 'teleporter' ),
		'section' => 'advanced',
	),

	// --- Script Debug Mode ---
	// 1.0.5: added for script debugging
	'script_debug' => array(
		'type'    => 'checkbox',
		'label'   => __( 'Debug Mode', 'teleporter' ),
		'value'    => 'yes',
		'default' => '',
		'helper'  => __( 'Use unminified script and output console debug messages.', 'teleporter' ),
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

// -----------------------------
// Check if Admin or Editor Mode
// -----------------------------
function teleporter_is_admin_or_editor() {

	// -- admin or preview ---
	if ( is_admin() ) {
		return true;
	}
	// --- customizer preview or page preview ---
	if ( is_customize_preview() || isset( $_REQUEST['preview_id'] ) ) {
		return true;
	}
	// --- block editor ---
	if ( function_exists( 'get_current_screen' ) ) {
		$current_screen = get_current_screen();
		if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			return true;
		}
	}
	// --- gutenberg plugin ---
	if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
		return true;
	}
	// --- elementor ---
	if ( ( isset( $_REQUEST['action'] ) && ( 'elementor' == $_REQUEST['action'] ) ) || isset( $_REQUEST['elementor-preview'] ) ) {
		return true;
	}
	// --- beaver builder ---
	if ( isset( $_REQUEST['fl_builder'] ) || isset( $_REQUEST['fl_builder_preview'] ) ) {
		return true;
	}
	
	return false;
}

// --------------------------
// Enqueue Teleporter Scripts
// --------------------------
add_action( 'wp_enqueue_scripts', 'teleporter_enqueue_scripts' );
function teleporter_enqueue_scripts() {

	// 1.0.7: added double checks to bug out in admin/editing modes
	if ( teleporter_is_admin_or_editor() ) {
		return;
	}

	// --- check settings ---
	// 1.0.0: added plugin settings
	$enabled = teleporter_get_setting( 'page_fade_switch' );
	$enabled = apply_filters( 'teleporter_page_fade_switch', $enabled );
	if ( 'yes' != $enabled ) {
		if ( isset( $_REQUEST['teleporter-debug'] ) && ( '1' == $_REQUEST['teleporter-debug'] ) ) {
			echo '<span style="display:none;">Teleporter NOT Enabled</span>';
		}
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
	// 1.0.4: allow for .dev script extension debugging via querystring
	$teleporter_debug = teleporter_get_setting( 'script_debug' );
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$suffix = '';
	} elseif ( isset( $_REQUEST['teleporter-debug'] ) ) {
		if ( '1' == $_REQUEST['teleporter-debug'] ) {
			$suffix = '';
		} elseif ( '2' == $_REQUEST['teleporter-debug'] ) {
			$suffix = '.min';
		} elseif ( 'dev' == $_REQUEST['teleporter-debug'] ) {
			$suffix = '.dev';
		}
	} elseif ( 'yes' == $teleporter_debug ) {
		$suffix = '';
	} else {
		$suffix = '.min';
	}
	$teleporter_url = plugins_url( 'js/teleporter' . $suffix . '.js', __FILE__ );
	$version = filemtime( dirname( __FILE__ ) . '/js/teleporter' . $suffix . '.js' );
	wp_enqueue_script( 'teleporter', $teleporter_url, array( 'jquery' ), $version, false );

	if ( $teleporter_debug || isset( $_REQUEST['teleporter-debug'] ) ) {
		echo '<span style="display:none;">Teleporter Script Enqueued: ' . $teleporter_url . '</span>';
	}

	// --- localize script settings ---
	teleporter_localize_settings();
}

// ------------------------
// Localize Script Settings
// ------------------------
function teleporter_localize_settings() {

	// --- set debug mode ---
	$debug = ( 'yes' == teleporter_get_setting( 'script_debug' ) ) ? 'true' : 'false';
	if ( isset( $_REQUEST['teleporter-debug'] ) ) {
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

	// 1.0.2: added extra check here 
	if ( $ignore_classes && is_string( $ignore_classes ) ) {
		if ( strstr( $ignore_classes, ',' ) ) {
			$ignore_classes = explode( ',', $ignore_classes );
		} else {
			$ignore_classes = array( $ignore_classes );
		}
	}
	
	// 1.0.1: fix to possible not countable warning with !empty check
	// 1.0.3: fix to the fix, the count brackets are the culprit here
	if ( is_array( $ignore_classes ) && !empty( $ignore_classes ) && ( count( $ignore_classes ) > 0 ) ) {
		foreach ( $ignore_classes as $i => $ignore_class ) {
			if ( $i > 0 ) {
				$ignore .= ',';
			}
			$ignore .= "'." . esc_js( trim( $ignore_class ) ) . "'";
		}
	}
	// 1.0.6: add filter for other more specific (not just class) selectors
	$ignore_selectors = apply_filters( 'teleporter_ignore_selectors', '' );
	if ( $ignore_selectors && is_string( $ignore_selectors ) ) {
		if ( strstr( $ignore_selectors, ',' ) ) {
			$ignore_selectors = explode( ',', $ignore_selectors );
		} else {
			$ignore_selectors = array( $ignore_selectors );
		}
	}
	if ( is_array( $ignore_selectors ) && ( count( $ignore_selectors ) > 0 ) ) {
		foreach ( $ignore_selectors as $ignore_selector ) {
			if ( strlen( $ignore ) > 1 ) {
				$ignore .= ',';
			}
			$ignore .= "'" . esc_js( trim( $ignore_selector ) ) . "'";
		}
	}
	$ignore .= ']';

	// --- set dynamic link classes ---
	// 1.0.4: added for dynamic links
	$dynamic_classes = teleporter_get_setting( 'dynamic_link_classes' );
	$dynamic_classes = apply_filters( 'teleporter_dynamic_classes', $dynamic_classes );
	$dynamic = '[';
	if ( $dynamic_classes && is_string( $dynamic_classes ) ) {
		if ( strstr( $dynamic_classes, ',' ) ) {
			$dynamic_classes = explode( ',', $dynamic_classes );
		} else {
			$dynamic_classes = array( $dynamic_classes );
		}
	}
	if ( is_array( $dynamic_classes ) && ( count( $dynamic_classes ) > 0 ) ) {
		foreach ( $dynamic_classes as $dynamic_class ) {
			if ( strlen( $dynamic ) > 1 ) {
				$dynamic .= ',';
			}
			$dynamic .= "'." . esc_js( trim( $dynamic_class ) ) . "'";
		}
	}
	// 1.0.6: add filter for other more specific (not just class) selectors
	$dynamic_selectors = apply_filters( 'teleporter_dynamic_selectors', '' );
	if ( $dynamic_selectors && is_string( $dynamic_selectors ) ) {
		if ( strstr( $dynamic_selectors, ',' ) ) {
			$dynamic_selectors = explode( ',', $dynamic_selectors );
		} else {
			$dynamic_selectors = array( $dynamic_selectors );
		}
	}
	if ( is_array( $dynamic_selectors ) && ( count( $dynamic_selectors ) > 0 ) ) {
		foreach ( $dynamic_selectors as $dynamic_selector ) {
			if ( strlen( $dynamic ) > 1 ) {
				$dynamic .= ',';
			}
			$dynamic .= "'" . esc_js( trim( $dynamic_selector ) ) . "'";
		}
	}
	$dynamic .= ']';

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
	// 1.0.4: added dynamic classes setting
	$js = "var teleporter = {";
		$js .= "debug: " . esc_js( $debug ) . ", ";
		$js .= "fadetime: " . esc_js( $fade_time ) . ", ";
		$js .= "timeout: " . esc_js( $timeout ) . ", ";
		$js .= "ignore: " . $ignore . ", ";
		$js .= "dynamic: " . $dynamic . ", ";
		$js .= "iframe: " . $iframe . ", ";
		$js .= "loading: " . $loading . ", ";
		$js .= "siteurl: " . $siteurl;
	$js .= "}" . "\n";

	// --- ignore WordPress admin links ---
	// 0.9.6: fix for admin bar links
	// 1.0.8: use no-teleporter attribute instead of ignore class
	$js .= "document.addEventListener('teleporter-check-links', function(event,params) {";

		// --- add ignore attribute to wp admin bar links ---
		$js .= "if (document.getElementById('wpadminbar')) {";
			$js .= "ablinks = document.getElementById('wpadminbar').getElementsByTagName('a'); ";
			$js .= "for (var i = 0; i < ablinks.length; i++) {";
				// 1.0.8: use no-teleporter attribute instead of ignore class
				$js .= "if (ablinks[i].getAttribute('no-teleporter') != '1') {";
					$js .= "ablinks[i].setAttribute('no-teleporter','1');";
				$js .= "}";
			$js .= "}";
		$js .= "} ";

		// --- links to WordPress admin area ---
		// 1.0.0: added href attribute typeof check
		// 1.0.0: added check for wp-login.php in URL
		$js .= "adlinks = document.getElementsByTagName('a'); ";
		$js .= "for (var i = 0; i < adlinks.length; i++) {";
			$js .= "link = adlinks[i]; ";
			$js .= "if (typeof link.href != 'undefined') {";
				$js .= "if ((link.href.indexOf('/wp-admin/') > -1) || (link.href.indexOf('wp-login.php') > -1)) {";
					// 1.0.8: use no-teleporter attribute instead of ignore class
					$js .= "if (link.getAttribute('no-teleporter') != '1') {";
						$js .= "link.setAttribute('no-teleporter','1');";
					$js .= "}";
				$js .= "}";
			$js .= "}";
		$js .= "}";

	$js .= "});" . "\n";

	// --- filter extra script and add to teleporter ---
	$js = apply_filters( 'teleporter_script_settings', $js );
	wp_add_inline_script( 'teleporter', $js );

	// --- check for always refresh pages ---
	// 1.0.8: added javascript body refresh attribute flag
	$teleporter_refresh = false;
	$always_refresh = trim( teleporter_get_setting( 'always_refresh' ) );
	$always_refresh = strstr( $always_refresh, ',' ) ? explode( ',', $always_refresh ) : array( $always_refresh );
	if ( is_singular() ) {
		global $post;
		if ( in_array( $post->post_name, $always_refresh ) || in_array( $post->ID, $always_refresh ) ) {
			$teleporter_refresh = true;
		}
	}
	// 1.0.9: allow for partial URL path matching (containing '/')
	if ( !$teleporter_refresh ) {
		foreach ( $always_refresh as $page ) {
			if ( strstr( $page, '/' ) && strstr( $_SERVER['SCRIPT_NAME'], $page ) ) {
				$teleporter_refresh = true;
			}
		}
	}
		
	$teleporter_refresh = apply_filters( 'teleporter_refresh', $teleporter_refresh );
	if ( $teleporter_refresh ) {
		$js .= "window.document.getElementsByTagName('body')[0].setAttribute('teleporter-refresh','1');";
		wp_add_inline_script( 'teleporter', $js );
	}

	// 1.0.6: added to fix dynamic links on iphones
	if ( is_array( $dynamic_classes ) && !empty( $dynamic_classes ) && ( count( $dynamic_classes ) > 0 ) ) {
		add_action( 'wp_footer', 'teleporter_dynamic_link_iphone_fix' );
	}

}

// -----------------------
// Dynamic Link iPhone Fix
// -----------------------
// 1.0.6: added for iPhone Safari click event bubbling
function teleporter_dynamic_link_iphone_fix() {

	$dynamic_classes = teleporter_get_setting( 'dynamic_link_classes' );
	$dynamic_classes = apply_filters( 'teleporter_dynamic_classes', $dynamic_classes );
	$dynamic = '';
	if ( $dynamic_classes && is_string( $dynamic_classes ) ) {
		if ( strstr( $dynamic_classes, ',' ) ) {
			$dynamic_classes = explode( ',', $dynamic_classes );
		} else {
			$dynamic_classes = array( $dynamic_classes );
		}
	}
	echo '<style>';
	if ( is_array( $dynamic_classes ) && !empty( $dynamic_classes ) && ( count( $dynamic_classes ) > 0 ) ) {
		// ref: https://gravitydept.com/blog/js-click-event-bubbling-on-ios
		foreach ( $dynamic_classes as $i => $dynamic_class ) {
			if ( $i > 0 ) {
				echo ',';
			}
			echo '.' . esc_html( trim( $dynamic_class ) );
		}
		echo ', ';
	}
	// 1.0.7: add is-ios class selector in any case
	echo '.is-ios * {cursor: pointer;}</style>' . "\n";

	// 1.0.7: detect iOS and add onclick function to body children
	// (an onclick function may be needed 'between body and element')
	// ref: https://www.quirksmode.org/blog/archives/2014/02/mouse_event_bub.html
	echo "<script>jQuery(document).ready(function() {
		isIOS = ['iPhone Simulator','iPad Simulator','iPod Simulator','iPhone','iPad','iPod'].includes(navigator.platform);
		if (isIOS) {
			document.querySelector('html').classList.add('is-ios');
			jQuery('body').children().each(function() {
				if (!jQuery(this).is('[onclick],script,style,link')) {jQuery(this).attr('onclick','function(){}');}
			});
		}
	});</script>" . "\n";
}

// --------------------------
// Ignore Comment Reply Links
// --------------------------
// 1.0.2: added ignore class for comment reply links
add_filter( 'teleporter_ignore_classes', 'teleporter_ignore_comment_reply_link_classes' );
function teleporter_ignore_comment_reply_link_classes( $classes ) {

	// 1.0.6: allow for array or string value
	if ( is_array( $classes ) ) {
		$classes[] = 'comment-reply-link';
	} elseif ( '' != $classes ) {
		$classes .= ',comment-reply-link';
	} else {
		$classes = 'comment-reply-link';
	}
	
	return $classes;
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
	// note: actual iframes are now added dynamically via script
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
	echo "}" . "\n";

	// --- loading bar styles ---
	// 1.0.0: add check for non-zero page fade time and loading bar position
	if ( ( $page_fade_time > 0 ) && ( 'none' != $loading_bar_position ) ) {
		echo "#" . esc_attr( $loading ) . " {position: fixed; left: 0; right: 0; margin: 0; padding: 0; ";
			echo "border: none; height: 7px; width: 0; max-width: 5000px; overflow: hidden; ";
			// 1.0.8: added missing esc_attr wrapper on loading bar color value
			echo "opacity: 0; transition: none; background: " . esc_attr( $loading_bar_color ) . ";";
		if ( 'top' == $loading_bar_position ) {
			echo " top: 0;";
		} else {
			echo " bottom: 0;";
		}
		echo "}" . "\n";

		// 1.0.0: use page load timeout for loading bar animation
		// 1.0.8: added missing esc_attr wrapper on timeout value
		$timeout = (string) round( $page_load_timeout / 1000, 3 );
		echo "#" . esc_attr( $loading ) . ".loading {transition: width " . esc_attr( $timeout ). "s ease-in-out; width: 100%; opacity: 1;}" . "\n";
		echo "#" . esc_attr( $loading ) . ".reset {transition: none; width: 0; opacity: 0;}" . "\n";

		// --- maybe shift top position for admin bar ---
		if ( 'none' != $loading_bar_position ) {
			echo "body.admin-bar #" . esc_attr( $loading ) . " {top: 32px;}" . "\n";
			echo "@media screen and (max-width: 782px) {";
				echo "body.admin-bar #" . esc_attr( $loading ) . "{top: 46px;}";
			echo "}" . "\n";
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
newlinka.href = '?page=a';
newlinka.onclick = function() {alert("JS Added Link A"); return false;};
document.getElementById('links').appendChild(newlinka);
bra = document.createElement('br');
brb = document.createElement('br');
document.getElementById('links').appendChild(bra);
document.getElementById('links').appendChild(brb);

newlinkb = document.createElement('a');
newlinkb.innerHTML = 'Add JS Link';
newlinkb.setAttribute('href', 'javascript:void(0);');
newlinkb.setAttribute('onclick', 'teleporter_add_dynamic_link(); return false;');
document.getElementById('links').appendChild(newlinkb);
brc = document.createElement('br');
brd = document.createElement('br');
document.getElementById('links').appendChild(brc);
document.getElementById('links').appendChild(brd);

/* Add Dynamic Links */
function teleporter_add_dynamic_link() {
	newlink = document.createElement('a');
	newlink.innerHTML = 'Dynamically Added Link';
	newlink.href = '?page=a';
	newlink.setAttribute('class', 'dynamic-link');
	document.getElementById('links').appendChild(newlink);
	br = document.createElement('br');
	document.getElementById('links').appendChild(br);
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
