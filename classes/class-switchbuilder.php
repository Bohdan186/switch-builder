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
		// Enqueue styles when admin bar is visible on both frontend and admin.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue styles for the plugin.
	 */
	public function enqueue_styles() {
		if ( is_admin_bar_showing() ) {
			wp_enqueue_style(
				'switch-builder-styles',
				SWITCH_BUILDER_URL . 'assets/switch-builder.css',
				array(),
				'0.1'
			);
		}
	}

	/**
	 * Added Switch Builder button to WP Admin Bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar instance WP_Admin_Bar class. Comes with admin_bar_menu hook.
	 */
	public function render_button( $wp_admin_bar ) {
		$wp_admin_bar->add_node(
			array(
				'id'    => 'sb-button',
				'title' => 'Switch Builder',
				'meta'  => array(
					'class' => 'sb-button',
				),
			)
		);

		$builders = $this->get_builders_status();

		foreach ( $builders as $builder_key => $builder_data ) {
			if ( ! $builder_data['installed'] ) {
				continue;
			}

			// Only show clean builder name; visual On/Off badge is added via CSS by class.
			$title = $builder_data['name'];

			$href = $builder_data['active'] ? '#' : esc_url(
				add_query_arg(
					array(
						'sb-nonce'    => wp_create_nonce( 'switch_builder_nonce' ),
						'sb-activate' => $builder_key,
					),
					admin_url()
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => 'sb-button',
					'id'     => 'sb-' . $builder_key,
					'title'  => $title,
					'href'   => $href,
					'meta'   => array(
						'class' => $builder_data['active'] ? 'sb-active' : 'sb-inactive',
					),
				)
			);
		}
	}

	/**
	 * The method that is executed after the button is pressed. It will check which builder is active now, and switch it to another.
	 */
	public function do_button_action() {
		if ( ! isset( $_GET['sb-nonce'] ) || ! wp_verify_nonce( $_GET['sb-nonce'], 'switch_builder_nonce' ) ) { // phpcs:ignore.
			return;
		}

		if ( ! empty( $_GET['sb-activate'] ) ) {
			$builder_to_activate = sanitize_text_field( wp_unslash( $_GET['sb-activate'] ) );

			$this->deactivate_all_builders();

			$this->disable_gutenberg_settings();

			switch ( $builder_to_activate ) {
				case 'elementor':
					if ( $this->check_plugin_installed( self::$elementor ) ) {
						activate_plugin( self::$elementor );
					}
					break;
				case 'wpbakery':
					if ( $this->check_plugin_installed( self::$wpbakery ) ) {
						activate_plugin( self::$wpbakery );
					}
					break;
				case 'gutenberg':
					$this->enable_gutenberg_settings();
					break;
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
	 * Enable Gutenberg settings in Woodmart theme options.
	 */
	public function enable_gutenberg_settings() {
		$options = get_option( 'xts-woodmart-options', array() );

		$options['current_builder']               = 'native';
		$options['gutenberg_blocks']              = '1';
		$options['enable_gutenberg_for_products'] = '1';

		update_option( 'xts-woodmart-options', $options );

		$this->clear_theme_cache();

		$GLOBALS['xts_woodmart_options']['current_builder']               = 'native';
		$GLOBALS['xts_woodmart_options']['gutenberg_blocks']              = '1';
		$GLOBALS['xts_woodmart_options']['enable_gutenberg_for_products'] = '1';
	}

	/**
	 * Disable Gutenberg settings in Woodmart theme options.
	 */
	public function disable_gutenberg_settings() {
		$options = get_option( 'xts-woodmart-options', array() );

		$options['current_builder']               = 'external';
		$options['gutenberg_blocks']              = '0';
		$options['enable_gutenberg_for_products'] = '0';

		update_option( 'xts-woodmart-options', $options );

		$this->clear_theme_cache();

		$GLOBALS['xts_woodmart_options']['current_builder']               = 'external';
		$GLOBALS['xts_woodmart_options']['gutenberg_blocks']              = '0';
		$GLOBALS['xts_woodmart_options']['enable_gutenberg_for_products'] = '0';
	}

	/**
	 * Clear theme cache if needed.
	 */
	private function clear_theme_cache() {
		if ( function_exists( 'woodmart_clear_custom_css' ) ) {
			woodmart_clear_custom_css();
		}

		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		delete_transient( 'woodmart_custom_css' );
		delete_transient( 'woodmart_settings_cache' );
	}

	/**
	 * Get status of all builders.
	 *
	 * @return array Builders status data.
	 */
	private function get_builders_status() {
		$wpb_active       = is_plugin_active( self::$wpbakery );
		$el_active        = is_plugin_active( self::$elementor );
		$gutenberg_active = ! $wpb_active && ! $el_active;

		return array(
			'wpbakery'  => array(
				'name'      => 'WPBakery',
				'active'    => $wpb_active,
				'installed' => $this->check_plugin_installed( self::$wpbakery ),
			),
			'elementor' => array(
				'name'      => 'Elementor',
				'active'    => $el_active,
				'installed' => $this->check_plugin_installed( self::$elementor ),
			),
			'gutenberg' => array(
				'name'      => 'Gutenberg',
				'active'    => $gutenberg_active,
				'installed' => true,
			),
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
	 * Deactivate all builders.
	 */
	private function deactivate_all_builders() {
		if ( is_plugin_active( self::$wpbakery ) ) {
			deactivate_plugins( self::$wpbakery );
		}
		if ( is_plugin_active( self::$elementor ) ) {
			deactivate_plugins( self::$elementor );
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
