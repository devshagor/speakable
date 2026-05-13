<?php
/**
 * Speakable
 *
 * @package Speakable
 * @author            ThemeShape
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Speakable
 * Plugin URI:        https://wordpress.org/plugins/speakable/
 * Description:       Add a browser-based text-to-speech player to your posts and pages using the Web Speech API.
 * Version:           1.0.0
 * Author:            ThemeShape
 * Author URI:        https://profiles.wordpress.org/themeshape/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       speakable
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      6.9
 * Requires PHP:      7.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prevent constant redefinition.
if ( ! defined( 'SPEAKABLE_VERSION' ) ) {
	define( 'SPEAKABLE_VERSION', '1.0.0' );
}
if ( ! defined( 'SPEAKABLE_PLUGIN_FILE' ) ) {
	define( 'SPEAKABLE_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'SPEAKABLE_PLUGIN_DIR' ) ) {
	define( 'SPEAKABLE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SPEAKABLE_PLUGIN_URL' ) ) {
	define( 'SPEAKABLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'SPEAKABLE_OPTION_KEY' ) ) {
	define( 'SPEAKABLE_OPTION_KEY', 'speakable_settings' );
}

/**
 * Plugin activation callback.
 *
 * Sets default options on first activation.
 *
 * @since 1.0.0
 *
 * @return void
 */
function speakable_activate() {
	$defaults = array(
		'enabled_post_types' => array( 'post' ),
		'voice_name'         => '',
		'speech_rate'        => 1.0,
		'pitch'              => 1.0,
		'volume'             => 1.0,
		'button_color'       => '#d60017',
		'button_position'    => 'before',
		'show_progress_bar'  => true,
		'show_speed_control' => true,
		'sticky_player'      => true,
	);

	if ( false === get_option( SPEAKABLE_OPTION_KEY ) ) {
		add_option( SPEAKABLE_OPTION_KEY, $defaults );
	}
}
register_activation_hook( __FILE__, 'speakable_activate' );

// Load admin class (admin only).
if ( is_admin() ) {
	require_once SPEAKABLE_PLUGIN_DIR . 'includes/class-speakable-admin.php';
	new SPEAKABLE_Admin();
}

// Load frontend class (frontend only — skip admin, AJAX, REST, and CLI).
if ( ! is_admin() && ! wp_doing_ajax() && ! defined( 'REST_REQUEST' ) && ! defined( 'WP_CLI' ) ) {
	require_once SPEAKABLE_PLUGIN_DIR . 'includes/class-speakable-frontend.php';
	new SPEAKABLE_Frontend();
}

// Load Gutenberg blocks (needed on both admin and frontend for SSR).
require_once SPEAKABLE_PLUGIN_DIR . 'includes/class-speakable-blocks.php';
new SPEAKABLE_Blocks();
