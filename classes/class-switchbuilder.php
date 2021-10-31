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
	/**
	 * List of instance this class.
	 *
	 * @var array
	 */
	private static $instance;

	/**
	 * Relative path to the plugin file wpbakery.
	 *
	 * @var string
	 */
	private static $wpbakery = 'js_composer/js_composer.php';

	/**
	 * Relative path to the plugin file elementor.
	 *
	 * @var string
	 */
	private static $elementor = 'elementor/elementor.php';

	/**
	 * Private construct method.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Checks if an instance of the class has been created. If so it will return it, if not it will create a new one.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * This method is executed when creating an instance. Actions are added here.
	 */
	public function init() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		add_action( 'admin_enqueue_scripts', array( $this, 'add_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );

		add_action( 'admin_bar_menu', array( $this, 'render_button' ), 40 );

		add_action( 'wp_ajax_switch-builder', array( $this, 'do_button_action' ) );
	}

	/**
	 * Attaches style files.
	 */
	public function add_styles() {
		wp_enqueue_style( 'switch-builder-styles', SWITCH_BUILDER_URL . 'assets/css/style.css', array(), '0.1' );
	}

	/**
	 * Attaches script files.
	 */
	public function add_scripts() {
		wp_enqueue_script( 'switchBuilder', SWITCH_BUILDER_URL . 'assets/js/switchBuilder.js', array( 'jquery' ), '0.1', true );
	}

	/**
	 * Added Switch Builder button to WP Admin Bar.
	 *
	 * @param object $wp_admin_bar instance WP_Admin_Bar class. Comes with admin_bar_menu hook.
	 */
	public function render_button( $wp_admin_bar ) {
		$wp_admin_bar->add_node(
			array(
				'id'    => 'sb-button',
				'title' => 'Switch Builder',
				'href'  => admin_url() . 'admin-ajax.php?action=switch-builder',
				'meta'  => array(
					'class' => 'sb-button',
					'html'  => '<input class="sb-button-data" type="hidden" data-active-builder="' . $this->get_active_builder() . '" data-el-exist="' . $this->check_plugin_installed( self::$elementor ) . '" data-wpb-exist="' . $this->check_plugin_installed( self::$wpbakery ) . '">',
				),
			)
		);
	}

	/**
	 * The method that is executed after the button is pressed. It will check which builder is active now, and switch it to another.
	 */
	public function do_button_action() {
		$el_exist  = $this->check_plugin_installed( self::$elementor );
		$wpb_exist = $this->check_plugin_installed( self::$wpbakery );

		if ( is_plugin_active( self::$wpbakery ) && $el_exist ) {
			deactivate_plugins( self::$wpbakery );
			activate_plugin( self::$elementor );
		} elseif ( is_plugin_active( self::$elementor ) && $wpb_exist ) {
			deactivate_plugins( self::$elementor );
			activate_plugin( self::$wpbakery );
		} elseif ( $el_exist && ! $wpb_exist ) {
			activate_plugin( self::$elementor );
		} elseif ( $wpb_exist && ! $el_exist ) {
			activate_plugin( self::$wpbakery );
		}

		wp_die();
	}

	/**
	 * Helper private method, get active builder.
	 *
	 * @return string name active builder.
	 */
	private function get_active_builder() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			return '';
		}

		if ( is_plugin_active( self::$wpbakery ) ) {
			return 'wpbakery';
		} elseif ( is_plugin_active( self::$elementor ) ) {
			return 'elementor';
		}
	}


	/**
	 * Check if plugin is installed by getting all plugins from the plugins dir.
	 *
	 * @param string $plugin_slug plugin slug, example: 'elementor/elementor.php'.
	 *
	 * @return bool
	 */
	private function check_plugin_installed( $plugin_slug ) {
		$installed_plugins = get_plugins();

		return array_key_exists( $plugin_slug, $installed_plugins ) || in_array( $plugin_slug, $installed_plugins, true );
	}
}
