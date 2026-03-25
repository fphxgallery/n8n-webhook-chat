<?php
/**
 * Bare HTML page served at /?wcc_embed=1
 * Loaded inside an <iframe> — no WordPress theme, header, or footer.
 * Variables available: $opts, $css_url, $js_url, $css_vars, $js_cfg
 */
defined( 'ABSPATH' ) || exit;
?><!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?php echo esc_html( $opts['chat_title'] ); ?></title>
<style>
/* Reset */
*, *::before, *::after { box-sizing: border-box; }
html, body {
	margin: 0; padding: 0;
	width: 100%;
	/* Auto-height: let content determine size so the iframe can grow */
	height: auto;
	overflow: visible;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
</style>
<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>?ver=<?php echo esc_attr( WC_CHAT_VERSION ); ?>">
<style>
/* CSS custom properties from settings — must come AFTER chat.css to override its :root defaults */
<?php echo $css_vars; // already sanitized via sprintf/esc_attr ?>
</style>
<style>
/* ── Auto-grow embed overrides ── */

/* Override chat.css body.wcc-embed-page fixed heights */
html, body.wcc-embed-page {
	height: auto !important;
	overflow: visible !important;
}

.wcc-chat-root {
	display: flex;
	flex-direction: column;
	width: 100%;
	height: auto !important;
}

/* Hide launcher */
.wcc-launcher-wrap { display: none !important; }

/* Window: static, full width, grows up to max height then clips */
.wcc-window {
	position: static !important;
	width: 100% !important;
	height: auto !important;
	max-width: 100% !important;
	max-height: <?php echo absint( $opts['iframe_height'] ); ?>px;
	overflow: hidden !important;
	border-radius: 0 !important;
	box-shadow: none !important;
	opacity: 1 !important;
	transform: none !important;
	pointer-events: auto !important;
	display: flex;
	flex-direction: column;
}

/* Messages: scroll when window is at max height */
.wcc-messages {
	flex: 1 !important;
	overflow-y: auto !important;
	height: auto !important;
	max-height: none !important;
}

@media (max-width: 480px) {
	.wcc-window {
		width: 100% !important;
		height: auto !important;
		bottom: auto !important;
		right: auto !important;
		left: auto !important;
		border-radius: 0 !important;
		transform: none !important;
	}
}
</style>
</head>
<body class="wcc-embed-page">

<?php
// Render the chat markup.
// In embed mode we don't need the launcher HTML, but chat-window.php already
// hides it via CSS above. We pull it in for the header + messages + input.
$position = sanitize_html_class( $opts['position'] );
$icon     = $opts['launcher_icon'];
$label    = $opts['launcher_label'];
include WC_CHAT_PLUGIN_DIR . 'templates/chat-window.php';
?>

<script>
/* JS config — embedMode: true tells chat.js to skip open/close toggle */
var wcChat = <?php echo $js_cfg ?: '{}'; ?>;
</script>
<script src="<?php echo esc_url( $js_url ); ?>?ver=<?php echo esc_attr( WC_CHAT_VERSION ); ?>"></script>
<script>
/* Auto-grow: notify parent of height changes via postMessage */
(function () {
  var parentOrigin = new URL(<?php echo wp_json_encode( home_url( '/' ) ); ?>).origin;
  function sendHeight() {
    var h = document.body.scrollHeight;
    window.parent.postMessage({ type: 'wcc_resize', height: h }, parentOrigin);
  }
  // Use ResizeObserver for reliable detection of content changes
  if (typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(sendHeight).observe(document.body);
  }
  // Also fire immediately after load
  sendHeight();
})();
</script>
</body>
</html>
