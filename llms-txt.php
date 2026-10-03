<?php
/**
 * Plugin Name:       llms.txt
 * Description:       Serves a dynamic llms.txt map of your WordPress site for AI agents.
 * Version:           1.2.0
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

define( 'LLMS_TXT_VERSION', '1.2.0' );
define( 'LLMS_TXT_FILE', __FILE__ );
define( 'LLMS_TXT_DIR', plugin_dir_path( __FILE__ ) );
define( 'LLMS_TXT_URL', plugin_dir_url( __FILE__ ) );

require_once LLMS_TXT_DIR . 'classes/class.plugin.php';

foreach ( glob( LLMS_TXT_DIR . 'classes/class.*.php' ) as $filename ) {
	if ( basename( $filename ) === 'class.plugin.php' ) {
		continue;
	}
	require_once $filename;
}

register_activation_hook(
	__FILE__,
	static function (): void {
		\Llms_Txt\Endpoint::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		flush_rewrite_rules();
	}
);
