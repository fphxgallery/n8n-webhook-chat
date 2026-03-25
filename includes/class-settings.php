<?php
defined( 'ABSPATH' ) || exit;

class Webhook_Chat_Settings {

	const OPTION_KEY = 'webhook_chat_settings';

	public static function get_defaults() {
		return array(
			// Webhook
			'webhook_url'          => '',
			'webhook_method'       => 'POST',
			'webhook_timeout'      => '60',
			'webhook_headers'      => '',
			'request_field'        => 'chatInput',
			'response_field'       => 'reply',
			'extra_body_fields'    => '',
			// Appearance
			'position'             => 'bottom-right',
			'primary_color'        => '#6366f1',
			'secondary_color'      => '#f1f5f9',
			'text_color'           => '#1e293b',
			'bot_bubble_color'     => '#f1f5f9',
			'bot_text_color'       => '#1e293b',
			'user_bubble_color'    => '#6366f1',
			'user_text_color'      => '#ffffff',
			'font_size'            => '14',
			'border_radius'        => '16',
			'window_width'         => '380',
			'window_height'        => '520',
			// Content
			'chat_title'           => 'Chat with us',
			'chat_subtitle'        => 'We typically reply instantly',
			'placeholder_text'     => 'Type your message…',
			'welcome_message'      => 'Hi! How can I help you today?',
			'launcher_icon'        => 'chat',
			'launcher_label'       => '',
			'send_label'           => 'Send',
			'show_header'          => '1',
			// Behaviour
			'display_mode'         => 'launcher',
			'iframe_height'        => '600',
			'open_on_load'         => '0',
			'show_timestamps'      => '1',
			'show_avatar'          => '1',
			'avatar_url'           => '',
			'typing_indicator'     => '1',
			'allowed_pages'        => '',
		);
	}

	public static function set_defaults() {
		if ( ! get_option( self::OPTION_KEY ) ) {
			add_option( self::OPTION_KEY, self::get_defaults() );
		}
	}

	public static function get( $key = null ) {
		$options = get_option( self::OPTION_KEY, self::get_defaults() );
		$options = wp_parse_args( $options, self::get_defaults() );
		if ( $key ) {
			return isset( $options[ $key ] ) ? $options[ $key ] : null;
		}
		return $options;
	}

	public static function register() {
		register_setting( 'webhook_chat_group', self::OPTION_KEY, array(
			'sanitize_callback' => array( __CLASS__, 'sanitize' ),
		) );
	}

	public static function sanitize( $input ) {
		$clean    = array();
		$defaults = self::get_defaults();

		$clean['webhook_url']       = esc_url_raw( $input['webhook_url'] ?? '' );
		$clean['webhook_method']    = in_array( $input['webhook_method'] ?? 'POST', array( 'POST', 'GET' ), true ) ? $input['webhook_method'] : 'POST';
		$clean['webhook_timeout']   = min( max( absint( $input['webhook_timeout'] ?? 60 ), 10 ), 300 );
		$clean['webhook_headers']   = sanitize_textarea_field( $input['webhook_headers'] ?? '' );
		$clean['request_field']     = sanitize_text_field( $input['request_field'] ?? 'chatInput' );
		$clean['response_field']    = sanitize_text_field( $input['response_field'] ?? 'reply' );
		$clean['extra_body_fields'] = sanitize_textarea_field( $input['extra_body_fields'] ?? '' );

		$color_fields = array( 'primary_color', 'secondary_color', 'text_color', 'bot_bubble_color', 'bot_text_color', 'user_bubble_color', 'user_text_color' );
		foreach ( $color_fields as $field ) {
			$clean[ $field ] = sanitize_hex_color( $input[ $field ] ?? $defaults[ $field ] ) ?: $defaults[ $field ];
		}

		$int_fields = array( 'font_size', 'border_radius', 'window_width', 'window_height' );
		foreach ( $int_fields as $field ) {
			$clean[ $field ] = absint( $input[ $field ] ?? $defaults[ $field ] );
		}

		$clean['display_mode']      = in_array( $input['display_mode'] ?? 'launcher', array( 'launcher', 'iframe' ), true ) ? $input['display_mode'] : 'launcher';
		$clean['iframe_height']     = absint( $input['iframe_height'] ?? 600 );
		$clean['position']          = in_array( $input['position'] ?? 'bottom-right', array( 'bottom-right', 'bottom-left', 'bottom-center' ), true ) ? $input['position'] : 'bottom-right';
		$clean['chat_title']        = sanitize_text_field( $input['chat_title'] ?? $defaults['chat_title'] );
		$clean['chat_subtitle']     = sanitize_text_field( $input['chat_subtitle'] ?? $defaults['chat_subtitle'] );
		$clean['placeholder_text']  = sanitize_text_field( $input['placeholder_text'] ?? $defaults['placeholder_text'] );
		$clean['welcome_message']   = sanitize_textarea_field( $input['welcome_message'] ?? $defaults['welcome_message'] );
		$clean['launcher_icon']     = in_array( $input['launcher_icon'] ?? 'chat', array( 'chat', 'support', 'bot' ), true ) ? $input['launcher_icon'] : 'chat';
		$clean['launcher_label']    = sanitize_text_field( $input['launcher_label'] ?? '' );
		$clean['send_label']        = sanitize_text_field( $input['send_label'] ?? 'Send' );
		$clean['avatar_url']        = esc_url_raw( $input['avatar_url'] ?? '' );
		$clean['allowed_pages']     = sanitize_textarea_field( $input['allowed_pages'] ?? '' );

		$bool_fields = array( 'open_on_load', 'show_timestamps', 'show_avatar', 'typing_indicator', 'show_header' );
		foreach ( $bool_fields as $field ) {
			$clean[ $field ] = isset( $input[ $field ] ) && $input[ $field ] ? '1' : '0';
		}

		return $clean;
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$opts = self::get();
		?>
		<div class="wrap wc-chat-admin">
			<h1><?php esc_html_e( 'Webhook Chat Settings', 'webhook-chat' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'webhook_chat_group' ); ?>
				<div class="wc-chat-tabs">
					<nav class="wc-chat-tab-nav">
						<a href="#tab-webhook" class="wc-chat-tab-link active"><?php esc_html_e( 'Webhook', 'webhook-chat' ); ?></a>
						<a href="#tab-appearance" class="wc-chat-tab-link"><?php esc_html_e( 'Appearance', 'webhook-chat' ); ?></a>
						<a href="#tab-content" class="wc-chat-tab-link"><?php esc_html_e( 'Content', 'webhook-chat' ); ?></a>
						<a href="#tab-behaviour" class="wc-chat-tab-link"><?php esc_html_e( 'Behaviour', 'webhook-chat' ); ?></a>
					</nav>

					<!-- Webhook Tab -->
					<div id="tab-webhook" class="wc-chat-tab-panel active">
						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'Webhook URL', 'webhook-chat' ); ?></th>
								<td>
									<input type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[webhook_url]" value="<?php echo esc_attr( $opts['webhook_url'] ); ?>" class="regular-text" placeholder="https://your-webhook.example.com/chat" />
									<p class="description"><?php esc_html_e( 'The URL that receives chat messages. Receives JSON POST with the request field.', 'webhook-chat' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'HTTP Method', 'webhook-chat' ); ?></th>
								<td>
									<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[webhook_method]">
										<option value="POST" <?php selected( $opts['webhook_method'], 'POST' ); ?>>POST</option>
										<option value="GET" <?php selected( $opts['webhook_method'], 'GET' ); ?>>GET</option>
									</select>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Timeout (seconds)', 'webhook-chat' ); ?></th>
								<td>
									<input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[webhook_timeout]" value="<?php echo esc_attr( $opts['webhook_timeout'] ); ?>" min="10" max="300" class="small-text" />
									<p class="description"><?php esc_html_e( 'How long to wait for the webhook to respond. Increase if you see timeout errors. (10–300 s)', 'webhook-chat' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Extra Headers', 'webhook-chat' ); ?></th>
								<td>
									<textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[webhook_headers]" rows="4" class="large-text"><?php echo esc_textarea( $opts['webhook_headers'] ); ?></textarea>
									<p class="description"><?php esc_html_e( 'One per line: Header-Name: value (e.g. Authorization: Bearer token123)', 'webhook-chat' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Request Field', 'webhook-chat' ); ?></th>
								<td>
									<input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[request_field]" value="<?php echo esc_attr( $opts['request_field'] ); ?>" class="small-text" />
									<p class="description"><?php esc_html_e( 'JSON key used to send the user message (default: message)', 'webhook-chat' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Response Field', 'webhook-chat' ); ?></th>
								<td>
									<input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[response_field]" value="<?php echo esc_attr( $opts['response_field'] ); ?>" class="small-text" />
									<p class="description"><?php esc_html_e( 'JSON key to read the bot reply from (default: reply). Use dot notation for nested: data.text', 'webhook-chat' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Extra Body Fields', 'webhook-chat' ); ?></th>
								<td>
									<textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[extra_body_fields]" rows="5" class="large-text"><?php echo esc_textarea( $opts['extra_body_fields'] ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'Additional fields to include in every request body. One per line as', 'webhook-chat' ); ?>
										<code>key=value</code>.<br>
										<?php esc_html_e( 'Use', 'webhook-chat' ); ?> <code>{session_id}</code> <?php esc_html_e( 'as a placeholder for the auto-generated conversation session ID.', 'webhook-chat' ); ?><br>
										<?php esc_html_e( 'Example (n8n):', 'webhook-chat' ); ?><br>
										<code>action=sendMessage<br>sessionId={session_id}</code>
									</p>
								</td>
							</tr>
						</table>
					</div>

					<!-- Appearance Tab -->
					<div id="tab-appearance" class="wc-chat-tab-panel">
						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'Position', 'webhook-chat' ); ?></th>
								<td>
									<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[position]">
										<option value="bottom-right" <?php selected( $opts['position'], 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'webhook-chat' ); ?></option>
										<option value="bottom-left" <?php selected( $opts['position'], 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'webhook-chat' ); ?></option>
										<option value="bottom-center" <?php selected( $opts['position'], 'bottom-center' ); ?>><?php esc_html_e( 'Bottom Center', 'webhook-chat' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Primary Color', 'webhook-chat' ); ?></th>
								<td><input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[primary_color]" value="<?php echo esc_attr( $opts['primary_color'] ); ?>" /></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'User Bubble Color', 'webhook-chat' ); ?></th>
								<td>
									<input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[user_bubble_color]" value="<?php echo esc_attr( $opts['user_bubble_color'] ); ?>" />
									<input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[user_text_color]" value="<?php echo esc_attr( $opts['user_text_color'] ); ?>" title="Text color" />
									<span class="description"><?php esc_html_e( 'Background · Text', 'webhook-chat' ); ?></span>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Bot Bubble Color', 'webhook-chat' ); ?></th>
								<td>
									<input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[bot_bubble_color]" value="<?php echo esc_attr( $opts['bot_bubble_color'] ); ?>" />
									<input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[bot_text_color]" value="<?php echo esc_attr( $opts['bot_text_color'] ); ?>" title="Text color" />
									<span class="description"><?php esc_html_e( 'Background · Text', 'webhook-chat' ); ?></span>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Font Size (px)', 'webhook-chat' ); ?></th>
								<td><input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[font_size]" value="<?php echo esc_attr( $opts['font_size'] ); ?>" min="10" max="24" class="small-text" /></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Border Radius (px)', 'webhook-chat' ); ?></th>
								<td><input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[border_radius]" value="<?php echo esc_attr( $opts['border_radius'] ); ?>" min="0" max="32" class="small-text" /></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Window Width (px)', 'webhook-chat' ); ?></th>
								<td><input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[window_width]" value="<?php echo esc_attr( $opts['window_width'] ); ?>" min="280" max="800" class="small-text" /></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Window Height (px)', 'webhook-chat' ); ?></th>
								<td><input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[window_height]" value="<?php echo esc_attr( $opts['window_height'] ); ?>" min="300" max="900" class="small-text" /></td>
							</tr>
						</table>
					</div>

					<!-- Content Tab -->
					<div id="tab-content" class="wc-chat-tab-panel">
						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'Chat Title', 'webhook-chat' ); ?></th>
								<td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[chat_title]" value="<?php echo esc_attr( $opts['chat_title'] ); ?>" class="regular-text" /></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Subtitle', 'webhook-chat' ); ?></th>
								<td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[chat_subtitle]" value="<?php echo esc_attr( $opts['chat_subtitle'] ); ?>" class="regular-text" /></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Welcome Message', 'webhook-chat' ); ?></th>
								<td>
									<textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[welcome_message]" rows="3" class="large-text"><?php echo esc_textarea( $opts['welcome_message'] ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Shown when the chat window opens. Leave blank to disable.', 'webhook-chat' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Input Placeholder', 'webhook-chat' ); ?></th>
								<td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[placeholder_text]" value="<?php echo esc_attr( $opts['placeholder_text'] ); ?>" class="regular-text" /></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Send Button Label', 'webhook-chat' ); ?></th>
								<td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[send_label]" value="<?php echo esc_attr( $opts['send_label'] ); ?>" class="small-text" /></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Show Header', 'webhook-chat' ); ?></th>
								<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_header]" value="1" <?php checked( $opts['show_header'], '1' ); ?> /> <?php esc_html_e( 'Display the title bar at the top of the chat window', 'webhook-chat' ); ?></label></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Launcher Icon', 'webhook-chat' ); ?></th>
								<td>
									<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[launcher_icon]">
										<option value="chat" <?php selected( $opts['launcher_icon'], 'chat' ); ?>><?php esc_html_e( 'Chat bubble', 'webhook-chat' ); ?></option>
										<option value="support" <?php selected( $opts['launcher_icon'], 'support' ); ?>><?php esc_html_e( 'Support headset', 'webhook-chat' ); ?></option>
										<option value="bot" <?php selected( $opts['launcher_icon'], 'bot' ); ?>><?php esc_html_e( 'Bot / robot', 'webhook-chat' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Launcher Label', 'webhook-chat' ); ?></th>
								<td>
									<input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[launcher_label]" value="<?php echo esc_attr( $opts['launcher_label'] ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Optional text shown next to the launcher button.', 'webhook-chat' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Avatar URL', 'webhook-chat' ); ?></th>
								<td>
									<input type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[avatar_url]" value="<?php echo esc_attr( $opts['avatar_url'] ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Bot avatar image. Leave blank to use a default icon.', 'webhook-chat' ); ?></p>
								</td>
							</tr>
						</table>
					</div>

					<!-- Behaviour Tab -->
					<div id="tab-behaviour" class="wc-chat-tab-panel">
						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'Display Mode', 'webhook-chat' ); ?></th>
								<td>
									<fieldset>
										<label style="display:block;margin-bottom:6px">
											<input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[display_mode]" value="launcher" <?php checked( $opts['display_mode'], 'launcher' ); ?> />
											<?php esc_html_e( 'Floating launcher — a button appears in the corner and opens a popup.', 'webhook-chat' ); ?>
										</label>
										<label style="display:block">
											<input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[display_mode]" value="iframe" <?php checked( $opts['display_mode'], 'iframe' ); ?> />
											<?php esc_html_e( 'Always-open iframe — use the shortcode [webhook_chat] to embed a self-contained chat window.', 'webhook-chat' ); ?>
										</label>
									</fieldset>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Iframe Height (px)', 'webhook-chat' ); ?></th>
								<td>
									<input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[iframe_height]" value="<?php echo esc_attr( $opts['iframe_height'] ); ?>" min="200" max="1200" class="small-text" />
									<p class="description"><?php esc_html_e( 'Height of the embedded iframe when using iframe display mode. Width fills the container.', 'webhook-chat' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Open on Page Load', 'webhook-chat' ); ?></th>
								<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[open_on_load]" value="1" <?php checked( $opts['open_on_load'], '1' ); ?> /> <?php esc_html_e( 'Automatically open the chat window (launcher mode only)', 'webhook-chat' ); ?></label></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Show Timestamps', 'webhook-chat' ); ?></th>
								<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_timestamps]" value="1" <?php checked( $opts['show_timestamps'], '1' ); ?> /> <?php esc_html_e( 'Show time below each message', 'webhook-chat' ); ?></label></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Show Avatar', 'webhook-chat' ); ?></th>
								<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_avatar]" value="1" <?php checked( $opts['show_avatar'], '1' ); ?> /> <?php esc_html_e( 'Show bot avatar beside messages', 'webhook-chat' ); ?></label></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Typing Indicator', 'webhook-chat' ); ?></th>
								<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[typing_indicator]" value="1" <?php checked( $opts['typing_indicator'], '1' ); ?> /> <?php esc_html_e( 'Show animated typing dots while waiting', 'webhook-chat' ); ?></label></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Restrict to Pages', 'webhook-chat' ); ?></th>
								<td>
									<textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[allowed_pages]" rows="4" class="large-text"><?php echo esc_textarea( $opts['allowed_pages'] ); ?></textarea>
									<p class="description"><?php esc_html_e( 'One URL path per line (e.g. /contact/). Leave blank to show on all pages.', 'webhook-chat' ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<?php submit_button(); ?>
			</form>

			<div class="wc-chat-shortcode-info">
				<h2><?php esc_html_e( 'Embed via Shortcode', 'webhook-chat' ); ?></h2>
				<p><?php esc_html_e( 'Use the shortcode below to embed the chat window inline instead of the floating launcher:', 'webhook-chat' ); ?></p>
				<code>[webhook_chat]</code>
			</div>
		</div>
		<script>
		(function(){
			var links = document.querySelectorAll('.wc-chat-tab-link');
			var panels = document.querySelectorAll('.wc-chat-tab-panel');
			links.forEach(function(link){
				link.addEventListener('click', function(e){
					e.preventDefault();
					links.forEach(function(l){ l.classList.remove('active'); });
					panels.forEach(function(p){ p.classList.remove('active'); });
					link.classList.add('active');
					var target = document.querySelector(link.getAttribute('href'));
					if(target) target.classList.add('active');
				});
			});
		})();
		</script>
		<style>
		.wc-chat-admin .wc-chat-tabs { margin-top: 20px; }
		.wc-chat-tab-nav { display: flex; gap: 4px; border-bottom: 2px solid #c3c4c7; margin-bottom: 0; }
		.wc-chat-tab-link { display: inline-block; padding: 8px 18px; text-decoration: none; color: #1d2327; border: 1px solid transparent; border-bottom: none; border-radius: 4px 4px 0 0; background: #f0f0f1; }
		.wc-chat-tab-link.active { background: #fff; border-color: #c3c4c7; color: #1d2327; position: relative; bottom: -2px; }
		.wc-chat-tab-panel { display: none; background: #fff; border: 1px solid #c3c4c7; border-top: none; padding: 10px 20px; }
		.wc-chat-tab-panel.active { display: block; }
		.wc-chat-shortcode-info { margin-top: 30px; background: #fff; border: 1px solid #c3c4c7; padding: 15px 20px; border-radius: 4px; }
		.wc-chat-shortcode-info code { font-size: 16px; background: #f0f0f1; padding: 4px 10px; border-radius: 3px; }
		</style>
		<?php
	}
}
