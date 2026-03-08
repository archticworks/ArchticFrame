<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles front-end template loading for ArchticFrame-managed archives.
 */
class ArchticFrame_Template_Loader {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'template_include', [ __CLASS__, 'load_template' ], 99 );
	}

	/**
	 * Load the correct template for a managed archive.
	 *
	 * Template priority:
	 * 1. archtic-{post_type}.php in the active theme
	 * 2. archtic.php in the active theme
	 * 3. plugin fallback template
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public static function load_template( $template ) {
		if ( ! is_post_type_archive() ) {
			return $template;
		}

		$post_type = archticframe_get_current_archive_post_type();

		if ( '' === $post_type ) {
			return $template;
		}

		if ( ! post_type_exists( $post_type ) ) {
			return $template;
		}

		// Only run when ArchticFrame is enabled for this post type.
		if ( ! archticframe_is_enabled_for_post_type( $post_type ) ) {
			return $template;
		}

		$theme_specific_template = locate_template( 'archtic-' . $post_type . '.php' );

		if ( is_string( $theme_specific_template ) && '' !== $theme_specific_template ) {
			return $theme_specific_template;
		}

		$theme_generic_template = locate_template( 'archtic.php' );

		if ( is_string( $theme_generic_template ) && '' !== $theme_generic_template ) {
			return $theme_generic_template;
		}

		$plugin_template = trailingslashit( ARCHTICFRAME_PATH ) . 'templates/archtic.php';

		if ( file_exists( $plugin_template ) && is_readable( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}
}