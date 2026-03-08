<?php
/**
 * Front-end template loading for ArchticFrame-managed archives.
 *
 * @package ArchticFrame
 */

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
		add_filter( 'template_include', array( __CLASS__, 'load_template' ), 99 );
	}

	/**
	 * Load the correct template for a managed archive.
	 *
	 * Template priority:
	 * 1. archtic-{post_type}.php in the active theme
	 * 2. archtic.php in the active theme
	 * 3. plugin fallback template
	 *
	 * Only overrides the template when:
	 * - the current request is a real post type archive
	 * - ArchticFrame is enabled for that post type
	 * - a linked archive object exists for that post type
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public static function load_template( $template ) {
		if ( ! is_post_type_archive() ) {
			return $template;
		}

		$post_type = archticframe_get_current_archive_post_type();

		if ( ! is_string( $post_type ) || '' === $post_type ) {
			return $template;
		}

		if ( ! post_type_exists( $post_type ) ) {
			return $template;
		}

		if ( ! archticframe_is_enabled_for_post_type( $post_type ) ) {
			return $template;
		}

		$archive_post = archticframe_get_archive_post( $post_type );

		if ( ! $archive_post instanceof WP_Post ) {
			return $template;
		}

		$theme_specific_template = locate_template( array( 'archtic-' . $post_type . '.php' ) );

		if ( is_string( $theme_specific_template ) && '' !== $theme_specific_template ) {
			return $theme_specific_template;
		}

		$theme_generic_template = locate_template( array( 'archtic.php' ) );

		if ( is_string( $theme_generic_template ) && '' !== $theme_generic_template ) {
			return $theme_generic_template;
		}

		$plugin_template = trailingslashit( ARCHTICFRAME_PATH ) . 'templates/archtic.php';

		if ( is_readable( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}
}