<?php
/**
 * Plugin Name: Webhook Chat
 * Plugin URI:  https://example.com/webhook-chat
 * Description: A highly customizable chat window that connects to a backend via webhook.
 * Version:     1.0.2
 * Author:      Your Name
 * License:     GPL-2.0+
 * Text Domain: webhook-chat
 */

defined( 'ABSPATH' ) || exit;

define( 'WC_CHAT_VERSION', '1.0.2' );
define( 'WC_CHAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_CHAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WC_CHAT_PLUGIN_DIR . 'includes/class-settings.php';
require_once WC_CHAT_PLUGIN_DIR . 'includes/class-chat.php';

add_action( 'plugins_loaded', array( 'Webhook_Chat', 'init' ) );

register_activation_hook( __FILE__, array( 'Webhook_Chat_Settings', 'set_defaults' ) );
