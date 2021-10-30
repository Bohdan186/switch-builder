<?php
/**
 * This file has a class for creating a toggle button builders.
 * 
 * @package Switch Builder.
 */

namespace SwitchBuilder;

/**
 * This class creates a toggle button builders.
 */
class SwitchBuilder {
	private static $instance;
	
	private static $wpbakery  = 'js_composer/js_composer.php';
	private static $elementor = 'elementor/elementor.php';

	private function __construct() {
		$this->init();
	}

	static public function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function get_active_builder() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			return '';
		}

		if ( is_plugin_active( self::$wpbakery ) ) {
			return 'wpbakery';
		}elseif ( is_plugin_active( self::$elementor ) ) {
			return 'elementor';
		}
	}

	public function init() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );

		add_action( 'admin_bar_menu', array( $this, 'render_button' ), 100 );

		add_action( 'wp_ajax_switch-builder', array( $this, 'do_button_action' ) );
	}

	public function add_styles(){
		wp_enqueue_style( 'switch-builder-styles', SWITCH_BUILDER_URL . 'assets/css/style.css', array(), '0.1' );
	}

	public function add_scripts(){
		wp_enqueue_script( 'switchBuilder', SWITCH_BUILDER_URL . 'assets/js/switchBuilder.js' , array( 'jquery' ), '0.1', true );
	}

	public function render_button( $wp_admin_bar ) {
		$wp_admin_bar->add_node(
			array(
				'id'    => 'sb-button',
				'title' => 'Switch Builder', 
				'href'  =>  admin_url() . 'admin-ajax.php?action=switch-builder',
				'meta'  => array(
					'class' => 'sb-button ' . $this->get_active_builder(),
				),
			)
		);
	}

	public function do_button_action() {
		if ( is_plugin_active( self::$wpbakery ) ) {
			deactivate_plugins( self::$wpbakery );
			activate_plugin( self::$elementor );
		}elseif ( is_plugin_active( self::$elementor ) ) {
			deactivate_plugins( self::$elementor );
			activate_plugin( self::$wpbakery );
		}

		wp_die();
	}
}
