<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ArchticFrame_Core {

	public static function init() {
		require_once ARCHTICFRAME_PATH . 'includes/helpers.php';
		require_once ARCHTICFRAME_PATH . 'includes/settings.php';
		require_once ARCHTICFRAME_PATH . 'includes/archive-post.php';
		require_once ARCHTICFRAME_PATH . 'includes/template-loader.php';

		ArchticFrame_Settings::init();
		ArchticFrame_Archive_Post::init();
		ArchticFrame_Template_Loader::init();

		if ( is_admin() ) {
			require_once ARCHTICFRAME_PATH . 'admin/admin.php';
			ArchticFrame_Admin::init();
		}

		add_action( 'wp_enqueue_scripts', function () {

			if ( ! is_post_type_archive() ) {
				return;
			}

			$post_type = archticframe_get_current_archive_post_type();

			if ( ! $post_type ) {
				return;
			}

			if ( ! archticframe_is_enabled_for_post_type( $post_type ) ) {
				return;
			}

			wp_enqueue_style(
				'archticframe-archive',
				ARCHTICFRAME_URL . 'assets/css/archive.css',
				[],
				ARCHTICFRAME_VERSION
			);

		} );
	}
}