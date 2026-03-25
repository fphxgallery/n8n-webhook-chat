<?php
defined( 'ABSPATH' ) || exit;
// $opts is available when called from render_launcher() / shortcode()
$opts     = Webhook_Chat_Settings::get();
$position = sanitize_html_class( $opts['position'] );
$icon     = $opts['launcher_icon'];
$label    = $opts['launcher_label'];
?>
<div class="wcc-chat-root" role="region" aria-label="<?php echo esc_attr( $opts['chat_title'] ); ?>">

	<!-- Launcher button -->
	<div class="wcc-launcher-wrap pos-<?php echo esc_attr( $position ); ?>">
		<?php if ( $label ) : ?>
			<span class="wcc-launcher-label" role="button" tabindex="0"><?php echo esc_html( $label ); ?></span>
		<?php endif; ?>
		<button class="wcc-launcher-btn" aria-label="<?php esc_attr_e( 'Open chat', 'webhook-chat' ); ?>" type="button">
			<!-- Chat icon -->
			<svg class="wcc-icon-chat<?php echo $icon !== 'chat' ? ' wcc-icon-hidden' : ''; ?>" viewBox="0 0 24 24" aria-hidden="true">
				<path d="M20 2H4a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V4a2 2 0 00-2-2z"/>
			</svg>
			<!-- Support icon -->
			<svg class="wcc-icon-support<?php echo $icon !== 'support' ? ' wcc-icon-hidden' : ''; ?>" viewBox="0 0 24 24" aria-hidden="true">
				<path d="M12 2C6.477 2 2 6.477 2 12c0 1.82.487 3.53 1.338 5L2 22l5-1.338A9.956 9.956 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a7.96 7.96 0 01-4.162-1.174l-.298-.177-3.07.82.82-3.07-.177-.298A7.96 7.96 0 014 12c0-4.411 3.589-8 8-8s8 3.589 8 8-3.589 8-8 8zm4.406-5.845c-.242-.121-1.43-.706-1.652-.786-.221-.08-.382-.121-.543.121-.16.242-.624.786-.764.947-.14.161-.281.182-.523.06-.242-.12-1.022-.376-1.946-1.2-.72-.641-1.206-1.432-1.347-1.674-.14-.242-.015-.373.105-.493.108-.108.242-.282.363-.423.12-.14.16-.242.242-.403.08-.161.04-.303-.02-.424-.06-.12-.543-1.308-.743-1.791-.196-.469-.396-.405-.543-.413-.14-.007-.302-.009-.463-.009-.16 0-.423.06-.645.303-.221.242-.847.827-.847 2.016s.867 2.34.988 2.502c.12.16 1.707 2.607 4.136 3.655.578.25 1.029.4 1.38.511.58.185 1.108.159 1.526.097.465-.07 1.43-.584 1.632-1.149.2-.563.2-1.046.14-1.147-.06-.1-.221-.16-.463-.282z"/>
			</svg>
			<!-- Bot icon -->
			<svg class="wcc-icon-bot<?php echo $icon !== 'bot' ? ' wcc-icon-hidden' : ''; ?>" viewBox="0 0 24 24" aria-hidden="true">
				<path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7v1h1v4h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2v-4h1v-1a7 7 0 017-7h1V5.73A2 2 0 0112 2zM9 14a2 2 0 100 4 2 2 0 000-4zm6 0a2 2 0 100 4 2 2 0 000-4z"/>
			</svg>
			<!-- Close icon -->
			<svg class="wcc-icon-close" viewBox="0 0 24 24" aria-hidden="true">
				<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
			</svg>
			<span class="wcc-badge" aria-live="polite"></span>
		</button>
	</div>

	<!-- Chat window -->
	<div class="wcc-window pos-<?php echo esc_attr( $position ); ?>" aria-hidden="true" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $opts['chat_title'] ); ?>">

		<?php if ( $opts['show_header'] === '1' ) : ?>
		<!-- Header -->
		<div class="wcc-header">
			<div class="wcc-header-avatar">
				<?php if ( ! empty( $opts['avatar_url'] ) ) : ?>
					<img src="<?php echo esc_url( $opts['avatar_url'] ); ?>" alt="" />
				<?php else : ?>
					<svg viewBox="0 0 24 24" aria-hidden="true">
						<path d="M12 2a5 5 0 100 10A5 5 0 0012 2zm0 12c-5.33 0-8 2.67-8 4v2h16v-2c0-1.33-2.67-4-8-4z"/>
					</svg>
				<?php endif; ?>
			</div>
			<div class="wcc-header-info">
				<div class="wcc-header-title"><?php echo esc_html( $opts['chat_title'] ); ?></div>
				<?php if ( ! empty( $opts['chat_subtitle'] ) ) : ?>
					<div class="wcc-header-subtitle">
						<span class="wcc-online-dot" aria-hidden="true"></span>
						<?php echo esc_html( $opts['chat_subtitle'] ); ?>
					</div>
				<?php endif; ?>
			</div>
			<button class="wcc-header-close" type="button" aria-label="<?php esc_attr_e( 'Close chat', 'webhook-chat' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
				</svg>
			</button>
		</div>
		<?php endif; ?>

		<!-- Messages -->
		<div class="wcc-messages" role="log" aria-live="polite" aria-relevant="additions"></div>

		<!-- Input -->
		<div class="wcc-input-area">
			<textarea
				class="wcc-input"
				rows="1"
				placeholder="<?php echo esc_attr( $opts['placeholder_text'] ); ?>"
				aria-label="<?php esc_attr_e( 'Type your message', 'webhook-chat' ); ?>"
			></textarea>
			<button class="wcc-send-btn" type="button" aria-label="<?php echo esc_attr( $opts['send_label'] ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
				</svg>
			</button>
		</div>
	</div><!-- .wcc-window -->

</div><!-- .wcc-chat-root -->
<style>.wcc-icon-hidden{display:none!important}</style>
