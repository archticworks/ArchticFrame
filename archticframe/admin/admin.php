<?php
/**
 * Admin page registration and assets for ArchticFrame.
 *
 * @package ArchticFrame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles ArchticFrame admin page registration and assets.
 */
class ArchticFrame_Admin {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const SETTINGS_SLUG = 'archticframe-settings';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register the ArchticFrame settings submenu.
	 *
	 * Places the settings screen under the Archive Pages CPT menu.
	 *
	 * @return void
	 */
	public static function add_menu_page() {
		add_submenu_page(
			'edit.php?post_type=' . archticframe_archive_cpt_slug(),
			__( 'ArchticFrame Settings', 'archticframe' ),
			__( 'Settings', 'archticframe' ),
			'manage_options',
			self::SETTINGS_SLUG,
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings page view.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		$view_path = trailingslashit( ARCHTICFRAME_PATH ) . 'admin/views/settings-page.php';

		if ( is_readable( $view_path ) ) {
			require_once $view_path;
		}
	}

	/**
	 * Enqueue admin assets for ArchticFrame admin screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		$allowed_hooks = array(
			'archticframe_archive_page_' . self::SETTINGS_SLUG,
			'edit.php',
			'post.php',
			'post-new.php',
		);

		if ( ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$is_archive_cpt_screen = (
			archticframe_archive_cpt_slug() === $screen->post_type
		);

		$is_archive_settings_screen = (
			'archticframe_archive_page_' . self::SETTINGS_SLUG === $hook_suffix
		);

		if ( ! $is_archive_cpt_screen && ! $is_archive_settings_screen ) {
			return;
		}

		wp_enqueue_style(
			'archticframe-admin',
			trailingslashit( ARCHTICFRAME_URL ) . 'assets/css/admin.css',
			array(),
			ARCHTICFRAME_VERSION
		);
	}
}