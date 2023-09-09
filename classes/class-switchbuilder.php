<?php
/**
 * This file has a class for creating a toggle button builders.
 *
 * @package Switch Builder.
 */

namespace SwitchBuilder;

use WP_Admin_Bar;

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

		add_action( 'admin_bar_menu', array( $this, 'render_button' ), 40 );
		add_action( 'init', array( $this, 'do_button_action' ) );
	}

	/**
	 * Added Switch Builder button to WP Admin Bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar instance WP_Admin_Bar class. Comes with admin_bar_menu hook.
	 */
	public function render_button( $wp_admin_bar ) {
		$builder_data = $this->get_active_builder();

		$wp_admin_bar->add_node(
			array(
				'id'    => 'sb-button',
				'title' => 'Switch Builder',
				'href'  => esc_url(
					add_query_arg(
						array(
							'sb-nonce' => wp_create_nonce( 'switch_builder_nonce' ),
						),
						admin_url()
					)
				),
				'meta'  => array(
					'class' => 'sb-button',
				),
			)
		);

		if ( ! empty( $builder_data ) ) {
			foreach ( $builder_data as $node_data ) {
				$wp_admin_bar->add_node(
					array(
						'parent' => 'sb-button',
						'id'     => $node_data['id'],
						'title'  => $node_data['title'],
						'href'   => $node_data['href'],
					)
				);
			}
		}
	}

	/**
	 * The method that is executed after the button is pressed. It will check which builder is active now, and switch it to another.
	 */
	public function do_button_action() {
		if ( ! isset( $_GET['sb-nonce'] ) || ! wp_verify_nonce( $_GET['sb-nonce'], 'switch_builder_nonce' ) ) { // phpcs:ignore.
			return;
		}

		$el_exist  = $this->check_plugin_installed( self::$elementor );
		$wpb_exist = $this->check_plugin_installed( self::$wpbakery );

		if ( ! empty( $_GET['sb-activate'] ) ) {
			switch ( $_GET['sb-activate'] ) {
				case 'elementor':
					activate_plugin( self::$elementor );
					break;
				case 'wpbakery':
					activate_plugin( self::$wpbakery );
					break;
			}
		} else {
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
		}

		wp_safe_redirect(
			remove_query_arg(
				array(
					'sb-nonce',
					'sb-activate',
				),
				admin_url()
			)
		);
	}

	/**
	 * Helper private method, get active builder.
	 *
	 * @return array Active builder data.
	 */
	private function get_active_builder() {
		$builder_data = array();

		if ( ! function_exists( 'is_plugin_active' ) ) {
			return $builder_data;
		}

		$el_exist  = $this->check_plugin_installed( self::$elementor );
		$wpb_exist = $this->check_plugin_installed( self::$wpbakery );

		if ( is_plugin_active( self::$wpbakery ) ) {
			$builder_data[] = array(
				'id'    => 'sb-wpb-setting',
				'title' => 'WPBakery Settings',
				'href'  => esc_url( admin_url( 'admin.php?page=vc-general' ) ),
			);
		} elseif ( is_plugin_active( self::$elementor ) ) {
			$builder_data[] = array(
				'id'    => 'sb-el-setting',
				'title' => 'Elementor Settings',
				'href'  => esc_url( admin_url( 'admin.php?page=elementor' ) ),
			);
		} else {
			if ( $wpb_exist ) {
				$builder_data[] = array(
					'id'    => 'sb-wpb-activate',
					'title' => 'Activate WPBakery',
					'href'  => esc_url(
						add_query_arg(
							array(
								'sb-nonce'    => wp_create_nonce( 'switch_builder_nonce' ),
								'sb-activate' => 'wpbakery',
							),
							admin_url()
						)
					),
				);
			}

			if ( $el_exist ) {
				$builder_data[] = array(
					'id'    => 'sb-el-activate',
					'title' => 'Activate Elementor',
					'href'  => esc_url(
						add_query_arg(
							array(
								'sb-nonce'    => wp_create_nonce( 'switch_builder_nonce' ),
								'sb-activate' => 'elementor',
							),
							admin_url()
						)
					),
				);
			}
		}

		return $builder_data;
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
