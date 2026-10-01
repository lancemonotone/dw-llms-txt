<?php
/**
 * Plugin Name:       llms.txt
 * Description:       Serves a dynamic llms.txt map of your WordPress site for AI agents.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Rus Miller
 * Text Domain:       llms-txt
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LLMS_TXT_VERSION', '1.1.0' );
define( 'LLMS_TXT_FILE', __FILE__ );
define( 'LLMS_TXT_DIR', plugin_dir_path( __FILE__ ) );
define( 'LLMS_TXT_URL', plugin_dir_url( __FILE__ ) );

foreach ( glob( LLMS_TXT_DIR . 'classes/class.*.php' ) as $filename ) {
	require_once $filename;
}

/**
 * Activation: register rewrite (WP_Rewrite exists in this hook), then flush.
 */
function llms_txt_activate(): void {
	add_rewrite_rule( \Llms_Txt\Endpoint::RULE_REGEX, \Llms_Txt\Endpoint::RULE_QUERY, 'top' );
	flush_rewrite_rules();
}

/**
 * Deactivation: flush rewrite rules.
 */
function llms_txt_deactivate(): void {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'llms_txt_activate' );
register_deactivation_hook( __FILE__, 'llms_txt_deactivate' );
