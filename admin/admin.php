<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles ArchticFrame admin page registration and assets.
 */
class ArchticFrame_Admin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_menu_page' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Register the ArchticFrame settings page.
	 *
	 * @return void
	 */
	public static function add_menu_page() {
		add_options_page(
			__( 'ArchticFrame', 'archticframe' ),
			__( 'ArchticFrame', 'archticframe' ),
			'manage_options',
			'archticframe',
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	/**
	 * Render the settings page view.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		$view_path = trailingslashit( ARCHTICFRAME_PATH ) . 'admin/views/settings-page.php';

		if ( file_exists( $view_path ) && is_readable( $view_path ) ) {
			require_once $view_path;
		}
	}

	/**
	 * Enqueue admin assets for the ArchticFrame settings page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( 'settings_page_archticframe' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'archticframe-admin',
			trailingslashit( ARCHTICFRAME_URL ) . 'assets/css/admin.css',
			[],
			ARCHTICFRAME_VERSION
		);
	}
}