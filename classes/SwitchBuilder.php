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

	private function __construct() {
		$this->init();
	}

	static public function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_action( 'admin_bar_menu', array( $this, 'render_button' ), 100 );
		add_action( 'wp_ajax_switch-builder', array( $this, 'do_button_action' ) );
	}

	public function add_styles(){
		wp_enqueue_style( 'switch-builder-button-styles', SWITCH_BUILDER_URL . 'assets/css/style.css', array(), '0.1' );
	}

	public function add_scripts(){
		wp_enqueue_script( 'switchBuilder', SWITCH_BUILDER_URL . 'assets/js/switchBuilder.js' , array( 'jquery' ), '0.1', true );
	}

	public function render_button( $wp_admin_bar ) {
		$wp_admin_bar->add_node(
			array(
				'id'    => 'switch-builder-button',
				'title' => 'Switch Builder', 
				'href'  =>  admin_url() . 'admin-ajax.php?action=switch-builder',
				'meta'  => array(
					'class' => 'switch-builder-button',
				),
			)
		);
	}

	public function do_button_action() {
		$wpbakery  = 'js_composer/js_composer.php';
		$elementor = 'elementor/elementor.php';

		if ( is_plugin_active( $wpbakery ) ) {
			deactivate_plugins( $wpbakery );
			activate_plugin( $elementor );
		}elseif ( is_plugin_active( $elementor ) ) {
			deactivate_plugins( $elementor );
			activate_plugin( $wpbakery );
		}

		wp_die();
	}
}
