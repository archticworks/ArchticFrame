<?php
/**
 * Core bootstrap for ArchticFrame.
 *
 * @package ArchticFrame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin bootstrap.
 */
class ArchticFrame_Core {

	/**
	 * Register core includes and hooks.
	 *
	 * @return void
	 */
	public static function init() {
		require_once ARCHTICFRAME_PATH . 'includes/archive-cpt.php';
		require_once ARCHTICFRAME_PATH . 'includes/helpers.php';
		require_once ARCHTICFRAME_PATH . 'includes/settings.php';
		require_once ARCHTICFRAME_PATH . 'includes/template-loader.php';
		require_once ARCHTICFRAME_PATH . 'includes/shortcodes.php';

		ArchticFrame_Settings::init();
		ArchticFrame_Template_Loader::init();

		if ( is_admin() ) {
			require_once ARCHTICFRAME_PATH . 'admin/admin.php';
			ArchticFrame_Admin::init();
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_archive_assets' ) );
	}

	/**
	 * Enqueue front-end archive assets for managed archives.
	 *
	 * @return void
	 */
	public static function enqueue_archive_assets() {
		if ( ! is_post_type_archive() ) {
			return;
		}

		$post_type = archticframe_get_current_archive_post_type();

		if ( ! is_string( $post_type ) || '' === $post_type ) {
			return;
		}

		if ( ! archticframe_is_enabled_for_post_type( $post_type ) ) {
			return;
		}

		$archive_id = archticframe_get_archive_id( $post_type );

		if ( ! $archive_id ) {
			return;
		}

		wp_enqueue_style(
			'archticframe-archive',
			ARCHTICFRAME_URL . 'assets/css/archive.css',
			array(),
			ARCHTICFRAME_VERSION
		);
	}
}