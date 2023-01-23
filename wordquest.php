<?php

// ===============================
// === WORDQUEST PLUGIN HELPER ===
// ===============================

$wordquestversion = '1.8.2';

// === Notes ===
// - Changelog at end of this file
// - Requires PHP 5.3 (for anonymous functions)
// -- (helper library load bypassed if not met)

// WordQuest Admin Menu Position
// -----------------------------
// Note: wordquest_menu_position filter is called in wordquest plugins - not in helper
// Example override (use in Child Theme functions.php or /wp-content/mu-plugins/):
//
// if (!has_filter('wordquest_menu_position', 'custom_wordquest_menu_position')) {
//	add_filter('wordquest_menu_position', 'custom_wordquest_menu_position');
// }
// if (!function_exists('custom_wordquest_menu_position')) {
//  function custom_wordquest_menu_position() {
//		return '10'; // numeric menu priority (defaults to 3)
//	}
// }

// Development TODOs
// -----------------
// TODO: handle Freemius plugin add-ons ?
// ? add collapse/expand buttons to righthand sidebar


// -------------------------------------------------
// Require PHP 5.3 Minimum (for Anonymous Functions)
// -------------------------------------------------
if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
	return;
}

// ---------------------------------
// Set this Wordquest Helper version
// ---------------------------------
// 1.6.0: wqv to wqhv for new variable functions
// 1.6.6: move wordquestversion to top for easy changing
$wqhv = str_replace( '.', '', $wordquestversion );

// --------------------
// Set Global Site URLs
// --------------------
// 1.6.5: added for clearer/cleaner usage
// 1.7.7: added https protocol to links
global $wqurls;
$wqurls = array(
	'wp'	=> 'https://wordpress.org',
	'wq'	=> 'https://wordquest.org',
	'wpm'	=> 'https://wpmedic.tech',
	'prn'	=> 'https://pluginreview.net',
	'bio'	=> 'https://bioship.space',
);

// ------------------------
// Set Debug Switch Default
// ------------------------
// 1.6.6: set debug switch to off to recheck later
global $wqdebug;
$wqdebug = false;


// -----------------------------------------
// === Version Handling Loader Functions ===
// -----------------------------------------
// ...future proof helper update library...

// ----------------------------------
// Add Helper version to global array
// ----------------------------------
// 1.6.0: change globals to use new variable functions (as not backcompatible!)
global $wordquesthelpers, $wqfunctions;
if ( !is_array( $wordquesthelpers ) ) {
	$wordquesthelpers = array( $wqhv );
} elseif ( !in_array( $wqhv, $wordquesthelpers ) ) {
	$wordquesthelpers[] = $wqhv;
}


// ------------------------------------------
// Set Latest Wordquest Version on Admin Load
// ------------------------------------------
// 1.5.0: use admin_init not plugins_loaded so as to be usable by themes
// 1.8.0: remove unnecessary third parameter
if ( !has_action( 'admin_init', 'wqhelper_admin_loader' ) ) {
	add_action( 'admin_init', 'wqhelper_admin_loader', 1 );
}
if ( !function_exists( 'wqhelper_admin_loader' ) ) {
 function wqhelper_admin_loader() {
	global $wqdebug;

	// --- maybe set debug mode ---
	// 1.6.6: check debug switch here so we can check permissions
	if ( current_user_can( 'manage_options' ) ) {
		// 1.8.1: use sanitize_text_field on request variable
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['wqdebug'] ) && in_array( sanitize_title( $_REQUEST['wqdebug'] ), array( '1', 'yes' ) ) ) {
			$wqdebug = true;
		}
	}

	// --- maybe remove old action ---
	// 1.6.0: maybe remove the pre 1.6.0 loader action
	if ( has_action( 'admin_init', 'wordquest_admin_load' ) ) {
		remove_action( 'admin_init', 'wordquest_admin_load' );
	}

	// --- set helper version to use ---
	// 1.6.0: new globals used for new method
	global $wordquesthelper, $wordquesthelpers;
	$wordquesthelper = max( $wordquesthelpers );
	if ( $wqdebug ) {
 		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		echo "<!-- WQ Helper Versions: " . esc_html( print_r( $wordquesthelpers, true ) ) . " -->";
		echo "<!-- Latest Version: " . esc_html( $wordquesthelper ) . " -->";
	}

	// --- load callable functions ---
	// 1.6.0: set the function caller helper
	global $wqcaller, $wqfunctions;
	$functionname = 'wqhelper_caller_';
	$func = $functionname . $wordquesthelper;
	if ( $wqdebug ) {
		echo "<!-- Caller Name: " . esc_html( $func ) . " -->";
	}

	// --- set callable functions ---
	if ( is_callable( $wqfunctions[$func] ) ) {
		$wqfunctions[$func]( $functionname );
	} elseif ( function_exists( $func ) ) {
		call_user_func( $func, $functionname );
	}
	if ( $wqdebug ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		echo "<!-- Caller Object: " . esc_html( print_r( $wqcaller, true ) ) . " -->";
	}

	// --- load admin notices ---
	// 1.5.0: set up any admin notices via helper version
	// 1.6.0: ...use caller function directly for this
	$adminnotices = 'wqhelper_admin_notices';
	if ( is_callable( $wqcaller ) ) {
		$wqcaller( $adminnotices );
	} elseif ( function_exists( $adminnotices ) ) {
		call_user_func( $adminnotices );
	}
 }
}

// ----------------------------------
// Function to Define Function Caller
// ----------------------------------
// 1.6.0: some lovely double abstraction here!
$funcname = 'wqhelper_caller_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
	$wqfunctions[$funcname] = function( $func ) {
		global $wqfunctions, $wqcaller;
		if ( !is_callable( $wqcaller ) ) {
			$wqcaller = function( $function, $args = null ) {
				global $wordquesthelper, $wqfunctions;
					$func = $function . '_' . $wordquesthelper;
					// echo "<!-- Called Function: ".$func." -->";
				if ( is_callable( $wqfunctions[$func] ) ) {
					return $wqfunctions[$func]( $args );
				} elseif ( function_exists( $func ) ) {
					return call_user_func( $func, $args );
				}
				// 1.8.0: added missing return null statement
				return null;
			};
		}
	};
}

// -------------------------------------
// Versioned Admin Page Caller Functions
// -------------------------------------
// 1.7.2: use direct superglobal to shorten functions
// wqhelper_admin_page
// wqhelper_admin_notice_boxer
// wqhelper_get_plugin_info
// wqhelper_admin_plugins_column
// wqhelper_admin_feeds_column
// wqhelper_install_plugin
// wqhelper_reminder_notice
// wqhelper_translate

// --- admin page ---
if ( !function_exists( 'wqhelper_admin_page' ) ) {
 function wqhelper_admin_page( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- admin notice boxer ---
if ( !function_exists( 'wqhelper_admin_notice_boxer' ) ) {
 function wqhelper_admin_notice_boxer( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- get plugins info ---
if ( !function_exists( 'wqhelper_get_plugin_info' ) ) {
 function wqhelper_get_plugin_info( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- admin page plugins column ---
if ( !function_exists( 'wqhelper_admin_plugins_column' ) ) {
 function wqhelper_admin_plugins_column( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- admin page feeds column ---
if ( !function_exists( 'wqhelper_admin_feeds_column' ) ) {
 function wqhelper_admin_feeds_column( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// 1.6.5: install WordQuest plugin
if ( !function_exists( 'wqhelper_install_plugin' ) ) {
 function wqhelper_install_plugin( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// 1.6.5: reminder notice message
if ( !function_exists( 'wqhelper_reminder_notice' ) ) {
 function wqhelper_reminder_notice( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// 1.6.9: translation wrapper
if ( !function_exists( 'wqhelper_translate' ) ) {
 function wqhelper_translate( $string ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $string );
 }
}

// ---------------------------------
// Sidebar Floatbox Caller Functions
// ---------------------------------
// 1.7.2: use direct superglobal to shorten functions
// 1.7.4: added patreon wrapper function
// - wqhelper_sidebar_floatbox
// - wqhelper_sidebar_patreon_button
// - wqhelper_sidebar_paypal_donations
// - wqhelper_sidebar_testimonial_box
// - wqhelper_sidebar_floatmenuscript
// - wqhelper_sidebar_stickykitscript

// --- sidebar floatbox ---
if ( !function_exists( 'wqhelper_sidebar_floatbox' ) ) {
 function wqhelper_sidebar_floatbox( $args = null ) {
	return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- patreon supporters button ---
if ( !function_exists( 'wqhelper_sidebar_patreon_button' ) ) {
 function wqhelper_sidebar_patreon_button( $args = null ) {
	return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- paypal donations button ---
if ( !function_exists( 'wqhelper_sidebar_paypal_donations' ) ) {
 function wqhelper_sidebar_paypal_donations( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- testimonials box ---
if ( !function_exists( 'wqhelper_sidebar_testimonial_box' ) ) {
 function wqhelper_sidebar_testimonial_box( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- floating menu script ---
if ( !function_exists( 'wqhelper_sidebar_floatmenuscript' ) ) {
 function wqhelper_sidebar_floatmenuscript( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- sticky kit script ---
if ( !function_exists( 'wqhelper_sidebar_stickykitscript' ) ) {
 function wqhelper_sidebar_stickykitscript( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}


// Dashboard Feed Caller Functions
// -------------------------------
// 1.7.2: use direct superglobal to shorten functions
// - wqhelper_add_dashboard_feed_widget
// - wqhelper_dashboard_feed_javascript
// - wqhelper_dashboard_feed_widget
// - wqhelper_pluginreview_feed_widget
// - wqhelper_process_rss_feed
// - wqhelper_load_category_feed
// - wqhelper_get_feed_ad

// --- add dashboard feed widget ---
if ( !function_exists( 'wqhelper_add_dashboard_feed_widget' ) ) {
 function wqhelper_add_dashboard_feed_widget( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- dashboard feed javascript ---
if ( !function_exists( 'wqhelper_dashboard_feed_javascript' ) ) {
 function wqhelper_dashboard_feed_javascript( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- dashboard feed widget ---
if ( !function_exists( 'wqhelper_dashboard_feed_widget' ) ) {
 function wqhelper_dashboard_feed_widget( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- plugin review feed widget ---
if ( !function_exists( 'wqhelper_pluginreview_feed_widget' ) ) {
 function wqhelper_pluginreview_feed_widget( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- process RSS feed ---
if ( !function_exists( 'wqhelper_process_rss_feed' ) ) {
 function wqhelper_process_rss_feed( $args = null ) {
	return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}
// --- load category feed ---
if ( !function_exists( 'wqhelper_load_category_feed' ) ) {
 function wqhelper_load_category_feed( $args = null ) {
	// 1.7.7: simplified admin condition logic
	if ( is_admin() ) {
		return $GLOBALS['wqcaller']( __FUNCTION__, $args );
	}
	// 1.8.0: added missing empty return
	return '';
 }
}
// --- get feed ad ---
// 1.8.0: added feed ad wrapper function
if ( !function_exists( 'wqhelper_get_feed_ad' ) ) {
 function wqhelper_get_feed_ad( $args = null ) {
	return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}


// --------------------------
// === Styles and Scripts ===
// --------------------------

// ---------------------------------
// Add Helper Styles to Admin Footer
// ---------------------------------
if ( !has_action( 'admin_footer', 'wqhelper_admin_styles' ) ) {
	add_action( 'admin_footer', 'wqhelper_admin_styles' );
}
if ( !function_exists( 'wqhelper_admin_styles' ) ) {
 function wqhelper_admin_styles( $args = null ) {
	remove_action( 'admin_footer', 'wordquest_admin_styles' );
	return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}

// ----------------------------------
// Add Helper Scripts to Admin Footer
// ----------------------------------
if ( !has_action( 'admin_footer', 'wqhelper_admin_scripts' ) ) {
	add_action( 'admin_footer', 'wqhelper_admin_scripts' );
}
if ( !function_exists( 'wqhelper_admin_scripts' ) ) {
 function wqhelper_admin_scripts( $args = null ) {
	remove_action( 'admin_footer', 'wordquest_admin_scripts' );
	return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}


// ----------------------
// === AJAX Functions ===
// ----------------------

// AJAX for reminder dismissal
// ---------------------------
// 1.6.5: added this AJAX function
if ( !has_action( 'wp_ajax_wqhelper_reminder_dismiss', 'wqhelper_reminder_dismiss' ) ) {
	add_action( 'wp_ajax_wqhelper_reminder_dismiss', 'wqhelper_reminder_dismiss' );
}
if ( !function_exists( 'wqhelper_reminder_dismiss' ) ) {
 function wqhelper_reminder_dismiss( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}

// -----------------------
// AJAX Load Category Feed
// -----------------------
if ( !has_action( 'wp_ajax_wqhelper_load_feed_cat', 'wqhelper_load_feed_category' ) ) {
	add_action( 'wp_ajax_wqhelper_load_feed_cat', 'wqhelper_load_feed_category' );
}
if ( !function_exists( 'wqhelper_load_feed_category' ) ) {
 function wqhelper_load_feed_category( $args = null ) {
	 return $GLOBALS['wqcaller']( __FUNCTION__, $args );
 }
}

// ----------------------
// Update Sidebar Options
// ----------------------
// 1.6.0: ! NOTE ! caller exception ! use matching form version function here just in case...
if ( !has_action( 'wp_ajax_wqhelper_update_sidebar_boxes', 'wqhelper_update_sidebar_boxes' ) ) {
	add_action( 'wp_ajax_wqhelper_update_sidebar_boxes', 'wqhelper_update_sidebar_boxes' );
}
if ( !function_exists( 'wqhelper_update_sidebar_boxes' ) ) {
 function wqhelper_update_sidebar_boxes() {

	// --- get helper version ---
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( !isset( $_POST['wqhv'] ) ) {
		return;
	} else {
		// 1.8.1: sanitize to integer and cast to string
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$wqhv = (string) absint( $_POST['wqhv'] );
	}
	// 1.6.6: added sanitization of version value
	// 1.8.1: simplified to check for 3 digits only
	if ( strlen( $wqhv ) !== 3 ) {
		return;
	}

	// --- set matching function version ---
	$func = 'wqhelper_update_sidebar_options_' . $wqhv;

	// --- call function ---
	// 1.6.5: fix to function call method
	global $wqfunctions;
	if ( is_callable( $wqfunctions[$func] ) ) {
		$wqfunctions[$func]();
	} elseif ( function_exists( $func ) ) {
		call_user_func( $func );
	}
 }
}


// ----------------------------------
// === Version Specific Functions ===
// ----------------------------------
// (functions below this point must be suffixed with _{VERSION} to work
// and update with each plugin helper version regardless of change state)

// -------------------
// Translation Wrapper
// -------------------
// 1.6.9: check translated labels global
$funcname = 'wqhelper_translate_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
	$wqfunctions[$funcname] = function( $string ) {
		global $wqlabels;
		if ( isset( $wqlabels[$string] ) ) {
			return $wqlabels[$string];
		}
		// 1.6.9: added fallback translation for bioship theme
		if ( function_exists( 'bioship_translate' ) ) {
			return bioship_translate( $string );
		}
		if ( function_exists( 'translate' ) ) {
			// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
			return translate( (string) $string, 'default' );
		}
		return $string;
	};
}

// ------------------
// Admin Notice Boxer
// ------------------
// (for settings pages)
$funcname = 'wqhelper_admin_notice_boxer_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
	$wqfunctions[$funcname] = function() {

	// 1.7.7: bug out if already using AdminSanity Notices box
	// 1.7.8: replace with better check for AdminSanity Notices box
	global $adminsanity;
	if ( isset( $adminsanity ) && isset( $adminsanity['load'] ) && $adminsanity['load']['notices'] ) {
		return;
	}

	// --- count admin notices ---
	// global $wp_filter; $notices = 0; // print_r($wp_filter);
	// if (isset($wp_filter['admin_notices'])) {$adminnotices = $notices = count($wp_filter['admin_notices']);}
	// if (is_network_admin()) {if (isset($wp_filter['network_admin_notices'])) {$networknotices = count($wp_filter['network_admin_notices']); $notices = $notices + $networknotices;} }
	// if (is_user_admin()) {if (isset($wp_filter['user_admin_notices'])) {$usernotices = count($wp_filter['user_admin_notices']); $notices = $notices + $usernotices;} }
	// if (isset($wp_filter['all_admin_notices'])) {$alladminnotices = count($wp_filter['all_admin_notices']); $notices = $notices + $alladminnotices;}
	// if ($notices == 0) {return;}

	// print_r($wp_filter['admin_notices']); print_r($wp_filter['all_admin_notices']);
	// echo "<!-- Notices: ".$adminnotices." - ".$networknotices." - ".$useradminnotices." - ".$alladminnotices." -->";

	// --- toggle notice box script ---
	echo "<script>function wq_togglenoticebox() {divid = 'adminnoticewrap';
	if (document.getElementById(divid).style.display == '') {
		document.getElementById(divid).style.display = 'none'; document.getElementById('adminnoticearrow').innerHTML = '&#9662;';}
	else {document.getElementById(divid).style.display = ''; document.getElementById('adminnoticearrow').innerHTML= '&#9656;';} } ";
	// this is from /wp-admin/js/common.js... to move the notices if common.js is not loaded...
	echo "jQuery(document).ready(function() {jQuery( 'div.updated, div.error, div.notice' ).not( '.inline, .below-h2' ).insertAfter( jQuery( '.wrap h1, .wrap h2' ).first() ); });";
	echo "</script>";

	// --- output notice box ---
	echo '<div style="width:680px" id="adminnoticebox" class="postbox">';
	echo '<h3 class="hndle" style="margin:7px 14px;font-size:12pt;" onclick="wq_togglenoticebox();">';
	echo '<span id="adminnoticearrow">&#9662;</span> &nbsp; ';
	echo esc_html( wqhelper_translate( 'Admin Notices' ) );
	echo '</span></h3><div id="adminnoticewrap" style="display:none;"><h2></h2></div></div>';

 };
}

// ---------------------------
// Usage Reminder Notice Check
// ---------------------------
// 1.5.0: added reminder prototype that does nothing yet
// 1.6.5: completed usage reminder notices
$funcname = 'wqhelper_admin_notices_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
	$wqfunctions[$funcname] = function() {

	global $wordquestplugins;
	foreach ( $wordquestplugins as $pluginslug => $wqplugin ) {

		// --- check current user capabilities ---
		// 1.7.3: only show reminder notice to users with capabilities
		$shownotice = false;
		if ( current_user_can( 'manage_options' )
			|| ( ( 'bioship' == $pluginslug ) && current_user_can( 'install_themes' ) )
			|| ( ( 'bioship' != $pluginslug ) && current_user_can( 'install_plugins' ) ) ) {
				$shownotice = true;
		}

		if ( $shownotice ) {

			// 1.6.8: move here to fix undefined index warning
			$prefix = $wqplugin['settings'];

			// 1.7.0: moved up here to fix install version check
			$sidebaroptions = get_option( $prefix . '_sidebar_options' );

			// 1.6.7: maybe set first install version for plugin
			if ( !isset( $sidebaroptions['installversion'] ) ) {
				$sidebaroptions['installversion'] = $wqplugin['version'];
				update_option( $prefix . '_sidebar_options', $sidebaroptions );
			}
			// 1.6.5: no reminders needed if pro version
			// 1.7.7: added check if plan key is set for back-compat
			if ( isset( $wqplugin['plan'] ) && ( 'premium' == $wqplugin['plan'] ) ) {
				return;
			}

			// 1.6.5: no reminders if donation box has been turned off
			// 1.6.7: revert that as so many other ways to still contribute
			// if ( (isset($sidebaroptions['donationboxoff']))
			//   && ($sidebaroptions['donationboxoff'] == 'checked') ) {return;}

			if ( isset( $sidebaroptions['installdate'] ) ) {

				// --- check usage length ---
				$reminder = false;
				$installtime = strtotime( $sidebaroptions['installdate'] );
				$timesince = time() - $installtime;
				$dayssince = floor( $timesince / ( 24 * 60 * 60 ) );

				// --- 30 days, 90 days and 1 year notices ---
				if ( $dayssince > 365 ) {
					// --- 365 day notice ---
					if ( !isset( $sidebaroptions['365days'] ) ) {
						$reminder = '365';
					}
				} elseif ( $dayssince > 90 ) {
					// --- 90 day notice ---
					if ( !isset( $sidebaroptions['90days'] ) ) {
						$reminder = '90';
					}
				} elseif ( $dayssince > 30 ) {
					// --- 30 day notice ---
					if ( !isset( $sidebaroptions['30days'] ) ) {
						$reminder = '30';
					}
				}

				if ( $reminder ) {
					// --- add an admin reminder notice ---
					global $wqreminder;
					$wqreminder[$pluginslug] = $wqplugin;
					$wqreminder[$pluginslug]['days'] = $dayssince;
					$wqreminder[$pluginslug]['notice'] = $reminder;
					add_action( 'admin_notices', 'wqhelper_reminder_notice' );
				}
			} else {
				$sidebaroptions['installdate'] = date( 'Y-m-d' );
				update_option( $prefix . '_sidebar_options', $sidebaroptions );
			}
		}
	}
 };
}

// ---------------------
// Usage Reminder Notice
// ---------------------
// 1.6.5: added reminder notice text
$funcname = 'wqhelper_reminder_notice_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
	$wqfunctions[$funcname] = function() {

		global $wqreminder, $wqurls, $wordquestplugins;

		// --- loop reminders ---
		foreach ( $wqreminder as $pluginslug => $reminder ) {

			// --- reminder wrapper ---
			echo '<div class="updated notice is-dismissable" id="' . esc_attr( $pluginslug ) . '-reminder-notice" style="font-size:16px; line-height:24px; margin:0;">';

			echo esc_html( wqhelper_translate( "You've been enjoying" ) ) . ' ';
			echo esc_html( $wqreminder[$pluginslug]['title'] ) . ' ' . esc_html( wqhelper_translate( 'for' ) ) . ' ';
			echo esc_html( $wqreminder[$pluginslug]['days'] ) . ' ' . esc_html( wqhelper_translate( 'days' ) ) . '. ';
			echo esc_html( wqhelper_translate( "If you like it, here's some ways you can help make it better" ) ) . ':<br>';

			// Action Links Table
			// ------------------
			// 1.6.7: extended link anchor text for clarity
			echo '<table id="wq-reminders" cellpadding="0" cellspacing="0" style="width:100%;"><tr><td>';
				echo '<ul>';

					// --- Supporter Link ---
					// 1.7.4: add supporter link heart icon
					// 1.7.6: fix to check if donate link defined
					if ( isset( $wordquestplugins[$pluginslug]['donate'] ) ) {
						$donatelink = $wordquestplugins[$pluginslug]['donate'];
						echo '<li style="margin-left:0px;">';
							echo '<span style="color:#E00;" class="dashicons dashicons-heart"></span> ';
							if ( strstr( $donatelink, 'patreon' ) ) {
								// --- Patreon supporter Link ---
								echo '<a class="notice-link" href="' . esc_url( $donatelink ) . '" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Become a Supporter' ) ) . '</a>';
							} else {
								// --- WordQuest subscription link ---
								echo '<a class="notice-link" href="' . esc_url( $wqurls['wq'] ) . '/contribute/?tab=supporterlevels" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Become a Supporter' ) ) . '</a>';
							}
						echo '</li>';
					}

					// --- Donate Link ---
					// 1.7.4: removed one-off donation link (just too many links)
					 // echo '<li style="display:inline-block;margin-left:15px;">';
					//    echo '<a href="' . esc_url( $wqurls['wq'] ) . '/contribute/?plugin=' . $pluginslug . '" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Make a Donation' ) ) . '</a>';
					// echo '</li>';

					// --- Rating Link (for WordPress.org) ---
					// 1.7.2: removed ?rate=5 from rating URLs (no longer used)
					// 1.7.4: added rating link star icon
					if ( isset( $wqreminder['wporgslug'] ) ) {
						echo '<li>';
							echo '<span style="color:#FC5;" class="dashicons dashicons-star-filled"></span> ';
							// 1.6.7: added different rating action for theme
							if ( 'bioship' == $pluginslug ) {
								// theme rate link --
								$rateurl = $wqurls['wp'] . '/support/theme/' . $pluginslug . '/reviews/#new-post';
								echo '<a class="notice-link" href="' . esc_url( $rateurl ) . '" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Rate this Theme' ) ) . '</a>';
							} else {
								// --- plugin rate link ---
								$rateurl = $wqurls['wp'] . '/support/plugin/' . $pluginslug . '/reviews/#new-post';
								echo '<a class="notice-link" href="' . esc_url( $rateurl ) . '" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Rate this Plugin' ) ) . '</a>';
							}
						echo '</li>';
					}

					// 1.7.2: removed unused testimonial link
					// echo "<li><a href='".$wqurls['wq']."/contribute/?tab=testimonial' target=_blank>&rarr; ".wqhelper_translate('Send a Testimonial')."</a></li>";

					// --- Share Link ---
					// 1.7.2: add share theme / plugin link
					// 1.7.4: added share link icon
					echo '<li>';
						echo '<span style="color:#E0E;" class="dashicons dashicons-share"></span> ';
						if ( 'bioship' == $pluginslug ) {
							echo '<a class="notice-link" href="' . esc_url( $wqurls['bio'] ) . '#share" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Share this Theme' ) ) . '</a>';
						} else {
							$shareurl = $wqurls['wq'] . '/plugins/' . $pluginslug . '/#share';
							echo '<a class="notice-link" href="' . esc_url( $shareurl ) . '" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Share this Plugin' ) ) . '</a>';
						}
					echo '</li>';

					// --- Feedback Link ---
					// 1.7.4: renamed anchor to simply feedback in this context
					// 1.7.4: added feedback link (slanted envelope) icon
					echo '<li>';
						echo '<span style="color:#00E;" class="dashicons dashicons-email-alt"></span> ';
						$supporturl = $wqurls['wq'] . '/support/' . $pluginslug;
						echo '<a class="notice-link" href="' . esc_url( $supporturl ) . '" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Provide Feedback' ) ) . '</a>';
					echo "</li>";

					// --- Contribute Link ---
					// 1.7.4: removed contribute link (just too many links)
					// echo "<li style='display:inline-block;margin-left:15px;'><a href='".$wqurls['wq']."/contribute/?tab=development' target=_blank>&rarr; ".wqhelper_translate('Contribute to Development')."</a></li>";

					// --- Pro Version plan link (Freemius) ---
					// TODO: handle Freemius plugin add-ons ?
					if ( isset( $wqreminder['hasplans'] ) && ( $wqreminder['hasplans'] ) ) {
						$upgradeurl = admin_url( 'admin.php' ) . '?page=' . $wqreminder['slug'] . '-pricing';
						echo '<li><a href="' . esc_url( $upgradeurl ) . '">';
							echo '<b>&rarr; ' . esc_html( wqhelper_translate( 'Go PRO' ) ) . '</b>';
						echo '</a></li>';
					}
				echo '</ul>';

			echo '</td><td style="text-align:right;">';

				// --- dismiss notice X link ---
				$dismisslink = admin_url( 'admin-ajax.php' ) . '?action=wqhelper_reminder_dismiss&slug=' . $pluginslug . '&notice=' . $wqreminder[$pluginslug]['notice'];
				echo '<a href="' . esc_url( $dismisslink ) . '" target="wqdismissframe" style="text-decoration:none;" title="' . esc_attr( wqhelper_translate( 'Dismiss this Notice' ) ) . '">';
				echo '<div class="dashicons dashicons-dismiss" style="font-size:16px;"></div></a>';

			echo '</td></tr></table></div>';
		}

		// --- notice styles ---
		// 1.7.5: added notice styling
		echo "<style>#wq-reminders ul {list-style:none; padding:0; margin:0;}
        #wq-reminders ul li {display:inline-block; margin-left:15px;}
        #wq-reminders span.dashicons {display:inline; font-size:16px; color:#00E; vertical-align:middle;}
        .notice-link {text-decoration:none;} .notice-link:hover {text-decoration:underline;}</style>";

		// --- notice dismissal iframe ---
		echo '<iframe style="display:none;" src="javascript:void(0);" name="wqdismissframe" id="wqdimissframe"></iframe>';
	};
}

// -----------------------
// AJAX Reminder Dismisser
// -----------------------
$funcname = 'wqhelper_reminder_dismiss_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
	$wqfunctions[$funcname] = function() {

		// --- check conditions ---
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pluginslug = sanitize_title( $_REQUEST['slug'] );
		// 1.8.1: sanitize to integer and validate against array
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice = absint( $_REQUEST['notice'] );
		if ( !in_array( $notice, array( 30, 90, 365 ) ) ) {
			return;
		}

		// --- check capabilities ---
		$dismiss = false;
		if ( current_user_can( 'manage_options' )
			|| ( ( 'bioship' == $pluginslug ) && current_user_can( 'install_themes' ) )
			|| ( ( 'bioship' != $pluginslug ) && current_user_can( 'install_plugins' ) ) ) {
				$dismiss = true;
		}

		if ( $dismiss ) {

			// --- dismiss the reminder notice ---
			global $wordquestplugins;
			$prefix = $wordquestplugins[$pluginslug]['settings'];
			$sidebaroptions = get_option( $prefix . '_sidebar_options' );
			if ( isset( $sidebaroptions[$notice . 'days'] ) && ( 'dismissed' == $sidebaroptions[$notice . 'days'] ) ) {
				$sidebaroptions[$notice . 'days'] = '';
			} else {
				$sidebaroptions[$notice . 'days'] = 'dismissed';
			}
			update_option( $prefix . '_sidebar_options', $sidebaroptions );

			// --- hide the notice in the parent window ---
			echo "<script>parent.document.getElementById('" . esc_js( $pluginslug ) . "-reminder-notice').style.display = 'none';</script>";
		}

		exit;
	};
}

// --------------------------
// Get WordQuest Plugins Info
// --------------------------
$funcname = 'wqhelper_get_plugin_info_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
	$wqfunctions[$funcname] = function() {
		global $wqurls, $wqdebug;

		// --- maybe get cached plugin info ---
		// 1.5.0: get plugin info (maximum twice daily)
		$plugininfo = get_transient( 'wordquest_plugin_info' );

		// clear transient when debugging
		if ( $wqdebug ) {
			$plugininfo = '';
		}

		// --- maybe get plugin info now ---
		if ( !$plugininfo || ( '' == $plugininfo ) || !is_array( $plugininfo ) ) {
			$pluginsurl = $wqurls['wq'] . '/?get_plugins_info=yes';
			$args = array( 'timeout' => 15 );
			$plugininfo = wp_remote_get( $pluginsurl, $args );
			if ( !is_wp_error( $plugininfo ) ) {
				$plugininfo = $plugininfo['body'];
				$dataend = "*****END DATA*****";
				if ( strstr( $plugininfo, $dataend ) ) {
					$pos = strpos( $plugininfo, $dataend );
					$plugininfo = substr( $plugininfo, 0, $pos );
					$plugininfo = json_decode( $plugininfo, true );
					set_transient( 'wordquest_plugin_info', $plugininfo, ( 12 * 60 * 60 ) );
				} else {
					$plugininfo = '';
				}
			} else {
				$plugininfo = '';
			}
		}

		if ( $wqdebug ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions
			echo "<!-- Plugin Info: " . esc_html( print_r( $plugininfo ) ) . " -->";
		}
		return $plugininfo;
	};
}

// ---------------------------
// Version Specific Admin Page
// ---------------------------
$funcname = 'wqhelper_admin_page_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
	$wqfunctions[$funcname] = function() {

	global $wordquesthelper, $wordquestplugins, $wqurls;

	echo '<div id="pagewrap" class="wrap">';

	// --- admin notice boxer ---
	wqhelper_admin_notice_boxer();

	// --- toggle metabox script ---
	echo "<script>function wq_togglemetabox(divid) {
		divid += '-inside';
		if (document.getElementById(divid).style.display == '') {
			document.getElementById(divid).style.display = 'none';
		} else {document.getElementById(divid).style.display = '';}
	}</script>";

	// --- admin page styles ---
	echo '<style>#plugincolumn, #feedcolumn {display: inline-block; float:left; margin: 0 5px;}
	#plugincolumn .postbox {max-width:300px;} #feedcolumn .postbox {max-width:300px;}
	#plugincolumn .postbox h2, #feedcolumn .postbox h2 {font-size: 16px; margin-top: 0; background-color: #E0E0EE; padding: 5px;}
	#page-title a {text-decoration:none;} #page-title h2 {color: #3568A9;}
	</style>';

	// --- Floating Sidebar ---
	// set dummy "plugin" values for admin page sidebar
	global $wordquestplugins, $wordquesthelper;
	$wordquestplugins['wordquest'] = array(
		'version'	=> $wordquesthelper,
		'title'		=> 'WordQuest Alliance',
		'namespace'	=> 'wordquest',
		'settings'	=> 'wq',
		'plan'		=> 'free',
		'wporg'		=> false,
		'wporgslug'	=> false,
	);
	$args = array( 'wordquest', 'special' );
	wqhelper_sidebar_floatbox( $args );

	// --- load sticky kit on sidebar ---
	// 1.6.5: replace floatmenu with stickykit
	// 1.8.1: use new echo argument on stickykit function
	wqhelper_sidebar_stickykitscript( true );
	echo "<style>#floatdiv {float:right;} #wpcontent, #wpfooter {margin-left:150px !important;}</style>";
	echo "<script>jQuery('#floatdiv').stick_in_parent();</script>";
	unset( $wordquestplugins['wordquest'] );

	// echo wqhelper_sidebar_floatmenuscript();
	// echo '<script language="javascript" type="text/javascript">
	// floatingMenu.add("floatdiv", {targetRight: 10, targetTop: 20, centerX: false, centerY: false});
	// function move_upper_right() {
	//	floatingArray[0].targetTop=20;
	//	floatingArray[0].targetBottom=undefined;
	//	floatingArray[0].targetLeft=undefined;
	//	floatingArray[0].targetRight=10;
	//	floatingArray[0].centerX=undefined;
	//	floatingArray[0].centerY=undefined;
	// }
	// move_upper_right();
	// </script>

	// --- Admin Page Title ---
	$wordquesticon = plugins_url( 'images/wordquest.png', __FILE__ );
	echo '<style>.wqlink {text-decoration:none;} .wqlink:hover {text-decoration:underline;}</style>';
	echo '<table><tr>';
		echo '<td width="20"></td>';
		echo '<td><img src="' . esc_url( $wordquesticon ) . '" alt="' . esc_attr( wqhelper_translate( 'WordQuest icon' ) ) . '"></td>';
		echo '<td width="20"></td>';
		echo '<td><div id="page-title"><a href="' . esc_url( $wqurls['wq'] ) . '" target="_blank"><h2>WordQuest Alliance</h2></a></div></td>';
		echo '<td width="30"></td>';
		echo '<td><h4>&rarr; <a href="' . esc_url( $wqurls['wq'] ) . '/register/" class="wqlink" target="_blank">' . esc_html( wqhelper_translate( 'Join' ) ) . '</a></h4></td>';
		echo '<td> / </td>';
		echo '<td><h4><a href="' . esc_url( $wqurls['wq'] ) . '/login/" class="wqlink" target="_blank">' . esc_html( wqhelper_translate( 'Login' ) ) . '</a></h4></td>';
		echo '<td width="20"></td>';
		echo '<td><h4>&rarr; <a href="' . esc_url( $wqurls['wq'] ) . '/solutions/" class="wqlink" target="_blank">' . esc_html( wqhelper_translate( 'Solutions' ) ) . '</a></h4></td>';
		echo '<td width="20"></td>';
		echo '<td><h4>&rarr; <a href="' . esc_url( $wqurls['wq'] ) . '/contribute/" class="wqlink" target="_blank">' . esc_html( wqhelper_translate( 'Contribute' ) ) . '</a></h4></td>';
	echo '</tr></table>';

	// --- Output Plugins Column ---
	wqhelper_admin_plugins_column( null );

	// --- Output Feeds Column ---
	wqhelper_admin_feeds_column( null );

	// --- Wordquest Sidebar 'plugin' box ---
	if ( !function_exists( 'wq_sidebar_plugin_footer' ) ) {
		function wq_sidebar_plugin_footer() {
			global $wqurls;
			$iconurl = plugins_url( 'images/wordquest.png', __FILE__ );
			echo '<div id="pluginfooter"><div class="stuffbox" style="width:250px;background-color:#ffffff;"><h3>' . esc_html( wqhelper_translate( 'Source Info' ) ) . '</h3><div class="inside">';
			echo '<center><table><tr>';
				echo '<td><a href="' . esc_url( $wqurls['wq'] ) . '" target="_blank"><img src="' . esc_url( $iconurl ) . '" alt="' . esc_attr( wqhelper_translate( 'WordQuest Icon' ) ) . ' "border="0"></a></td>';
				echo '<td width="14"></td>';
				echo '<td><a href="' . esc_url( $wqurls['wq'] ) . '" target="_blank">WordQuest Alliance</a><br>';
				echo '<a href="' . esc_url( $wqurls['wq'] ) . '/plugins/" target="_blank"><b>&rarr; WordQuest Plugins</b></a><br>';
				echo '<a href="' . esc_url( $wqurls['prn'] ) . '/directory/" target="_blank">&rarr; Plugin Directory</a></td>';
				echo '</tr></table></center>';
			echo '</div></div></div>';
		}
	}

	echo '</div>';

	// --- hidden iframe for plugin actions ---
	echo '<iframe id="pluginactionframe" src="javascript:void(0);" style="display:none;"></iframe>';

	echo '</div>';
 };
}

// -------------------------------
// Version Specific Plugins Column
// -------------------------------
$funcname = 'wqhelper_admin_plugins_column_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
	$wqfunctions[$funcname] = function( $args ) {

	global $wordquesthelper, $wordquestplugins, $wqurls, $wqdebug;

	// --- check if WordPress.Org plugins only ---
	// 1.6.6: check if current WQ plugins are all installed via WordPress.Org
	// (if so, only provide option to install other WQ plugins in repository)
	global $wq_wordpress_org;
	$wq_wordpress_org = true;
	foreach ( $wordquestplugins as $pluginslug => $plugin ) {
		// - if this is false, it was from WordQuest not WordPress -
		if ( !$plugin['wporg'] ) {
			$wq_wordpress_org = false;
		}
	}

	// --- Plugin Action Select Javascript ---
	// 1.7.2: updated star rating link
	// TODO: test all options here more thoroughly..?
	echo "<script>
	function wq_plugin_action(pluginslug) {
		selectelement = document.getElementById(pluginslug+'-action');
		actionvalue = selectelement.options[selectelement.selectedIndex].value;
		linkel = document.getElementById(pluginslug+'-link');
		adminpageurl = '" . esc_url( admin_url( 'admin.php' ) ) . "';
		wqurl = '" . esc_url( $wqurls['wq'] ) . "';
		if (actionvalue == 'settings') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug;}
		if (actionvalue == 'update') {linkel.target = '_self'; linkel.href = document.getElementById(pluginslug+'-update-link').value;}
		if (actionvalue == 'activate') {linkel.target = '_self'; linkel.href = document.getElementById(pluginslug+'-activate-link').value;}
		if (actionvalue == 'install') {linkel.target = '_self';	linkel.href = document.getElementById(pluginslug+'-install-link').value;}
		if (actionvalue == 'support') {linkel.target = '_blank'; linkel.href = adminpageurl+'?page='+pluginslug+'-wp-support-forum';}
		if (actionvalue == 'donate') {linkel.target = '_blank';	linkel.href = wqurl+'/contribute/?plugin='+pluginslug;}
		if (actionvalue == 'testimonial') {linkel.target = '_blank'; linkel.href = wqurl+'/contribute/?tab=testimonial';}
		if (actionvalue == 'rate') {linkel.target = '_blank'; linkel.href = '" . esc_url( $wqurls['wp'] ) . "/plugins/'+pluginslug+'/reviews/#new-post';}
		if (actionvalue == 'development') {linkel.target = '_blank'; linkel.href= wqurl+'/contribute/?tab=development';}
		if (actionvalue == 'contact') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug+'-contact';}
		if (actionvalue == 'home') {linkel.target = '_blank'; linkel.href = wqurl+'/plugins/'+pluginslug+'/';}
		if (actionvalue == 'upgrade') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug+'-pricing';}
		if (actionvalue == 'account') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug+'-account';}
	}</script>";

	// --- link styles ---
	echo "<style>.pluginlink {text-decoration:none;} .pluginlink:hover {text-decoration:underline;}</style>";

	// --- Get Installed and Active Plugin Slugs ---
	$i = 0;
	$pluginslugs = array();
	foreach ( $wordquestplugins as $pluginslug => $values ) {
		$pluginslugs[$i] = $pluginslug;
		$i++;
	}
	// if ($wqdebug) {echo "<!-- Active Wordquest Plugins: "; print_r($pluginslugs); echo " -->";}

	// --- Get All Installed Plugins Info ---
	$i = 0;
	$installedplugins = get_plugins();
	$installedslugs = array();
	foreach ( $installedplugins as $pluginfile => $values ) {
		$installedslugs[$i] = sanitize_title( $values['Name'] );
		$i++;
	}
	// if ($wqdebug) {echo "<!-- Installed Plugins: "; print_r($installedplugins); echo " -->";}
	// if ($wqdebug) {echo "<!-- Installed Plugin Slugs: "; print_r($installedslugs); echo " -->";}

	// Get Plugin Update Info
	// ----------------------
	// 1.6.6: define empty pluginupdates array
	$i = 0;
	$updateplugins = get_site_transient( 'update_plugins' );
	$pluginupdates = array();
	// 1.7.3: adde property exists check
	if ( property_exists( $updateplugins, 'response' ) ) {
		foreach ( $updateplugins->response as $pluginfile => $values ) {
			$pluginupdates[$i] = $values->slug;
			$i++;
		}
	}
	// if ($wqdebug) {echo "<!-- Plugin Updates: "; print_r($updateplugins); echo " -->";}
	// if ($wqdebug) {echo "<!-- Plugin Update Slugs: "; print_r($pluginupdates); echo " -->";}

	// --- Get Available Plugins from WordQuest.org ---
	$plugininfo = wqhelper_get_plugin_info();

	// process plugin info
	$i = 0;
	$wqplugins = $wqpluginslugs = array();
	if ( is_array( $plugininfo ) ) {
		foreach ( $plugininfo as $plugin ) {
			// print_r( $plugin ); // debug point
			if ( isset( $plugin['slug'] ) ) {
				$wqpluginslugs[$i] = $pluginslug = $plugin['slug'];
				$i++;
				if ( isset( $plugin['title'] ) ) {
					$wqplugins[$pluginslug]['title'] = $plugin['title'];
				}
				if ( isset( $plugin['home'] ) ) {
					$wqplugins[$pluginslug]['home'] = $plugin['home'];
				}
				if ( isset( $plugin['description'] ) ) {
					$wqplugins[$pluginslug]['description'] = $plugin['description'];
				}
				if ( isset( $plugin['icon'] ) ) {
					$wqplugins[$pluginslug]['icon'] = $plugin['icon'];
				}
				if ( isset( $plugin['paidplans'] ) ) {
					$wqplugins[$pluginslug]['paidplans'] = $plugin['paidplans'];
				}
				if ( isset( $plugin['package'] ) ) {
					$wqplugins[$pluginslug]['package'] = $plugin['package'];
				}

				if ( isset( $plugin['tags'] ) ) {
					$wqplugins[$pluginslug]['tags'] = $plugin['tags'];
				}
				if ( isset( $plugin['cats'] ) ) {
					$wqplugins[$pluginslug]['cats'] = $plugin['cats'];
				}

				// 1.6.5: check release date and status
				if ( isset( $plugin['releasedate'] ) ) {
					$wqplugins[$pluginslug]['releasedate'] = $plugin['releasedate'];
				}
				if ( isset( $plugin['releasestatus'] ) ) {
					$wqplugins[$pluginslug]['releasestatus'] = $plugin['releasestatus'];
				} else {
					$wqplugins[$pluginslug]['releasestatus'] = 'Upcoming';
				}

				// 1.6.6: check for wordpress.org slug
				if ( isset( $plugin['wporgslug'] ) ) {
					$wqplugins[$pluginslug]['wporgslug'] = $plugin['wporgslug'];
				} else {
					$wpplugins[$pluginslug]['wporgslug'] = false;
				}

				if ( in_array( $pluginslug, $installedslugs ) ) {
					$wqplugins[$pluginslug]['installed'] = 'yes';
				} else {
					$wqplugins[$pluginslug]['installed'] = 'no';
				}

				// --- get latest plugin release ---
				if ( isset( $plugin['latestrelease'] ) && ( 'yes' == $plugin['latestrelease'] ) ) {
					$wqplugins[$pluginslug]['latestrelease'] = 'yes';
					$latestrelease = $wqplugins[$pluginslug];
					$latestrelease['slug'] = $pluginslug;
				}

				// --- get next plugin release ---
				// 1.6.5: check for next plugin release also
				if ( isset( $plugin['nextrelease'] ) && ( 'yes' == $plugin['nextrelease'] ) ) {
					$wqplugins[$pluginslug]['nextrelease'] = 'yes';
					$nextrelease = $wqplugins[$pluginslug];
					$nextrelease['slug'] = $pluginslug;
				}
			}
		}
	}
	// if ($wqdebug) {echo "<!-- WQ Plugin Slugs: "; print_r($wqpluginslugs); echo " -->";}
	if ( $wqdebug ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		echo "<!-- WQ Plugins: " . esc_html( print_r( $wqplugins ) ) . " -->";
	}

	// --- maybe set Plugin Release Info ---
	global $wqreleases;
	if ( isset( $latestrelease ) ) {
		$wqreleases['latest'] = $latestrelease;
	}
	if ( isset( $nextrelease ) ) {
		$wqreleases['next'] = $nextrelease;
	}

	// --- get Installed Wordquest Plugin Data ---
	$plugins = $inactiveplugins = $pluginfiles = $inactiveversions = array();
	$i = $j = 0;
	foreach ( $installedplugins as $pluginfile => $values ) {

		$pluginslug = sanitize_title( $values['Name'] );
		$pluginfiles[$pluginslug] = $pluginfile;
		// echo '***'.$pluginslug.'***'; // debug point
		if ( in_array( $pluginslug, $wqpluginslugs ) || in_array( $pluginslug, $pluginslugs ) ) {

			// --- set plugin data ---
			$plugins[$i]['slug'] = $pluginslug;
			$plugins[$i]['name'] = $values['Name'];
			$plugins[$i]['filename'] = $pluginfile;
			$plugins[$i]['version'] = $values['Version'];
			$plugins[$i]['description'] = $values['Description'];

			// --- check for matching plugin update ---
			if ( in_array( $pluginslug, $pluginupdates ) ) {
				$plugins[$i]['update'] = 'yes';
			} else {
				$plugins[$i]['update'] = 'no';
			}

			// --- filter out to get inactive plugins ---
			if ( !in_array( $pluginslug, $pluginslugs ) ) {
				$inactiveversions[$pluginslug] = $values['Version'];
				$inactiveplugins[$j] = $pluginslug;
				$j++;
			}
			$i++;
		}
	}
	// if ($wqdebug) {echo "<!-- Plugin Data: "; print_r($plugins); echo " -->";}
	// if ($wqdebug) {echo "<!-- Inactive Plugins: "; print_r($inactiveplugins); echo " -->";}

	// --- check if BioShip Theme installed ---
	$themes = wp_get_themes();
	$bioshipinstalled = false;
	foreach ( $themes as $theme ) {
		// 1.8.0: use public get_stylesheet method
		if ( 'bioship' == $theme->get_stylesheet() ) {
			$bioshipinstalled = true;
		}
	}

	// --- open plugin column ---
	echo '<div id="plugincolumn">';

		// Active Plugin Panel
		// -------------------
		$boxid = 'wordquestactive';
		$boxtitle = wqhelper_translate( 'Active WordQuest Plugins' );
		echo '<div id="' . esc_attr( $boxid ) . '" class="postbox">';
		echo '<h2 class="hndle" onclick="wq_togglemetabox(\'' . esc_attr( $boxid ) . '\');">';
		echo '<span>' . esc_html( $boxtitle ) . '</span></h2>';
		echo '<div class="inside" id="' . esc_attr( $boxid ) . '-inside" style="margin-bottom:0;"><table>';
		foreach ( $wordquestplugins as $pluginslug => $plugin ) {
			// filter out theme here
			if ( 'bioship' != $pluginslug ) {

				// --- set update link ---
				if ( in_array( $pluginslug, $pluginupdates ) ) {
					$updatelink = admin_url( 'update.php' ) . '?action=upgrade-plugin&plugin=' . $pluginfiles[$pluginslug];
					$updatelink = wp_nonce_url( $updatelink, 'upgrade-plugin_' . $pluginfiles[$pluginslug] );
					echo '<input type="hidden" id="' . esc_attr( $pluginslug ) . '-update-link" value="' . esc_url( $updatelink ) . '">';
				}

				// --- linked title and version ---
				echo '<tr><td>';
					echo '<a href="' . esc_url( $wqurls['wq'] ) . '/plugins/' . esc_attr( $pluginslug ) . '" class="pluginlink" target="_blank">';
					echo esc_html( $plugin['title'] ) . '</a>';
				echo '</td><td width="20"></td><td>';
					echo esc_html( $plugin['version'] );
				echo '</td><td width="20"></td>';

				// --- update / settings options ---
				echo '<td><select name="' . esc_attr( $pluginslug ) . '-action" id="' . esc_attr( $pluginslug ) . '-action" style="font-size:8pt;">';
				if ( in_array( $pluginslug, $pluginupdates ) ) {
					echo '<option value="update" selected="selected">' . esc_html( wqhelper_translate( 'Update' ) ) . '</option>';
					echo '<option value="settings">' . esc_html( wqhelper_translate( 'Settings' ) ) . '</option>';
				} else {
					echo '<option value="settings" selected="selected">' . esc_html( wqhelper_translate( 'Settings' ) ) . '</option>';
				}

				// --- donate / testimonial / support / development options ---
				echo '<option value="donate">' . esc_html( wqhelper_translate( 'Donate' ) ) . '</option>';
				// echo '<option value="testimonial">' . esc_html( wqhelper_translate( 'Testimonial' ) . '</option>';
				echo '<option value="support">' . esc_html( wqhelper_translate( 'Support' ) ) . '</option>';
				echo '<option value="development">' . esc_html( wqhelper_translate( 'Development' ) ) . '</option>';
				if ( isset( $plugin['wporgslug'] ) ) {
					echo '<option value="Rate">' . esc_html( wqhelper_translate( 'Rate' ) ) . '</option>';
				}

				// --- check for Pro Plan availability ---
				// 1.7.2: added missing translation wrappers
				// TODO: check for Pro add-ons ?
				// if ($plugin['plan'] == 'premium') {echo "<option value='contact'>".wqhelper_translate('Contact')."</option>";}
				if ( isset( $wordquestplugins[$pluginslug]['hasplans'] ) && $wordquestplugins[$pluginslug]['hasplans'] ) {
					// 1.7.7: add isset check for plugin plan for back-compat
					if ( !isset( $plugin['plan'] ) || ( 'premium' != $plugin['plan'] ) ) {
						echo '<option style="font-weight:bold;" value="upgrade">' . esc_html( wqhelper_translate( 'Go PRO' ) ) . '</option>';
					} else {
						echo '<option value="account">' . esc_html( wqhelper_translate( 'Account' ) ) . '</option>';
					}
				}

				// --- close action select ---
				echo '</select></td><td width="20"></td>';

				// --- do selected action button ---
				echo '<td>';
					echo '<a href="javascript:void(0);" target="_blank" id="' . esc_attr( $pluginslug ) . '-link" onclick="wq_plugin_action(\'' . esc_attr( $pluginslug ) . '\');">';
					echo '<input class="button-secondary" type="button" value="' . esc_attr( wqhelper_translate( 'Go' ) ) . '"></a>';
				echo '</td></tr>';
			}
		}
		echo '</table></div></div>';

		// Inactive Plugin Panel
		// ---------------------
		if ( count( $inactiveplugins ) > 0 ) {
			$boxid = 'wordquestinactive';
			$boxtitle = wqhelper_translate( 'Inactive WordQuest Plugins' );
			echo '<div id="' . esc_attr( $boxid ) . '" class="postbox">';
			echo '<h2 class="hndle" onclick="wq_togglemetabox(\'' . esc_attr( $boxid ) . '\');">';
			echo '<span>' . esc_html( $boxtitle ) . '</span></h2>';
			echo '<div class="inside" id="' . esc_attr( $boxid ) . '-inside" style="margin-bottom:0;"><table>';
			foreach ( $inactiveplugins as $inactiveplugin ) {

				// --- set activate link ---
				$activatelink = admin_url( 'plugins.php' ) . '?action=activate&plugin=' . $pluginfiles[$inactiveplugin];
				$activatelink = wp_nonce_url( $activatelink, 'activate-plugin_' . $pluginfiles[$inactiveplugin] );
				echo "<input type='hidden' id='" . esc_attr( $inactiveplugin ) . "-activate-link' value='" . esc_url( $activatelink ) . "'>";

				// --- set update link ---
				if ( in_array( $inactiveplugin, $pluginupdates ) ) {
					$updatelink = admin_url( 'update.php' ) . '?action=upgrade-plugin&plugin=' . $pluginfiles[$inactiveplugin];
					// 1.8.0: fix to incorrect plural key usage, should be singular
					$updatelink = wp_nonce_url( $updatelink, 'upgrade-plugin_' . $pluginfiles[$inactiveplugin] );
					echo "<input type='hidden' id='" . esc_attr( $inactiveplugin ) . "-update-link' value='" . esc_url( $updatelink ) . "'>";
				}

				// --- linked title and version ---
				echo '<tr><td>';
					echo '<a href="' . esc_url( $wqplugins[$inactiveplugin]['home'] ) . '" class="pluginlink" target="_blank">';
					echo esc_html( $wqplugins[$inactiveplugin]['title'] ) . '</a>';
				echo '</td><td width="20"></td><td>';
					echo esc_html( $inactiveversions[$inactiveplugin] );
				echo '</td><td width="20"></td>';

				// --- select plugin action ---
				echo '<td><select name="' . esc_attr( $inactiveplugin ) . '-action" id="' . esc_attr( $inactiveplugin ) . '-action" style="font-size:8pt;">';
				if ( in_array( $inactiveplugin, $pluginupdates ) ) {
					echo '<option value="update" selected="selected">' . esc_html( wqhelper_translate( 'Update' ) ) . '</option>';
					echo '<option value="activate">' . esc_html( wqhelper_translate( 'Activate' ) ) . '</option>';
				} else {
					echo '<option value="activate" selected="selected">' . esc_html( wqhelper_translate( 'Activate' ) ) . '</option>';
				}
				echo '</select></td><td width="20"></td>';

				// --- plugin action button ---
				echo '<td>';
					echo '<a href="javascript:void(0);" target="_blank" id="' . esc_attr( $inactiveplugin ) . '-link" onclick="wq_plugin_action(\'' . esc_attr( $inactiveplugin ) . '\');">';
					echo '<input class="button-secondary" type="button" value="' . esc_html( wqhelper_translate( 'Go' ) ) . '"></a>';
				echo '</td></tr>';
			}
			echo '</table></div></div>';
		}

		$releasedplugins = $unreleasedplugins = array();
		if ( count( $wqplugins ) > count( $wordquestplugins ) ) {
			foreach ( $wqplugins as $pluginslug => $wqplugin ) {
				if ( !in_array( $pluginslug, $installedslugs ) && !in_array( $pluginslug, $inactiveplugins ) ) {
					if ( 'Released' == $wqplugin['releasestatus'] ) {
						$releasedplugins[$pluginslug] = $wqplugin;
					} else {
						$releasetime = strtotime( $wqplugin['releasedate'] );
						$wqplugin['slug'] = $pluginslug;
						$unreleasedplugins[$releasetime] = $wqplugin;
					}
				}
			}
		}

		// Available Plugin Panel
		// ----------------------
		if ( count( $releasedplugins ) > 0 ) {

			if ( $wqdebug ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions
				echo "<!-- Released Plugins: " . esc_html( print_r( $releasedplugins, true ) ) . " -->";
			}

			$boxid = 'wordquestavailable';
			$boxtitle = wqhelper_translate( 'Available WordQuest Plugins' );
			echo '<div id="' . esc_attr( $boxid ) . '" class="postbox">';
			echo '<h2 class="hndle" onclick="wq_togglemetabox(\'' . esc_attr( $boxid ) . '\');">';
			echo '<span>' . esc_html( $boxtitle ) . '</span></h2>';
			echo '<div class="inside" id="' . esc_attr( $boxid ) . '-inside" style="margin-bottom:0;"><table>';

			foreach ( $releasedplugins as $pluginslug => $wqplugin ) {

				// --- set install plugin link ---
				// 1.6.5: add separate install link URL for each plugin for nonce checking
				// 1.6.6: use wordpress.org link if all plugins are from wordpress.org
				if ( $wq_wordpress_org && $wqplugin['wporgslug'] ) {
					$installlink = self_admin_url( 'update.php' ) . '?action=install-plugin&plugin=' . $wqplugin['wporgslug'];
					$installlink = wp_nonce_url( $installlink, 'install-plugin_' . $wqplugin['wporgslug'] );
					echo '<input type="hidden" name="' . esc_attr( $pluginslug ) . '-install-link" value="' . esc_url( $installlink ) . '">';
				} elseif ( !$wq_wordpress_org && is_array( $wqplugin['package'] ) ) {
					$installlink = admin_url( 'update.php' ) . '?action=wordquest_plugin_install&plugin=' . $pluginslug;
					$installlink = wp_nonce_url( $installlink, 'plugin-upload' );
					echo '<input type="hidden" name="' . esc_attr( $pluginslug ) . '-install-link" value="' . esc_url( $installlink ) . '">';
				}

				// --- linked plugin title ---
				echo '<tr><td>';
					echo '<a href="' . esc_url( $wqplugin['home'] ) . '" class="pluginlink" target="_blank">';
					echo esc_html( $wqplugin['title'] ) . '</a>';
				echo '</td><td width="20"></td>';
				// echo '<td>' . esc_html( $wqplugin['version'] ) . '</td><td width="20"></td>';

				// --- plugin action select ---
				echo '<td><select name="' . esc_attr( $pluginslug ) . '-action" id="' . esc_attr( $pluginslug ) . '-action" style="font-size:8pt;">';

				// 1.6.6: check if only wp.org plugins installable
				if ( $wq_wordpress_org && $wqplugin['wporgslug'] ) {
					// --- has a wordpress.org slug so installable from repository ---
					echo '<option value="install" selected="selected">' . esc_html( wqhelper_translate( 'Install Now' ) ) . '</option>';
				} elseif ( !$wq_wordpress_org && is_array( $wqplugin['package'] ) ) {
					// --- not all plugins are from wordpress.org, use the install package ---
					echo '<option value="install" selected="selected">' . esc_html( wqhelper_translate( 'Install Now' ) ) . '</option>';
				} else {
					// oops, installation package currently unavailable (404)
					echo '';
				}
				echo '<option value="home" selected="selected">' . esc_html( wqhelper_translate( 'Plugin Home' ) ) . '</option>';
				echo "</select></td><td width='20'></td>";

				// --- plugin action button ---
				echo '<td>';
					echo '<a href="javascript:void(0);" target="_blank" id="' . esc_attr( $pluginslug ) . '-link" onclick="wq_plugin_action(\'' . esc_attr( $pluginslug ) . '\');">';
					echo '<input class="button-secondary" type="button" value="' . esc_attr( wqhelper_translate( 'Go' ) ) . '"></a>';
				echo '</td></tr>';
			}
			echo '</table></div></div>';
		}

		// Upcoming Plugin Panel
		// ---------------------
		if ( count( $unreleasedplugins ) > 0 ) {
			ksort( $unreleasedplugins );
			if ( $wqdebug ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions
				echo "<!-- Unreleased Plugins: " . esc_html( print_r( $unreleasedplugins, true ) ) . " -->";
			}

			$boxid = 'wordquestupcoming';
			$boxtitle = wqhelper_translate( 'Upcoming WordQuest Plugins' );
			echo '<div id="' . esc_attr( $boxid ) . '" class="postbox">';
			echo '<h2 class="hndle" onclick="wq_togglemetabox(\'' . esc_attr( $boxid ) . '\');">';
			echo '<span>' . esc_html( $boxtitle ) . '</span></h2>';
			echo '<div class="inside" id="' . esc_attr( $boxid ) . '-inside" style="margin-bottom:0;"><table>';
			foreach ( $unreleasedplugins as $releasetime => $wqplugin ) {
				// $pluginslug = $wqplugin['slug'];
				echo '<tr><td>';
					echo '<a href="' . esc_url( $wqplugin['home'] ) . '" class="pluginlink" target="_blank">';
					echo esc_html( $wqplugin['title'] ) . '</a>';
				echo '</td><td>';
					echo '<span style="font-size:9pt;">';
					echo esc_html( wqhelper_translate( 'Expected' ) ) . ': ' . esc_html( date( 'jS F Y', $releasetime ) );
					echo '</span>';
				echo '</td></tr>';
			}
			echo '</table></div></div>';
		}

		// BioShip Theme Panel
		// -------------------
		$boxid = 'bioship';
		$boxtitle = wqhelper_translate( 'BioShip Theme Framework' );
		echo '<div id="' . esc_attr( $boxid ) . '" class="postbox">';
		echo '<h2 class="hndle" onclick="wq_togglemetabox(\'' . esc_attr( $boxid ) . '\');">';
		echo '<span>' . esc_html( $boxtitle ) . '</span></h2>';
		echo '<div class="inside" id="' . esc_attr( $boxid ) . '-inside" style="margin-bottom:0;">';
		echo '<table><tr><td><center>';

		if ( $bioshipinstalled ) {

			// --- check if BioShip Theme is active ---
			$theme = wp_get_theme();
			// 1.8.0: use public get_stylesheet method
			if ( 'bioship' == $theme->get_stylesheet() ) {

				echo esc_html( wqhelper_translate( 'Sweet! You are using' ) ) . ' <b>';
				echo esc_html( wqhelper_translate( 'BioShip Theme Framework' ) ) . '</b>.<br>';
				echo esc_html( wqhelper_translate( 'Great choice!' ) ) . ' ';

				// 1.6.7: added BioShip Theme Options link here
				if ( THEMETITAN ) {
					$optionsurl = admin_url( 'admin.php' ) . '?page=bioship-options';
				} elseif ( THEMEOPT ) {
					$optionsurl = admin_url( 'admin.php' ) . '?page=options-framework';
				} else {
					$optionsurl = admin_url( 'customize.php' );
				}
				echo '<a href="' . esc_url( $optionsurl ) . '">' . esc_html( wqhelper_translate( 'Theme Options' ) ) . '</a>';

			} elseif ( is_child_theme() && ( 'bioship' == $theme->get_template() ) ) {
				// 1.8.0: use public get_template method

				echo esc_html( wqhelper_translate( 'Groovy. You are using' ) ) . ' <b>';
				echo esc_html( wqhelper_translate( 'BioShip Framework' ) ) . '</b>!<br>';
				echo esc_html( wqhelper_translate( 'Your Child Theme is' ) ) . ' <b>' . esc_html( $theme->Name ) . '</b><br><br>';

				// 1.6.7: add Child Theme Options link here
				if ( THEMETITAN ) {
					$optionsurl = admin_url( 'admin.php' ) . '?page=bioship-options';
				} elseif ( THEMEOPT ) {
					$optionsurl = admin_url( 'admin.php' ) . '?page=options-framework';
				} else {
					$optionsurl = admin_url( 'customize.php' );
				}
				echo '<a href="' . esc_url( $optionsurl ) . '">' . esc_html( wqhelper_translate( 'Theme Options' ) ) . '</a>';

			} else {

				echo esc_html( wqhelper_translate( 'Looks like you have BioShip installed!' ) ) . '<br>';
				echo '...' . esc_html( wqhelper_translate( 'but it is not yet your active theme.' ) ) . '<br><br>';

				// --- BioShip Theme activation link ---
				$activatelink = admin_url( 'themes.php' ) . '?action=activate&stylesheet=bioship';
				$activatelink = wp_nonce_url( $activatelink, 'switch-theme_bioship' );
				echo '<a href="' . esc_url( $activatelink ) . '">' . esc_html( wqhelper_translate( 'Click here to activate it now' ) ) . '</a>.<br><br>';

				// --- Check for Theme Test Drive ---
				echo '<div id="testdriveoptions">';
				if ( function_exists( 'themedrive_determine_theme' ) ) {

					// TODO: a better check here? this actually makes no sense
					// ...as should be using an options page redirect link instead
					if ( class_exists( 'TitanFramework' ) ) {
						$testdrivelink = admin_url( 'admin.php' ) . '?page=bioship-options&theme=bioship';
					} elseif ( function_exists( 'OptionsFramework_Init' ) ) {
						$testdrivelink = admin_url( 'themes.php' ) . '?page=options-framework&theme=bioship';
					} else {
						$testdrivelink = admin_url( 'customize.php' ) . '?theme=bioship';
					}
					echo esc_html( wqhelper_translate( 'or' ) ) . ', <a href="' . esc_url( $testdrivelink ) . '">';
					echo esc_html( wqhelper_translate( 'take it for a Theme Test Drive' ) ) . '</a>.';

				} elseif ( in_array( 'theme-test-drive', $installedplugins ) ) {

					// --- Theme Test Drive plugin activation link ---
					$activatelink = admin_url( 'plugins.php' ) . '?action=activate&plugin=' . rawurlencode( 'theme-test-drive/themedrive.php' );
					$activatelink = wp_nonce_url( $activatelink, 'activate-plugin_theme-test-drive/themedrive.php' );
					echo esc_html( wqhelper_translate( 'or' ) ) . ', <a href="' . esc_url( $activatelink ) . '">';
					echo esc_html( wqhelper_translate( 'activate Theme Test Drive plugin' ) ) . '</a><br>';
					echo esc_html( wqhelper_translate( 'to test BioShip without affecting your current site.' ) );

				} else {

					// --- Theme Test Drive plugin installation link ---
					$installlink = admin_url( 'update.php' ) . '?action=install-plugin&plugin=theme-test-drive';
					$installlink = wp_nonce_url( $installlink, 'install-plugin' );
					echo esc_html( wqhelper_translate( 'or' ) ) . ', <a href="' . esc_url( $installlink ) . '">';
					echo esc_html( wqhelper_translate( 'install Theme Test Drive plugin' ) ) . '</a><br>';
					echo esc_html( wqhelper_translate( 'to test BioShip without affecting your current site.' ) );

				}
				echo '</div>';
			}

		} else {

			// --- BioShip not installed, just provide a link ---
			echo esc_html( wqhelper_translate( 'Also from' ) ) . ' <b>WordQuest Alliance</b>, ' . esc_html( wqhelper_translate( 'check out the' ) ) . '<br>';
			echo '<a href="' . esc_url( $wqurls['bio'] ) . '" target="_blank"><b>BioShip Theme Framework</b></a><br>';
			echo esc_html( wqhelper_translate( 'A highly flexible and responsive starter theme' ) ) . '<br>';
			echo esc_html( wqhelper_translate( 'for users, designers and developers.' ) );

		}

		// 1.8.0: added missing bioship installed check
		if ( $bioshipinstalled && isset( $theme ) && is_object( $theme ) ) {
			if ( ( 'bioship' == $theme->get_template() ) || ( 'bioship' == $theme->get_stylesheet() ) ) {
				// 1.8.0: use public get_template and get_stylesheet methods
				// 1.7.3: addded missing function prefix
				if ( function_exists( 'bioship_admin_theme_updates_available' ) ) {
					$themeupdates = bioship_admin_theme_updates_available();
					if ( '' != $themeupdates ) {
						echo '<div class="update-nag" style="padding:3px 10px;margin:0 0 10px 0;text-align:center;">';
						echo wp_kses_post( $themeupdates );
						echo '</div></font><br>';
					}
				}

				// TODO: future link for rating BioShip on wordpress.org theme repository ?
				// $ratelink = 'https://wordpress.org/support/theme/bioship/reviews/#new-post';
				// echo '<br><a href="' . esc_url( $ratelink ) . '" target="_blank">' . esc_html( wqhelper_translate( 'Rate BioShip on WordPress.Org' ) ) . '</a><br>';
			}
		}

		// BioShip Feed
		// ------------
		// (only displays if Bioship theme is active)
		// 1.7.3: added missing bioship function prefix
		if ( function_exists( 'bioship_muscle_bioship_dashboard_feed_widget' ) ) {
			// $boxid = 'bioshipfeed'; $boxtitle = wphelper_translate('BioShip News');
			// echo '<div id="'.$boxid.'" class="postbox">';
			// echo '<h2 class="hndle" onclick="wq_togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			// echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;">';
				bioship_muscle_bioship_dashboard_feed_widget( false );
			// echo '</div></div>';
		}

		echo '</center></td></tr></table>';
		echo '</div></div>';

	// --- end column ---
	echo '</div>';
 };
}

// ----------------------------
// Version Specific Feed Column
// ----------------------------
$funcname = 'wqhelper_admin_feeds_column_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function( $args ) {

	// 1.8.0: set missing global
	global $wqurls;

	// --- open feeds column ---
	echo '<div id="feedcolumn">';

		// Latest / Next Release
		// ---------------------
		global $wqreleases;
		$latestrelease = $nextrelease = '';
		if ( isset( $wqreleases['latest'] ) ) {
			$latestrelease = $wqreleases['latest'];
		}
		if ( isset( $wqreleases['next'] ) ) {
			$nextrelease = $wqreleases['next'];
		}

		if ( isset( $latestrelease ) && is_array( $latestrelease ) ) {
			if ( 'no' == $latestrelease['installed'] ) {
				$release = $latestrelease;
				$boxid = 'wordquestlatest';
				$boxtitle = wqhelper_translate( 'Latest Release' );
			} else {
				$release = $nextrelease;
				$boxid = 'wordquestupcoming';
				$boxtitle = wqhelper_translate( 'Upcoming Release' );
			}
		} elseif ( isset( $nextrelease ) && is_array( $nextrelease ) ) {
			$release = $nextrelease;
			$boxid = 'wordquestupcoming';
			$boxtitle = wqhelper_translate( 'Upcoming Release' );
		}

		if ( isset( $release ) && is_array( $release ) ) {

			echo '<div id="' . esc_attr( $boxid ) . '" class="postbox">';
			echo '<h2 class="hndle" onclick="wq_togglemetabox(\'' . esc_attr( $boxid ) . '\');">';
			echo '<span>' . esc_html( $boxtitle ) . '</span></h2>';
			echo '<div class="inside" id="' . esc_attr( $boxid ) . '-inside"><table>';

				echo '<table><tr><td align="center">';
					echo '<img src="' . esc_url( $release['icon'] ) . '" width="100" height="100" alt="' . esc_attr( wqhelper_translate( 'Latest Release Icon' ) ) . '"><br>';
					echo '<a href="' . esc_url( $latestrelease['home'] ) . '" target="_blank"><b>' . esc_html( $release['title'] ) . '</b></a>';
				echo '</td><td width="10"></td><td>';
					echo '<span style="font-size:9pt;">' . esc_html( $release['description'] ) . '</span><br><br>';

					if ( isset( $release['package'] ) && is_array( $release['package'] ) ) {
						// 1.6.6: check for wordpress.org only installs
						global $wq_wordpress_org;
						$installlink = false;
						if ( $wq_wordpress_org && $release['wporgslug'] ) {
							$installlink = self_admin_url( 'update.php' ) . '?action=install-plugin&plugin=' . $release['wporgslug'];
							$installlink = wp_nonce_url( $installlink, 'install-plugin_' . $release['wporgslug'] );
						} else {
							$installlink = admin_url( 'update.php' ) . '?action=wordquest_plugin_install&plugin=' . $release['slug'];
							$installlink = wp_nonce_url( $installlink, 'plugin-upload' );
						}

						if ( $installlink ) {
							echo '<input type="hidden" name="' . esc_attr( $release['slug'] ) . '-install-link" value="' . esc_url( $installlink ) . '">';
							echo '<center><a href="' . esc_url( $installlink ) . '" class="button-primary">' . esc_html( wqhelper_translate( 'Install Now' ) ) . '</a></center>';
						} else {
							$pluginlink = $wqurls['wq'] . '/plugins/' . $release['slug'];
							echo '<center><a href="' . esc_url( $pluginlink ) . '" class="button-primary" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Plugin Home' ) ) . '</a></center>';
						}
					} else {
						echo '<center>' . esc_html( wqhelper_translate( 'Expected' ) ) . ': ' . esc_html( date( 'jS F Y', strtotime( $release['releasedate'] ) ) );
					}
				echo '</td></tr></table>';
			echo '</table></div></div>';
		}

		// WordQuest Feed
		// --------------
		$boxid = 'wordquestfeed';
		$boxtitle = wqhelper_translate( 'WordQuest News' );
		if ( function_exists( 'wqhelper_dashboard_feed_widget' ) ) {
			echo '<div id="' . esc_attr( $boxid ) . '" class="postbox">';
			echo '<h2 class="hndle" onclick="wq_togglemetabox(\'' . esc_attr( $boxid ) . '\');">';
			echo '<span>' . esc_html( $boxtitle ) . '</span></h2>';
			echo '<div class="inside" id="' . esc_attr( $boxid ) . '-inside" style="margin-bottom:0;">';
				wqhelper_dashboard_feed_widget();
			echo '</div></div>';
		}

		// Editors Picks
		// -------------
		$boxid = 'recommendations';
		$boxtitle = wqhelper_translate( 'Editor Picks' );
		// TODO: Recommended Plugins via Plugin Review?
		// echo '<div id="'.$boxid.'" class="postbox">';
		// echo '<h2 class="hndle" onclick="wq_togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
		// echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';
		// 	echo "Recommended Plugins...";
		//	print_r($recommended);
		// echo '</table></div></div>';

		// PluginReview Feed
		// -----------------
		$boxid = 'pluginreviewfeed';
		$boxtitle = wqhelper_translate( 'Plugin Reviews' );
		if ( function_exists( 'wqhelper_pluginreview_feed_widget' ) ) {
			echo '<div id="' . esc_attr( $boxid ) . '" class="postbox">';
			echo '<h2 class="hndle" onclick="wq_togglemetabox(\'' . esc_attr( $boxid ) . '\');">';
			echo '<span>' . esc_html( $boxtitle ) . '</span></h2>';
			echo '<div class="inside" id="' . esc_attr( $boxid ) . '-inside" style="margin-bottom:0;">';
				wqhelper_pluginreview_feed_widget();
			echo '</div></div>';
		}

	// --- close column ---
	echo '</div>';

	// --- enqueue feed javascript ---
	if ( !has_action( 'admin_footer', 'wqhelper_dashboard_feed_javascript' ) ) {
		add_action( 'admin_footer', 'wqhelper_dashboard_feed_javascript' );
	}

 };
}

// -----------------------------
// Version Specific Admin Styles
// -----------------------------
$funcname = 'wqhelper_admin_styles_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function() {

	// --- hide Wordquest plugin freemius submenu items if top level admin menu not open ---
	echo "<style>#toplevel_page_wordquest a.wp-first-item:after {content: ' Alliance';}
	#toplevel_page_wordquest.wp-not-current-submenu .fs-submenu-item {display: none; line-height: 0px; height: 0px;}
	#toplevel_page_wordquest li.wp-first-item {margin-bottom: 5px; margin-left: -10px;}
	span.fs-submenu-item.fs-sub {display: none;}
	.current span.fs-submenu-item.fs-sub {display: block;}
	#wpfooter {display:none !important;}</style>";
 };
}

// -----------------------------
// Version Specific Admin Script
// -----------------------------
$funcname = 'wqhelper_admin_scripts_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function() {

	// --- wordquest admin submenu icon and styling fixes ---
	// 1.7.9: change margin-left to padding-left on link
	// 1.7.9: added margin left and top to icon
	// 1.8.0: fix insertBefore(this) to insertBefore(jQuery(this))
	echo "<script>function wordquestsubmenufix(slug,iconurl,current) {
		jQuery('li a').each(function() {
			position = this.href.indexOf('admin.php?page='+slug);
			if (position > -1) {
				linkref = this.href.substr(position);
				jQuery(this).css('padding-left','10px');
				if (linkref == 'admin.php?page='+slug) {
					jQuery('<img src=\"'+iconurl+'\" style=\"float:left;margin-left:3px;margin-top:3px;\">').insertBefore(jQuery(this));
					jQuery(this).css('margin-top','-3px');
				} else {if (current == 1) {
					if (linkref == 'admin.php?page='+slug+'-account') {jQuery(this).addClass('current');}
					if (linkref == 'admin.php?page='+slug+'-pricing') {jQuery(this).addClass('current');}
					if (linkref == 'admin.php?page='+slug+'-contact') {jQuery(this).addClass('current');}
					if (linkref == 'admin.php?page='+slug+'-wp-support-forum') {jQuery(this).addClass('current');}
					jQuery(this).css('margin-top','-3px');
				} else {jQuery(this).css('margin-top','-10px');} }
			}
		});
	}</script>";
 };
}

// --------------------------
// Install a WordQuest Plugin
// --------------------------
// 1.6.5: hook to update.php update-custom_{ACTION} where ACTION = 'wordquest_plugin_install'
add_action( 'update-custom_wordquest_plugin_install', 'wqhelper_install_plugin' );

$funcname = 'wqhelper_install_plugin_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function() {

	global $wqurls;

	// --- check permissions and nonce ---
	if ( !current_user_can( 'upload_plugins' ) ) {
		wp_die( esc_html( wqhelper_translate( 'Sorry, you are not allowed to install plugins on this site.' ) ) );
	}
	check_admin_referer( 'plugin-upload' );

	// --- get the package info from download server ---
	if ( !isset( $_REQUEST['plugin'] ) ) {
		wp_die( esc_html( wqhelper_translate( 'Error: No Plugin specified.' ) ) );
	}
	// 1.5.9: sanitize plugin slug
	// 1.8.1: combined sanitize_title on same line
	$pluginslug = sanitize_title( $_REQUEST['plugin'] );
	if ( '' == $pluginslug ) {
		wp_die( esc_html( wqhelper_translate( 'Error: Invalid Plugin slug specified.' ) ) );
	}

	// --- get the plugin package data ---
	$url = $wqurls['wq'] . '/downloads/?action=get_metadata&slug=' . $pluginslug;
	$response = wp_remote_get( $url, array( 'timeout' => 30 ) );
	if ( !is_wp_error( $response ) ) {
		if ( '404' == $response['response']['code'] ) {
			// --- on failure try to get package info from stored transient data ---
			$plugininfo = get_transient( 'wordquest_plugin_info' );
			// 1.8.0: fix to variable typo pluginfo
			if ( is_array( $plugininfo ) ) {
				foreach ( $plugininfo as $plugin ) {
					if ( $plugin['slug'] == $pluginslug ) {
						$pluginpackage = $plugin['package'];
					}
				}
			}
		} else {
			$pluginpackage = json_decode( $response['body'], true );
		}
	}

	if ( !isset( $pluginpackage ) ) {
		$protocol = is_ssl() ? 'https://' : 'http://';
		// 1.8.2: add sanitize_text_field to $_SERVER['HTTP_HOST']
		$tryagainurl = $protocol . sanitize_text_field( $_SERVER['HTTP_HOST'] ) . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
		$message = esc_html( wqhelper_translate( 'Failed to retrieve download package information.' ) );
		$message .= ' <a href="' . esc_url( $tryagainurl ) . '">';
		$message .= esc_html( wqhelper_translate( 'Click here to try again.' ) ) . '</a>';
		// 1.8.1: use wp_kses_post on message
		wp_die( wp_kses_post( $message ) );
	}

	// 1.6.5: pass the package download URL to WordPress to do the rest

	// --- set the Plugin_Installer_Skin arguments ---
	$url = $pluginpackage['download_url'];
	$title = sprintf( wqhelper_translate( 'Installing Plugin from URL: %s' ), esc_html( $url ) );
	$nonce = 'plugin-upload';
	$type = 'web';
	// 1.8.0: disuse compact function
	// $args = compact( 'type', 'title', 'nonce', 'url' );
	$args = array(
		'type' => 'web',
		'title' => $title,
		'nonce' => 'plugin-upload',
		'url' => $url,
	);

	// --- custom Plugin_Upgrader (via /wp-admin/upgrade.php) ---
	// 1.8.0: fix to function typo wqhelper_translate
	$title = wqhelper_translate( 'Upload Plugin' );
	$parent_file = 'plugins.php';
	$submenu_file = 'plugin-install.php';
	require_once ABSPATH . 'wp-admin/admin-header.php';
	$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( $args ) );
	$result = $upgrader->install( $url );
	include ABSPATH . 'wp-admin/admin-footer.php';

 };
}


// ------------------------
// === Sidebar FloatBox ===
// ------------------------

// ----------------------
// Main Floatbox Function
// ----------------------
$funcname = 'wqhelper_sidebar_floatbox_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function( $args ) {

	global $wqdebug, $wqurls;
	if ( $wqdebug ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		echo "<!-- Sidebar Args: " . esc_html( print_r( $args, true ) ) . " -->";
	}

	if ( count( $args ) == 7 ) {

		// --- the old way, sending all args individually ---
		$prefix = $args[0];
		$pluginslug = $slug = $args[1];
		$freepremium = $args[2];
		$wporgslug = $args[3];
		$savebutton = $args[4];
		$plugintitle = $args[5];
		$pluginversion = $args[6];

	} else {

		// --- the new way, just sending two args ---
		$pluginslug = $slug = $args[0];
		$savebutton = $args[1];

		// --- get the other args using the slug and global array ---
		global $wordquestplugins;
		$pluginversion = $wordquestplugins[$slug]['version'];
		$plugintitle = $wordquestplugins[$slug]['title'];
		$prefix = $wordquestplugins[$slug]['settings'];
		// 1.7.7: added isset check for plan key for back-compat
		if ( isset( $wordquestplugins[$slug]['plan'] ) ) {
			$freepremium = $wordquestplugins[$slug]['plan'];
		} else {
			$freepremium = 'free';
		}
		$wporg = $wordquestplugins[$slug]['wporg'];

		if ( isset( $wordquestplugins[$slug]['wporgslug'] ) ) {
			$wporgslug = $wordquestplugins[$slug]['wporgslug'];
		} else {
			$wporgslug = '';
		}

		// --- get donate link ---
		// 1.7.4: get donate link and set author
		// 1.7.6: fix to check if donate key is defined
		$author = 'wordquest';
		if ( isset( $wordquestplugins[$slug]['donate'] ) ) {
				$donatelink = $wordquestplugins[$slug]['donate'];
			if ( strstr( $donatelink, 'wpmedic' ) ) {
				$author = 'wpmedic';
			}
		}

		if ( $wqdebug ) {
			echo "<!-- Sidebar Plugin Info: " . esc_html( $wordquestplugins[$slug] ) . "-->";
		}
	}

	// --- set which boxes to show ---
	$boxes = array( 'donate', 'subscribe', 'recommend' ); // 'report', 'testimonials'
	$boxes = apply_filters( $prefix . '_display_sidebar_boxes', $boxes );
	if ( $wqdebug ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		echo "<!-- Sidebar Boxes: " . esc_html( print_r( $boxes, true ) ) . " -->";
	}

	// 1.5.0: get/convert to single array of plugin sidebar options
	// 1.6.0: fix to sidebar options variable
	$sidebaroptions = get_option( $prefix . '_sidebar_options' );
	if ( !$sidebaroptions || ( '' == $sidebaroptions ) || !is_array( $sidebaroptions ) ) {
		$sidebaroptions = array(
			'installdate'		=> date( 'Y-m-d' ),
			'adsboxoff'		=> get_option( $prefix . '_ads_box_off' ),
			'donationboxoff'	=> get_option( $prefix . '_donation_box_off' ),
			'reportboxoff'		=> get_option( $prefix . '_report_box_off' ),
			'subscribeboxoff'	=> '',
			// 'testimonialboxoff'	=> '',
		);
		delete_option( $prefix . '_ads_box_off' );
		delete_option( $prefix . '_donation_box_off' );
		delete_option( $prefix . '_report_box_off' );
		update_option( $prefix . '_sidebar_options', $sidebaroptions );
	}
	// 1.6.9: fix to possible undefined keys
	$changed = false;
	if ( !isset( $sidebaroptions['installdate'] ) ) {
		$sidebaroptions['installdate'] = date( 'Y-m-d' );
		$changed = true;
	}
	if ( !isset( $sidebaroptions['donationboxoff'] ) ) {
		$sidebaroptions['donationboxoff'] = '';
		$changed = true;
	}
	if ( !isset( $sidebaroptions['subscribeboxoff'] ) ) {
		$sidebaroptions['subscribeboxoff'] = '';
		$changed = true;
	}
	// if ( !isset( $sidebaroptions['testimonialboxoff' ) ) {
	//	$sidebaroptions['testimonialboxoff'] = '';
	//	$changed = true;
	// }
	if ( !isset( $sidebaroptions['reportboxoff'] ) ) {
		$sidebaroptions['reportboxoff'] = '';
		$changed = true;
	}
	if ( !isset( $sidebaroptions['adsboxoff'] ) ) {
		$sidebaroptions['adsboxoff'] = '';
		$changed = true;
	}
	update_option( $prefix, '_sidebar_options', $sidebaroptions );

	// --- sidebar scripts ---
	// 1.7.8: fix to sticky kit recalc function check (sticky_in_parent)
	echo "<script>
	function wq_hide_sidebar_saved() {document.getElementById('sidebarsaved').style.display = 'none';}
	function wq_showhide_div(divname) {
		if (document.getElementById(divname).style.display == 'none') {document.getElementById(divname).style.display = '';}
		else {document.getElementById(divname).style.display = 'none';}
		if (typeof stick_in_parent === 'function') {jQuery(document.body).trigger('sticky_kit:recalc');}
	}</script>";

	// --- Sidebar Floatbox Styles ---
	echo '<style>#floatdiv {margin-top:20px;} .inside {font-size:9pt; line-height:1.6em; padding:0px;}
	#floatdiv a {text-decoration:none;} #floatdiv a:hover {text-decoration:underline;}
	#floatdiv .stuffbox {background-color:#FFFFFF; margin-bottom:10px; padding-bottom:10px; text-align:center; width:25%;}
	#floatdiv .stuffbox .inside {padding:0 3px;} .stuffbox h3 {margin:10px 0; background-color:#FAFAFA; font-size:12pt;}
	</style>';

	// --- open sidebar div --
	echo '<div id="floatdiv" class="floatbox">';
	if ( $wqdebug ) {
		echo '<!-- WQ Helper Loaded From: ' . esc_html( dirname( __FILE__ ) ) . ' -->';
	}

	// --- call (optional) Plugin Sidebar Header ---
	$funcname = $prefix . '_sidebar_plugin_header';
	if ( function_exists( $funcname ) ) {
		call_user_func( $funcname );
	}

	// Save Settings Button
	// --------------------
	if ( 'replace' != $savebutton ) {

		echo '<div id="savechanges"><div class="stuffbox" style="width:250px;background-color:#ffffff;">';
		echo '<h3>' . esc_html( wqhelper_translate( 'Update Settings' ) ) . '</h3>';
		echo '<div class="inside"><center>';

		$savebuttontrigger = $sidebaroptionsbutton = false;
		if ( 'yes' == $savebutton ) {

			$savebuttontrigger = $sidebaroptionsbutton = true;

			// --- set button output ---
			// 1.8.1: remove onclick attributes from buttons to script
			$button = '<table><tr>';
				$button .= '<td align="center">';
					$button .= '<input id="sidebarsavebutton" type="button" class="button-primary" value="Save Settings">';
				$button .= '</td><td width="30"></td><td>';
					$button .= '<div style="line-height:1em;"><font style="font-size:8pt;"><a href="javascript:void(0);" id="sidebaroptionsbutton" style="text-decoration:none;">' . esc_html( wqhelper_translate( 'Sidebar' ) ) . '<br>';
					$button .= esc_html( wqhelper_translate( 'Options' ) ) . '</a></font></div>';
				$button .= '</td>';
			$button .= '</tr></table>';
			$button = apply_filters( 'wordquest_sidebar_save_button', $button, $pluginslug );
			// 1.8.1: use wp_kses_post on button
			echo wp_kses_post( $button );

		} elseif ( 'no' == $savebutton ) {

			echo '';

		} else {

			// --- show sidebar save options only ---
			$sidebaroptionsbutton = true;
			echo '<div style="line-height:1em;text-align:center;">';
				echo '<font style="font-size:8pt;"><a href="javascript:void(0);" id="sidebaroptionsbutton" style="text-decoration:none;">' . esc_html( wqhelper_translate( 'Sidebar Options' ) ) . '</a></font>';
			echo '</div>';

		}

		if ( $savebuttontrigger || $sidebaroptionsbutton ) {
			// 1.8.1: output script function directly
			// echo "<script>function wq_sidebar_save_settings() {jQuery('#plugin-settings-save').trigger('click');}</script>";
			echo "<script>";
				if ( $savebuttontrigger ) {
					echo "jQuery('#sidebarsavebutton').on('click', function() {" . PHP_EOL;
					echo "	jQuery('#plugin-settings-save').trigger('click');" . PHP_EOL;
					echo "});" . PHP_EOL;
				}
				if ( $sidebaroptionsbutton ) {
					echo "jQuery('#sidebaroptionsbutton').on('click', function() {" . PHP_EOL;
					echo "wq_showhide_div('sidebarsettings'); wq_hide_sidebar_saved();" . PHP_EOL;
					echo "});" . PHP_EOL;
				}
			echo "</script>";
		}

		// --- sidebar settings box ---
		echo '<div id="sidebarsettings" style="display:none;"><br>';

			// 1.6.0: added version matching form field
			// 1.6.5: added nonce field
			global $wordquesthelper;
			echo '<form action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" target="savesidebar" method="post">';
			wp_nonce_field( $prefix . '_sidebar' );
			echo '<input type="hidden" name="action" value="wqhelper_update_sidebar_boxes">';
			echo '<input type="hidden" name="wqhv" value="' . esc_attr( $wordquesthelper ) . '">';
			echo '<input type="hidden" name="sidebarprefix" value="' . esc_attr( $prefix ) . '">';

			// --- donation box option ---
			if ( in_array( 'donate', $boxes ) ) {
				echo '<table><tr><td align="center">';
					echo '<b>' . esc_html( wqhelper_translate( 'Hide Support Offer' ) ) . '</b></td>';
				echo '<td width="10"></td><td align="center">';
					echo '<input type="checkbox" name="' . esc_attr( $prefix ) . '_donation_box_off" value="checked"';
					if ( 'checked' == $sidebaroptions['donationboxoff'] ) {
						echo ' checked="checked">';
					}
					echo '>';
				echo '</td></tr>';
			}

			// --- report box option ---
			if ( in_array( 'report', $boxes ) ) {
				echo '<tr><td align="center">';
					echo '<b>' . esc_html( wqhelper_translate( 'Hide Report Offer' ) ) . '</b>';
				echo '</td><td width="10"></td><td align="center">';
					echo '<input type="checkbox" name="' . esc_attr( $prefix ) . '_report_box_off" value="checked"';
					if ( 'checked' == $sidebaroptions['reportboxoff'] ) {
						echo ' checked="checked">';
					}
					echo '>';
				echo '</td></tr>';
			}

			// --- subscribe box option ---
			if ( in_array( 'subscribe', $boxes ) ) {
				echo '<tr><td align="center">';
					echo '<b>' . esc_html( wqhelper_translate( 'Hide Subscribe Offer' ) ) . '</b>';
				echo '</td><td width="10"></td><td align="center">';
					echo '<input type="checkbox" name="' . esc_attr( $prefix ) . '_subscribe_box_off" value="checked"';
					if ( 'checked' == $sidebaroptions['subscribeboxoff'] ) {
						echo ' checked="checked">';
					}
					echo '>';
				echo '</td></tr>';
			}

			// --- ads box option ---
			if ( in_array( 'recommend', $boxes ) ) {
				echo '<tr><td align="center">';
					echo '<b>' . esc_html( wqhelper_translate( 'Hide Recommendations' ) ) . '</b>';
					echo '</td><td width="10"></td><td align="center">';
				echo '<input type="checkbox" name="' . esc_attr( $prefix ) . '_ads_box_off" value="checked"';
					// 1.6.5: fix to undefined index warning
					if ( 'checked' == $sidebaroptions['adsboxoff'] ) {
						echo ' checked="checked">';
					}
					echo '>';
				echo '</td></tr>';
			}

			echo '</table><br>';

			// --- save sidebar options button ---
			echo '<center><input type="submit" class="button-secondary" value="' . esc_attr( wqhelper_translate( 'Save Sidebar Options' ) ) . '"></center></form><br>';
			echo '<iframe src="javascript:void(0);" name="savesidebar" id="savesidebar" width="200" height="200" style="display:none;"></iframe>';

			// --- sidebar options saved message ---
			echo '<div id="sidebarsaved" style="display:none;">';
			echo '<table style="background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;">';
			echo '<tr><td><div class="message" style="margin:0.25em;"><font style="font-weight:bold;">';
			echo esc_html( wqhelper_translate( 'Sidebar Options Saved.' ) ) . '</font></div></td></tr></table></div>';

		echo '</div></center>';

		echo '</div></div></div>';
	}

	// Donation Box
	// ------------
	// 1.7.8: check if donation box is on
	$args = array( $prefix, $pluginslug );
	if ( in_array( 'donate', $boxes ) ) {

		// --- open donate box ---
		echo '<div id="donate-box" class="sidebar-box"';
		if ( 'checked' == $sidebaroptions['donationboxoff'] ) {
			echo ' style="display:none;">';
		}
		echo '>';

		if ( 'free' == $freepremium ) {

			echo '<div class="stuffbox" style="width:250px;background-color:#ffffff;">';

			// --- box title ---
			// 1.7.4: different title for patreon/paypal
			// 1.7.6: fix to check if donate link defined
			$boxtitle = wqhelper_translate( 'Support Subscription' );
			if ( isset( $donatelink ) && strstr( $donatelink, 'patreon' ) ) {
				$boxtitle = wqhelper_translate( 'Become a Supporter' );
			}
			echo '<h3>' . esc_html( $boxtitle ) . '</h3><div class="inside">';

			// --- maybe call special top ---
			if ( function_exists( $prefix . '_donations_special_top' ) ) {
				$funcname = $prefix . '_donations_special_top';
				call_user_func( $funcname );
			}

			// --- patreon support or paypal donations ---
			// 1.7.4: different title for patreon/paypal
			// 1.7.6: fix to check if donate link is defined
			if ( isset( $donatelink ) && strstr( $donatelink, 'patreon' ) ) {
				wqhelper_sidebar_patreon_button( $args );
			} else {
				wqhelper_sidebar_paypal_donations( $args );
			}

			// --- call donations special bottom ---
			if ( function_exists( $prefix . '_donations_special_bottom' ) ) {
				$funcname = $prefix . '_donations_special_bottom';
				call_user_func( $funcname );
			}

			// 1.7.2: remove rate link from sidebar (now in plugin header)
			// TODO: maybe re-add theme rating when in repository ?
			// if ($wporgslug != '') {
				// echo "<a href='".$wqurls['wp']."/plugins/'".$wporgslug."'/reviews/#new-post' target='_blank'>";
				// echo "&#9733; ".wqhelper_translate('Rate this Plugin on Wordpress.Org')."</a></center>";
			// } elseif ($pluginslug == 'bioship') {
				// 1.5.0: add star rating for theme
				// echo "<a href='".$wqurls['wp']."/support/theme/bioship/reviews/#new-post' target='_blank'>";
				// echo "&#9733; ".wqhelper_translate('Rate this Theme on Wordpress.Org')."</a></center>";
			// }

			echo '</div></div>';

		} elseif ( 'premium' == $freepremium ) {

			// TODO: Go Pro Link if has Pro plans ?
			echo '';

		}

		// --- close donate box
		echo '</div>';
	}

	// Subscriber Form Box
	// -------------------
	// 1.7.8: added subscriber form box
	if ( in_array( 'subscribe', $boxes ) ) {

		// --- open donate box ---
		echo '<div id="subscribe-box" class="sidebar-box"';
		if ( 'checked' == $sidebaroptions['subscribeboxoff'] ) {
			echo ' style="display:none;">';
		}
		echo '>';

		// --- output subscribe box ---
		$funcname = $prefix . '_sidebar_subscribe_box';
		if ( function_exists( $funcname ) ) {
			call_user_func( $funcname );
		} else {

			// --- populated form for current user ---
			global $current_user;
			$current_user = wp_get_current_user();
			$useremail = $current_user->user_email;
			if ( strstr( $useremail, '@localhost' ) ) {
				$useremail = '';
			}
			$userid = $current_user->ID;
			$userdata = get_userdata( $userid );
			$username = $userdata->first_name;
			$lastname = $userdata->last_name;
			if ( '' != $lastname ) {
				$username .= ' ' . $lastname;
			}
			$timestamp = time();

			// --- set report image URL ---
			if ( 'bioship' == $pluginslug ) {
				$image = get_template_directory_uri() . '/images/bioship.png';
				$image_alt = wqhelper_translate( 'BioShip Theme Icon' );
				$action_url = $wqurls['bio'];
				$leadin = wqhelper_translate( 'Subscribe to BioShip Updates' );
				$headline = wqhelper_translate( 'and New Feature Releases' );
				$button = wqhelper_translate( 'Subscribe' );
			} elseif ( 'wordquest' == $author ) {
				$image = plugins_url( 'images/wordquest.png', __FILE__ );
				$image_alt = wqhelper_translate( 'WordQuest Icon' );
				$action_url = $wqurls['wq'];
				$leadin = wqhelper_translate( 'Join WordQuest for Updates' );
				$headline = wqhelper_translate( 'and New Plugin Releases' );
				$button = wqhelper_translate( 'Join' );
			} elseif ( 'wpmedic' == $author ) {
				$image = plugins_url( 'images/wpmedic.png', __FILE__ );
				$image_alt = wqhelper_translate( 'WP Medic Icon' );
				$action_url = $wqurls['wpm'];
				$leadin = wqhelper_translate( 'Join WP Medic for Updates' );
				$headline = wqhelper_translate( 'and New Tool Releases' );
				$button = wqhelper_translate( 'Join' );
			}

			echo '<div class="stuffbox" style="width:250px;background-color:#ffffff;">';
			echo '<h3>' . esc_html( wqhelper_translate( 'Stay Up to Date' ) ) . '</h3>';
			echo '<div class="inside"><center>';
				echo '<table cellpadding="0" cellspacing="0"><tr><td align="center">';
					echo '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( wqhelper_translate( $image_alt ) ) . '"><br>';
				echo '</td><td width="7"></td><td align="center">';
					echo '<b><font style="color:#0000EE;font-size:11px;">' . esc_html( $leadin ) . '</font><br>';
					echo '<font style="color:#EE0000;font-size:13px;">' . esc_html( $headline ) . '</font></b><br>';
					echo '<form style="margin-top:7px;" action="' . esc_url( $action_url ) . '/" target="_blank" method="get">';
					echo '<input type="hidden" name="visitor-vortex" value="join">';
					echo '<input type="hidden" name="timestamp" value="' . esc_attr( $timestamp ) . '">';
					echo '<input type="hidden" name="source" value="' . esc_attr( $pluginslug ) . '-sidebar">';
					echo '<input placeholder="' . esc_attr( wqhelper_translate( 'Your Email' ) ) . '..." type="text" style="width:140px;font-size:12px;" name="subemail" value="' . esc_attr( $useremail ) . '"><br>';
					echo '<table><tr><td>';
						echo '<input placeholder="' . esc_attr( wqhelper_translate( 'Your Name' ) ) . '..." type="text" style="width:90px;font-size:12px;margin-top:5px;" name="subname" value="' . esc_attr( $username ) . '">';
					echo '</td><td>';
						echo '<input type="submit" class="button-primary" value="' . esc_attr( $button ) . '">';
					echo '</td></tr></table>';
				echo '</td></tr></table></form>';
			echo '</center></div></div>';
		}

		// --- close subscribe box ---
		echo '</div>';
	}

	// Testimonials Form Box
	// ---------------------
	if ( in_array( 'testimonial', $boxes ) ) {

		// --- open donate box ---
		echo '<div id="testimonial"';
		if ( 'checked' == $sidebaroptions['testimonialboxoff'] ) {
			echo ' style="display:none;">';
		}
		echo '>';

		// 1.7.2: remove testimonial box from sidebar
		// wqhelper_sidebar_testimonial_box( $args );

		echo '</div>';
	}

	// Bonus Report Subscription Form
	// ------------------------------
	// 1.7.2: allow for bonus offer box override
	if ( in_array( 'report', $boxes ) ) {

		echo '<div id="report-box"';
		if ( 'checked' == $sidebaroptions['reportboxoff'] ) {
			echo ' style="display:none;">';
		}
		echo '>';

		$funcname = $prefix . '_sidebar_bonus_offer';
		if ( function_exists( $funcname ) ) {
			call_user_func( $funcname );
		} else {

			// --- populated form for current user ---
			global $current_user;
			$current_user = wp_get_current_user();
			$useremail = $current_user->user_email;
			if ( strstr( $useremail, '@localhost' ) ) {
				$useremail = '';
			}
			$userid = $current_user->ID;
			$userdata = get_userdata( $userid );
			$username = $userdata->first_name;
			$lastname = $userdata->last_name;
			if ( '' != $lastname ) {
				$username .= ' ' . $lastname;
			}

			// --- set report image URL ---
			if ( 'bioship' == $pluginslug ) {
				$reportimage = get_template_directory_uri() . '/images/rv-report.jpg';
			} else {
				$reportimage = plugins_url( 'images/rv-report.jpg', __FILE__ );
			}

			echo '<div class="stuffbox" style="width:250px;background-color:#ffffff;">';
			echo '<h3>' . esc_html( wqhelper_translate( 'Bonus Offer' ) ) . '</h3>';
			echo '<div class="inside"><center>';
				echo '<table cellpadding="0" cellspacing="0"><tr><td align="center">';
					echo '<img src="' . esc_url( $reportimage ) . '" width="60" height="80" alt="' . esc_attr( wqhelper_translate( 'Report Image' ) ) . '"><br>';
					echo '<font style="font-size:6pt;"><a href="' . esc_url( $wqurls['prn'] ) . '/return-visitors-report/" target="_blank">' . esc_html( wqhelper_translate( 'learn more' ) ) . '...</a></font>';
				echo '</td><td width="7"></td><td align="center">';
					echo '<b><font style="color:#ee0000;font-size:9pt;">Maximize Sales Conversions:</font><br>';
					echo '<font style="color:#0000ee;font-size:10pt;">The Return Visitors Report</font></b><br>';
					echo '<form style="margin-top:7px;" action="' . esc_url( $wqurls['prn'] ) . '" target="_blank" method="post">';
					echo '<input type="hidden" name="visitor-vortex" value="join">';
					echo '<input type="hidden" name="source" value="' . esc_attr( $pluginslug ) . '-sidebar">';
					echo '<input placeholder="' . esc_attr( wqhelper_translate( 'Your Email' ) ) . '..." type="text" style="width:150px;font-size:9pt;" name="subemail" value="' . esc_attr( $useremail ) . '"><br>';
					echo '<table><tr><td>';
						echo '<input placeholder="' . esc_attr( wqhelper_translate( 'Your Name' ) ) . '..." type="text" style="width:90px;font-size:9pt;" name="subname" value="' . esc_attr( $username ) . '">';
					echo '</td><td>';
						echo '<input type="submit" class="button-secondary" value="' . esc_attr( wqhelper_translate( 'Get it!' ) ) . '">';
					echo '</td></tr></table>';
				echo '</td></tr></table></form>';
			echo '</center></div></div>';
		}
		echo '</div>';
	}

	// PluginReview.Net Plugin Recommendations
	// ---------------------------------------
	if ( in_array( 'recommend', $boxes ) ) {

		echo '<div id="recommend-box"';
		if ( 'checked' == $sidebaroptions['adsboxoff'] ) {
			echo ' style="display:none;"';
		}
		echo '>';

		// 1.7.2: allow for recommendation box override
		$funcname = $prefix . '_sidebar_plugin_recommendation';
		if ( function_exists( $funcname ) ) {
			call_user_func( $funcname );
		} else {
			// 1.8.0: check for contents before box output
			$feedad = wqhelper_get_feed_ad( $pluginslug );
			if ( $feedad ) {
				echo '<div class="stuffbox" style="width:250px;">';
				echo '<h3>' . esc_html( wqhelper_translate( 'Recommended' ) ) . '</h3>';
				echo '<div class="inside">';
				if ( 'checked' != $sidebaroptions['adsboxoff'] ) {
					// 1.8.0: replaced script with RSS feed ad
					// echo '<script src="' . esc_url( $wqurls['prn'] ) . '/recommends/?s=yes&a=majick&c=' . esc_attr( $pluginslug ) . '&t=sidebar"></script>';
					// 1.8.1: use wp_kses_post on feed ad
					echo wp_kses_post( $feedad );
				}
				// 1.8.2: fix to move close divs back inside condition
				echo '</div></div>';
			}
		}

		echo '</div>';
	}

	// Call Plugin Footer Function
	// ---------------------------
	$funcname = $prefix . '_sidebar_plugin_footer';
	echo '<div id="pluginfooter">';
	if ( function_exists( $funcname ) ) {
		call_user_func( $funcname );
	} else {

		// Default Sidebar Plugin Footer
		// -----------------------------

		// 1.7.4: link display depending on author
		$wqanchor = wqhelper_translate( 'WordQuest Plugins' );
		$wpmanchor = wqhelper_translate( 'WP Medic Tools' );
		if ( 'wordquest' == $author ) {
		   $authordisplay = 'WordQuest Alliance';
		   $authorurl = $wqurls['wq'];
		   $wqanchor = wqhelper_translate( 'More WordQuest Plugins' );
		   $pluginurl = $wqurls['wq'] . '/plugins/' . $pluginslug . '/';
		} elseif ( 'wpmedic' == $author ) {
		   $authordisplay = 'WP Medic';
		   $authorurl = $wqurls['wpm'];
		   $wpmanchor = wqhelper_translate( 'More WP Medic Tools' );
		   $pluginurl = $wqurls['wpm'] . '/' . $pluginslug . '/';
		}
		// $wqlink = '<a href="' . esc_url( $wqurls['wq'] ) . '/plugins/" target="_blank"><b>&rarr; ' . esc_html( $wqanchor ) . '</b></a><br>';
		// $wpmlink = '<a href="' . esc_url( $wqurls['wpm'] ) . '" target="_blank"><b>&rarr; ' . esc_html( $wpmanchor ) . '</b></a><br>';

		// --- set values for theme or plugin ---
		$footertitle = wqhelper_translate( 'Plugin Info' );
		$bioanchor = wqhelper_translate( 'BioShip Framework' );
		if ( 'bioship' == $pluginslug ) {
			// $iconurl = get_template_directory_uri() . '/images/' . $author . '.png';
			$pluginurl = $wqurls['bio'];
			$footertitle = wqhelper_translate( 'Theme Info' );
		} // else {
			// $iconurl = plugins_url( 'images/' . $author . '.png', __FILE__ );
		// }

		// --- output plugin footer ---
		echo '<div class="stuffbox" style="width:250px;background-color:#ffffff;">';
		echo '<h3>' . esc_html( $footertitle ) . '</h3><div class="inside"><center>';
		// echo '<table><tr><td>';
			// echo '<a href="' . esc_url( $wqurls['wq'] ) . '" target="_blank"><img src="' . esc_url( $iconurl ) . '" border="0"></a>';
			// echo '</td><td width="14"></td><td>';

			echo '<table><tr><td>';
				echo '<a href="' . esc_url( $pluginurl ) . '" target="_blank">' . esc_html( $plugintitle ) . '</a> &nbsp; <i>v' . esc_html( $pluginversion ) . '</i><br>';
				echo esc_html( wqhelper_translate( 'by' ) ) . ' <a href="' . esc_url( $authorurl ) . '" target="_blank">' . esc_html( $authordisplay ) . '</a><br><br></center>';

				// 1.8.1: output links direct instead of storing
				if ( 'wpmedic' == $author ) {
					echo '<a href="' . esc_url( $wqurls['wpm'] ) . '" target="_blank"><b>&rarr; ' . esc_html( $wpmanchor ) . '</b></a><br>';
					echo '<a href="' . esc_url( $wqurls['wq'] ) . '/plugins/" target="_blank"><b>&rarr; ' . esc_html( $wqanchor ) . '</b></a><br>';
				} else {
					echo '<a href="' . esc_url( $wqurls['wq'] ) . '/plugins/" target="_blank"><b>&rarr; ' . esc_html( $wqanchor ) . '</b></a><br>';
					echo '<a href="' . esc_url( $wqurls['wpm'] ) . '" target="_blank"><b>&rarr; ' . esc_html( $wpmanchor ) . '</b></a><br>';
				}
				if ( 'bioship' != $pluginslug ) {
					echo '<a href="' . esc_url( $wqurls['bio'] ) . '" target="_blank"><b>&rarr; ' . esc_html( $bioanchor ) . '</a></b><br>';
				}
				echo '<a href="' . esc_url( $wqurls['prn'] ) . '/directory/" target="_blank"><b>&rarr; ' . esc_html( wqhelper_translate( 'Sorted Plugin Directory' ) ) . '</b></a>';
			echo '</td></tr></table>';

		// echo '</td>';
		// echo '</tr></table>';
		echo '</div></div>';
	}
	echo '</div>';

	// --- close sidebar float div ---
	echo '</div>';

 };
}

// ------------------------
// Patreon Supporter Button
// ------------------------
// 1.7.4: added Patreon Supporter Button
$funcname = 'wqhelper_sidebar_patreon_button_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function( $args ) {

	global $wordquestplugins;
	$prefix = $args[0];
	$pluginslug = $args[1];
	$settings = $wordquestplugins[$pluginslug];
	$donatelink = apply_filters( 'wqhelper_donate_link', $settings['donate'], $pluginslug );
	$message = apply_filters( 'wqhelper_donate_message', $settings['donatetext'], $pluginslug );

	// --- set button image URL ---
	if ( 'bioship' == $pluginslug ) {
		$imageurl = get_template_directory_uri() . '/images/patreon-button.jpg';
	} else {
		// 1.7.5: check/fix for Patreon button URL (cross-versions)
		if ( file_exists( dirname( __FILE__ ) . '/images/patreon-button.jpg' ) ) {
			$imageurl = plugins_url( 'images/patreon-button.jpg', __FILE__ );
		} else {
			// --- try to reliably get actual plugin path/URL ---
			$realslug = sanitize_title( $wordquestplugins[$pluginslug]['title'] );
			if ( file_exists( WP_PLUGIN_DIR . '/' . $realslug . '/images/patreon-button.jpg' ) ) {
				$imageurl = WP_PLUGIN_URL . '/' . $realslug . '/images/patreon-button.jpg';
			}
		}
	}
	$imageurl = apply_filters( 'wqhelper_donate_image', $imageurl, $pluginslug );

	// --- output Patreon button ---
	echo '<center><div class="supporter-message">' . esc_html( $message ) . '</div>';
	echo '<a href="' . esc_url( $donatelink ) . '" target="_blank">';
	if ( $imageurl ) {
		echo '<img id="patreon-button" src="' . esc_url( $imageurl ) . '">';
	} else {
		echo esc_html( wqhelper_translate( 'Become a Patron' ) );
	}
	echo '</a><center>';

	// --- image hover styling ---
	echo '<style>.supporter-message {font-size:15px; margin-bottom:5px;}
	#patreon-button {opacity: 0.9;} #patreon-button:hover {opacity: 1;}</style>';

 };
}

// ----------------
// Paypal Donations
// ----------------
$funcname = 'wqhelper_sidebar_paypal_donations_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function( $args ) {

	global $wqurls;
	$prefix = $args[0];
	$pluginslug = $args[1];

	// --- make display name from the plugin slug ---
	if ( strstr( $pluginslug, '-' ) ) {
		$parts = explode( '-', $pluginslug );
		$i = 0;
		foreach ( $parts as $part ) {
			if ( 'wp' == $part ) {
				$parts[$i] = 'WP';
			} else {
				$parts[$i] = strtoupper( substr( $part, 0, 1 ) ) . substr( $part, 1, ( strlen( $part ) - 1 ) );
			}
			$i++;
		}
		$pluginname = implode( ' ', $parts );
	} else {
		$pluginname = strtoupper( substr( $pluginslug, 0, 1 ) ) . substr( $pluginslug, 1, ( strlen( $pluginslug ) - 1 ) );
	}

	// --- donations scripts ---
	echo "<script>
	function wq_show_recurring_form() {
		document.getElementById('recurradio').checked = true;
		document.getElementById('onetimedonation').style.display = 'none';
		document.getElementById('recurringdonation').style.display = '';
	}
	function wq_show_one_time_form() {
		document.getElementById('onetimeradio').checked = true;
		document.getElementById('recurringdonation').style.display = 'none';
		document.getElementById('onetimedonation').style.display = '';
	}
	function wq_switch_period_options() {
		selectelement = document.getElementById('recurperiod');
		recurperiod = selectelement.options[selectelement.selectedIndex].value;
		if ( (recurperiod == 'Weekly') || (recurperiod == 'W') ) {
			document.getElementById('periodoptions').innerHTML = document.getElementById('weeklyamounts').innerHTML;
			monthlyselected = document.getElementById('monthlyselected').value;
			weeklyselected = monthlyselected++;
			selectelement = document.getElementById('periodoptions');
			selectelement.selectedIndex = weeklyselected;
		}
		if ( (recurperiod == 'Monthly') || (recurperiod == 'M') ) {
			document.getElementById('periodoptions').innerHTML = document.getElementById('monthlyamounts').innerHTML;
			weeklyselected = document.getElementById('weeklyselected').value;
			monthlyselected = weeklyselected--;
			selectelement = document.getElementById('periodoptions');
			selectelement.selectedIndex = monthlyselected;
		}
	}
	function wq_store_amount() {
		selectelement = document.getElementById('recurperiod');
		recurperiod = selectelement.options[selectelement.selectedIndex].value;
		selectelement = document.getElementById('periodoptions');
		selected = selectelement.selectedIndex;
		if ( (recurperiod == 'Weekly') || (recurperiod == 'W') ) {
			document.getElementById('weeklyselected').value = selected;
		}
		if ( (recurperiod == 'Monthly') || (recurperiod == 'M') ) {
			document.getElementById('monthlyselected').value = selected;
		}
	}</script>";

	// --- set Paypal notification URL ---
	$notifyurl = $wqurls['wq'] . '/?estore_pp_ipn=process';
	$sandbox = ''; // $sandbox = 'sandbox.';

	// --- recurring / one-time switcher ---
	echo '<center><table cellpadding="0" cellspacing="0"><tr><td>';
		echo '<input name="donatetype" id="recurradio" type="radio" onclick="wq_show_recurring_form();" checked="checked">';
		echo ' <a href="javascript:void(0);" onclick="wq_show_recurring_form();" style="text-decoration:none;">' . esc_html( wqhelper_translate( 'Supporter' ) ) . '</a> ';
	echo '</td><td width="10"></td><td>';
		echo '<input name="donatetype" id="onetimeradio" type="radio" onclick="wq_show_one_time_form();"> ';
		echo '<a href="javascript:void(0);" onclick="wq_show_one_time_form();" style="text-decoration:none;">' . esc_html( wqhelper_translate( 'One Time' ) ) . '</a>';
	echo '</td></tr></table></center>';

	// --- set weekly amount options ---
	// 1.5.0: added weekly amounts
	echo '<div style="display:none;">';
	echo '<input type="hidden" id="weeklyselected" value="3">';
	echo '<select name="wp_eStore_subscribe" id="weeklyamounts" style="font-size:8pt;" size="1">';
	echo '<optgroup label="' . esc_attr( wqhelper_translate( 'Supporter Amount' ) ) . '">';
	echo '<option value="1">' . esc_html( wqhelper_translate( 'Copper' ) ) . ': $1 </option>';
	echo '<option value="3">' . esc_html( wqhelper_translate( 'Bronze' ) ) . ': $2</option>';
	echo '<option value="5">' . esc_html( wqhelper_translate( 'Silver' ) ) . ': $4</option>';
	echo '<option value="7" selected="selected">' . esc_html( wqhelper_translate( 'Gold' ) ) . ': $5</option>';
	echo '<option value="9">' . esc_html( wqhelper_translate( 'Platinum' ) ) . ': $7.50</option>';
	echo '<option value="11">' . esc_html( wqhelper_translate( 'Titanium' ) ) . ': $10</option>';
	echo '<option value="13">' . esc_html( wqhelper_translate( 'Star Ruby' ) ) . ': $12.50</option>';
	echo '<option value="15">' . esc_html( wqhelper_translate( 'Star Topaz' ) ) . ': $15</option>';
	echo '<option value="17">' . esc_html( wqhelper_translate( 'Star Emerald' ) ) . ': $17.50</option>';
	echo '<option value="19">' . esc_html( wqhelper_translate( 'Star Sapphire' ) ) . ': $20</option>';
	echo '<option value="21">' . esc_html( wqhelper_translate( 'Star Diamond' ) ) . ': $25</option>';
	echo '</select></div>';

	// --- set monthly amount options ---
	// 1.5.0: added monthly amounts
	echo '<div style="display:none;">';
	echo '<input type="hidden" id="monthlyselected" value="3">';
	echo '<select name="wp_eStore_subscribe" id="monthlyamounts" style="font-size:8pt;" size="1">';
	echo '<optgroup label="' . esc_attr( wqhelper_translate( 'Supporter Amount' ) ) . '">';
	echo '<option value="2">' . esc_html( wqhelper_translate( 'Copper' ) ) . ': $5</option>';
	echo '<option value="4">' . esc_html( wqhelper_translate( 'Bronze' ) ) . ': $10</option>';
	echo '<option value="6">' . esc_html( wqhelper_translate( 'Silver' ) ) . ': $15</option>';
	echo '<option value="9" selected="selected">' . esc_html( wqhelper_translate( 'Gold' ) ) . ': $20</option>';
	echo '<option value="10">' . esc_html( wqhelper_translate( 'Platinum' ) ) . ': $30</option>';
	echo '<option value="12">' . esc_html( wqhelper_translate( 'Titanium' ) ) . ': $40</option>';
	echo '<option value="14">' . esc_html( wqhelper_translate( 'Star Ruby' ) ) . ': $50</option>';
	echo '<option value="16">' . esc_html( wqhelper_translate( 'Star Topaz' ) ) . ': $60</option>';
	echo '<option value="18">' . esc_html( wqhelper_translate( 'Star Emerald' ) ) . ': $70</option>';
	echo '<option value="20">' . esc_html( wqhelper_translate( 'Star Sapphire' ) ) . ': $80</option>';
	echo '<option value="22">' . esc_html( wqhelper_translate( 'Star Diamond' ) ) . ': $100</option>';
	echo '</select></div>';

	// note: eStore recurring subscription form
	// $wqurls['wq'].'/?wp_eStore_subscribe=LEVEL&c_input='.$pluginslug;

	// --- set donate image URL ---
	if ( 'bioship' == $pluginslug ) {
		$donateimage = get_template_directory_uri() . '/images/pp-donate.jpg';
	} else {
		$donateimage = plugins_url( '/images/pp-donate.jpg', __FILE__ );
	}

	// --- recurring donation form ---
	echo '<center><form id="recurringdonation" method="GET" action="' . esc_url( $wqurls['wq'] ) . '" target="_blank">';
		echo '<input type="hidden" name="c_input" value="' . esc_attr( $pluginslug ) . '">';
		echo '<select name="wp_eStore_subscribe" style="font-size:10pt;" size="1" id="periodoptions" onchange="wq_store_amount();">';
		echo '<optgroup label="' . esc_attr( wqhelper_translate( 'Supporter Amount' ) ) . '">';
		echo '<option value="1">' . esc_html( wqhelper_translate( 'Copper' ) ) . ': $1 </option>';
		echo '<option value="3">' . esc_html( wqhelper_translate( 'Bronze' ) ) . ': $2</option>';
		echo '<option value="5">' . esc_html( wqhelper_translate( 'Silver' ) ) . ': $4</option>';
		echo '<option value="7" selected="selected">' . esc_html( wqhelper_translate( 'Gold' ) ) . ': $5</option>';
		echo '<option value="9">' . esc_html( wqhelper_translate( 'Platinum' ) ) . ': $7.50</option>';
		echo '<option value="11">' . esc_html( wqhelper_translate( 'Titanium' ) ) . ': $10</option>';
		echo '<option value="13">' . esc_html( wqhelper_translate( 'Ruby' ) ) . ': $12.50</option>';
		echo '<option value="15">' . esc_html( wqhelper_translate( 'Topaz' ) ) . ': $15</option>';
		echo '<option value="17">' . esc_html( wqhelper_translate( 'Emerald' ) ) . ': $17.50</option>';
		echo '<option value="19">' . esc_html( wqhelper_translate( 'Sapphire' ) ) . ': $20</option>';
		echo '<option value="21">' . esc_html( wqhelper_translate( 'Diamond' ) ) . ': $25</option>';
		echo '</select>';

	echo '</td><td width="5"></td><td>';
	echo '<select name="t3" style="font-size:10pt;" id="recurperiod" onchange="wq_switch_period_options()">';
	echo '<option selected="selected" value="W">' . esc_html( wqhelper_translate( 'Weekly' ) ) . '</option>';
	echo '<option value="M">' . esc_html( wqhelper_translate( 'Monthly' ) ) . '</option>';
	echo '</select></tr></table>';

	echo '<input type="image" src="' . esc_url( $donateimage ) . '" border="0" name="I1">';
	echo '</center></form>';

	/// --- one time donation form ---
	// $wqurls['wq'].'/?wp_eStore_donation=23&var1_price=AMOUNT&c_input='.$pluginslug;
	echo '<center><form id="onetimedonation" style="display:none;" method="GET" action="' . esc_url( $wqurls['wq'] ) . '" target="_blank">';
		echo '<input type="hidden" name="wp_eStore_donation" value="23">';
		echo '<input type="hidden" name="c_input" value="' . esc_attr( $pluginslug ) . '">';
		echo '<select name="var1_price" style="font-size:10pt;" size="1">';
			echo '<option selected value="">' . esc_html( wqhelper_translate( 'Select Gift Amount' ) ) . '</option>';
			echo '<option value="5">$5 - ' . esc_html( wqhelper_translate( 'Buy me a Cuppa' ) ) . '</option>';
			echo '<option value="10">$10 - ' . esc_html( wqhelper_translate( 'Log a Feature Request' ) ) . '</option>';
			echo '<option value="20">$20 - ' . esc_html( wqhelper_translate( 'Support a Minor Bugfix' ) ) . '</option>';
			echo '<option value="50">$50 - ' . esc_html( wqhelper_translate( 'Support a Minor Update' ) ) . '</option>';
			echo '<option value="100">$100 - ' . esc_html( wqhelper_translate( 'Support a Major Update' ) ) . '</option>';
			echo '<option value="250">$250 - ' . esc_html( wqhelper_translate( 'Support a Minor Feature' ) ) . '</option>';
			echo '<option value="500">$500 - ' . esc_html( wqhelper_translate( 'Support a Major Feature' ) ) . '</option>';
			echo '<option value="1000">$1000 - ' . esc_html( wqhelper_translate( 'Support a New Module' ) ) . '</option>';
			echo '<option value="2000">$1000 - ' . esc_html( wqhelper_translate( 'Support a New Plugin' ) ) . '</option>';
			echo '<option value="">' . esc_html( wqhelper_translate( 'Be Unique: Enter Custom Amount' ) ) . '</option>';
		echo '</select>';
		echo '<input type="image" src="' . esc_url( $donateimage ) . '" border="0" name="I1">';
	echo '</center></form>';

 };
}

// ---------------
// Testimonial Box
// ---------------
$funcname = 'wqhelper_sidebar_testimonial_box_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function( $args ) {

	global $wqurls, $current_user;
	$current_user = wp_get_current_user();
	$useremail = $current_user->user_email;
	if ( strstr( $useremail, '@localhost' ) ) {
		$useremail = '';
	}
	$userid = $current_user->ID;
	$userdata = get_userdata( $userid );
	$username = $userdata->first_name;
	$lastname = $userdata->last_name;
	if ( '' != $lastname ) {
		$username .= ' ' . $lastname;
	}

	$prefix = $args[0];
	$pluginslug = $args[1];
	$pluginslug = str_replace( '-', '', $pluginslug );

	// --- testimonial script ---
	echo "<script>
	function wq_showhide_testimonial_box() {
		if (document.getElementById('sendtestimonial').style.display == '') {
			document.getElementById('sendtestimonial').style.display = 'none';
		} else {
			document.getElementById('sendtestimonial').style.display = '';
			document.getElementById('testimonialbox').style.display = 'none';
		}
	}
	function wq_submit_testimonial() {
		document.getElementById('testimonialbox').style.display='';
		document.getElementById('sendtestimonial').style.display='none';
	}</script>";

	// --- testimonial form ---
	echo '<center><a href="javascript:void(0);" onclick="wq_showhide_testimonial_box();">' . esc_html( wqhelper_translate( 'Send me a thank you or testimonial.' ) ) . '</a><br>';
	echo '<div id="sendtestimonial" style="display:none;" align="center"><center>';
		echo '<form action="' . esc_url( $wqurls['wq'] ) . '" method="post" target="testimonialbox" onsubmit="wq_submit_testimonial();">';
		echo '<b>' . esc_html( wqhelper_translate( 'Your Testimonial' ) ) . ':</b><br>';
		echo '<textarea rows="5" cols="25" name="message"></textarea><br>';
		echo '<label for="testimonial_sender">' . esc_html( wqhelper_translate( 'Your Name' ) ) . ':</label> ';
		echo '<input type="text" style="width:200px;" name="testimonial_sender" id="testimonial_sender" value="' . esc_attr( $username ) . '"><br>';
		echo '<input type="text" placeholder="' . esc_attr( wqhelper_translate( 'Your Website' ) ) . '... (' . esc_html( wqhelper_translate( 'optional' ) ) . ')" style="width:200px;" name="testimonial_website" value=""><br>';
		echo '<input type="hidden" name="sending_plugin_testimonial" value="yes">';
		echo '<input type="hidden" name="for_plugin" value="' . esc_attr( $pluginslug ) . '">';
		echo '<input type="submit" class="button-secondary" value="' . esc_attr( wqhelper_translate( 'Send Testimonial' ) ) . '">';
	echo '</form></center></div>';
	echo '<iframe name="testimonialbox" id="testimonialbox" frameborder="0" src="javascript:void(0);" style="display:none;" width="250" height="50" scrolling="no"></iframe>';
 };
}

// ---------------------
// Save Sidebar Settings
// ---------------------
// !! caller exception !! uses form matching version function
$funcname = 'wqhelper_update_sidebar_options_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function() {

	// 1.7.8: fix sidebar option saveing variable pre to prefix
	// 1.8.1: use sanitize_title on request variable
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$prefix = sanitize_title( $_REQUEST['sidebarprefix'] );
	if ( current_user_can( 'manage_options' ) ) {

		// 1.6.5: check nonce field
		check_admin_referer( $prefix . '_sidebar' );

		// 1.5.0: convert to single array of plugin sidebar options
		$sidebaroptions = get_option( $prefix . '_sidebar_options' );
		if ( !$sidebaroptions ) {
			$sidebaroptions = array(
				'installdate' => date( 'Y-m-d' ),
			);
		}
		$sidebaroptions['adsboxoff'] = $sidebaroptions['subscribeboxoff'] = $sidebaroptions['donationboxoff'] = $sidebaroptions['reportboxoff'] = '';

		// 1.8.0: reduce code by looping box options
		$options = array(
			'_donation_box_off' => 'donationboxoff',
			// '_testimonial_box_off' => 'testimonialboxoff',
			'_subscribe_box_off' => 'subscribeboxoff',
			'_report_box_off' => 'reportboxoff',
			'_ads_box_off' => 'adsboxoff',
		);
		foreach ( $options as $key => $option ) {
			// phpcs:ignore WordPress.Security.NonceValidation.Recommended
			if ( isset( $_POST[$prefix . $key] ) && ( 'checked' == sanitize_title( $_POST[$prefix . $key] ) ) ) {
				$sidebaroptions[$option] = 'checked';
			}
		}
		update_option( $prefix . '_sidebar_options', $sidebaroptions );
		// print_r($sidebaroptions); // debug point

		// --- javascript response callbacks ---
		// 1.8.0: reduce code by looping box options
		$boxes = array(
			'donationboxoff' => 'donate-box',
			'subscribeboxoff' => 'subscribe-box',
			'reportboxoff' => 'report-box',
			'adsboxoff' => 'recommend-box',
		);
		echo "<script>" . PHP_EOL;
		foreach ( $boxes as $key => $id ) {
			echo "if (parent.document.getElementById('" . esc_js( $id ) . "')) {";
			if ( 'checked' == $sidebaroptions[$key] ) {
				echo "parent.document.getElementById('" . esc_js( $id ) . "').style.display = 'none';}";
			} else {
				echo "parent.document.getElementById('" . esc_js( $id ) . "').style.display = '';}";
			}
			echo PHP_EOL;
		}

		echo PHP_EOL . "parent.document.getElementById('sidebarsaved').style.display = ''; ";
		echo PHP_EOL . "parent.document.getElementById('sidebarsettings').style.display = 'none'; ";
		echo "</script>";

		// --- maybe call Special Update Options ---
		$funcname = $prefix . '_update_sidebar_options_special';
		if ( function_exists( $funcname ) ) {
			call_user_func( $funcname );
		}
	}

	exit;
 };
}

// ---------------------
// Sticky Kit Javascript
// ---------------------
// 1.8.1: added echo argument to function
$funcname = 'wqhelper_sidebar_stickykitscript_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function( $echo = false ) {

	// 1.8.1: added for backwards compatibility
	if ( !$echo ) {
		ob_start();
	}

	echo '<script>/* Sticky-kit v1.1.2 | WTFPL | Leaf Corcoran 2015 | http://leafo.net */
(function(){var b,f;b=this.jQuery||window.jQuery;f=b(window);b.fn.stick_in_parent=function(d){var A,w,J,n,B,K,p,q,k,E,t;null==d&&(d={});t=d.sticky_class;B=d.inner_scrolling;E=d.recalc_every;k=d.parent;q=d.offset_top;p=d.spacer;w=d.bottoming;null==q&&(q=0);null==k&&(k=void 0);null==B&&(B=!0);null==t&&(t="is_stuck");A=b(document);null==w&&(w=!0);J=function(a,d,n,C,F,u,r,G){var v,H,m,D,I,c,g,x,y,z,h,l;if(!a.data("sticky_kit")){a.data("sticky_kit",!0);I=A.height();g=a.parent();null!=k&&(g=g.closest(k));
if(!g.length)throw"failed to find stick parent";v=m=!1;(h=null!=p?p&&a.closest(p):b("<div />"))&&h.css("position",a.css("position"));x=function(){var c,f,e;if(!G&&(I=A.height(),c=parseInt(g.css("border-top-width"),10),f=parseInt(g.css("padding-top"),10),d=parseInt(g.css("padding-bottom"),10),n=g.offset().top+c+f,C=g.height(),m&&(v=m=!1,null==p&&(a.insertAfter(h),h.detach()),a.css({position:"",top:"",width:"",bottom:""}).removeClass(t),e=!0),F=a.offset().top-(parseInt(a.css("margin-top"),10)||0)-q,
u=a.outerHeight(!0),r=a.css("float"),h&&h.css({width:a.outerWidth(!0),height:u,display:a.css("display"),"vertical-align":a.css("vertical-align"),"float":r}),e))return l()};x();if(u!==C)return D=void 0,c=q,z=E,l=function(){var b,l,e,k;if(!G&&(e=!1,null!=z&&(--z,0>=z&&(z=E,x(),e=!0)),e||A.height()===I||x(),e=f.scrollTop(),null!=D&&(l=e-D),D=e,m?(w&&(k=e+u+c>C+n,v&&!k&&(v=!1,a.css({position:"fixed",bottom:"",top:c}).trigger("sticky_kit:unbottom"))),e<F&&(m=!1,c=q,null==p&&("left"!==r&&"right"!==r||a.insertAfter(h),
h.detach()),b={position:"",width:"",top:""},a.css(b).removeClass(t).trigger("sticky_kit:unstick")),B&&(b=f.height(),u+q>b&&!v&&(c-=l,c=Math.max(b-u,c),c=Math.min(q,c),m&&a.css({top:c+"px"})))):e>F&&(m=!0,b={position:"fixed",top:c},b.width="border-box"===a.css("box-sizing")?a.outerWidth()+"px":a.width()+"px",a.css(b).addClass(t),null==p&&(a.after(h),"left"!==r&&"right"!==r||h.append(a)),a.trigger("sticky_kit:stick")),m&&w&&(null==k&&(k=e+u+c>C+n),!v&&k)))return v=!0,"static"===g.css("position")&&g.css({position:"relative"}),
a.css({position:"absolute",bottom:d,top:"auto"}).trigger("sticky_kit:bottom")},y=function(){x();return l()},H=function(){G=!0;f.off("touchmove",l);f.off("scroll",l);f.off("resize",y);b(document.body).off("sticky_kit:recalc",y);a.off("sticky_kit:detach",H);a.removeData("sticky_kit");a.css({position:"",bottom:"",top:"",width:""});g.position("position","");if(m)return null==p&&("left"!==r&&"right"!==r||a.insertAfter(h),h.remove()),a.removeClass(t)},f.on("touchmove",l),f.on("scroll",l),f.on("resize",
y),b(document.body).on("sticky_kit:recalc",y),a.on("sticky_kit:detach",H),setTimeout(l,0)}};n=0;for(K=this.length;n<K;n++)d=this[n],J(b(d));return this}}).call(this);</script>';

	// 1.8.1 added for backwards compatibility
	if ( !$echo ) {
		$stickykit = ob_get_contents();
		ob_end_clean();
		return $stickykit;
	}
 };
} // '

// ---------------------
// Float Menu Javascript
// ---------------------
// 1.8.1: added echo argument to function
$funcname = 'wqhelper_sidebar_floatmenuscript_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function( $echo = false ) {

	// 1.8.1: added for backwards compatibility
	if ( !$echo ) {
		ob_start();
	}

	echo "
	<style>.floatbox {position:absolute;width:250px;top:30px;right:15px;z-index:100;}</style>

	<script>
	/* Script by: www.jtricks.com
	 * Version: 1.8 (20111103)
	 * Latest version: www.jtricks.com/javascript/navigation/floating.html
	 *
	 * License:
	 * GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
	 */
	var floatingMenu =
	{
		hasInner: typeof(window.innerWidth) == 'number',
		hasElement: typeof(document.documentElement) == 'object'
		&& typeof(document.documentElement.clientWidth) == 'number'
	};

	var floatingArray =
	[
	];

	floatingMenu.add = function(obj, options)
	{
		var name;  var menu;
		if (typeof(obj) === 'string') name = obj; else menu = obj;
		if (options == undefined) {
		floatingArray.push( {id: name, menu: menu, targetLeft: 0, targetTop: 0, distance: .07, snap: true});
		}
		else  {
		floatingArray.push(
			{id: name, menu: menu, targetLeft: options.targetLeft, targetRight: options.targetRight,
			targetTop: options.targetTop, targetBottom: options.targetBottom, centerX: options.centerX,
			centerY: options.centerY, prohibitXMovement: options.prohibitXMovement,
			prohibitYMovement: options.prohibitYMovement, distance: options.distance != undefined ? options.distance : .07,
			snap: options.snap, ignoreParentDimensions: options.ignoreParentDimensions, scrollContainer: options.scrollContainer,
			scrollContainerId: options.scrollContainerId
			});
		}
	};

	floatingMenu.findSingle = function(item) {
		if (item.id) item.menu = document.getElementById(item.id);
		if (item.scrollContainerId) item.scrollContainer = document.getElementById(item.scrollContainerId);
	};

	floatingMenu.move = function (item) {
		if (!item.prohibitXMovement) {item.menu.style.left = item.nextX + 'px'; item.menu.style.right = '';}
		if (!item.prohibitYMovement) {item.menu.style.top = item.nextY + 'px'; item.menu.style.bottom = '';}
	};

	floatingMenu.scrollLeft = function(item) {
		// If floating within scrollable container use it's scrollLeft
		if (item.scrollContainer) return item.scrollContainer.scrollLeft;
		var w = window.top; return this.hasInner ? w.pageXOffset : this.hasElement
		  ? w.document.documentElement.scrollLeft : w.document.body.scrollLeft;
	};
	floatingMenu.scrollTop = function(item) {
		// If floating within scrollable container use it's scrollTop
		if (item.scrollContainer)
		return item.scrollContainer.scrollTop;
		var w = window.top; return this.hasInner ? w.pageYOffset : this.hasElement
		  ? w.document.documentElement.scrollTop : w.document.body.scrollTop;
	};
	floatingMenu.windowWidth = function() {
		return this.hasElement ? document.documentElement.clientWidth : document.body.clientWidth;
	};
	floatingMenu.windowHeight = function() {
		if (floatingMenu.hasElement && floatingMenu.hasInner) {
		// Handle Opera 8 problems
		return document.documentElement.clientHeight > window.innerHeight
			? window.innerHeight : document.documentElement.clientHeight
		}
		else {
		return floatingMenu.hasElement ? document.documentElement.clientHeight : document.body.clientHeight;
		}
	};
	floatingMenu.documentHeight = function() {
		var innerHeight = this.hasInner ? window.innerHeight : 0;
		var body = document.body, html = document.documentElement;
		return Math.max(body.scrollHeight, body.offsetHeight, html.clientHeight,
		html.scrollHeight, html.offsetHeight, innerHeight);
	};
	floatingMenu.documentWidth = function() {
		var innerWidth = this.hasInner ? window.innerWidth : 0;
		var body = document.body, html = document.documentElement;
		return Math.max(body.scrollWidth, body.offsetWidth, html.clientWidth, html.scrollWidth, html.offsetWidth,
		innerWidth);
	};
	floatingMenu.calculateCornerX = function(item) {
		var offsetWidth = item.menu.offsetWidth;
		if (item.centerX)
		return this.scrollLeft(item) + (this.windowWidth() - offsetWidth)/2;
		var result = this.scrollLeft(item) - item.parentLeft;
		if (item.targetLeft == undefined) {result += this.windowWidth() - item.targetRight - offsetWidth;}
		else {result += item.targetLeft;}
		if (document.body != item.menu.parentNode && result + offsetWidth >= item.confinedWidthReserve)
		{result = item.confinedWidthReserve - offsetWidth;}
		if (result < 0) result = 0;
		return result;
	};
	floatingMenu.calculateCornerY = function(item) {
		var offsetHeight = item.menu.offsetHeight;
		if (item.centerY) return this.scrollTop(item) + (this.windowHeight() - offsetHeight)/2;
		var result = this.scrollTop(item) - item.parentTop;
		if (item.targetTop === undefined) {result += this.windowHeight() - item.targetBottom - offsetHeight;}
		else {result += item.targetTop;}

		if (document.body != item.menu.parentNode && result + offsetHeight >= item.confinedHeightReserve) {
		result = item.confinedHeightReserve - offsetHeight;
		}

		if (result < 0) result = 0;
		return result;
	};
	floatingMenu.computeParent = function(item) {
		if (item.ignoreParentDimensions) {
		item.confinedHeightReserve = this.documentHeight(); item.confinedWidthReserver = this.documentWidth();
		item.parentLeft = 0; item.parentTop = 0; return;
		}
		var parentNode = item.menu.parentNode; var parentOffsets = this.offsets(parentNode, item);
		item.parentLeft = parentOffsets.left; item.parentTop = parentOffsets.top;
		item.confinedWidthReserve = parentNode.clientWidth;

		// We could have absolutely-positioned DIV wrapped
		// inside relatively-positioned. Then parent might not
		// have any height. Try to find parent that has
		// and try to find whats left of its height for us.
		var obj = parentNode; var objOffsets = this.offsets(obj, item);
		while (obj.clientHeight + objOffsets.top < item.menu.offsetHeight + parentOffsets.top) {
		obj = obj.parentNode; objOffsets = this.offsets(obj, item);
		}
		item.confinedHeightReserve = obj.clientHeight - (parentOffsets.top - objOffsets.top);
	};
	floatingMenu.offsets = function(obj, item)
	{
		var result = {left: 0, top: 0};
		if (obj === item.scrollContainer) return;
		while (obj.offsetParent && obj.offsetParent != item.scrollContainer) {
		result.left += obj.offsetLeft; result.top += obj.offsetTop; obj = obj.offsetParent;
		}
		if (window == window.top) return result;

		// we are IFRAMEd
		var iframes = window.top.document.body.getElementsByTagName('IFRAME');
		for (var i = 0; i < iframes.length; i++)
		{
		if (iframes[i].contentWindow != window) continue;
		obj = iframes[i];
		while (obj.offsetParent) {
			result.left += obj.offsetLeft; result.top += obj.offsetTop; obj = obj.offsetParent;
		}
		}
		return result;
	};
	floatingMenu.doFloatSingle = function(item) {
		this.findSingle(item); var stepX, stepY; this.computeParent(item);
		var cornerX = this.calculateCornerX(item); var stepX = (cornerX - item.nextX) * item.distance;
		if (Math.abs(stepX) < .5 && item.snap || Math.abs(cornerX - item.nextX) == 1) {
		stepX = cornerX - item.nextX;
		}
		var cornerY = this.calculateCornerY(item);
		var stepY = (cornerY - item.nextY) * item.distance;
		if (Math.abs(stepY) < .5 && item.snap || Math.abs(cornerY - item.nextY) == 1) {
		stepY = cornerY - item.nextY;
		}
		if (Math.abs(stepX) > 0 || Math.abs(stepY) > 0) {
		item.nextX += stepX; item.nextY += stepY; this.move(item);
		}
	};
	floatingMenu.fixTargets = function() {};
	floatingMenu.fixTarget = function(item) {};
	floatingMenu.doFloat = function() {
		this.fixTargets();
		for (var i=0; i < floatingArray.length; i++) {
		this.fixTarget(floatingArray[i]); this.doFloatSingle(floatingArray[i]);
		}
		setTimeout('floatingMenu.doFloat()', 20);
	};
	floatingMenu.insertEvent = function(element, event, handler) {
		// W3C
		if (element.addEventListener != undefined) {
		element.addEventListener(event, handler, false); return;
		}
		var listener = 'on' + event;
		// MS
		if (element.attachEvent != undefined) {
		element.attachEvent(listener, handler);
		return;
		}
		// Fallback
		var oldHandler = element[listener];
		element[listener] = function (e) {
			e = (e) ? e : window.event;
			var result = handler(e);
			return (oldHandler != undefined)
			&& (oldHandler(e) == true)
			&& (result == true);
		};
	};

	floatingMenu.init = function() {
		floatingMenu.fixTargets();
		for (var i=0; i < floatingArray.length; i++) {
		floatingMenu.initSingleMenu(floatingArray[i]);
		}
		setTimeout('floatingMenu.doFloat()', 100);
	};
	// Some browsers init scrollbars only after
	// full document load.
	floatingMenu.initSingleMenu = function(item) {
		this.findSingle(item); this.computeParent(item); this.fixTarget(item); item.nextX = this.calculateCornerX(item);
		item.nextY = this.calculateCornerY(item); this.move(item);
	};
	floatingMenu.insertEvent(window, 'load', floatingMenu.init);

	// Register ourselves as jQuery plugin if jQuery is present
	if (typeof(jQuery) !== 'undefined') {
		(function ($) {
		$.fn.addFloating = function(options) {
			return this.each(function() {
			floatingMenu.add(this, options);
			});
		};
		}) (jQuery);
	}
	</script>";

	// 1.8.1: added for backwards compatibility
	if ( !$echo ) {
		$floatbox = ob_get_contents();
		ob_end_clean();
		return $floatbox;
	}
 };
}


// -----------------------------
// === Dashboard Feed Widget ===
// -----------------------------

// -----------------------------
// Add the Dashboard Feed Widget
// -----------------------------
// 1.7.8: simplify dashboard detection
$requesturi = $_SERVER['REQUEST_URI'];
if ( ( preg_match( '|index.php|i', $requesturi ) )
	|| ( stristr( $requesturi, '/wp-admin/' ) )
	|| ( stristr( $requesturi, '/wp-admin/network/' ) ) ) {
	if ( !has_action( 'wp_dashboard_setup', 'wqhelper_add_dashboard_feed_widget' ) ) {
		add_action( 'wp_dashboard_setup', 'wqhelper_add_dashboard_feed_widget' );
	}
}

// ------------------------
// Load the Dashboard Feeds
// ------------------------
$funcname = 'wqhelper_add_dashboard_feed_widget_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function() {

	global $wp_meta_boxes, $current_user;
	if ( current_user_can( 'manage_options' ) || current_user_can( 'install_plugins' ) ) {

		// --- check if already loaded ---
		// 1.6.1: fix to undefined index warning
		$wordquestloaded = $pluginreviewloaded = false;
		foreach ( array_keys( $wp_meta_boxes['dashboard']['normal']['core'] ) as $name ) {
			if ( 'wordquest' == $name ) {
				$wordquestloaded = true;
			}
			if ( 'pluginreview' == $name ) {
				$pluginreviewloaded = true;
			}
		}

		// --- maybe add wordquest feed widget
		if ( !$wordquestloaded ) {
			wp_add_dashboard_widget( 'wordquest', 'WordQuest Alliance', 'wqhelper_dashboard_feed_widget' );
		}

		// --- maybe add plugin review feed widget ---
		if ( !$pluginreviewloaded ) {
			wp_add_dashboard_widget( 'pluginreview', 'Plugin Review Network', 'wqhelper_pluginreview_feed_widget' );
		}

		// --- enqueue dashboard feed javascript ---
		if ( !has_action( 'admin_footer', 'wqhelper_dashboard_feed_javascript' ) ) {
			add_action( 'admin_footer', 'wqhelper_dashboard_feed_javascript' );
		}
	}
 };
}

// -----------------------------------
// WordQuest Dashboard Feed Javascript
// -----------------------------------
$funcname = 'wqhelper_dashboard_feed_javascript_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function() {
	// 1.8.0: added get admin AJAX URL
	$adminajax = admin_url( 'admin-ajax.php' );
	// 1.8.0: removed siteurl from function for security
	echo "<script>function wq_load_feed_cat(site) {
		selectelement = document.getElementById(site+'catselector');
		catslug = selectelement.options[selectelement.selectedIndex].value;
		url = '" . esc_url( $adminajax ) . "?action=wqhelper_load_feed_category&category='+catslug+'&site='+site;
		document.getElementById('feedcatloader').src = url;
		
	}</script>";
	echo '<iframe src="javascript:void(0);" id="feedcatloader" style="display:none;"></iframe>';
 };
}

// -------------------------------
// WordQuest Dashboard Feed Widget
// -------------------------------
$funcname = 'wqhelper_dashboard_feed_widget_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function() {

	// maybe Get Latest Release info
	// -----------------------------
	global $wqdebug, $wqreleases, $wqurls;
	$latestrelease = $nextrelease = '';

	if ( isset( $wqreleases ) ) {
		if ( isset( $wqreleases['latest'] ) ) {
			$latestrelease = $wqreleases['latest'];
		}
		if ( isset( $wqreleases['next'] ) ) {
			$nextrelease = $wqreleases['next'];
		}
	} else {
		$pluginsinfo = wqhelper_get_plugin_info();
		if ( is_array( $pluginsinfo ) ) {
			foreach ( $pluginsinfo as $plugin ) {
				if ( isset( $plugin['slug'] ) ) {
					if ( ( isset( $plugin['latestrelease'] ) && ( 'yes' == $plugin['latestrelease'] ) )
					  || ( isset( $plugin['nextrelease'] ) && ( 'yes' == $plugin['nextrelease'] ) ) ) {
						$plugininfo = $plugin;
						$plugins = get_plugins();
						$plugininfo['installed'] = 'no';
						foreach ( $plugins as $pluginfile => $values ) {
							if ( sanitize_title( $values['Name'] ) == $plugininfo['slug'] ) {
								$plugininfo['installed'] = 'yes';
							}
						}
					}
					if ( isset( $plugin['latestrelease'] ) && ( 'yes' == $plugin['latestrelease'] ) ) {
						$latestrelease = $plugininfo;
					}
					if ( isset( $plugin['nextrelease'] ) && ( 'yes' == $plugin['nextrelease'] ) ) {
						$nextrelease = $plugininfo;
					}
				}
			}
		}
	}
	// echo "<!-- Latest Release: " . print_r( $latestrelease, true ) . " -->";
	// echo "<!-- Next Release: " . print_r( $nextrelease, true ) . " -->";

	// maybe Display Latest Release Info
	// ---------------------------------
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_REQUEST['page'] ) && ( 'wordquest' == sanitize_title( $_REQUEST['page'] ) ) ) {

		// --- do not duplicate here as already output for wordquest page ---
		echo '';

	} elseif ( isset( $latestrelease ) && is_array( $latestrelease ) && ( 'no' == $latestrelease['installed'] ) ) {

		echo '<b>' . esc_html( wqhelper_translate( 'Latest Plugin Release' ) ) . '</b><br>';
		echo '<table><tr><td align="center">';
			echo '<img src="' . esc_url( $latestrelease['icon'] ) . '" width="75" height="75" alt="' . esc_attr( wqhelper_translate( 'Latest Release Icon' ) ) . '"><br>';
			echo '<a href="' . esc_url( $latestrelease['home'] ) . '" target="_blank"><b>' . esc_html( $latestrelease['title'] ) . '</b></a>';
		echo '</td><td width="10"></td><td>';
			echo '<span style="font-size:9pt;">' . esc_html( $latestrelease['description'] ) . '</span><br><br>';

			if ( isset( $latestrelease['package'] ) && is_array( $latestrelease['package'] ) ) {

				// 1.6.6: check for wordpress.org only installs
				global $wq_wordpress_org;
				$installlink = false;
				// 1.8.0: fix to variable typo (wqplugin)
				if ( $wq_wordpress_org && $latestrelease['wporgslug'] ) {
					$installlink = self_admin_url( 'update.php' ) . '?action=install-plugin&plugin=' . $latestrelease['wporgslug'];
					$installlink = wp_nonce_url( $installlink, 'install-plugin_' . $latestrelease['wporgslug'] );
				} else {
					// 1.8.0: fix to missing left hand side variable installlink
					$installlink = admin_url( 'update.php' ) . '?action=wordquest_plugin_install&plugin=' . $latestrelease['slug'];
					$installlink = wp_nonce_url( $installlink, 'plugin-upload' );
				}
				if ( $installlink ) {
					echo '<input type="hidden" name="' . esc_attr( $latestrelease['slug'] ) . '-install-link" value="' . esc_url( $installlink ) . '">';
					echo '<center><a href="' . esc_url( $installlink ) . '" class="button-primary">' . esc_html( wqhelper_translate( 'Install Now' ) ) . '</a></center>';
				} else {
					$pluginlink = $wqurls['wq'] . '/plugins/' . $latestrelease['slug'];
					echo '<center><a href="' . esc_url( $pluginlink ) . '" class="button-primary" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Plugin Home' ) ) . '</a></center>';
				}
			}

		echo '</td></tr></table><br>';

	} elseif ( isset( $nextrelease ) && is_array( $nextrelease ) ) {

		echo '<b>' . esc_html( wqhelper_translate( 'Upcoming Plugin Release' ) ) . '</b><br>';
		echo '<table><tr><td align="center">';
			echo '<img src="' . esc_url( $nextrelease['icon'] ) . '" width="75" height="75"alt="' . esc_attr( wqhelper_translate( 'Next Release Icon' ) ) . '"><br>';
			echo '<a href="' . esc_url( $nextrelease['home'] ) . '" target="_blank"><b>' . esc_html( $nextrelease['title'] ) . '</b></a>';
		echo '</td><td width="10"></td><td><span style="font-size:9pt;">' . esc_html( $nextrelease['description'] ) . '</span><br><br>';
		$releasetime = strtotime( $nextrelease['releasedate'] );
		echo '<center><span style="font-size:9pt;">' . esc_html( wqhelper_translate( 'Expected' ) ) . ': ' . esc_html( date( 'jS F Y', $releasetime ) ) . '</span></center>';
		echo '</td></tr></table><br>';

	}

	// --- feed link styles ---
	echo "<style>.feedlink {text-decoration:none;} .feedlink:hover {text-decoration:underline;}</style>";

	// WordQuest Posts Feed
	// --------------------
	$rssurl = $wqurls['wq'] . '/category/guides/feed/';
	if ( $wqdebug ) {
		$feed = '';
		delete_transient( 'wordquest_guides_feed' );
	} else {
		$feed = trim( get_transient( 'wordquest_guides_feed' ) );
	}

	// --- fetch posts feed ---
	if ( !$feed || ( '' == $feed ) ) {
		$rssfeed = fetch_feed( $rssurl );
		$feeditems = 4;
		$args = array( $rssfeed, $feeditems );
		$feed = wqhelper_process_rss_feed( $args );
		if ( '' != $feed ) {
			set_transient( 'wordquest_guides_feed', $feed, ( 24 * 60 * 60 ) );
		}
	}

	// --- WordQuest Guides ----
	echo '<div id="wordquestguides">';
	echo '<div style="float:right;">&rarr;<a href="' . esc_url( $wqurls['wq'] ) . '/category/guides/" class="feedlink" target="_blank"> ' . esc_html( wqhelper_translate( 'More' ) ) . '...</a></div>';
	echo '<b><a href="' . esc_url( $wqurls['wq'] ) . '/category/guides/" class="feedlink" target="_blank">' . esc_html( wqhelper_translate( 'Latest WordQuest Guides' ) ) . '</a></b><br>';
	if ( '' != $feed ) {
		// 1.8.1: use wp_kses_post on feed output
		echo wp_kses_post( $feed );
	} else {
		echo esc_html( wqhelper_translate( 'Feed Currently Unavailable.' ) );
		delete_transient( 'wordquest_guides_feed' );
	}
	echo '</div>';

	// WordQuest Solutions Feed
	// ------------------------
	$rssurl = $wqurls['wq'] . '/quest/feed/';
	if ( $wqdebug ) {
		$feed = '';
		delete_transient( 'wordquest_quest_feed' );
	} else {
		$feed = trim( get_transient( 'wordquest_quest_feed' ) );
	}

	// --- fetch solutions feed ---
	if ( !$feed || ( '' != $feed ) ) {
		$rssfeed = fetch_feed( $rssurl );
		$feeditems = 4;
		$args = array( $rssfeed, $feeditems );
		$feed = wqhelper_process_rss_feed( $args );
		if ( '' != $feed ) {
			set_transient( 'wordquest_quest_feed', $feed, ( 24 * 60 * 60 ) );
		}
	}

	// --- output solutions feed ---
	echo '<div id="wordquestsolutions">';
	echo '<div style="float:right;">&rarr;<a href="' . esc_url( $wqurls['wq'] ) . '/solutions/" class="feedlink" target="_blank"> ' . esc_html( wqhelper_translate( 'More' ) ) . '...</a></div>';
	echo '<b><a href="' . esc_url( $wqurls['wq'] ) . '/solutions/" class="feedlink" target="_blank">' . esc_html( wqhelper_translate( 'Latest Solution Quests' ) ) . '</a></b><br>';
	if ( '' != $feed ) {
		// 1.8.1: use wp_kses_post on feed output
		echo wp_kses_post( $feed );
	} else {
		echo esc_html( wqhelper_translate( 'Feed Currently Unavailable.' ) );
		delete_transient( 'wordquest_quest_feed' );
	}
	echo '</div>';

	return;

	// -----------------------
	// Category Feed Selection
	// -----------------------
	// [currently not implented...]

	/* $pluginsurl = $wqurls['wq'] . '/?get_post_categories=yes';

	if ( $wqdebug ) {
		$categorylist = '';
		delete_transient( 'wordquest_feed_cats' );
	} else {
		$categorylist = trim( get_transient( 'wordquest_feed_cats' ) );
	}

	if ( !$categorylist || ( '' == $categorylist ) ) {
		$args = array( 'timeout' => 10 );
		$getcategorylist = wp_remote_get( $pluginsurl, $args );
		if ( !is_wp_error( $getcategorylist ) ) {
			$categorylist = $getcategorylist['body'];
			if ( $categorylist ) {
				set_transient( 'wordquest_feed_cats', $categorylist, ( 24 * 60 * 60 ) );
			}
		}
	}

	if ( strstr( $categorylist, '::::' ) ) {
		$categories = explode( '::::', $categorylist );
		if ( count( $categories ) > 0 ) {
			$i = 0;
			foreach ( $categories as $category ) {
				$catinfo = explode( '::', $category );
				$cats[$i]['name'] = $catinfo[0];
				$cats[$i]['slug'] = $catinfo[1];
				$cats[$i]['count'] = $catinfo[2];
				$i++;
			}

			if ( count( $cats ) > 0 ) {
				echo '<table><tr><td>';
					echo '<b>' . esc_html( wqhelper_translate( 'Category' ) ) . ':</b>';
					echo '</td><td width="7"></td><td>';
						echo '<select id="wqcatselector" onchange="wq_load_feed_cat(\'wq\',\'' . esc_url( $wqurls['wq'] ) . '\');">';
				// echo '<option value="news" selected="selected">WordQuest News</option>';
				foreach ( $cats as $cat ) {
					echo '<option value="' . esc_attr( $cat['slug'] ) . '"';
					if ( 'news' == $cat['slug'] ) {
						echo " selected='selected'";
					}
					echo '>' . esc_html( $cat['name'] ) . ' (' . esc_html( $cat['count'] ) . ')</option>';
				}
				echo '</select></td></tr></table>';
				echo '<div id="wqfeeddisplay"></div>';
			}
		}
	} */
 };
}

// ---------------------------------
// Plugin Review Network Feed Widget
// ---------------------------------
$funcname = 'wqhelper_pluginreview_feed_widget_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function() {

	// --- feed link styles ---
	echo "<style>.feedlink {text-decoration:none;} .feedlink:hover {text-decoration:underline;}</style>";

	// Latest Plugins Feed
	// -------------------
	global $wqdebug, $wqurls;
	$rssurl = $wqurls['prn'] . '/feed/';
	if ( $wqdebug ) {
		$feed = '';
		delete_transient( 'pluginreview_newest_feed' );
	} else {
		$feed = trim( get_transient( 'pluginreview_newest_feed' ) );
	}

	// --- fetch new plugins feed ---
	if ( !$feed || ( '' == $feed ) ) {
		$rssfeed = fetch_feed( $rssurl );
		$feeditems = 4;
		$args = array( $rssfeed, $feeditems );
		$feed = wqhelper_process_rss_feed( $args );
		if ( '' != $feed ) {
			set_transient( 'pluginreview_newest_feed', $feed, ( 24 * 60 * 60 ) );
		}
	}

	echo '<center><b><a href="' . esc_url( $wqurls['prn'] ) . '/directory/" class="feedlink" style="font-size:11pt;" target="_blank">';
	echo esc_html( wqhelper_translate( 'NEW' ) ) . ' ';
	echo esc_html( wqhelper_translate( 'Plugin Directory' ) ) . ' - ';
	echo esc_html( wqhelper_translate( 'by Category' ) ) . '!</a></b></center><br>';
	echo '<div id="pluginslatest">';

	// --- ouput latest plugins feed ---
	echo '<div style="float:right;">&rarr;<a href="' . esc_url( $wqurls['prn'] ) . '/directory/latest/" class="feedlink" target="_blank"> ' . esc_html( wqhelper_translate( 'More' ) ) . '...</a></div>';
	if ( '' != $feed ) {
		echo '<b>' . esc_html( wqhelper_translate( 'Latest Plugin Releases' ) ) . '</b><br>';
		// 1.8.1: use wp_kses_post on feed output
		echo wp_kses_post( $feed );
	} else {
		echo esc_html( wqhelper_translate( 'Feed Currently Unavailable' ) );
		delete_transient( 'prn_feed' );
	}
	echo '</div>';

	// Recently Updated Feed
	// ---------------------
	$rssurl = $wqurls['prn'] . '/feed/?orderby=modified';
	if ( $wqdebug ) {
		$feed = '';
		delete_transient( 'pluginreview_updated_feed' );
	} else {
		$feed = trim( get_transient( 'pluginreview_updated_feed' ) );
	}

	// --- fetch recently updated feed ---
	if ( !$feed || ( '' == $feed ) ) {
		$rssfeed = fetch_feed( $rssurl );
		$feeditems = 4;
		$args = array( $rssfeed, $feeditems );
		$feed = wqhelper_process_rss_feed( $args );
		if ( '' != $feed ) {
			set_transient( 'pluginreview_updated_feed', $feed, ( 24 * 60 * 60 ) );
		}
	}

	// --- output recently updated feed ---
	echo '<div id="pluginsupdated">';
	echo '<div style="float:right;">&rarr;<a href="' . esc_url( $wqurls['prn'] ) . '/directory/updated/" class="feedlink" target="_blank"> ' . esc_html( wqhelper_translate( 'More' ) ) . '...</a></div>';
	if ( '' != $feed ) {
		echo '<b>' . esc_html( wqhelper_translate( 'Recently Updated Plugins' ) ) . '</b><br>';
		// 1.8.1: use wp_kses_post on feed output
		echo wp_kses_post( $feed );
	} else {
		echo esc_html( wqhelper_translate( 'Feed Currently Unavailable' ) );
		delete_transient( 'prn_feed' );
	}
	echo '</div>';

	return;

	// -----------------------
	// Category Feed Selection
	// -----------------------
	// [currently not implented...]

	/* $categoryurl = $wqurls['prn'] . '/?get_review_categories=yes';

	// refresh once a day only to limit downloads
	if ( $wqdebug ) {
		$categorylist = '';
		delete_transient('prn_feed_cats');
	} else {
		$categorylist = trim( get_transient( 'prn_feed_cats' ) );
	}

	if ( !$categorylist || ( '' == $categorylist ) ) {
		$args = array( 'timeout' => 10 );
		$getcategorylist = wp_remote_get( $categoryurl, $args );
		if ( !is_wp_error( $getcategorylist ) ) {
			$categorylist = $getcategorylist['body'];
			if ( $categorylist ) {
				set_transient( 'prn_feed_cats', $categorylist, ( 24 * 60 * 60 ) );
			}
		}
	}

	if ( strstr( $categorylist, '::::' ) ) {
		$categories = explode( '::::', $categorylist );
		if ( count( $categories) > 0 ) {
			$i = 0;
			foreach ( $categories as $category ) {
				$catinfo = explode( '::', $category);
				$cats[$i]['name'] = $catinfo[0];
				$cats[$i]['slug'] = $catinfo[1];
				$cats[$i]['count'] = $catinfo[2];
				$i++;
			}

			if ( count( $cats ) > 0 ) {
				echo '<table><tr><td>';
					echo '<b>' . esc_html( wqhelper_translate( 'Category' ) ) . ':</b>';
				echo '</td><td width="7"></td><td>';
					echo "<select id='prncatselector' onchange='wq_load_feed_cat(\"prn\",\"".$wqurls['prn']."\");'>";
					// echo "<option value='reviews' selected='selected'>".wqhelper_translate('Plugin Reviews')."</option>";
					foreach ( $cats as $cat ) {
						echo '<option value="' . esc_attr( $cat['slug'] ) . '"';
						if ( 'reviews' == $cat['slug'] ) {
							echo ' selected="selected"';
						}
						echo '>' . esc_html( $cat['name'] ) . ' (' . esc_html( $cat['count'] ) . ')</option>';
					}
					echo '</select>';
				echo '</td></tr></table>';
				echo '<div id="prnfeeddisplay"></div>';
			}
		}
	} */
 };
}

// --------------------
// Load a Category Feed
// --------------------
$funcname = 'wqhelper_load_feed_category_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function() {

	global $wqurls;
	// 1.8.0: remove siteurl usage and validate site key instead
	// 1.8.1: use sanitize_title on request value
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$site = sanitize_title( $_GET['site'] );
	if ( !array_key_exists( $site, $wqurls ) ) {
		return;
	}
	$baseurl = $wqurls[$site];

	// 1.8.0: use sanitize_title_ on category slug
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$catslug = sanitize_title( $_GET['category'] );
	$categoryurl = $baseurl . '/category/' . $catslug . '/feed/';

	// 1.8.0: set separate URL and use esc_url
	$moreurl = $baseurl . '/category/' . $catslug . '/';
	$morelink = '<div align="right">&rarr; <a href="' . esc_url( $moreurl ) . '" style="feedlink" target="_blank"> ' . esc_html( wqhelper_translate( 'More' ) ) . '...</a></div>';

	// --- fetch the category feed ---
	$categoryrss = fetch_feed( $categoryurl );
	$feeditems = 10;

	// --- Process the Category Feed ---
	$args = array( $categoryrss, $feeditems );
	$categoryfeed = wqhelper_process_rss_feed( $args );
	if ( '' != $categoryfeed ) {
		$categoryfeed .= $morelink;
	}

	// --- send back to parent window ---
	// 1.8.0: added missing esc_js wrappers on variables
	echo "<script>categoryfeed = '" . esc_js( $categoryfeed ) . "';
	parent.document.getElementById('" . esc_js( site ) . "feeddisplay').innerHTML = categoryfeed;
	</script>";

	exit;
 };
}

// ----------------
// Process RSS Feed
// ----------------
$funcname = 'wqhelper_process_rss_feed_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function( $args ) {

	$rss = $args[0];
	$feeditems = $args[1];
	$processed = '';
	if ( is_wp_error( $rss ) ) {
		return '';
	}

	$maxitems = $rss->get_item_quantity( $feeditems );
	$rssitems = $rss->get_items( 0, $maxitems );

	if ( 0 == $maxitems ) {
		$processed = '';
	} else {
		// --- create feed list item display ---
		$processed = '<ul style="list-style:none;margin:0;text-align:left;">';
		foreach ( $rssitems as $item ) {
			$processed .= '<li>&rarr; <a href="' . esc_url( $item->get_permalink() ) . '" class="feedlink" target="_blank"';
			$processed .= ' title="Posted ' . esc_attr( $item->get_date( 'j F Y | g:i a' ) ) . '">';
			$processed .= esc_html( $item->get_title() ) . '</a></li>';
		}
		$processed .= '</ul>';
	}
	return $processed;
 };
}

// -----------
// Get Feed Ad
// -----------
// 1.8.0: get ad via feed
$funcname = 'wqhelper_get_feed_ad_' . $wqhv;
if ( !isset( $wqfunctions[$funcname] ) || !is_callable( $wqfunctions[$funcname] ) ) {
 $wqfunctions[$funcname] = function( $pluginslug ) {

	global $wqurls;
	$feedurl = $wqurls['prn'] . '/recommends/?s=1&f=1&c=' . esc_attr( $pluginslug ) . '&t=sidebar';
	add_filter( 'wp_feed_cache_transient_lifetime', 'wqhelper_ad_feed_interval' );
	$rss = fetch_feed( $feedurl );
	remove_filter( 'wp_feed_cache_transient_lifetime', 'wqhelper_ad_feed_interval' );
	if ( !$rss || is_wp_error( $rss ) ) {
		return '';
	}
	$rssitems = $rss->get_items( 0, 1 );
	$item = $rssitems[0];
	return $item->get_content();
 };
}

// ----------------------
// Ad Feed Cache Interval
// ----------------------
// 1.8.0: added for ad feed interval
if ( !function_exists( 'wqhelper_ad_feed_interval' ) ) {
 function wqhelper_ad_feed_interval( $seconds ) {
	// --- change feed interval ---
	return 300;
 }
}


// --- manual function list debug point ---
// add_action( 'plugins_loaded', function() {
//  echo "<!-- WQ Helper Functions: " . print_r( $wqfunctions, true ) . " -->";
// });


// -----------------
// === Changelog ===
// -----------------

// = 1.8.2 =
// - fix to floatbox divs condition when no recommendation
// - sanitize HTTP_HOST server global key

// = 1.8.1 =
// - improved output escaping and sanitization

// = 1.8.0 =
// - Fix WordPress Coding Standards sniffs
// - Change from script ads to feed ads

// = 1.7.9 =
// - fix to Admin submenu icon styles

// = 1.7.8 =
// - WordPress Coding Standards redux
// - added output escaping sanitization
// - fix to sidebar option saving variable
// - prefix javascript functions

// = 1.7.7 =
// - add isset check for plan key for backwards compatibility

// = 1.7.6 =
// - fix to check if donate link key setting is defined

// = 1.7.5 =
// - check/fix for Patreon button image URL (cross-versions)

// = 1.7.4 =
// - simplified PHP 5.3 minimum check
// - added Patreon supporter button for WP Medic plugins
// - added reminder notice link item icons
// - updated Supporter URLs in Usage Reminder Notices
// - replaced sidebar footer links for WP Medic plugins

// = 1.7.3 =
// - only show reminder notice to users with capabilities

// = 1.7.2 =
// - use superglobal directly in named functions to reduce code
// - replaced testimonial link in reminder with share link
// - removed testimonial sender box from sidebar
// - removed star rating link from sidebar

// = 1.7.1 =
// - adjust admin menu heading sizes from h3 to h4
// - adjust admin page column max-widths to 300px

// = 1.7.0 =
// - fix to first plugin install version saving

// = 1.6.9 =
// - replaced all translation wrappers
// - default translation to bioship theme text domain

// = 1.6.7 =
// - updated wordpress.org review links
// - added some missing translation wrappers

// = 1.6.6 =
// - check wordpress.org only installs/availability
// - sanitize posted wqhv version value
// - add permission check to debug switch

// = 1.6.5 =
// - added stickykit to replace float script
// - added basic string translation wrappers
// - added debug output switch
// - split released / upcoming plugin boxes
// - fix to latest / next release box
// - fix to sidebar options saving call
// - fix to admin notice boxer

// = 1.6.0 =
// - use variable function names
// - change function prefix to wqhelper
// - text link forms for donations

// = 1.5.0 =
// - added version checking/loading
// - added global admin page
// - added admin styles/scripts
// - added subscriber levels
// - further wordquest conversions
// - added freemius submenu styling
// - split feed load metaxboxes
// - added feed transient storage
// - added admin notice boxer
// - sidebar options to single array
// - AJAXify some helper actions

// = 1.4.0 =
// - change to wordquest.org
// - updated donation amounts

// = 1.3.0 =
// - added recurring donations
// - user email populate bonus form

// = 1.2.0 =
// - added bonus report form
