<?php

// ===============================
// === WORDQUEST PLUGIN HELPER ===
// ===============================

$wordquestversion = '1.7.7';

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
	'bio'	=> 'https://bioship.space'
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
if ( !has_action( 'admin_init', 'wqhelper_admin_loader', 1 ) ) {
	add_action( 'admin_init', 'wqhelper_admin_loader', 1 );
}
if ( !function_exists('wqhelper_admin_loader' ) ) {
 function wqhelper_admin_loader() {
 	global $wqdebug;

	// --- maybe set debug mode ---
	// 1.6.6: check debug switch here so we can check permissions
	if ( current_user_can('manage_options' ) ) {
		if ( isset( $_REQUEST['wqdebug'] ) ) {
            if ( in_array( $_REQUEST['wqdebug'], array( '1', 'yes' ) ) ) {
            	$wqdebug = true;
            }
        }
	}

	// --- maybe remove old action ---
 	// 1.6.0: maybe remove the pre 1.6.0 loader action
 	if ( has_action('admin_init', 'wordquest_admin_load' ) ) {
 		remove_action( 'admin_init', 'wordquest_admin_load' );
 	}

	// --- set helper version to use ---
 	// 1.6.0: new globals used for new method
 	global $wordquesthelper, $wordquesthelpers;
 	$wordquesthelper = max( $wordquesthelpers );
 	if ( $wqdebug ) {
        echo "<!-- WQ Helper Versions: " . print_r( $wordquesthelpers, true ) . " -->";
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
		echo "<!-- Caller Object: "; var_dump( $wqcaller ); echo " -->";
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
if ( !function_exists('wqhelper_update_sidebar_boxes' ) ) {
 function wqhelper_update_sidebar_boxes() {

	// --- get helper version ---
 	if ( !isset( $_POST['wqhv'] ) ) {
 		return;
 	} else {
 		$wqhv = $_POST['wqhv'];
 	}
 	// 1.6.6: added sanitization of version value
 	if ( !is_numeric( $wqhv ) || ( strlen( $wqhv ) !== 3 ) ) {
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
		if ( function_exists('translate' ) ) {
			return translate( $string, 'default' );
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

	// 1.7.7: bug out if using Adminsanity Notices box
	if ( defined( 'ADMINSANITY_LOAD_NOTICES' ) && ADMINSANITY_LOAD_NOTICES ) {
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
	echo "<script>function togglenoticebox() {divid = 'adminnoticewrap';
	if (document.getElementById(divid).style.display == '') {
		document.getElementById(divid).style.display = 'none'; document.getElementById('adminnoticearrow').innerHTML = '&#9662;';}
	else {document.getElementById(divid).style.display = ''; document.getElementById('adminnoticearrow').innerHTML= '&#9656;';} } ";
	// this is from /wp-admin/js/common.js... to move the notices if common.js is not loaded...
	echo "jQuery(document).ready(function() {jQuery( 'div.updated, div.error, div.notice' ).not( '.inline, .below-h2' ).insertAfter( jQuery( '.wrap h1, .wrap h2' ).first() ); });";
	echo "</script>";

	// --- output notice box ---
	$adminnotices = ''; // $adminnotices = '('.$notices.')';
	echo '<div style="width:680px" id="adminnoticebox" class="postbox">';
	echo '<h3 class="hndle" style="margin:7px 14px;font-size:12pt;" onclick="togglenoticebox();">';
	echo '<span id="adminnoticearrow">&#9662;</span> &nbsp; ';
	echo wqhelper_translate( 'Admin Notices' );
	echo $adminnotices . '</span></h3>';
	echo '<div id="adminnoticewrap" style="display:none";><h2></h2></div></div>';

	// echo '<div style="width:75%" id="adminnoticebox" class="postbox">';
	// echo '<h3 class="hndle" style="margin-left:20px;" onclick="togglenoticebox();"><span>&#9660; ';
	// echo wqhelper_translate('Admin Notices');
	// echo ' ('.$adminnotices.')</span></h3>';
	// echo '<div id="adminnoticewrap" style="display:none";><h2></h2></div></div>';

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
			|| ( ( 'bioship' != $pluginslug ) && current_user_can( 'install_plugins') ) ) {
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
				$installtime = @strtotime( $sidebaroptions['installdate'] );
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
            echo "<div class='updated notice is-dismissable' id='" . esc_attr( $pluginslug ) . "-reminder-notice' style='font-size:16px; line-height:24px; margin:0;'>";

			echo wqhelper_translate( "You've been enjoying" ) . ' ';
			echo $wqreminder[$pluginslug]['title'] . ' ' . wqhelper_translate( 'for' ) . ' ';
			echo $wqreminder[$pluginslug]['days'] . ' ' . wqhelper_translate( 'days' ) . '. ';
			echo wqhelper_translate( "If you like it, here's some ways you can help make it better" ) . ':<br>';

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
								echo '<a class="notice-link" href="' . esc_url( $wqurls['wp'] ) . '/support/theme/' . $pluginslug . '/reviews/#new-post" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Rate this Theme' ) ) . '</a>';
							} else {
								// --- plugin rate link ---
								echo '<a class="notice-link" href="' . esc_url( $wqurls['wp'] ) . '/support/plugin/' . $pluginslug . '/reviews/#new-post" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Rate this Plugin' ) ) . '</a>';
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
							echo '<a class="notice-link" href="' . esc_url( $wqurls['wq'] ) . '/plugins/' . $pluginslug . '/#share" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Share this Plugin' ) ) . '</a>';
						}
					echo '</li>';

					// --- Feedback Link ---
					// 1.7.4: renamed anchor to simply feedback in this context
					// 1.7.4: added feedback link (slanted envelope) icon
					echo '<li>';
						echo '<span style="color:#00E;" class="dashicons dashicons-email-alt"></span> ';
						echo '<a class="notice-link" href="' . esc_url( $wqurls['wq'] ) . '/support/' . $pluginslug . '" target="_blank">&rarr; ' . esc_html( wqhelper_translate( 'Provide Feedback' ) ) . '</a>';
					echo "</li>";

					// --- Contribute Link ---
					// 1.7.4: removed contribute link (just too many links)
					// echo "<li style='display:inline-block;margin-left:15px;'><a href='".$wqurls['wq']."/contribute/?tab=development' target=_blank>&rarr; ".wqhelper_translate('Contribute to Development')."</a></li>";

					// --- Pro Version plan link (Freemius) ---
					// TODO: handle Freemius plugin add-ons ?
					if ( isset( $wqreminder['hasplans'] ) && ( $wqreminder['hasplans'] ) ) {
						$upgradeurl = admin_url( 'admin.php' ) . '?page=' . $wqreminder['slug'] . '-pricing';
						echo '<li><a href="' . esc_url( $upgradeurl ) . '">';
							echo '<b>&rarr; ' . wqhelper_translate( 'Go PRO' ) .'</b>';
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
if ( !isset( $wqfunctions[$funcname]) || !is_callable( $wqfunctions[$funcname] ) ) {
	$wqfunctions[$funcname] = function() {

		// --- check conditions ---
		$pluginslug = $_REQUEST['slug'];
		$notice = $_REQUEST['notice'];
		if ( ( '30' != $notice ) && ( '90' != $notice ) && ( '365' != $notice ) ) {
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
			if ( !is_wp_error($plugininfo ) ) {
				$plugininfo = $plugininfo['body'];
				$dataend = "*****END DATA*****";
				if ( strstr( $plugininfo, $dataend ) ) {
					$pos = strpos( $plugininfo, $dataend );
					$plugininfo = substr( $plugininfo, 0, $pos );
					$plugininfo = json_decode( $plugininfo, true );
					set_transient( 'wordquest_plugin_info', $plugininfo, (12*60*60 ) );
				} else {
					$plugininfo = '';
				}
			} else {
				$plugininfo = '';
			}
		}

		if ( $wqdebug ) {
			echo "<!-- Plugin Info: "; print_r( $plugininfo ); echo " -->";
		}
		return $plugininfo;
	};
}

// ---------------------------
// Version Specific Admin Page
// ---------------------------
$funcname = 'wqhelper_admin_page_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
	$wqfunctions[$funcname] = function() {

	global $wordquesthelper, $wordquestplugins, $wqurls;

	echo '<div id="pagewrap" class="wrap">';

	// --- admin notice boxer ---
	wqhelper_admin_notice_boxer();

	// --- toggle metabox script ---
	echo "<script>function togglemetabox(divid) {
		var divid = divid+'-inside';
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
	$args = array('wordquest', 'special');
	wqhelper_sidebar_floatbox($args);

	// --- load sticky kit on sidebar ---
	// 1.6.5: replace floatmenu with stickykit
	echo wqhelper_sidebar_stickykitscript();
	echo '<style>#floatdiv {float:right;} #wpcontent, #wpfooter {margin-left:150px !important;}</style>';
	echo '<script>jQuery("#floatdiv").stick_in_parent();</script>';
	unset($wordquestplugins['wordquest']);

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
	$wordquesticon = plugins_url('images/wordquest.png', __FILE__);
	echo '<style>.wqlink {text-decoration:none;} .wqlink:hover {text-decoration:underline;}</style>';
	echo '<table><tr><td width="20"></td><td><img src="'.$wordquesticon.'"></td><td width="20"></td>';
	echo '<td><div id="page-title"><a href="'.$wqurls['wq'].'" target=_blank><h2>WordQuest Alliance</h2></a></div></td>';
	echo '<td width="30"></td><td><h4>&rarr; <a href="'.$wqurls['wq'].'/register/" class="wqlink" target=_blank>'.wqhelper_translate('Join').'</a></h4></td>';
	echo '<td> / </td><td><h4><a href="'.$wqurls['wq'].'/login/"  class="wqlink" target=_blank>'.wqhelper_translate('Login').'</a></h4></td>';
	echo '<td width="20"></td><td><h4>&rarr; <a href="'.$wqurls['wq'].'/solutions/"  class="wqlink" target=_blank>'.wqhelper_translate('Solutions').'</a></h4></td>';
	echo '<td width="20"></td><td><h4>&rarr; <a href="'.$wqurls['wq'].'/contribute/"  class="wqlink" target=_blank>'.wqhelper_translate('Contribute').'</a></h4></td>';
	echo '</tr></table>';

	// --- Output Plugins Column ---
	wqhelper_admin_plugins_column(null);

	// --- Output Feeds Column ---
	wqhelper_admin_feeds_column(null);

	// --- Wordquest Sidebar 'plugin' box ---
	function wq_sidebar_plugin_footer() {
		global $wqurls;
		$iconurl = plugins_url('images/wordquest.png', __FILE__);
		echo '<div id="pluginfooter"><div class="stuffbox" style="width:250px;background-color:#ffffff;"><h3>Source Info</h3><div class="inside">';
		echo "<center><table><tr>";
		echo "<td><a href='".$wqurls['wq']."' target='_blank'><img src='".$iconurl."' border=0></a></td></td>";
		echo "<td width='14'></td>";
		echo "<td><a href='".$wqurls['wq']."' target='_blank'>WordQuest Alliance</a><br>";
		echo "<a href='".$wqurls['wq']."/plugins/' target='_blank'><b>&rarr; WordQuest Plugins</b></a><br>";
		echo "<a href='".$wqurls['prn']."/directory/' target='_blank'>&rarr; Plugin Directory</a></td>";
		echo "</tr></table></center>";
		echo '</div></div></div>';
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
$funcname = 'wqhelper_admin_plugins_column_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
	$wqfunctions[$funcname] = function($args) {

	global $wordquesthelper, $wordquestplugins, $wqurls, $wqdebug;

	// --- check if WordPress.Org plugins only ---
	// 1.6.6: check if current WQ plugins are all installed via WordPress.Org
	// (if so, only provide option to install other WQ plugins in repository)
	global $wordpressorgonly; $wordpressorgonly = true;
	foreach ($wordquestplugins as $pluginslug => $plugin) {
		// if this is false, it was from wordquest not wordpress
		if (!$plugin['wporg']) {$wordpressorgonly = false;}
	}

	// --- Plugin Action Select Javascript ---
	// 1.7.2: updated star rating link
	// TODO: test all options here more thoroughly..?
	echo "<script>
	function dopluginaction(pluginslug) {
		var selectelement = document.getElementById(pluginslug+'-action');
		var actionvalue = selectelement.options[selectelement.selectedIndex].value;
		var linkel = document.getElementById(pluginslug+'-link');
		var adminpageurl = '".admin_url('admin.php')."';
		if (actionvalue == 'settings') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug;}
		if (actionvalue == 'update') {linkel.target = '_self'; linkel.href = document.getElementById(pluginslug+'-update-link').value;}
		if (actionvalue == 'activate') {linkel.target = '_self'; linkel.href = document.getElementById(pluginslug+'-activate-link').value;}
		if (actionvalue == 'install') {linkel.target = '_self';	linkel.href = document.getElementById(pluginslug+'-install-link').value;}
		if (actionvalue == 'support') {linkel.target = '_blank'; linkel.href = adminpageurl+'?page='+pluginslug+'-wp-support-forum';}
		if (actionvalue == 'donate') {linkel.target = '_blank';	linkel.href = '".$wqurls['wq']."/contribute/?plugin='+pluginslug;}
		if (actionvalue == 'testimonial') {linkel.target = '_blank'; linkel.href = '".$wqurls['wq']."/contribute/?tab=testimonial';}
		if (actionvalue == 'rate') {linkel = '_blank'; linkel.href = '".$wqurls['wp']."/plugins/'+pluginslug+'/reviews/#new-post';}
		if (actionvalue == 'development') {linkel.target = '_blank'; linkel.href= '".$wqurls['wq']."/contribute/?tab=development';}
		if (actionvalue == 'contact') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug+'-contact';}
		if (actionvalue == 'home') {linkel.target = '_blank'; linkel.href = '".$wqurls['wq']."/plugins/'+pluginslug+'/';}
		if (actionvalue == 'upgrade') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug+'-pricing';}
		if (actionvalue == 'account') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug+'-account';}
	}</script>";

	// --- link styles ---
	echo "<style>.pluginlink {text-decoration:none;} .pluginlink:hover {text-decoration:underline;}</style>";

	// --- Get Installed and Active Plugin Slugs ---
	$i = 0; foreach ($wordquestplugins as $pluginslug => $values) {$pluginslugs[$i] = $pluginslug; $i++;}
	// if ($wqdebug) {echo "<!-- Active Wordquest Plugins: "; print_r($pluginslugs); echo " -->";}

	// --- Get All Installed Plugins Info ---
	$i = 0; $installedplugins = get_plugins();
	foreach ($installedplugins as $pluginfile => $values) {$installedslugs[$i] = sanitize_title($values['Name']); $i++;}
	// if ($wqdebug) {echo "<!-- Installed Plugins: "; print_r($installedplugins); echo " -->";}
	// if ($wqdebug) {echo "<!-- Installed Plugin Slugs: "; print_r($installedslugs); echo " -->";}

	// Get Plugin Update Info
	// ----------------------
	// 1.6.6: define empty pluginupdates array
	$i = 0; $updateplugins = get_site_transient('update_plugins'); $pluginupdates = array();
	// 1.7.3: adde property exists check
	if (property_exists($updateplugins, 'response')) {
		foreach ($updateplugins->response as $pluginfile => $values) {$pluginupdates[$i] = $values->slug; $i++;}
	}
	// if ($wqdebug) {echo "<!-- Plugin Updates: "; print_r($updateplugins); echo " -->";}
	// if ($wqdebug) {echo "<!-- Plugin Update Slugs: "; print_r($pluginupdates); echo " -->";}

	// --- Get Available Plugins from WordQuest.org ---
	$plugininfo = wqhelper_get_plugin_info();

	// process plugin info
	$i = 0; $wqplugins = $wqpluginslugs = array();
	if (is_array($plugininfo)) {
		foreach ($plugininfo as $plugin) {
			// print_r($plugin); // debug point
			if (isset($plugin['slug'])) {
				$wqpluginslugs[$i] = $pluginslug = $plugin['slug']; $i++;
				if (isset($plugin['title'])) {$wqplugins[$pluginslug]['title'] = $plugin['title'];}
				if (isset($plugin['home'])) {$wqplugins[$pluginslug]['home'] = $plugin['home'];}
				if (isset($plugin['description'])) {$wqplugins[$pluginslug]['description'] = $plugin['description'];}
				if (isset($plugin['icon'])) {$wqplugins[$pluginslug]['icon'] = $plugin['icon'];}
				if (isset($plugin['paidplans'])) {$wqplugins[$pluginslug]['paidplans'] = $plugin['paidplans'];}
				if (isset($plugin['package'])) {$wqplugins[$pluginslug]['package'] = $plugin['package'];}

				if (isset($plugin['tags'])) {$wqplugins[$pluginslug]['tags'] = $plugin['tags'];}
				if (isset($plugin['cats'])) {$wqplugins[$pluginslug]['cats'] = $plugin['cats'];}

				// 1.6.5: check release date and status
				if (isset($plugin['releasedate'])) {$wqplugins[$pluginslug]['releasedate'] = $plugin['releasedate'];}
				if (isset($plugin['releasestatus'])) {$wqplugins[$pluginslug]['releasestatus'] = $plugin['releasestatus'];}
				else {$wqplugins[$pluginslug]['releasestatus'] = 'Upcoming';}

				// 1.6.6: check for wordpress.org slug
				if (isset($plugin['wporgslug'])) {$wqplugins[$pluginslug]['wporgslug'] = $plugin['wporgslug'];}
				else {$wpplugins[$pluginslug]['wporgslug'] = false;}

				if (in_array($pluginslug, $installedslugs)) {$wqplugins[$pluginslug]['installed'] = 'yes';}
				else {$wqplugins[$pluginslug]['installed'] = 'no';}

				// --- get latest plugin release ---
				if (isset($plugin['latestrelease']) && ($plugin['latestrelease'] == 'yes')) {
					$wqplugins[$pluginslug]['latestrelease'] = 'yes';
					$latestrelease = $wqplugins[$pluginslug];
					$latestrelease['slug'] = $pluginslug;
				}

				// --- get next plugin release ---
				// 1.6.5: check for next plugin release also
				if (isset($plugin['nextrelease']) && ($plugin['nextrelease'] == 'yes')) {
					$wqplugins[$pluginslug]['nextrelease'] = 'yes';
					$nextrelease = $wqplugins[$pluginslug];
					$nextrelease['slug'] = $pluginslug;
				}
			}
		}
	}
	// if ($wqdebug) {echo "<!-- WQ Plugin Slugs: "; print_r($wqpluginslugs); echo " -->";}
	if ($wqdebug) {echo "<!-- WQ Plugins: "; print_r($wqplugins); echo " -->";}

	// --- maybe set Plugin Release Info ---
	global $wqreleases;
	if (isset($latestrelease)) {$wqreleases['latest'] = $latestrelease;}
	if (isset($nextrelease)) {$wqreleases['next'] = $nextrelease;}

	// --- get Installed Wordquest Plugin Data ---
	$plugins = array(); $inactiveplugins = array();
	$i = $j = 0;
	foreach ($installedplugins as $pluginfile => $values) {

		$pluginslug = sanitize_title($values['Name']);
		$pluginfiles[$pluginslug] = $pluginfile;
		// echo '***'.$pluginslug.'***'; // debug point
		if (in_array($pluginslug, $wqpluginslugs) || in_array($pluginslug, $pluginslugs)) {

			// --- set plugin data ---
			$plugins[$i]['slug'] = $pluginslug;
			$plugins[$i]['name'] = $values['Name'];
			$plugins[$i]['filename'] = $pluginfile;
			$plugins[$i]['version'] = $values['Version'];
			$plugins[$i]['description'] = $values['Description'];

			// --- check for matching plugin update ---
			if (in_array($pluginslug, $pluginupdates)) {$plugins[$i]['update'] = 'yes';}
			else {$plugins[$i]['update'] = 'no';}

			// --- filter out to get inactive plugins ---
			if (!in_array($pluginslug, $pluginslugs)) {
				$inactiveplugins[$j] = $pluginslug; $j++;
				$inactiveversions[$pluginslug] = $values['Version'];
			}
			$i++;
		}
	}
	// if ($wqdebug) {echo "<!-- Plugin Data: "; print_r($plugins); echo " -->";}
	// if ($wqdebug) {echo "<!-- Inactive Plugins: "; print_r($inactiveplugins); echo " -->";}

	// --- check if BioShip Theme installed ---
	$themes = wp_get_themes(); $bioshipinstalled = false;
	foreach ($themes as $theme) {if ($theme->stylesheet == 'bioship') {$bioshipinstalled = true;} }

	// --- open plugin column ---
	echo '<div id="plugincolumn">';

		// Active Plugin Panel
		// -------------------
		$boxid = 'wordquestactive'; $boxtitle = wqhelper_translate('Active WordQuest Plugins');
		echo '<div id="'.$boxid.'" class="postbox">';
		echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
		echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';
		foreach ($wordquestplugins as $pluginslug => $plugin) {
			if ($pluginslug != 'bioship') { // filter out theme here

				// --- set update link ---
				if (in_array($pluginslug, $pluginupdates)) {
					$updatelink = admin_url('update.php').'?action=upgrade-plugin&plugin='.$pluginfiles[$pluginslug];
					$updatelink = wp_nonce_url($updatelink, 'upgrade-plugin_'.$pluginfiles[$pluginslug]);
					echo "<input type='hidden' id='".$pluginslug."-update-link' value='".$updatelink."'>";
				}

				// --- linked title and version ---
				echo "<tr><td><a href='".$wqurls['wq']."/plugins/".$pluginslug."' class='pluginlink' target=_blank>";
				echo $plugin['title']."</a></td><td width='20'></td>";
				echo "<td>".$plugin['version']."</td><td width='20'></td>";

				// --- update / settings options ---
				echo "<td><select name='".$pluginslug."-action' id='".$pluginslug."-action' style='font-size:8pt;'>";
				if (in_array($pluginslug, $pluginupdates)) {
					echo "<option value='update' selected='selected'>".wqhelper_translate('Update')."</option>";
					echo "<option value='settings'>".wqhelper_translate('Settings')."</option>";
				} else {echo "<option value='settings' selected='selected'>".wqhelper_translate('Settings')."</option>";}

				// --- donate / testimonial / support / development options ---
				echo "<option value='donate'>".wqhelper_translate('Donate')."</option>";
				// echo "<option value='testimonial'>".wqhelper_translate('Testimonial')."</option>";
				echo "<option value='support'>".wqhelper_translate('Support')."</option>";
				echo "<option value='development'>".wqhelper_translate('Development')."</option>";
				if (isset($plugin['wporgslug'])) {echo "<option value='Rate'>".wqhelper_translate('Rate')."</option>";}

				// --- check for Pro Plan availability ---
				// 1.7.2: added missing translation wrappers
				// TODO: check for Pro add-ons ?
				// if ($plugin['plan'] == 'premium') {echo "<option value='contact'>".wqhelper_translate('Contact')."</option>";}
				if (isset($wordquestplugins[$pluginslug]['hasplans']) && $wordquestplugins[$pluginslug]['hasplans']) {
					// 1.7.7: add isset check for plugin plan for back-compat
					if ( !isset( $plugin['plan'] ) || ( 'premium' != $plugin['plan'] ) ) {
						echo "<option style='font-weight:bold;' value='upgrade'>".wqhelper_translate('Go PRO!')."</option>";
					} else {
						echo "<option value='account'>".wqhelper_translate('Account')."</option>";
					}
				}

				// --- close select ---
				echo "</select></td><td width='20'></td>";

				// --- do selected action button ---
				echo "<td><a href='javascript:void(0);' target=_blank id='".$pluginslug."-link' onclick='dopluginaction(\"".$pluginslug."\");'>";
				echo "<input class='button-secondary' type='button' value='".wqhelper_translate('Go')."'></a></td></tr>";
			}
		}
		echo '</table></div></div>';

		// Inactive Plugin Panel
		// ---------------------
		if (count($inactiveplugins) > 0) {
			$boxid = 'wordquestinactive'; $boxtitle = wqhelper_translate('Inactive WordQuest Plugins');
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';
			foreach ($inactiveplugins as $inactiveplugin) {

				// --- set activate link ---
				$activatelink = admin_url('plugins.php').'?action=activate&plugin='.$pluginfiles[$inactiveplugin];
				$activatelink = wp_nonce_url($activatelink, 'activate-plugin_'.$pluginfiles[$inactiveplugin]);
				echo "<input type='hidden' id='".$inactiveplugin."-activate-link' value='".$activatelink."'>";

				// --- set update link ---
				if (in_array($inactiveplugin, $pluginupdates)) {
					$updatelink = admin_url('update.php').'?action=upgrade-plugin&plugin='.$pluginfiles[$inactiveplugin];
					$updatelink = wp_nonce_url($updatelink, 'upgrade-plugin_'.$pluginfiles[$inactiveplugins]);
					echo "<input type='hidden' id='".$inactiveplugin."-update-link' value='".$updatelink."'>";
				}

				// --- linked title and version ---
				echo "<tr><td><a href='".$wqplugins[$inactiveplugin]['home']."' class='pluginlink' target=_blank>";
				echo $wqplugins[$inactiveplugin]['title']."</a></td><td width='20'></td>";
				echo "<td>".$inactiveversions[$inactiveplugin]."</td><td width='20'></td>";

				// --- select plugin action ---
				echo "<td><select name='".$inactiveplugin."-action' id='".$inactiveplugin."-action' style='font-size:8pt;'>";
				if (in_array($inactiveplugin, $pluginupdates)) {
					echo "<option value='update' selected='selected'>".wqhelper_translate('Update')."</option>";
					echo "<option value='activate'>".wqhelper_translate('Activate')."</option>";
				} else {echo "<option value='activate' selected='selected'>".wqhelper_translate('Activate')."</option>";}
				echo "</select></td><td width='20'></td>";

				// --- plugin action button ---
				echo "<td><a href='javascript:void(0);' target=_blank id='".$inactiveplugin."-link' onclick='dopluginaction(\"".$inactiveplugin."\");'>";
				echo "<input class='button-secondary' type='button' value='".wqhelper_translate('Go')."'></a></td>";
				echo "</tr>";
			}
			echo '</table></div></div>';
		}

		$releasedplugins = $unreleasedplugins = array();
		if ( count($wqplugins) > count($wordquestplugins) ) {
			foreach ($wqplugins as $pluginslug => $wqplugin) {
				if (!in_array($pluginslug, $installedslugs) && !in_array($pluginslug, $inactiveplugins)) {
					if ($wqplugin['releasestatus'] == 'Released') {$releasedplugins[$pluginslug] = $wqplugin;}
					else {
						$releasetime = strtotime($wqplugin['releasedate']);
						$wqplugin['slug'] = $pluginslug;
						$unreleasedplugins[$releasetime] = $wqplugin;
					}
				}
			}
		}

		// Available Plugin Panel
		// ----------------------
		if (count($releasedplugins) > 0) {
			if ($wqdebug) {echo "<!-- Released Plugins: "; print_r($releasedplugins); echo " -->";}
			$boxid = 'wordquestavailable'; $boxtitle = wqhelper_translate('Available WordQuest Plugins');
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';

			foreach ($releasedplugins as $pluginslug => $wqplugin) {

				// --- set install plugin link ---
				// 1.6.5: add separate install link URL for each plugin for nonce checking
				// 1.6.6: use wordpress.org link if all plugins are from wordpress.org
				if ($wordpressorgonly && $wqplugin['wporgslug']) {
					$installlink = self_admin_url('update.php')."?action=install-plugin&plugin=".$wqplugin['wporgslug'];
					$installlink = wp_nonce_url($installlink, 'install-plugin_'.$wqplugin['wporgslug']);
					echo "<input type='hidden' name='".$pluginslug."-install-link' value='".$installlink."'>";
				} elseif (!$wordpressorgonly && is_array($wqplugin['package'])) {
					$installlink = admin_url('update.php')."?action=wordquest_plugin_install&plugin=".$pluginslug;
					$installlink = wp_nonce_url($installlink, 'plugin-upload');
					echo "<input type='hidden' name='".$pluginslug."-install-link' value='".$installlink."'>";
				}

				// --- linked plugin title ---
				echo "<tr><td><a href='".$wqplugin['home']."' class='pluginlink' target=_blank>";
				echo $wqplugin['title']."</a></td><td width='20'></td>";
				// echo "<td>".$wqplugin['version']."</td><td width='20'></td>";

				// --- plugin action select ---
				echo "<td><select name='".$pluginslug."-action' id='".$pluginslug."-action' style='font-size:8pt;'>";

				// 1.6.6: check if only wp.org plugins installable
				if ($wordpressorgonly && $wqplugin['wporgslug']) {
					// --- has a wordpress.org slug so installable from repository ---
					echo "<option value='install' selected='selected'>".wqhelper_translate('Install Now')."</option>";
					echo "<option value='home'>".wqhelper_translate('Plugin Home')."</option>";
				} elseif (!$wordpressorgonly && is_array($wqplugin['package'])) {
					// --- not all plugins are from wordpress.org, use the install package ---
					echo "<option value='install' selected='selected'>".wqhelper_translate('Install Now')."</option>";
					echo "<option value='home'>".wqhelper_translate('Plugin Home')."</option>";
				} else {
					// oops, installation package currently unavailable (404)
					echo "<option value='home' selected='selected'>".wqhelper_translate('Plugin Home')."</option>";
				}
				echo "</select></td><td width='20'></td>";

				// --- plugin action button ---
				echo "<td><a href='javascript:void(0);' target=_blank id='".$pluginslug."-link' onclick='dopluginaction(\"".$pluginslug."\");'>";
				echo "<input class='button-secondary' type='button' value='".wqhelper_translate('Go')."'></a></td></tr>";
			}
			echo "</table></div></div>";
		}

		// Upcoming Plugin Panel
		// ---------------------
		if (count($unreleasedplugins) > 0) {
			ksort($unreleasedplugins);
			if ($wqdebug) {echo "<!-- Unreleased Plugins: "; print_r($unreleasedplugins); echo " -->";}
			$boxid = 'wordquestupcoming'; $boxtitle = wqhelper_translate('Upcoming WordQuest Plugins');
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';
			foreach ($unreleasedplugins as $releasetime => $wqplugin) {
				// $pluginslug = $wqplugin['slug'];
				echo "<tr><td><a href='".$wqplugin['home']."' class='pluginlink' target=_blank>";
				echo $wqplugin['title']."</a></td>";
				echo "<td><span style='font-size:9pt;'>";
				echo wqhelper_translate('Expected').': '.date('jS F Y', $releasetime);
				echo "</span></td></tr>";
			}
			echo "</table></div></div>";
		}

		// BioShip Theme Panel
		// -------------------
		$boxid = 'bioship'; $boxtitle = wqhelper_translate('BioShip Theme Framework');
		echo '<div id="'.$boxid.'" class="postbox">';
		echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
		echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table><tr><td><center>';

		if ($bioshipinstalled) {

			// --- check if BioShip Theme is active ---
			$theme = wp_get_theme();
			if ($theme->stylesheet == 'bioship') {

				echo wqhelper_translate('Sweet! You are using').' <b>';
				echo wqhelper_translate('BioShip Theme Framework').'</b>.<br>';
				echo wqhelper_translate('Great choice!').' ';

				// 1.6.7: added BioShip Theme Options link here
				if (THEMETITAN) {$optionsurl = admin_url('admin.php').'?page=bioship-options';}
				elseif (THEMEOPT) {$optionsurl = admin_url('admin.php').'?page=options-framework';}
				else {$optionsurl = admin_url('customize.php');}
				echo '<a href="'.$optionsurl.'">'.wqhelper_translate('Theme Options').'</a>';

			} elseif (is_child_theme() && ($theme->template == 'bioship')) {

				echo wqhelper_translate('Groovy. You are using').' <b>';
				echo wqhelper_translate('BioShip Framework').'</b>!<br>';
				echo wqhelper_translate('Your Child Theme is').' <b>'.$theme->Name.'</b><br><br>';

				// 1.6.7: add Child Theme Options link here
				if (THEMETITAN) {$optionsurl = admin_url('admin.php').'?page=bioship-options';}
				elseif (THEMEOPT) {$optionsurl = admin_url('admin.php').'?page=options-framework';}
				else {$optionsurl = admin_url('customize.php');}
				echo '<a href="'.$optionsurl.'">'.wqhelper_translate('Theme Options').'</a>';

			} else {

				echo wqhelper_translate('Looks like you have BioShip installed!').'<br>';
				echo '...'.wqhelper_translate('but it is not yet your active theme.').'<br><br>';

				// --- BioShip Theme activation link ---
				$activatelink = admin_url('themes.php').'?action=activate&stylesheet=bioship';
				$activatelink = wp_nonce_url($activatelink, 'switch-theme_bioship');
				echo '<a href="'.$activatelink.'">'.wqhelper_translate('Click here to activate it now').'</a>.<br><br>';

				// Check for Theme Test Drive
				echo "<div id='testdriveoptions'>";
				if (function_exists('themedrive_determine_theme')) {

					// TODO: a better check here? this actually makes no sense
					if (class_exists('TitanFramework')) {
						$testdrivelink = admin_url('admin.php').'?page=bioship-options&theme=bioship';
					} elseif (function_exists('OptionsFramework_Init')) {
						$testdrivelink = admin_url('themes.php').'?page=options-framework&theme=bioship';
					} else {$testdrivelink = admin_url('customize.php').'?theme=bioship';}
					echo wqhelper_translate('or').', <a href="'.$testdrivelink.'">';
					echo wqhelper_translate('take it for a Theme Test Drive').'</a>.';

				} elseif (in_array('theme-test-drive', $installedplugins)) {

					// --- Theme Test Drive plugin activation link ---
					$activatelink = admin_url('plugins.php').'?action=activate&plugin='.urlencode('theme-test-drive/themedrive.php');
					$activatelink = wp_nonce_url($activatelink,'activate-plugin_theme-test-drive/themedrive.php');
					echo wqhelper_translate('or').', <a href="'.$activatelink.'">';
					echo wqhelper_translate('activate Theme Test Drive plugin').'</a><br>';
					echo wqhelper_translate('to test BioShip without affecting your current site.');

				} else {

					// --- Theme Test Drive plugin installation link ---
					$installlink = admin_url('update.php').'?action=install-plugin&plugin=theme-test-drive';
					$installlink = wp_nonce_url($installlink, 'install-plugin');
				 	echo wqhelper_translate('or').', <a href="'.$installlink.'">';
				 	echo wqhelper_translate('install Theme Test Drive plugin').'</a><br>';
					echo wqhelper_translate('to test BioShip without affecting your current site.');

				}
				echo "</div>";
			}

 		} else {

			echo wqhelper_translate('Also from').' <b>WordQuest Alliance</b>, '.wqhelper_translate('check out the').'<br>';
			echo "<a href='".$wqurls['bio']."' target=_blank><b>BioShip Theme Framework</b></a><br>";
			echo wqhelper_translate('A highly flexible and responsive starter theme').'<br>'.wqhelper_translate('for users, designers and developers.');

		}

		if ( ($theme->template == 'bioship') || ($theme->stylesheet == 'bioship') ) {
			// 1.7.3: addded missing function prefix
			if (function_exists('bioship_admin_theme_updates_available')) {
				$themeupdates = bioship_admin_theme_updates_available();
				if ($themeupdates != '') {
					echo '<div class="update-nag" style="padding:3px 10px;margin:0 0 10px 0;text-align:center;">'.$themeupdates.'</div></font><br>';
				}
			}

			// TODO: future link for rating BioShip on wordpress.org theme repository ?
			// $ratelink = 'https://wordpress.org/support/theme/bioship/reviews/#new-post';
			// echo '<br><a href="'.$ratelink.'" target=_blank>'.wqhelper_translate('Rate BioShip on WordPress.Org').'</a><br>';
		}

		// BioShip Feed
		// ------------
		// (only displays if Bioship theme is active)
		// 1.7.3: added missing bioship function prefix
		if (function_exists('bioship_muscle_bioship_dashboard_feed_widget')) {
			// $boxid = 'bioshipfeed'; $boxtitle = wphelper_translate('BioShip News');
			// echo '<div id="'.$boxid.'" class="postbox">';
			// echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			// echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;">';
				bioship_muscle_bioship_dashboard_feed_widget(false);
			// echo '</div></div>';
		}

		echo '</center></td></tr></table>';
		echo '</div></div>';

	// end column
	echo '</div>';
 };
}

// ----------------------------
// Version Specific Feed Column
// ----------------------------
$funcname = 'wqhelper_admin_feeds_column_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function($args) {

	// --- open feeds column ---
	echo '<div id="feedcolumn">';

		// Latest / Next Release
		// ---------------------
		global $wqreleases; $latestrelease = $nextrelease = '';
		if (isset($wqreleases['latest'])) {$latestrelease = $wqreleases['latest'];}
		if (isset($wqreleases['next'])) {$nextrelease = $wqreleases['next'];}

		if (isset($latestrelease) && is_array($latestrelease)) {
			if ($latestrelease['installed'] == 'no') {
				$release = $latestrelease; $boxid = 'wordquestlatest'; $boxtitle = wqhelper_translate('Latest Release');
			} else {$release = $nextrelease; $boxid = 'wordquestupcoming'; $boxtitle = wqhelper_translate('Upcoming Release');}
		} elseif (isset($nextrelease) && is_array($nextrelease)) {
			$release = $nextrelease; $boxid = 'wordquestupcoming'; $boxtitle = wqhelper_translate('Upcoming Release');
		}

		if (isset($release) && is_array($release)) {

			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside"><table>';
			echo "<table><tr><td align='center'><img src='".$release['icon']."' width='100' height='100'><br>";
			echo "<a href='".$latestrelease['home']."' target=_blank><b>".$release['title']."</b></a></td><td width='10'></td>";
			echo "<td><span style='font-size:9pt;'>".$release['description']."</span><br><br>";

			if (isset($release['package']) && is_array($release['package'])) {
				// 1.6.6: check for wordpress.org only installs
				global $wordpressorgonly; $installlink = false;
				if ($wordpressorgonly && $release['wporgslug']) {
					$installlink = self_admin_url('update.php')."?action=install-plugin&plugin=".$release['wporgslug'];
					$installlink = wp_nonce_url($installlink, 'install-plugin_'.$release['wporgslug']);
				} else {
					$installlink = admin_url('update.php')."?action=wordquest_plugin_install&plugin=".$release['slug'];
					$installlink = wp_nonce_url($installlink, 'plugin-upload');
				}

				if ($installlink) {
					echo "<input type='hidden' name='".$release['slug']."-install-link' value='".$installlink."'>";
					echo "<center><a href='".$installlink."' class='button-primary'>".wqhelper_translate('Install Now')."</a></center>";
				} else {
					$pluginlink = $wqurls['wq'].'/plugins/'.$release['slug'];
					echo "<center><a href='".$pluginlink."' class='button-primary' target=_blank>&rarr; ".wqhelper_translate('Plugin Home')."</a></center>";
				}
			} else {echo "<center>".wqhelper_translate('Expected').": ".date('jS F Y',strtotime($release['releasedate']));}
			echo "</td></tr></table>";
			echo '</table></div></div>';
		}

		// WordQuest Feed
		// --------------
		$boxid = 'wordquestfeed'; $boxtitle = wqhelper_translate('WordQuest News');
		if (function_exists('wqhelper_dashboard_feed_widget')) {
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;">';
				wqhelper_dashboard_feed_widget();
			echo '</div></div>';
		}

		// Editors Picks
		// -------------
		$boxid = 'recommendations'; $boxtitle = wqhelper_translate('Editor Picks');
		// TODO: Recommended Plugins via Plugin Review?
		// echo '<div id="'.$boxid.'" class="postbox">';
		// echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
		// echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';
		// 	echo "Recommended Plugins...";
		//	print_r($recommended);
		// echo '</table></div></div>';

		// PluginReview Feed
		// -----------------
		$boxid = 'pluginreviewfeed'; $boxtitle = wqhelper_translate('Plugin Reviews');
		if (function_exists('wqhelper_pluginreview_feed_widget')) {
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;">';
				wqhelper_pluginreview_feed_widget();
			echo '</div></div>';
		}

	// --- close column ---
	echo "</div>";

	// --- enqueue feed javascript ---
	if (!has_action('admin_footer', 'wqhelper_dashboard_feed_javascript')) {
		add_action('admin_footer', 'wqhelper_dashboard_feed_javascript');
	}

 };
}

// -----------------------------
// Version Specific Admin Styles
// -----------------------------
$funcname = 'wqhelper_admin_styles_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {

	// --- hide Wordquest plugin freemius submenu items if top level admin menu not open ---
	echo "<style>#toplevel_page_wordquest a.wp-first-item:after {content: ' Alliance';}
		#toplevel_page_wordquest.wp-not-current-submenu .fs-submenu-item
			{display: none; line-height: 0px; height: 0px;}
    #toplevel_page_wordquest li.wp-first-item {margin-bottom: 5px; margin-left: -10px;}
    span.fs-submenu-item.fs-sub {display: none;}
		.current span.fs-submenu-item.fs-sub {display: block;}
		#wpfooter {display:none !important;}
    </style>";
 };
}

// -----------------------------
// Version Specific Admin Script
// -----------------------------
$funcname = 'wqhelper_admin_scripts_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {

 	// --- wordquest admin submenu icon and styling fixes ---
	echo "<script>function wordquestsubmenufix(slug,iconurl,current) {
		jQuery('li a').each(function() {
			position = this.href.indexOf('admin.php?page='+slug);
			if (position > -1) {
				linkref = this.href.substr(position);
				jQuery(this).css('margin-left','10px');
				if (linkref == 'admin.php?page='+slug) {
					jQuery('<img src=\"'+iconurl+'\" style=\"float:left;\">').insertBefore(this);
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
add_action('update-custom_wordquest_plugin_install', 'wqhelper_install_plugin');

$funcname = 'wqhelper_install_plugin_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {

	global $wqurls;

	// --- check permissions and nonce ---
	if (!current_user_can('upload_plugins')) {
		wp_die( wqhelper_translate('Sorry, you are not allowed to install plugins on this site.') );
	}
	check_admin_referer('plugin-upload');

	// --- get the package info from download server ---
	if (!isset($_REQUEST['plugin'])) {wp_die( wqhelper_translate('Error: No Plugin specified.') );}
	$pluginslug = $_REQUEST['plugin'];
	// 1.5.9: sanitize plugin slug
	$pluginslug = sanitize_title($pluginslug);
	if ($pluginslug == '') {wp_dir( wqhelper_translate('Error: Invalid Plugin slug specified.') );}

	// --- get the plugin package data ---
	$url = $wqurls['wq'].'/downloads/?action=get_metadata&slug='.$pluginslug;
	$response = wp_remote_get($url, array('timeout' => 30));
	if (!is_wp_error($response)) {
		if ($response['response']['code'] == '404') {
			// --- on failure try to get package info from stored transient data ---
			$plugininfo = get_transient('wordquest_plugin_info');
			if (is_array($pluginfo)) {
				foreach ($plugininfo as $plugin) {
					if ($plugin['slug'] == $pluginslug) {$pluginpackage = $plugin['package'];}
				}
			}
		} else {$pluginpackage = json_decode($response['body'], true);}
	}

	if (!isset($pluginpackage)) {
		if (is_ssl()) {$tryagainurl = 'https://';} else {$tryagainurl = 'http://';}
		$tryagainurl .= $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
		$message = wqhelper_translate('Failed to retrieve download package information.');
		$message .= ' <a href="'.$tryagainurl.'">'.wqhelper_translate('Click here to try again.').'</a>';
		wp_die($message);
	}

	// 1.6.5: pass the package download URL to Wordpress to do the rest

	// --- set the Plugin_Installer_Skin arguments ---
	$url = $pluginpackage['download_url'];
	$title = sprintf( wqhelper_translate('Installing Plugin from URL: %s'), esc_html($url) );
	$nonce = 'plugin-upload';
	$type = 'web';
	$args = compact('type', 'title', 'nonce', 'url');

	// --- custom Plugin_Upgrader (via /wp-admin/upgrade.php) ---
	$title = wqhelper_translage('Upload Plugin');
	$parent_file = 'plugins.php'; $submenu_file = 'plugin-install.php';
	require_once(ABSPATH . 'wp-admin/admin-header.php');
	$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( $args ) );
	$result = $upgrader->install($url);
	include(ABSPATH . 'wp-admin/admin-footer.php');

 };
}


// ------------------------
// === Sidebar FloatBox ===
// ------------------------

// ----------------------
// Main Floatbox Function
// ----------------------
$funcname = 'wqhelper_sidebar_floatbox_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function($args) {

	global $wqdebug, $wqurls;
	if ($wqdebug) {echo "<!-- Sidebar Args: "; print_r($args); echo " -->";}

	if (count($args) == 7) {
		// --- the old way, sending all args individually ---
		$prefix = $args[0]; $pluginslug = $args[1]; $freepremium = $args[2];
		$wporgslug = $args[3]; $savebutton = $args[4];
		$plugintitle = $args[5]; $pluginversion = $args[6];
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
		if (isset($wordquestplugins[$slug]['plan'])) {
			$freepremium = $wordquestplugins[$slug]['plan'];
		} else {
			$freepremium = 'free';
		}
		$wporg = $wordquestplugins[$slug]['wporg'];

		if (isset($wordquestplugins[$slug]['wporgslug'])) {
			$wporgslug = $wordquestplugins[$slug]['wporgslug'];
		} else {$wporgslug = '';}

        // --- get donate link ---
        // 1.7.4: get donate link and set author
        // 1.7.6: fix to check if donate key is defined
		$author = 'wordquest';
		if (isset($wordquestplugins[$slug]['donate'])) {
	        $donatelink = $wordquestplugins[$slug]['donate'];
	        echo "<!-- Donate Link: ".$donatelink." -->";
	        if (strstr($donatelink, 'wpmedic')) {$author = 'wpmedic';}
	    }

        if ($wqdebug) {echo "<!-- Sidebar Plugin Info: "; print_r($wordquestplugins[$slug]); echo "-->";}
	}

	// 1.5.0: get/convert to single array of plugin sidebar options
	// 1.6.0: fix to sidebar options variable
	$sidebaroptions = get_option($prefix.'_sidebar_options');
	if ( ($sidebaroptions == '') || !is_array($sidebaroptions) ) {
		$sidebaroptions['installdate'] = date('Y-m-d');
		$sidebaroptions['adsboxoff'] = get_option($prefix.'_ads_box_off');
		$sidebaroptions['donationboxoff'] = get_option($prefix.'_donation_box_off');
		$sidebaroptions['reportboxoff'] = get_option($prefix.'_report_box_off');
		delete_option($prefix.'_ads_box_off'); delete_option($prefix.'_donation_box_off'); delete_option($prefix.'_report_box_off');
		add_option($prefix.'_sidebar_options', $sidebaroptions);
	}
	// 1.6.9: fix to possible undefined keys
	if (!isset($sidebaroptions['installdate'])) {$sidebaroptions['installdate'] = date('Y-m-d');}
	if (!isset($sidebaroptions['adsboxoff'])) {$sidebaroptions['adsboxoff'] = '';}
	if (!isset($sidebaroptions['donationboxoff'])) {$sidebaroptions['donationboxoff'] = '';}
	if (!isset($sidebaroptions['reportboxoff'])) {$sidebaroptions['reportboxoff'] = '';}

	// --- sidebar scripts ---
	echo "<script language='javascript' type='text/javascript'>
	function hidesidebarsaved() {document.getElementById('sidebarsaved').style.display = 'none';}
	function doshowhidediv(divname) {
		if (document.getElementById(divname).style.display == 'none') {document.getElementById(divname).style.display = '';}
		else {document.getElementById(divname).style.display = 'none';}
		if (typeof sticky_in_parent === 'function') {jQuery(document.body).trigger('sticky_kit:recalc');}
	}</script>";

	// --- Sidebar Floatbox Styles ---
	echo '<style>#floatdiv {margin-top:20px;} .inside {font-size:9pt; line-height:1.6em; padding:0px;}
	#floatdiv a {text-decoration:none;} #floatdiv a:hover {text-decoration:underline;}
	#floatdiv .stuffbox {background-color:#FFFFFF; margin-bottom:10px; padding-bottom:10px; text-align:center; width:25%;}
	#floatdiv .stuffbox .inside {padding:0 3px;} .stuffbox h3 {margin:10px 0; background-color:#FAFAFA; font-size:12pt;}
	</style>';

	// --- open sidebar div --
	echo '<div id="floatdiv" class="floatbox">';
	if ($wqdebug) {echo '<!-- WQ Helper Loaded From: '.dirname(__FILE__).' -->';}

	// --- call (optional) Plugin Sidebar Header ---
	$funcname = $prefix.'_sidebar_plugin_header';
	if (function_exists($funcname)) {call_user_func($funcname);}

	// Save Settings Button
	// --------------------
	if ($savebutton != 'replace') {

		echo '<div id="savechanges"><div class="stuffbox" style="width:250px;background-color:#ffffff;">';
		echo '<h3>'.wqhelper_translate('Update Settings').'</h3><div class="inside"><center>';

		if ($savebutton == 'yes') {
			$buttonoutput = "<script>function sidebarsavepluginsettings() {jQuery('#plugin-settings-save').trigger('click');}</script>";
			$buttonoutput .= "<table><tr>";
			$buttonoutput .= "<td align='center'><input id='sidebarsavebutton' onclick='sidebarsavepluginsettings();' type='button' class='button-primary' value='Save Settings'></td>";
			$buttonoutput .= "<td width='30'></td>";
			$buttonoutput .= "<td><div style='line-height:1em;'><font style='font-size:8pt;'><a href='javascript:void(0);' style='text-decoration:none;' onclick='doshowhidediv(\"sidebarsettings\");hidesidebarsaved();'>".wqhelper_translate('Sidebar')."<br>";
			$buttonoutput .= wqhelper_translate('Options')."</a></font></div></td>";
			$buttonoutput .= "</tr></table>";
			$buttonoutput = apply_filters('wordquest_sidebar_save_button', $buttonoutput);
			echo $buttonoutput;
		}	elseif ($savebutton == 'no') {echo "";}
		else {echo "<div style='line-height:1em;text-align:center;'><font style='font-size:8pt;'><a href='javascript:void(0);' style='text-decoration:none;' onclick='doshowhidediv(\"sidebarsettings\");hidesidebarsaved();'>".wqhelper_translate('Sidebar Options')."</a></font></div>";}

		// --- sidebar settings box ---
		echo "<div id='sidebarsettings' style='display:none;'><br>";

			global $wordquesthelper;
			echo "<form action='".admin_url('admin-ajax.php')."' target='savesidebar' method='post'>";
			// 1.6.5: added nonce field
			wp_nonce_field($prefix.'_sidebar');
			echo "<input type='hidden' name='action' value='wqhelper_update_sidebar_boxes'>";
			// 1.6.0: added version matching form field
			echo "<input type='hidden' name='wqhv' value='".$wordquesthelper."'>";
			echo "<input type='hidden' name='sidebarprefix' value='".$prefix."'>";
			echo "<table><tr><td align='center'>";
			echo "<b>".wqhelper_translate('I rock! I have made a donation.')."</b><br>(".wqhelper_translate('hides donation box').")</td><td width='10'></td>";
			echo "<td align='center'><input type='checkbox' name='".$prefix."_donation_box_off' value='checked'";
			if ($sidebaroptions['donationboxoff'] == 'checked') {echo " checked>";} else {echo ">";}
			echo "</td></tr>";

			echo "<tr><td align='center'>";
			echo "<b>".wqhelper_translate("I've got your report, you")."<br>".wqhelper_translate('can stop bugging me now.')." :-)</b><br>(".wqhelper_translate('hides report box').")</td><td width='10'></td>";
			echo "<td align='center'><input type='checkbox' name='".$prefix."_report_box_off' value='checked'";
			if ($sidebaroptions['reportboxoff'] == 'checked') {echo " checked>";} else {echo ">";}
			echo "</td></tr>";

			echo "<tr><td align='center'>";
			echo "<b>".wqhelper_translate('My site is so awesome it')."<br>"._("doesn't need any more quality")."<br>".wqhelper_translate('plugin recommendations').".</b><br>(".wqhelper_translate('hides sidebar ads.').")</td><td width='10'></td>";
			echo "<td align='center'><input type='checkbox' name='".$prefix."_ads_box_off' value='checked'";
			// 1.6.5: fix to undefined index warning
			if ($sidebaroptions['adsboxoff'] == 'checked') {echo " checked>";} else {echo ">";}
			echo "</td></tr></table><br>";

			echo "<center><input type='submit' class='button-secondary' value='".wqhelper_translate('Save Sidebar Options')."'></center></form><br>";
			echo "<iframe src='javascript:void(0);' name='savesidebar' id='savesidebar' width='200' height='200' style='display:none;'></iframe>";

			echo "<div id='sidebarsaved' style='display:none;'>";
			echo "<table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>";
			echo "<tr><td><div class='message' style='margin:0.25em;'><font style='font-weight:bold;'>";
			echo wqhelper_translate('Sidebar Options Saved.')."</font></div></td></tr></table></div>";

		echo "</div></center>";

		echo '</div></div></div>';
	}

	// Donation Box
	// ------------
	// TODO: Go Pro Link if has Pro plans ?
	$args = array($prefix, $pluginslug);
	if ($sidebaroptions['donationboxoff'] == 'checked') {$hide = " style='display:none;'>";} else {echo $hide = '';}
	echo '<div id="donate"'.$hide.'>';

	if ($freepremium == 'free') {

        echo '<div class="stuffbox" style="width:250px;background-color:#ffffff;">';

        // --- box title ---
        // 1.7.4: different title for patreon/paypal
        // 1.7.6: fix to check if donate link defined
        $boxtitle = wqhelper_translate('Support Subscription');
        if (isset($donatelink) && strstr($donatelink, 'patreon')) {
        	$boxtitle = wqhelper_translate('Become a Supporter');
        }
        echo '<h3>'.$boxtitle.'</h3><div class="inside">';

        // --- maybe call special top ---
        if (function_exists($prefix.'_donations_special_top')) {
            $funcname = $prefix.'_donations_special_top';
            call_user_func($funcname);
        }

        // --- patreon support or paypal donations ---
        // 1.7.4: different title for patreon/paypal
        // 1.7.6: fix to check if donate link is defined
        if (isset($donatelink) && strstr($donatelink, 'patreon')) {
        	wqhelper_sidebar_patreon_button($args);
        } else {wqhelper_sidebar_paypal_donations($args);}

        // --- call donations special bottom ---
        if (function_exists($prefix.'_donations_special_bottom')) {
            $funcname = $prefix.'_donations_special_bottom';
            call_user_func($funcname);
        }

		// 1.7.2: remove testimonial box from sidebar
		// wqhelper_sidebar_testimonial_box($args);

		// 1.7.2: remove rate link from sidebar (now in plugin header)
		// TODO: re-add theme rating when in repository ?
		// if ($wporgslug != '') {
			// echo "<a href='".$wqurls['wp']."/plugins/'".$wporgslug."'/reviews/#new-post' target='_blank'>";
			// echo "&#9733; ".wqhelper_translate('Rate this Plugin on Wordpress.Org')."</a></center>";
		// } elseif ($pluginslug == 'bioship') {
			// 1.5.0: add star rating for theme
			// echo "<a href='".$wqurls['wp']."/support/theme/bioship/reviews/#new-post' target='_blank'>";
			// echo "&#9733; ".wqhelper_translate('Rate this Theme on Wordpress.Org')."</a></center>";
		// }

		echo '</div></div>';

	} elseif ($freepremium == 'premium') {

		// 1.7.3: temp: remove other testimonial box also
		// echo '<div class="stuffbox" style="width:250px;background-color:#ffffff;">';
		// echo '<h3>'.wqhelper_translate('Testimonials').'</h3><div class="inside">';
		//	wqhelper_sidebar_testimonial_box($args);
		// echo '</div></div>';

	}
	echo '</div>';

	// Bonus Subscription Form
	// -----------------------
	// 1.7.2: allow for bonus offer box override
	$funcname = $prefix.'_sidebar_bonus_offer';
	if (function_exists($funcname)) {call_user_func($funcname);}
	else {

		// --- populated form for current user ---
		global $current_user; $current_user = wp_get_current_user();
		$useremail = $current_user->user_email;
		if (strstr($useremail, '@localhost')) {$useremail = '';}
		$userid = $current_user->ID; $userdata = get_userdata($userid);
		$username = $userdata->first_name; $lastname = $userdata->last_name;
		if ($lastname != '') {$username .= ' '.$lastname;}

		// --- set report image URL ---
		if ($pluginslug == 'bioship') {$reportimage = get_template_directory_uri().'/images/rv-report.jpg';}
		else {$reportimage = plugins_url('images/rv-report.jpg', __FILE__);}

		echo '<div id="bonusoffer"';
		if (get_option($prefix.'_report_box_off') == 'checked') {echo " style='display:none;'>";} else {echo ">";}
		echo '<div class="stuffbox" style="width:250px;background-color:#ffffff;">';
		echo '<h3>'.wqhelper_translate('Bonus Offer').'</h3><div class="inside">';
		echo "<center><table cellpadding='0' cellspacing='0'><tr><td align='center'><img src='".$reportimage."' width='60' height='80'><br>";
		echo "<font style='font-size:6pt;'><a href='".$wqurls['prn']."/return-visitors-report/' target=_blank>".wqhelper_translate('learn more')."...</a></font></td><td width='7'></td>";
		echo "<td align='center'><b><font style='color:#ee0000;font-size:9pt;'>Maximize Sales Conversions:</font><br><font style='color:#0000ee;font-size:10pt;'>The Return Visitors Report</font></b><br>";
		echo "<form style='margin-top:7px;' action='".$wqurls['prn']."/?visitorfunnel=join' target='_blank' method='post'>";
		echo "<input type='hidden' name='source' value='".$pluginslug."-sidebar'>";
		echo "<input placeholder='".wqhelper_translate('Your Email')."...' type='text' style='width:150px;font-size:9pt;' name='subemail' value='".$useremail."'><br>";
		echo "<table><tr><td><input placeholder='".wqhelper_translate('Your Name')."...' type='text' style='width:90px;font-size:9pt;' name='subname' value='".$username."'></td>";
		echo "<td><input type='submit' class='button-secondary' value='".wqhelper_translate('Get it!')."'></td></tr></table>";
		echo "</td></tr></table></form></center>";
		echo '</div></div></div>';
	}

	// PluginReview.Net Plugin Recommendations
	// ---------------------------------------
	if ($sidebaroptions['adsboxoff'] != 'checked') {
		// 1.7.2: allow for recommendation box override
		$funcname = $prefix.'_sidebar_plugin_recommendation';
		if (function_exists($funcname)) {call_user_func($funcname);}
		else {
			echo '<div id="pluginads">';
			echo '<div class="stuffbox" style="width:250px;">';
			echo '<h3>'.wqhelper_translate('Recommended').'</h3><div class="inside">';
			echo "<script language='javascript' src='".$wqurls['prn']."/recommends/?s=yes&a=majick&c=".$pluginslug."&t=sidebar'></script>";
			echo '</div></div></div>';
		}
	}

	// Call Plugin Footer Function
	// ---------------------------
	$funcname = $prefix.'_sidebar_plugin_footer';
	if (function_exists($funcname)) {call_user_func($funcname);}
	else {

		// Default Sidebar Plugin Footer
        // -----------------------------

        // 1.7.4: link display depending on author
        if ($author == 'wpmedic') {
            $authordisplay = 'WP Medic';
            $authorurl = $wqurls['wpm'];
            $wqanchor = wqhelper_translate('WordQuest Plugins');
            $pluginurl = $wqurls['wpm'].'/'.$pluginslug."/";
        } else {
            $authordisplay = 'WordQuest Alliance';
            $authorurl = $wqurls['wq'];
            $wqanchor = wqhelper_translate('More Cool Plugins');
            $pluginurl = $wqurls['wq'].'/plugins/'.$pluginslug."/";
        }

		// --- set values for theme or plugin ---
		if ($pluginslug == 'bioship') {
			$iconurl = get_template_directory_uri().'/images/'.$author.'.png';
			$pluginurl = $wqurls['bio'];
			$pluginfootertitle = wqhelper_translate('Theme Info');
		} else {
            $iconurl = plugins_url('images/'.$author.'.png', __FILE__);
			$pluginfootertitle = wqhelper_translate('Plugin Info');
            $bioanchor = wqhelper_translate('BioShip Framework');
		}

        // --- output plugin footer ---
		echo '<div id="pluginfooter"><div class="stuffbox" style="width:250px;background-color:#ffffff;"><h3>'.$pluginfootertitle.'</h3><div class="inside">';
		echo "<center><table><tr>";
		echo "<td><a href='".$wqurls['wq']."/' target='_blank'><img src='".$iconurl."' border=0></a></td></td>";
		echo "<td width='14'></td>";
		echo "<td><a href='".$pluginurl."' target='_blank'>".$plugintitle."</a> <i>v".$pluginversion."</i><br>";
		echo "by <a href='".$authorurl."/' target='_blank'>".$authordisplay."</a><br>";
        echo "<a href='".$wqurls['wq']."/plugins/' target='_blank'><b>&rarr; ".$wqanchor."</b></a><br>";
        if ($pluginslug != 'bioship') {
            echo "<a href='".$wqurls['bio']."' target='_blank'>&rarr; ".$bioanchor."</a></td>";
        }
        // echo "<a href='".$wqurls['prn']."/directory/' target='_blank'>&rarr; ".wqhelper_translate('Plugin Directory')."</a></td>";
		echo "</tr></table></center>";
		echo '</div></div></div>';
	}

	// --- close sidebar float div ---
	echo '</div>';

 };
}

// ------------------------
// Patreon Supporter Button
// ------------------------
// 1.7.4: added Patreon Supporter Button
$funcname = 'wqhelper_sidebar_patreon_button_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function($args) {

	global $wordquestplugins;
	$prefix = $args[0]; $pluginslug = $args[1];
    $settings = $wordquestplugins[$pluginslug];
    $donatelink = apply_filters('wqhelper_donate_link', $settings['donate'], $pluginslug);
    $message = apply_filters('wqhelper_donate_message', $settings['donatetext'], $pluginslug);

    // --- set button image URL ---
    if ($pluginslug == 'bioship') {$imageurl = get_template_directory_uri().'/images/patreon-button.jpg';}
    else {
        // 1.7.5: check/fix for Patreon button URL (cross-versions)
        if (file_exists(dirname(__FILE__).'/images/patreon-button.jpg')) {
            $imageurl = plugins_url('images/patreon-button.jpg', __FILE__);
        } else {
            // --- try to reliably get actual plugin path/URL ---
            $realslug = sanitize_title($wordquestplugins[$pluginslug]['title']);
            if (file_exists(WP_PLUGIN_DIR.'/'.$realslug.'/images/patreon-button.jpg')) {
                $imageurl = WP_PLUGIN_URL.'/'.$realslug.'/images/patreon-button.jpg';
            }
        }
    }
    $imageurl = apply_filters('wqhelper_donate_image', $imageurl, $pluginslug);

    // --- output Patreon button ---
    echo "<center><div class='supporter-message'>".$message."</div>".PHP_EOL;
    echo "<a href='".$donatelink."' target=_blank>";
        if ($imageurl) {echo "<img id='patreon-button' src='".$imageurl."'>";}
        else {echo __('Become a Patron','forcefield');}
    echo "</a><center>".PHP_EOL;

    // --- image hover styling ---
    echo "<style>.supporter-message {font-size:15px; margin-bottom:5px;}
    #patreon-button {opacity: 0.9;} #patreon-button:hover {opacity: 1;}</style>".PHP_EOL;

 };
}

// ----------------
// Paypal Donations
// ----------------
$funcname = 'wqhelper_sidebar_paypal_donations_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function($args) {

	global $wqurls;
	$prefix = $args[0]; $pluginslug = $args[1];

    // --- make display name from the plugin slug ---
	if (strstr($pluginslug, '-')) {
		$parts = explode('-', $pluginslug);
		$i = 0;
		foreach ($parts as $part) {
			if ($part == 'wp') {$parts[$i] = 'WP';}
			else {$parts[$i] = strtoupper(substr($part, 0, 1)).substr($part, 1, (strlen($part)-1));}
			$i++;
		}
		$pluginname = implode(' ', $parts);
	} else {
		$pluginname = strtoupper(substr($pluginslug, 0, 1)).substr($pluginslug, 1, (strlen($pluginslug)-1));
	}

	// --- donations scripts ---
	echo "<script language='javascript' type='text/javascript'>
	function showrecurringform() {
		document.getElementById('recurradio').checked = true;
		document.getElementById('onetimedonation').style.display = 'none';
		document.getElementById('recurringdonation').style.display = '';
	}
	function showonetimeform() {
		document.getElementById('onetimeradio').checked = true;
		document.getElementById('recurringdonation').style.display = 'none';
		document.getElementById('onetimedonation').style.display = '';
	}
	function switchperiodoptions() {
		var selectelement = document.getElementById('recurperiod');
		var recurperiod = selectelement.options[selectelement.selectedIndex].value;
		if ( (recurperiod == 'Weekly') || (recurperiod == 'W') ) {
			document.getElementById('periodoptions').innerHTML = document.getElementById('weeklyamounts').innerHTML;
			var monthlyselected = document.getElementById('monthlyselected').value;
			var weeklyselected = monthlyselected++;
			var selectelement = document.getElementById('periodoptions');
			selectelement.selectedIndex = weeklyselected;
		}
		if ( (recurperiod == 'Monthly') || (recurperiod == 'M') ) {
			document.getElementById('periodoptions').innerHTML = document.getElementById('monthlyamounts').innerHTML;
			var weeklyselected = document.getElementById('weeklyselected').value;
			var monthlyselected = weeklyselected--;
			var selectelement = document.getElementById('periodoptions')
			selectelement.selectedIndex = monthlyselected;
		}
	}
	function storeamount() {
		var selectelement = document.getElementById('recurperiod');
		var recurperiod = selectelement.options[selectelement.selectedIndex].value;
		var selectelement = document.getElementById('periodoptions');
		var selected = selectelement.selectedIndex;
		if ( (recurperiod == 'Weekly') || (recurperiod == 'W') ) {
			document.getElementById('weeklyselected').value = selected;
		}
		if ( (recurperiod == 'Monthly') || (recurperiod == 'M') ) {
			document.getElementById('monthlyselected').value = selected;
		}
	}
	</script>";

	// --- set Paypal notification URL ---
	$notifyurl = $wqurls['wq'].'/?estore_pp_ipn=process';
	$sandbox = ''; // $sandbox = 'sandbox.';

	// --- recurring / one-time switcher ---
	echo "<center><table cellpadding='0' cellspacing='0'><tr><td>";
	echo "<input name='donatetype' id='recurradio' type='radio' onclick='showrecurringform();' checked> <a href='javascript:void(0);' onclick='showrecurringform();' style='text-decoration:none;'>".wqhelper_translate('Supporter')."</a> ";
	echo "</td><td width='10'></td><td>";
	echo "<input name='donatetype' id='onetimeradio' type='radio' onclick='showonetimeform();'> <a href-'javascript:void(0);' onclick='showonetimeform();' style='text-decoration:none;'>".wqhelper_translate('One Time')."</a>";
	echo "</td></tr></table></center>";

	// --- set weekly amount options ---
	// 1.5.0: added weekly amounts
	echo '<div style="display:none;"><input type="hidden" id="weeklyselected" value="3">
	<select name="wp_eStore_subscribe" id="weeklyamounts" style="font-size:8pt;" size="1">
	<optgroup label="'.wqhelper_translate('Supporter Amount').'">
	<option value="1">'.wqhelper_translate('Copper').': $1 </option>
	<option value="3">'.wqhelper_translate('Bronze').': $2</option>
	<option value="5">'.wqhelper_translate('Silver').': $4</option>
	<option value="7">'.wqhelper_translate('Gold').': $5</option>
	<option value="9">'.wqhelper_translate('Platinum').': $7.50</option>
	<option value="11">'.wqhelper_translate('Titanium').': $10</option>
	<option value="13">'.wqhelper_translate('Star Ruby').': $12.50</option>
	<option value="15">'.wqhelper_translate('Star Topaz').': $15</option>
	<option value="17">'.wqhelper_translate('Star Emerald').': $17.50</option>
	<option value="19">'.wqhelper_translate('Star Sapphire').': $20</option>
	<option value="21">'.wqhelper_translate('Star Diamond').': $25</option>
	</select></div>';

	// --- set monthly amount options ---
	// 1.5.0: added monthly amounts
	echo '<div style="display:none;"><input type="hidden" id="monthlyselected" value="3">
	<select name="wp_eStore_subscribe" id="monthlyamounts" style="font-size:8pt;" size="1">
	<optgroup label="'.wqhelper_translate('Supporter Amount').'">
	<option value="2">'.wqhelper_translate('Copper').': $5</option>
	<option value="4">'.wqhelper_translate('Bronze').': $10</option>
	<option value="6">'.wqhelper_translate('Silver').': $15</option>
	<option value="9" selected="selected">'.wqhelper_translate('Gold').': $20</option>
	<option value="10">'.wqhelper_translate('Platinum').': $30</option>
	<option value="12">'.wqhelper_translate('Titanium').': $40</option>
	<option value="14">'.wqhelper_translate('Star Ruby').': $50</option>
	<option value="16">'.wqhelper_translate('Star Topaz').': $60</option>
	<option value="18">'.wqhelper_translate('Star Emerald').': $70</option>
	<option value="20">'.wqhelper_translate('Star Sapphire').': $80</option>
	<option value="22">'.wqhelper_translate('Star Diamond').': $100</option>
	</select></div>';

	// note: eStore recurring subscription form
	// $wqurls['wq'].'/?wp_eStore_subscribe=LEVEL&c_input='.$pluginslug;

	// --- set donate image URL ---
	if ($pluginslug == 'bioship') {$donateimage = get_template_directory_uri().'/images/pp-donate.jpg';}
	else {$donateimage = plugins_url('/images/pp-donate.jpg', __FILE__);}

	// --- recurring donation form ---
	echo '
		<center><form id="recurringdonation" method="GET" action="'.$wqurls['wq'].'" target="_blank">
		<input type="hidden" name="c_input" value="'.$pluginslug.'">
		<select name="wp_eStore_subscribe" style="font-size:10pt;" size="1" id="periodoptions" onchange="storeamount();">
		<optgroup label="'.wqhelper_translate('Supporter Amount').'">
		<option value="1">'.wqhelper_translate('Copper').': $1 </option>
		<option value="3">'.wqhelper_translate('Bronze').': $2</option>
		<option value="5">'.wqhelper_translate('Silver').': $4</option>
		<option value="7" selected="selected">'.wqhelper_translate('Gold').': $5</option>
		<option value="9">'.wqhelper_translate('Platinum').': $7.50</option>
		<option value="11">'.wqhelper_translate('Titanium').': $10</option>
		<option value="13">'.wqhelper_translate('Ruby').': $12.50</option>
		<option value="15">'.wqhelper_translate('Topaz').': $15</option>
		<option value="17">'.wqhelper_translate('Emerald').': $17.50</option>
		<option value="19">'.wqhelper_translate('Sapphire').': $20</option>
		<option value="21">'.wqhelper_translate('Diamond').': $25</option>
		</select>
		</td><td width="5"></td><td>
		<select name="t3" style="font-size:10pt;" id="recurperiod" onchange="switchperiodoptions()">
		<option selected="selected" value="W">'.wqhelper_translate('Weekly').'</option>
		<option value-"M">'.wqhelper_translate('Monthly').'</option>
		</select></tr></table>
		<input type="image" src="'.$donateimage.'" border="0" name="I1">
		</center></form>';

	/// --- one time donation form ---
	// $wqurls['wq'].'/?wp_eStore_donation=23&var1_price=AMOUNT&c_input='.$pluginslug;
	echo '
	<center><form id="onetimedonation" style="display:none;" method="GET" action="'.$wqurls['wq'].'" target="_blank">
		<input type="hidden" name="wp_eStore_donation" value="23">
		<input type="hidden" name="c_input" value="'.$pluginslug.'">
		<select name="var1_price" style="font-size:10pt;" size="1">
		<option selected value="">'.wqhelper_translate('Select Gift Amount').'</option>
		<option value="5">$5 - '.wqhelper_translate('Buy me a Cuppa').'</option>
		<option value="10">$10 - '.wqhelper_translate('Log a Feature Request').'</option>
		<option value="20">$20 - '.wqhelper_translate('Support a Minor Bugfix').'</option>
		<option value="50">$50 - '.wqhelper_translate('Support a Minor Update').'</option>
		<option value="100">$100 - '.wqhelper_translate('Support a Major Bugfix/Update').'</option>
		<option value="250">$250 - '.wqhelper_translate('Support a Minor Feature').'</option>
		<option value="500">$500 - '.wqhelper_translate('Support a Major Feature').'</option>
        <option value="1000">$1000 - '.wqhelper_translate('Support a New Module').'</option>
        <option value="2000">$1000 - '.wqhelper_translate('Support a New Plugin').'</option>
		<option value="">'.wqhelper_translate('Be Unique: Enter Custom Amount').'</option>
		</select>
		<input type="image" src="'.$donateimage.'" border="0" name="I1">
		</center></form>
	';

 };
}

// ---------------
// Testimonial Box
// ---------------
$funcname = 'wqhelper_sidebar_testimonial_box_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function($args) {

	global $wqurls, $current_user; $current_user = wp_get_current_user();
	$useremail = $current_user->user_email;
	if (strstr($useremail, '@localhost')) {$useremail = '';}
	$userid = $current_user->ID; $userdata = get_userdata($userid);
	$username = $userdata->first_name; $lastname = $userdata->last_name;
	if ($lastname != '') {$username .= ' '.$lastname;}

	$prefix = $args[0]; $pluginslug = $args[1];
	$pluginslug = str_replace('-', '', $pluginslug);

	// --- testimonial script ---
	echo "<script>
	function showhidetestimonialbox() {
		if (document.getElementById('sendtestimonial').style.display == '') {
			document.getElementById('sendtestimonial').style.display = 'none';
		}
		else {
			document.getElementById('sendtestimonial').style.display = '';
			document.getElementById('testimonialbox').style.display = 'none';
		}
	}
	function submittestimonial() {
		document.getElementById('testimonialbox').style.display='';
		document.getElementById('sendtestimonial').style.display='none';
	}</script>";

	// --- testimonial form ---
	echo "<center><a href='javascript:void(0);' onclick='showhidetestimonialbox();'>".wqhelper_translate('Send me a thank you or testimonial.')."</a><br>";
	echo "<div id='sendtestimonial' style='display:none;' align='center'>";
	echo "<center><form action='".$wqurls['wq']."' method='post' target='testimonialbox' onsubmit='submittestimonial();'>";
	echo "<b>".wqhelper_translate('Your Testimonial').":</b><br>";
	echo "<textarea rows='5' cols='25' name='message'></textarea><br>";
	echo "<label for='testimonial_sender'>".wqhelper_translate('Your Name').":</label> ";
	echo "<input type='text' placeholder='".wqhelper_translate('Your Name')."... (".wqhelper_translate('optional').")' style='width:200px;' name='testimonial_sender' value='".$username."'><br>";
	echo "<input type='text' placeholder='".wqhelper_translate('Your Website')."... (".wqhelper_translate('optional').")' style='width:200px;' name='testimonial_website' value=''><br>";
	echo "<input type='hidden' name='sending_plugin_testimonial' value='yes'>";
	echo "<input type='hidden' name='for_plugin' value='".$pluginslug."'>";
	echo "<input type='submit' class='button-secondary' value='".wqhelper_translate('Send Testimonial')."'>";
	echo "</form>";
	echo "</div>";
	echo "<iframe name='testimonialbox' id='testimonialbox' frameborder='0' src='javascript:void(0);' style='display:none;' width='250' height='50' scrolling='no'></iframe>";
 };
}

// ---------------------
// Save Sidebar Settings
// ---------------------
// !! caller exception !! uses form matching version function
$funcname = 'wqhelper_update_sidebar_options_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {

	$pre = $_REQUEST['sidebarprefix'];
	if (current_user_can('manage_options')) {

		// 1.6.5: check nonce field
		check_admin_referer($prefix.'_sidebar');

		// 1.5.0: convert to single array of plugin sidebar options
		$sidebaroptions = get_option($prefix.'_sidebar_options');
		if (!$sidebaroptions) {$sidebaroptions = array('installdate' => date('Y-m-d'));}
		$sidebaroptions['adsboxoff'] = $sidebaroptions['donationboxoff'] = $sidebaroptions['reportboxoff'] = '';
		if (isset($_POST[$prefix.'_ads_box_off']) && ($_POST[$prefix.'_ads_box_off'] == 'checked')) {$sidebaroptions['adsboxoff'] = 'checked';}
		if (isset($_POST[$prefix.'_donation_box_off']) && ($_POST[$prefix.'_donation_box_off'] == 'checked')) {$sidebaroptions['donationboxoff'] = 'checked';}
		if (isset($_POST[$prefix.'_report_box_off']) && ($_POST[$prefix.'_report_box_off'] == 'checked')) {$sidebaroptions['reportboxoff'] = 'checked';}
		update_option($prefix.'_sidebar_options', $sidebaroptions);
		// print_r($sidebaroptions); // debug point

		// --- javascript response callbacks ---
		echo "<script>";
		echo PHP_EOL."if (parent.document.getElementById('donate')) {";
		if ($sidebaroptions['donationboxoff'] == 'checked') {echo "parent.document.getElementById('donate').style.display = 'none';}";}
		else {echo "parent.document.getElementById('donate').style.display = '';}";}
		echo PHP_EOL."if (parent.document.getElementById('bonusoffer')) {";
		if ($sidebaroptions['reportboxoff'] == 'checked') {echo "parent.document.getElementById('bonusoffer').style.display = 'none';}";}
		else {echo "parent.document.getElementById('bonusoffer').style.display = '';}";}
		echo PHP_EOL."if (parent.document.getElementById('pluginads')) {";
		if ($sidebaroptions['adsboxoff'] == 'checked') {echo "parent.document.getElementById('pluginads').style.display = 'none';}";}
		else {echo "parent.document.getElementById('pluginads').style.display = '';}";}
		echo PHP_EOL."parent.document.getElementById('sidebarsaved').style.display = ''; ";
		echo PHP_EOL."parent.document.getElementById('sidebarsettings').style.display = 'none'; ";
		echo "</script>";

		// --- maybe call Special Update Options ---
		$funcname = $prefix.'_update_sidebar_options_special';
		if (function_exists($funcname)) {call_user_func($funcname);}
	}

	exit;
 };
}

// ---------------------
// Sticky Kit Javascript
// ---------------------
$funcname = 'wqhelper_sidebar_stickykitscript_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {
return '<script>/* Sticky-kit v1.1.2 | WTFPL | Leaf Corcoran 2015 | http://leafo.net */
(function(){var b,f;b=this.jQuery||window.jQuery;f=b(window);b.fn.stick_in_parent=function(d){var A,w,J,n,B,K,p,q,k,E,t;null==d&&(d={});t=d.sticky_class;B=d.inner_scrolling;E=d.recalc_every;k=d.parent;q=d.offset_top;p=d.spacer;w=d.bottoming;null==q&&(q=0);null==k&&(k=void 0);null==B&&(B=!0);null==t&&(t="is_stuck");A=b(document);null==w&&(w=!0);J=function(a,d,n,C,F,u,r,G){var v,H,m,D,I,c,g,x,y,z,h,l;if(!a.data("sticky_kit")){a.data("sticky_kit",!0);I=A.height();g=a.parent();null!=k&&(g=g.closest(k));
if(!g.length)throw"failed to find stick parent";v=m=!1;(h=null!=p?p&&a.closest(p):b("<div />"))&&h.css("position",a.css("position"));x=function(){var c,f,e;if(!G&&(I=A.height(),c=parseInt(g.css("border-top-width"),10),f=parseInt(g.css("padding-top"),10),d=parseInt(g.css("padding-bottom"),10),n=g.offset().top+c+f,C=g.height(),m&&(v=m=!1,null==p&&(a.insertAfter(h),h.detach()),a.css({position:"",top:"",width:"",bottom:""}).removeClass(t),e=!0),F=a.offset().top-(parseInt(a.css("margin-top"),10)||0)-q,
u=a.outerHeight(!0),r=a.css("float"),h&&h.css({width:a.outerWidth(!0),height:u,display:a.css("display"),"vertical-align":a.css("vertical-align"),"float":r}),e))return l()};x();if(u!==C)return D=void 0,c=q,z=E,l=function(){var b,l,e,k;if(!G&&(e=!1,null!=z&&(--z,0>=z&&(z=E,x(),e=!0)),e||A.height()===I||x(),e=f.scrollTop(),null!=D&&(l=e-D),D=e,m?(w&&(k=e+u+c>C+n,v&&!k&&(v=!1,a.css({position:"fixed",bottom:"",top:c}).trigger("sticky_kit:unbottom"))),e<F&&(m=!1,c=q,null==p&&("left"!==r&&"right"!==r||a.insertAfter(h),
h.detach()),b={position:"",width:"",top:""},a.css(b).removeClass(t).trigger("sticky_kit:unstick")),B&&(b=f.height(),u+q>b&&!v&&(c-=l,c=Math.max(b-u,c),c=Math.min(q,c),m&&a.css({top:c+"px"})))):e>F&&(m=!0,b={position:"fixed",top:c},b.width="border-box"===a.css("box-sizing")?a.outerWidth()+"px":a.width()+"px",a.css(b).addClass(t),null==p&&(a.after(h),"left"!==r&&"right"!==r||h.append(a)),a.trigger("sticky_kit:stick")),m&&w&&(null==k&&(k=e+u+c>C+n),!v&&k)))return v=!0,"static"===g.css("position")&&g.css({position:"relative"}),
a.css({position:"absolute",bottom:d,top:"auto"}).trigger("sticky_kit:bottom")},y=function(){x();return l()},H=function(){G=!0;f.off("touchmove",l);f.off("scroll",l);f.off("resize",y);b(document.body).off("sticky_kit:recalc",y);a.off("sticky_kit:detach",H);a.removeData("sticky_kit");a.css({position:"",bottom:"",top:"",width:""});g.position("position","");if(m)return null==p&&("left"!==r&&"right"!==r||a.insertAfter(h),h.remove()),a.removeClass(t)},f.on("touchmove",l),f.on("scroll",l),f.on("resize",
y),b(document.body).on("sticky_kit:recalc",y),a.on("sticky_kit:detach",H),setTimeout(l,0)}};n=0;for(K=this.length;n<K;n++)d=this[n],J(b(d));return this}}).call(this);</script>';
 };
} // '

// ---------------------
// Float Menu Javascript
// ---------------------
$funcname = 'wqhelper_sidebar_floatmenuscript_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {

	return "
	<style>.floatbox {position:absolute;width:250px;top:30px;right:15px;z-index:100;}</style>

	<script language='javascript' type='text/javascript'>
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
 };
}


// -----------------------------
// === Dashboard Feed Widget ===
// -----------------------------

// -----------------------------
// Add the Dashboard Feed Widget
// -----------------------------
$requesturi = $_SERVER['REQUEST_URI'];
if ( (preg_match('|index.php|i', $requesturi))
  || (substr($requesturi, -(strlen('/wp-admin/'))) == '/wp-admin/')
  || (substr($requesturi, -(strlen('/wp-admin/network'))) == '/wp-admin/network/') ) {
	if (!has_action('wp_dashboard_setup', 'wqhelper_add_dashboard_feed_widget')) {
		add_action('wp_dashboard_setup', 'wqhelper_add_dashboard_feed_widget');
	}
}

// ------------------------
// Load the Dashboard Feeds
// ------------------------
$funcname = 'wqhelper_add_dashboard_feed_widget_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {

	global $wp_meta_boxes, $current_user;
	if (current_user_can('manage_options') || current_user_can('install_plugins')) {

		// --- check if already loaded ---
		// 1.6.1: fix to undefined index warning
		$wordquestloaded = $pluginreviewloaded = false;
		foreach (array_keys($wp_meta_boxes['dashboard']['normal']['core']) as $name) {
			if ($name == 'wordquest') {$wordquestloaded = true;}
			if ($name == 'pluginreview') {$pluginreviewloaded = true;}
		}

		// --- maybe add wordquest feed widget
		if (!$wordquestloaded) {
			wp_add_dashboard_widget('wordquest', 'WordQuest Alliance', 'wqhelper_dashboard_feed_widget');
		}

		// --- maybe add plugin review feed widget ---
		if (!$pluginreviewloaded) {
			wp_add_dashboard_widget('pluginreview', 'Plugin Review Network', 'wqhelper_pluginreview_feed_widget');
		}

		// --- enqueue dashboard feed javascript ---
		if (!has_action('admin_footer', 'wqhelper_dashboard_feed_javascript')) {
			add_action('admin_footer', 'wqhelper_dashboard_feed_javascript');
		}
	}
 };
}

// -----------------------------------
// WordQuest Dashboard Feed Javascript
// -----------------------------------
$funcname = 'wqhelper_dashboard_feed_javascript_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {
	echo "<script>
	function doloadfeedcat(namespace,siteurl) {
		var selectelement = document.getElementById(namespace+'catselector');
		var catslug = selectelement.options[selectelement.selectedIndex].value;
		var siteurl = encodeURIComponent(siteurl);
		document.getElementById('feedcatloader').src='admin-ajax.php?action=wqhelper_load_feed_category&category='+catslug+'&namespace='+namespace+'&siteurl='+siteurl;
	}</script>";
	echo "<iframe src='javascript:void(0);' id='feedcatloader' style='display:none;'></iframe>";
 };
}

// -------------------------------
// WordQuest Dashboard Feed Widget
// -------------------------------
$funcname = 'wqhelper_dashboard_feed_widget_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {

	// maybe Get Latest Release info
	// -----------------------------
	global $wqdebug, $wqreleases, $wqurls;
	$latestrelease = $nextrelease = '';
	if (isset($wqreleases)) {
		if (isset($wqreleases['latest'])) {$latestrelease = $wqreleases['latest'];}
		if (isset($wqreleases['next'])) {$nextrelease = $wqreleases['next'];}
	} else {
		$pluginsinfo = wqhelper_get_plugin_info();
		if (is_array($pluginsinfo)) {
			foreach ($pluginsinfo as $plugin) {
				if (isset($plugin['slug'])) {
					if ( (isset($plugin['latestrelease']) && ($plugin['latestrelease'] == 'yes'))
					  || (isset($plugin['nextrelease']) && ($plugin['nextrelease'] == 'yes')) ) {
						$plugininfo = $plugin; $plugins = get_plugins(); $plugininfo['installed'] = 'no';
						foreach ($plugins as $pluginfile => $values) {
							if ($plugininfo['slug'] == sanitize_title($values['Name'])) {$plugininfo['installed'] = 'yes';}
						}
					}
					if (isset($plugin['latestrelease']) && ($plugin['latestrelease'] == 'yes')) {$latestrelease = $plugininfo;}
					if (isset($plugin['nextrelease']) && ($plugin['nextrelease'] == 'yes')) {$nextrelease = $plugininfo;}
				}
			}
		}
	}
	// echo "<!-- Latest Release: "; print_r($latestrelease); echo " -->";
 	// echo "<!-- Next Release: "; print_r($nextrelease); echo " -->";

	// maybe Display Latest Release Info
	// ---------------------------------
	if (isset($_REQUEST['page']) && ($_REQUEST['page'] == 'wordquest')) {
		// --- do not duplicate here as already output for wordquest page ---
	} elseif (isset($latestrelease) && is_array($latestrelease) && ($latestrelease['installed'] == 'no')) {

		echo "<b>".wqhelper_translate('Latest Plugin Release')."</b><br>";
		echo "<table><tr><td align='center'><img src='".$latestrelease['icon']."' width='75' height='75'><br>";
		echo "<a href='".$latestrelease['home']."' target=_blank><b>".$latestrelease['title']."</b></a></td>";
		echo "<td width='10'></td><td><span style='font-size:9pt;'>".$latestrelease['description']."</span><br><br>";

		if (isset($latestrelease['package']) && is_array($latestrelease['package'])) {

			// 1.6.6: check for wordpress.org only installs
			global $wordpressorgonly; $installlink = false;
			if ($wordpressorgonly && $wqplugin['wporgslug']) {
				$installlink = self_admin_url('update.php')."?action=install-plugin&plugin=".$latestrelease['wporgslug'];
				$installlink = wp_nonce_url($installlink, 'install-plugin_'.$latestrelease['wporgslug']);
			} else {
				admin_url('update.php').'?action=wordquest_plugin_install&plugin='.$latestrelease['slug'];
				$installlink = wp_nonce_url($installlink, 'plugin-upload');
			}
			if ($installlink) {
				echo "<input type='hidden' name='".$latestrelease['slug']."-install-link' value='".$installlink."'>";
				echo "<center><a href='".$installlink."' class='button-primary'>".wqhelper_translate('Install Now')."</a></center>";
			} else {
				$pluginlink = $wqurls['wq'].'/plugins/'.$latestrelease['slug'];
				echo "<center><a href='".$pluginlink."' class='button-primary' target=_blank>&rarr; ".wqhelper_translate('Plugin Home')."</a></center>";
			}
		}
		echo "</td></tr></table><br>";

	} elseif (isset($nextrelease) && is_array($nextrelease)) {

		echo "<b>".wqhelper_translate('Upcoming Plugin Release')."</b><br>";
		echo "<table><tr><td align='center'><img src='".$nextrelease['icon']."' width='75' height='75'><br>";
		echo "<a href='".$nextrelease['home']."' target=_blank><b>".$nextrelease['title']."</b></a></td>";
		echo "<td width='10'></td><td><span style='font-size:9pt;'>".$nextrelease['description']."</span><br><br>";
		$releasetime = strtotime($nextrelease['releasedate']);
		echo "<center><span style='font-size:9pt;'>".wqhelper_translate('Expected').": ".date('jS F Y', $releasetime)."</span></center>";
		echo "</td></tr></table><br>";

	}

	// --- feed link styles ---
	echo "<style>.feedlink {text-decoration:none;} .feedlink:hover {text-decoration:underline;}</style>";

	// WordQuest Posts Feed
	// --------------------
	$rssurl = $wqurls['wq']."/category/guides/feed/";
	if ($wqdebug) {$feed = ''; delete_transient('wordquest_guides_feed');}
	else {$feed = trim(get_transient('wordquest_guides_feed'));}

	// --- fetch posts feed ---
	if (!$feed || ($feed == '')) {
		$rssfeed = fetch_feed($rssurl); $feeditems = 4;
		$args = array($rssfeed, $feeditems);
		$feed = wqhelper_process_rss_feed($args);
		if ($feed != '') {set_transient('wordquest_guides_feed', $feed, (24*60*60));}
	}

	// --- WordQuest Guides ----
	echo "<div id='wordquestguides'>";
	echo "<div style='float:right;'>&rarr;<a href='".$wqurls['wq']."/category/guides/' class='feedlink' target=_blank> ".wqhelper_translate('More')."...</a></div>";
	echo "<b><a href='".$wqurls['wq']."/category/guides/' class='feedlink' target=_blank>".wqhelper_translate('Latest WordQuest Guides')."</a></b><br>";
	if ($feed != '') {echo $feed;} else {echo wqhelper_translate('Feed Currently Unavailable.'); delete_transient('wordquest_guides_feed');}
	echo "</div>";

	// WordQuest Solutions Feed
	// ------------------------
	$rssurl = $wqurls['wq']."/quest/feed/";
	if ($wqdebug) {$feed = ''; delete_transient('wordquest_quest_feed');}
	else {$feed = trim(get_transient('wordquest_quest_feed'));}

	// --- fetch solutions feed ---
	if (!$feed || ($feed == '')) {
		$rssfeed = fetch_feed($rssurl); $feeditems = 4;
		$args = array($rssfeed, $feeditems);
		$feed = wqhelper_process_rss_feed($args);
		if ($feed != '') {set_transient('wordquest_quest_feed', $feed, (24*60*60));}
	}

	// --- output solutions feed ---
	echo "<div id='wordquestsolutions'>";
	echo "<div style='float:right;'>&rarr;<a href='".$wqurls['wq']."/solutions/' class='feedlink' target=_blank> ".wqhelper_translate('More')."...</a></div>";
	echo "<b><a href='".$wqurls['wq']."/solutions/' class='feedlink' target=_blank>".wqhelper_translate('Latest Solution Quests')."</a></b>";
	if ($feed != '') {echo $feed;} else {echo wqhelper_translate('Feed Currently Unavailable.'); delete_transient('wordquest_quest_feed');}
	echo "</div>";

	return;

	// --------------------------
	// currently not implented...

	// Category Feed Selection
	// -----------------------
	$pluginsurl = $wqurls['wq']."/?get_post_categories=yes";

	if ($wqdebug) {$categorylist = ''; delete_transient('wordquest_feed_cats');}
	else {$categorylist = trim(get_transient('wordquest_feed_cats'));}

	if (!$categorylist || ($categorylist == '')) {
		$args = array('timeout' => 10);
		$getcategorylist = wp_remote_get($pluginsurl, $args);
		if (!is_wp_error($getcategorylist)) {
			$categorylist = $getcategorylist['body'];
			if ($categorylist) {set_transient('wordquest_feed_cats', $categorylist, (24*60*60));}
		}
	}

	if (strstr($categorylist, "::::")) {
		$categories = explode("::::", $categorylist);
		if (count($categories) > 0) {
			$i = 0;
			foreach ($categories as $category) {
				$catinfo = explode("::", $category);
				$cats[$i]['name'] = $catinfo[0];
				$cats[$i]['slug'] = $catinfo[1];
				$cats[$i]['count'] = $catinfo[2];
				$i++;
			}

			if (count($cats) > 0) {
				echo "<table><tr><td><b>".wqhelper_translate('Category').":</b></td>";
				echo "<td width='7'></td>";
				echo "<td><select id='wqcatselector' onchange='doloadfeedcat(\"wq\",\"".$wqurls['wq']."\");'>";
				// echo "<option value='news' selected='selected'>WordQuest News</option>";
				foreach ($cats as $cat) {
					echo "<option value='".$cat['slug']."'";
						if ($cat['slug'] == 'news') {echo " selected='selected'";}
					echo ">".$cat['name']." (".$cat['count'].")</option>";
				}
				echo "</select></td></tr></table>";
				echo "<div id='wqfeeddisplay'></div>";
			}
		}
	}
 };
}

// ---------------------------------
// Plugin Review Network Feed Widget
// ---------------------------------
$funcname = 'wqhelper_pluginreview_feed_widget_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {

	// --- feed link styles ---
	echo "<style>.feedlink {text-decoration:none;} .feedlink:hover {text-decoration:underline;}</style>";

	// Latest Plugins Feed
	// -------------------
	global $wqdebug, $wqurls;
	$rssurl = $wqurls['prn']."/feed/";
	if ($wqdebug) {$feed = ''; delete_transient('pluginreview_newest_feed');}
	else {$feed = trim(get_transient('pluginreview_newest_feed'));}

	// --- fetch new plugins feed ---
	if (!$feed || ($feed == '')) {
		$rssfeed = fetch_feed($rssurl); $feeditems = 4;
		$args = array($rssfeed, $feeditems);
		$feed = wqhelper_process_rss_feed($args);
		if ($feed != '') {set_transient('pluginreview_newest_feed', $feed, (24*60*60));}
	}

	echo "<center><b><a href='".$wqurls['prn']."/directory/' class='feedlink' style='font-size:11pt;' target=_blank>";
	echo wqhelper_translate('NEW', 'bioship').' '.wqhelper_translate('Plugin Directory')." - ".wqhelper_translate('by Category')."!</a></b></center><br>";
	echo "<div id='pluginslatest'>";

	// --- ouput latest plugins feed ---
	echo "<div style='float:right;'>&rarr;<a href='".$wqurls['prn']."/directory/latest/' class='feedlink' target=_blank> ".wqhelper_translate('More')."...</a></div>";
	if ($feed != '') {echo "<b>".wqhelper_translate('Latest Plugin Releases')."</b><br>".$feed;}
	else {echo wqhelper_translate('Feed Currently Unavailable'); delete_transient('prn_feed');}
	echo "</div>";

	// Recently Updated Feed
	// ---------------------
	$rssurl = $wqurls['prn']."/feed/?orderby=modified";
	if ($wqdebug) {$feed = ''; delete_transient('pluginreview_updated_feed');}
	else {$feed = trim(get_transient('pluginreview_updated_feed'));}

	// --- fetch recently updated feed ---
	if (!$feed || ($feed == '')) {
		$rssfeed = fetch_feed($rssurl); $feeditems = 4;
		$args = array($rssfeed, $feeditems);
		$feed = wqhelper_process_rss_feed($args);
		if ($feed != '') {set_transient('pluginreview_updated_feed', $feed, (24*60*60));}
	}

	// --- output recently updated feed ---
	echo "<div id='pluginsupdated'>";
	echo "<div style='float:right;'>&rarr;<a href='".$wqurls['prn']."/directory/updated/' class='feedlink' target=_blank> ".wqhelper_translate('More')."...</a></div>";
	if ($feed != '') {echo "<b>".wqhelper_translate('Recently Updated Plugins')."</b><br>".$feed;}
	else {echo wqhelper_translate('Feed Currently Unavailable'); delete_transient('prn_feed');}
	echo "</div>";

	return;

	// --------------------------
	// currently not implented...

	// Category Feed Selection
	// -----------------------
	$categoryurl = $wqurls['prn']."/?get_review_categories=yes";

	// refresh once a day only to limit downloads
	if ($wqdebug) {$categorylist = ''; delete_transient('prn_feed_cats');}
	else {$categorylist = trim(get_transient('prn_feed_cats'));}

	if (!$categorylist || ($categorylist == '')) {
		$args = array('timeout' => 10);
		$getcategorylist = wp_remote_get($categoryurl, $args);
		if (!is_wp_error($getcategorylist)) {
			$categorylist = $getcategorylist['body'];
			if ($categorylist) {set_transient('prn_feed_cats', $categorylist, (24*60*60));}
		}
	}

	if (strstr($categorylist, "::::")) {
		$categories = explode("::::", $categorylist);
		if (count($categories) > 0) {
			$i = 0;
			foreach ($categories as $category) {
				$catinfo = explode("::", $category);
				$cats[$i]['name'] = $catinfo[0];
				$cats[$i]['slug'] = $catinfo[1];
				$cats[$i]['count'] = $catinfo[2];
				$i++;
			}

			if (count($cats) > 0) {
				echo "<table><tr><td><b>".wqhelper_translate('Category').":</b></td>";
				echo "<td width='7'></td>";
				echo "<td><select id='prncatselector' onchange='doloadfeedcat(\"prn\",\"".$wqurls['prn']."\");'>";
				// echo "<option value='reviews' selected='selected'>".wqhelper_translate('Plugin Reviews')."</option>";
				foreach ($cats as $cat) {
					echo "<option value='".$cat['slug']."'";
						if ($cat['slug'] == 'reviews') {echo " selected='selected'";}
					echo ">".$cat['name']." (".$cat['count'].")</option>";
				}
				echo "</select></td></tr></table>";
				echo "<div id='prnfeeddisplay'></div>";
			}
		}
	}
 };
}

// --------------------
// Load a Category Feed
// --------------------
$funcname = 'wqhelper_load_feed_category_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function() {

	$namespace = $_GET['namespace'];
	$baseurl = $_GET['siteurl'];
	$catslug = $_GET['category'];

	$categoryurl = $baseurl."/category/".$catslug."/feed/";
	$morelink = "<div align='right'>&rarr; <a href='".$baseurl."/category/".$catslug."/' style='feedlink' target=_blank> More...</a></div>";

	// --- fetch the category feed ---
	$categoryrss = @fetch_feed($categoryurl); $feeditems = 10;

	// --- Process the Category Feed ---
	$args = array($categoryrss, $feeditems);
	$categoryfeed = wqhelper_process_rss_feed($args);
	if ($categoryfeed != '') {$categoryfeed .= $morelink;}

	// --- send back to parent window ---
	echo '<script language="javascript" type="text/javascript">
	var categoryfeed = "'.$categoryfeed.'";
	parent.document.getElementById("'.$namespace.'feeddisplay").innerHTML = categoryfeed;
	</script>';

	exit;
 };
}

// ----------------
// Process RSS Feed
// ----------------
$funcname = 'wqhelper_process_rss_feed_'.$wqhv;
if (!isset($wqfunctions[$funcname]) || !is_callable($wqfunctions[$funcname])) {
 $wqfunctions[$funcname] = function($args) {

	$rss = $args[0]; $feeditems = $args[1]; $processed = '';
	if (is_wp_error($rss)) {return '';}

	$maxitems = $rss->get_item_quantity($feeditems);
	$rssitems = $rss->get_items(0, $maxitems);

	if ($maxitems == 0) {$processed = '';}
	else {
		// --- create feed list item display ---
		$processed = "<ul style='list-style:none;margin:0;text-align:left;'>";
		foreach ($rssitems as $item) {
			$processed .= "<li>&rarr; <a href='".esc_url($item->get_permalink())."' class='feedlink' target='_blank' ";
			$processed .= "title='Posted ".$item->get_date('j F Y | g:i a')."'>";
			$processed .= esc_html($item->get_title())."</a></li>";
		}
		$processed .= "</ul>";
	}
	return $processed;
 };
}

// --- manual function list debug point ---
// add_action('plugins_loaded', function() {
//  echo "<!-- WQ Helper Functions: ".print_r($wqfunctions,true)." -->;
// });


// -----------------
// === Changelog ===
// -----------------

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
