<?php

/*
Plugin Name: Teleporter
Plugin URI: http://wordquest.org/plugins/teleporter/
Author: Tony Hayes
Description: Seamless fading Page Transitions via the Browser History API
Version: 0.9.6
Author URI: http://wordquest.org
GitHub Plugin URI: majick777/teleporter
*/

if ( !function_exists( 'add_action' ) ) {
	exit;
}

// === Teleporter ===
// - Enqueue Teleporter Scripts
// - Localize Script Settings
// - Add History API Support
// - Add Teleporter Styles
// - Minify Development Script
// - Link Test Shortcode


// ----------------
// Define Constants
// ----------------
define( 'TELEPORTER_FILE', __FILE__ );
define( 'TELEPORTER_DIR', dirname( __FILE__ ) );

// --------------------------
// Enqueue Teleporter Scripts
// --------------------------
add_action( 'wp_enqueue_scripts', 'teleporter_enqueue_scripts' );
function teleporter_enqueue_scripts() {

	// --- enqueue find event handlers script ---
	// TODO: check for click event handlers in teleporter.js using findEventHandlers
	// ref: https://stackoverflow.com/questions/2518421/jquery-find-events-handlers-registered-with-an-object
	// ref: https://www.blinkingcaret.com/2014/01/17/quickly-finding-and-debugging-jquery-event-handlers/
	// ref: http://findhandlersjsexample.azurewebsites.net/
	// ref: https://stackoverflow.com/questions/446892/how-to-find-event-listeners-on-a-dom-node-when-debugging-or-from-the-javascript/22841712#22841712
	$event_handlers_url = plugins_url( 'js/findEventHandlers.js', __FILE__ );
	$version = filemtime( dirname( __FILE__ ) . '/js/findEventHandlers.js' );
	wp_enqueue_script( 'find-event-handlers', $event_handlers_url, array(), $version, false );

	// --- enqueue history js for browser compatibility ---
	// ref: https://github.com/browserstate/history.js/
	$version = filemtime( dirname( __FILE__ ) . '/js/history.js' );
	$history_js_url = plugins_url( 'js/history.js', __FILE__ );
	wp_enqueue_script( 'historyjs', $history_js_url, array(), $version, false );

	// --- enqueue teleporter script ---
	$suffix = '.dev';
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
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
	$debug = 'true'; // DEV TEMP

	// --- set iframe fadein time ---
	$fade_time = 2000;
	$fade_time = apply_filters( 'teleporter_fade_time', $fade_time );
	// $fade_time = teleporter_get_setting( 'teleporter_fade_time' );
	if ( !$fade_time ) {
		$fade_time = 'false';
	}

	// --- set ignore classes ---
	$ignore_classes = array( 'no-teleporter', 'no-transition' );
	$ignore_classes = apply_filters( 'teleporter_ignore_classes', $ignore_classes );
	$ignore = '[';
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

	// --- output script settings object ---
	$js = "var teleporter = {";
		$js .= "debug: " . esc_js( $debug ) . ", ";
		$js .= "fadetime: " . esc_js( $fade_time ) . ", ";
		$js .= "ignore: " . $ignore . ", ";
		$js .= "iframe: " . $iframe . ", ";
		$js .= "loading: " . $loading ;
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
						// $js .= "console.log(ablinks[i].className);";
					$js .= "}";
				$js .= "}";
			$js .= "} ";
		}
		// --- links to WordPress admin area ---
		$js .= "adlinks = document.getElementsByTagName('a'); ";
		$js .= "for (var i = 0; i < adlinks.length; i++) {";
			$js .= "if (adlinks[i].href && (adlinks[i].href.indexOf('/wp-admin/') > -1)) {";
				$js .= "if (!adlinks[i].classList.contains(ignoreclass)) {";
					$js .= "adlinks[i].classList.add(ignoreclass);";
					// $js .= "console.log(adlinks[i].className);";
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
			echo "historyjs = document.createElement('script');";
			echo "historyjs.setAttribute('src', '" . $history_js_url . "');";
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

	// --- get iframe class and loading div ID ---
	$iframe = apply_filters( 'teleporter_iframe_class', 'teleporter-iframe' );
	$loading = apply_filters( 'teleporter_loading_id', 'teleporter-loading' );

	// --- output page transition iframe ---
	// note: actual iframes are added dynamically via script
	// ref: https://stackoverflow.com/questions/3982422/full-screen-iframe
	// echo '<iframe src="javascript:void(0);" id="' . esc_attr( $iframe ) . '" name="' . esc_attr( $iframe ) . '" width="100%" height="100%" frameborder="0" scrolling="auto" allowfullscreen="true" style="display:none;"></iframe>';

	// --- output loading div ---
	if ( $loading && is_string( $loading ) ) {
		echo '<div id="' . esc_attr( $loading ) . '"></div>';
	}

	// --- iframe and loading styles ---
	if ( $iframe && is_string( $iframe ) ) {
		echo "<style>." . $iframe . " {";
			echo "background: #FFF; overflow: hidden; z-index: 999999;";
			echo "position: fixed; border: none; margin: 0; padding: 0; top: 0; left: 0; bottom: 0; right: 0;";
		echo "}" . PHP_EOL;

		if ( $loading && is_string( $loading ) ) {
			echo "#" . $loading . " {position: fixed; top: 0; left: 0; right: 0; margin: 0; padding: 0; ";
				echo "border: none; height: 7px; width: 0; max-width: 5000px; overflow: hidden; ";
				echo "opacity: 0; transition: none; background: #00CC00;";
			echo "}" . PHP_EOL;
			echo "#" . $loading . ".loading {transition: width 10s ease-in-out; width: 100%; opacity: 1;}" . PHP_EOL;
			echo "#" . $loading . ".reset {transition: none; width: 0; opacity: 0;}" . PHP_EOL;

			// --- maybe shift top position for admin bar ---
			echo "body.admin-bar #" . $loading . " {top: 32px;}" . PHP_EOL;
			echo "@media screen and (max-width: 782px) {";
				echo "body.admin-bar #" . $loading . "{top: 46px;}";
			echo "}" . PHP_EOL;
		}
		echo "</style>";
	}
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
	echo '<br>----- Minified Script -----<br>';
	echo $mincontents;

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
	echo '<br>----- Comment Free Script -----<br>';
	echo $contents;

	exit;
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
?>

<title>Transition <?php echo $title; ?></title>

<?php echo $text; ?><br><br>

<div id='links'>

	<a href='?next=<?php echo $nextvalue; ?>'>Link to Next Page.</a><br><br>

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

