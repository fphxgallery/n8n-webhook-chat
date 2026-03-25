<?php
defined( 'ABSPATH' ) || exit;

class Webhook_Chat {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_init', array( 'Webhook_Chat_Settings', 'register' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_launcher' ) );
		add_action( 'wp_ajax_wc_chat_send', array( __CLASS__, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_wc_chat_send', array( __CLASS__, 'handle_ajax' ) );
		add_shortcode( 'webhook_chat', array( __CLASS__, 'shortcode' ) );

		// Register the bare-HTML embed endpoint ( /?wcc_embed=1 )
		add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_embed_page' ) );
	}

	/* ── Admin ──────────────────────────────────────── */
	public static function add_admin_menu() {
		add_options_page(
			__( 'Webhook Chat', 'webhook-chat' ),
			__( 'Webhook Chat', 'webhook-chat' ),
			'manage_options',
			'webhook-chat',
			array( 'Webhook_Chat_Settings', 'render_admin_page' )
		);
	}

	/* ── Assets ─────────────────────────────────────── */
	public static function enqueue_assets() {
		if ( ! self::should_load() ) {
			return;
		}
		// Only enqueue on the parent page (not inside the iframe itself — that page
		// handles its own assets via render_embed_page()).
		if ( get_query_var( 'wcc_embed' ) ) {
			return;
		}

		$opts = Webhook_Chat_Settings::get();
		$ver  = WC_CHAT_VERSION;

		// In iframe mode the shortcode outputs an <iframe>; assets live inside it.
		// We still enqueue nothing extra on the outer page.
		if ( $opts['display_mode'] === 'iframe' ) {
			return;
		}

		wp_enqueue_style( 'webhook-chat', WC_CHAT_PLUGIN_URL . 'assets/css/chat.css', array(), $ver );
		wp_enqueue_script( 'webhook-chat', WC_CHAT_PLUGIN_URL . 'assets/js/chat.js', array(), $ver, true );

		wp_localize_script( 'webhook-chat', 'wcChat', self::js_config( $opts ) );
		wp_add_inline_style( 'webhook-chat', self::build_css_vars( $opts ) );
	}

	/* ── Embed endpoint ─────────────────────────────── */
	public static function register_query_var( $vars ) {
		$vars[] = 'wcc_embed';
		return $vars;
	}

	public static function maybe_render_embed_page() {
		if ( ! get_query_var( 'wcc_embed' ) ) {
			return;
		}
		self::render_embed_page();
		exit;
	}

	/**
	 * Outputs a bare, self-contained HTML page that is loaded inside the iframe.
	 * No WordPress theme, header, or footer — just the chat UI.
	 */
	public static function render_embed_page() {
		$opts    = Webhook_Chat_Settings::get();
		$css_url = WC_CHAT_PLUGIN_URL . 'assets/css/chat.css';
		$js_url  = WC_CHAT_PLUGIN_URL . 'assets/js/chat.js';
		$css_vars = self::build_css_vars( $opts );
		$js_cfg   = wp_json_encode( array_merge( self::js_config( $opts ), array(
			'embedMode' => true,
		) ) );

		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		// Allow the parent origin to iframe us
		header( 'Content-Security-Policy: frame-ancestors \'self\'' );

		include WC_CHAT_PLUGIN_DIR . 'templates/iframe-page.php';
	}

	/* ── Footer: launcher (launcher mode only) ───────── */
	public static function render_launcher() {
		if ( ! self::should_load() ) {
			return;
		}
		$opts = Webhook_Chat_Settings::get();

		// In iframe mode the shortcode handles embedding; no floating launcher.
		if ( $opts['display_mode'] === 'iframe' ) {
			return;
		}

		$position = sanitize_html_class( $opts['position'] );
		$icon     = $opts['launcher_icon'];
		$label    = $opts['launcher_label'];
		include WC_CHAT_PLUGIN_DIR . 'templates/chat-window.php';
	}

	/* ── Shortcode ───────────────────────────────────── */
	public static function shortcode( $atts ) {
		$opts = Webhook_Chat_Settings::get();

		if ( $opts['display_mode'] === 'iframe' ) {
			$embed_url = add_query_arg( 'wcc_embed', '1', home_url( '/' ) );
			$height    = absint( $opts['iframe_height'] ) ?: 600;
			$title     = esc_attr( $opts['chat_title'] );
			$iframe_id = 'wcc-iframe-' . wp_unique_id();

			$iframe = sprintf(
				'<iframe id="%s" src="%s" width="100%%" height="%d" frameborder="0" scrolling="no" title="%s" style="border:none;display:block;border-radius:%dpx;overflow:hidden;" loading="lazy" allowtransparency="true"></iframe>',
				esc_attr( $iframe_id ),
				esc_url( $embed_url ),
				$height,
				$title,
				absint( $opts['border_radius'] )
			);

			// Inline listener: resize this specific iframe when its content reports a new height
			$max_height = absint( $opts['iframe_height'] ) ?: 550;
			$script = sprintf(
				'<script>(function(){' .
				'var f=document.getElementById(%s),maxH=%d;' .
				'if(!f)return;' .
				'window.addEventListener("message",function(e){' .
				'if(e.data&&e.data.type==="wcc_resize"&&e.source===f.contentWindow){' .
				'f.style.height=Math.min(e.data.height,maxH)+"px";' .
				'}});' .
				'})();</script>',
				wp_json_encode( $iframe_id ),
				$max_height
			);

			return $iframe . $script;
		}

		// Launcher mode: inline embed (rare, but supported via shortcode)
		ob_start();
		$position = sanitize_html_class( $opts['position'] );
		$icon     = $opts['launcher_icon'];
		$label    = $opts['launcher_label'];
		include WC_CHAT_PLUGIN_DIR . 'templates/chat-window.php';
		return '<div class="wcc-inline-wrap">' . ob_get_clean() . '</div>';
	}

	/* ── AJAX handler ────────────────────────────────── */
	public static function handle_ajax() {
		check_ajax_referer( 'wc_chat_nonce', 'nonce' );

		$message = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Empty message.', 'webhook-chat' ) ) );
		}

		$opts        = Webhook_Chat_Settings::get();
		$webhook_url = $opts['webhook_url'];

		if ( empty( $webhook_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Webhook URL not configured.', 'webhook-chat' ) ) );
		}

		$headers = array( 'Content-Type' => 'application/json' );
		foreach ( array_filter( array_map( 'trim', explode( "\n", $opts['webhook_headers'] ) ) ) as $line ) {
			if ( strpos( $line, ':' ) !== false ) {
				list( $key, $val ) = explode( ':', $line, 2 );
				$headers[ trim( $key ) ] = trim( $val );
			}
		}

		$request_field = $opts['request_field'] ?: 'message';
		$session_id    = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

		// Build extra fields first so they appear before the message field in the JSON
		$payload   = array();
		$extra_raw = $opts['extra_body_fields'] ?? '';
		foreach ( array_filter( array_map( 'trim', explode( "\n", $extra_raw ) ) ) as $line ) {
			if ( strpos( $line, '=' ) !== false ) {
				list( $ekey, $eval ) = explode( '=', $line, 2 );
				$payload[ trim( $ekey ) ] = str_replace( '{session_id}', $session_id, trim( $eval ) );
			}
		}

		// Message field goes last, matching the expected payload order
		$payload[ $request_field ] = $message;

		$body = wp_json_encode( $payload );

		$response = wp_remote_request( $webhook_url, array(
			'method'  => $opts['webhook_method'],
			'headers' => $headers,
			'body'    => $body,
			'timeout' => absint( $opts['webhook_timeout'] ) ?: 60,
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'Webhook returned HTTP %d.', 'webhook-chat' ), $code ) ) );
		}

		$response_field = $opts['response_field'] ?: 'reply';
		$reply          = self::get_nested( $data, $response_field );

		if ( $reply === null ) {
			$reply = is_string( $raw ) ? $raw : __( '(no reply)', 'webhook-chat' );
		}

		wp_send_json_success( array(
			'reply' => wp_kses( self::linkify( (string) $reply ), array(
				'br'     => array(),
				'strong' => array(),
				'em'     => array(),
				'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
			) ),
		) );
	}

	/* ── Helpers ─────────────────────────────────────── */
	private static function js_config( $opts ) {
		return array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'wc_chat_nonce' ),
			'openOnLoad'      => $opts['open_on_load'] === '1',
			'showTimestamps'  => $opts['show_timestamps'] === '1',
			'showAvatar'      => $opts['show_avatar'] === '1',
			'typingIndicator' => $opts['typing_indicator'] === '1',
			'welcomeMessage'  => $opts['welcome_message'],
			'primaryColor'    => $opts['primary_color'],
			'userBubbleColor' => $opts['user_bubble_color'],
			'userTextColor'   => $opts['user_text_color'],
			'botBubbleColor'  => $opts['bot_bubble_color'],
			'botTextColor'    => $opts['bot_text_color'],
			'avatarUrl'       => $opts['avatar_url'],
			'embedMode'       => false,
		);
	}

	private static function build_css_vars( $opts ) {
		return sprintf(
			':root {
				--wcc-primary: %s;
				--wcc-user-bg: %s;
				--wcc-user-text: %s;
				--wcc-bot-bg: %s;
				--wcc-bot-text: %s;
				--wcc-font-size: %spx;
				--wcc-radius: %spx;
				--wcc-width: %spx;
				--wcc-height: %spx;
			}',
			esc_attr( $opts['primary_color'] ),
			esc_attr( $opts['user_bubble_color'] ),
			esc_attr( $opts['user_text_color'] ),
			esc_attr( $opts['bot_bubble_color'] ),
			esc_attr( $opts['bot_text_color'] ),
			absint( $opts['font_size'] ),
			absint( $opts['border_radius'] ),
			absint( $opts['window_width'] ),
			absint( $opts['window_height'] )
		);
	}

	private static function should_load() {
		$allowed = Webhook_Chat_Settings::get( 'allowed_pages' );
		if ( empty( trim( $allowed ) ) ) {
			return true;
		}
		$current = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH ) ?: '/';
		foreach ( array_filter( array_map( 'trim', explode( "\n", $allowed ) ) ) as $path ) {
			if ( strpos( $current, $path ) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Convert markdown links and bare URLs in a reply string to HTML <a> tags.
	 * Runs before wp_kses so the tags survive sanitisation.
	 */
	private static function linkify( $text ) {
		// 1. Markdown-style links: [label](https://…)
		$text = preg_replace_callback(
			'/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/',
			function ( $m ) {
				return '<a href="' . esc_url( $m[2] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $m[1] ) . '</a>';
			},
			$text
		);

		// 2. Bare URLs not already inside an href="…" attribute
		$text = preg_replace_callback(
			'~(?<!href=[\'"])(?<!href=)(https?://[^\s<>\'")\]]+)~i',
			function ( $m ) {
				return '<a href="' . esc_url( $m[1] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $m[1] ) . '</a>';
			},
			$text
		);

		return $text;
	}

	private static function get_nested( $data, $key ) {
		$parts = explode( '.', $key );
		$value = $data;
		foreach ( $parts as $part ) {
			if ( ! is_array( $value ) || ! array_key_exists( $part, $value ) ) {
				return null;
			}
			$value = $value[ $part ];
		}
		return $value;
	}
}
