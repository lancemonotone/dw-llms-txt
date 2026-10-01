<?php
/**
 * Plugin Name: llms.txt
 * Description: Serves a dynamic llms.txt map of your WordPress site for AI agents.
 * Version:     1.1.0
 * Author:      Rus Miller
 * Text Domain: llms-txt
 *
 * @package LlmsTxt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LLMS_TXT_VERSION', '1.1.0' );
define( 'LLMS_TXT_FILE', __FILE__ );
define( 'LLMS_TXT_PATH', plugin_dir_path( __FILE__ ) );

require_once LLMS_TXT_PATH . 'classes/class-document.php';
require_once LLMS_TXT_PATH . 'classes/class-cache.php';
require_once LLMS_TXT_PATH . 'classes/class-endpoint.php';
require_once LLMS_TXT_PATH . 'classes/class-admin.php';
require_once LLMS_TXT_PATH . 'classes/class-plugin.php';

register_activation_hook(
	__FILE__,
	static function () {
		\LlmsTxt\Endpoint::register_rewrite();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		flush_rewrite_rules();
	}
);

\LlmsTxt\Plugin::instance();
