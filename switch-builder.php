<?php
/**
 * Plugin Name: Switch Builder
 * Description: Switch Builder plugin.
 * Version: 0.1
 * Domain: switch_builder
 *
 * @package Switch Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	wp_die( '-1' );
}

require_once 'classes/class-switchbuilder.php';

use SwitchBuilder\SwitchBuilder;

if ( ! defined( 'SWITCH_BUILDER_URL' ) ) {
	define( 'SWITCH_BUILDER_URL', plugin_dir_url( __FILE__ ) );
}

SwitchBuilder::get_instance();
