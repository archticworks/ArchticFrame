<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all saved archive settings.
 *
 * Structure:
 * [
 *   'services' => [
 *     'enabled' => 1,
 *     'post_id' => 123,
 *   ],
 * ]
 *
 * @return array
 */
function archticframe_get_archive_settings() {
	$settings = get_option( 'archticframe_archives', [] );

	return is_array( $settings ) ? $settings : [];
}

/**
 * Get archive settings for a single post type.
 *
 * @param string $post_type Post type slug.
 * @return array
 */
function archticframe_get_post_type_archive_settings( $post_type ) {
	$post_type = is_string( $post_type ) ? $post_type : '';

	if ( '' === $post_type ) {
		return [];
	}

	$settings = archticframe_get_archive_settings();

	if ( empty( $settings[ $post_type ] ) || ! is_array( $settings[ $post_type ] ) ) {
		return [];
	}

	return $settings[ $post_type ];
}

/**
 * Check whether ArchticFrame is enabled for a post type.
 *
 * @param string $post_type Post type slug.
 * @return bool
 */
function archticframe_is_enabled_for_post_type( $post_type ) {
	$settings = archticframe_get_post_type_archive_settings( $post_type );

	return ! empty( $settings['enabled'] );
}

/**
 * Get the assigned archive post ID for a post type.
 *
 * @param string $post_type Post type slug.
 * @return int
 */
function archticframe_get_archive_post_id( $post_type ) {
	$settings = archticframe_get_post_type_archive_settings( $post_type );

	return ! empty( $settings['post_id'] ) ? absint( $settings['post_id'] ) : 0;
}

/**
 * Get the assigned archive post object for a post type.
 *
 * @param string $post_type Post type slug.
 * @return WP_Post|null
 */
function archticframe_get_archive_post( $post_type ) {
	$post_id = archticframe_get_archive_post_id( $post_type );

	if ( ! $post_id ) {
		return null;
	}

	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post || $post->post_type !== $post_type ) {
		return null;
	}

	return $post;
}

/**
 * Check whether a given post is the assigned archive post.
 *
 * @param int|WP_Post|null $post Post object or ID.
 * @return bool
 */
function archticframe_is_archive_post( $post = null ) {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$archive_id = archticframe_get_archive_post_id( $post->post_type );

	return $archive_id > 0 && (int) $archive_id === (int) $post->ID;
}

/**
 * Check whether a given post ID is a managed ArchticFrame archive post.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function archticframe_is_managed_archive_post_id( $post_id ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		return false;
	}

	return '1' === (string) get_post_meta( $post_id, '_archticframe_archive_post', true );
}

/**
 * Get eligible public post types.
 *
 * @return array
 */
function archticframe_get_eligible_post_types() {
	$post_types = get_post_types(
		[
			'public' => true,
		],
		'objects'
	);

	unset( $post_types['post'], $post_types['page'], $post_types['attachment'] );

	return $post_types;
}

/**
 * Get the current archive post type.
 *
 * @return string
 */
function archticframe_get_current_archive_post_type() {
	if ( ! is_post_type_archive() ) {
		return '';
	}

	$object = get_queried_object();

	if ( $object && ! empty( $object->name ) && is_string( $object->name ) ) {
		return $object->name;
	}

	$post_type = get_query_var( 'post_type' );

	if ( is_array( $post_type ) ) {
		$post_type = reset( $post_type );
	}

	return is_string( $post_type ) ? $post_type : '';
}

/**
 * Find a managed archive post ID for a post type.
 *
 * Includes trashed posts so they can be restored.
 *
 * @param string $post_type Post type slug.
 * @return int
 */
function archticframe_find_managed_archive_post_id( $post_type ) {
	$post_type = is_string( $post_type ) ? $post_type : '';

	if ( '' === $post_type ) {
		return 0;
	}

	$posts = get_posts(
		[
			'post_type'              => $post_type,
			'post_status'            => [ 'publish', 'draft', 'private', 'pending', 'future', 'trash' ],
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'meta_key'               => '_archticframe_archive_post',
			'meta_value'             => '1',
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		]
	);

	return ! empty( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Create or restore a managed archive post for a post type.
 *
 * @param string $post_type Post type slug.
 * @return int
 */
function archticframe_create_archive_post( $post_type ) {
	static $created_in_request = [];

	$post_type = is_string( $post_type ) ? $post_type : '';

	if ( '' === $post_type ) {
		return 0;
	}

	if ( isset( $created_in_request[ $post_type ] ) ) {
		return absint( $created_in_request[ $post_type ] );
	}

	$post_type_object = get_post_type_object( $post_type );

	if ( ! $post_type_object ) {
		return 0;
	}

	$existing_id = archticframe_find_managed_archive_post_id( $post_type );

	if ( $existing_id ) {
		$existing_post = get_post( $existing_id );

		if ( $existing_post instanceof WP_Post && 'trash' === $existing_post->post_status ) {
			wp_untrash_post( $existing_id );
		}

		$created_in_request[ $post_type ] = $existing_id;

		return $existing_id;
	}

	$label = ! empty( $post_type_object->labels->singular_name )
		? $post_type_object->labels->singular_name
		: ucfirst( $post_type );

	$title = sprintf(
		/* translators: %s: post type singular label */
		__( '%s Archive', 'archticframe' ),
		$label
	);

	$post_id = wp_insert_post(
		[
			'post_type'    => $post_type,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => '',
			'meta_input'   => [
				'_archticframe_archive_post' => '1',
			],
		],
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return 0;
	}

	$post_id = absint( $post_id );

	$created_in_request[ $post_type ] = $post_id;

	return $post_id;
}

/**
 * Get the managed archive post content.
 *
 * @param string $post_type              Optional post type slug.
 * @param bool   $apply_content_filters  Whether to apply the_content filters.
 * @return string
 */
function archticframe_get_archive_content( $post_type = '', $apply_content_filters = true ) {
	if ( '' === $post_type ) {
		$post_type = archticframe_get_current_archive_post_type();
	}

	if ( '' === $post_type ) {
		return '';
	}

	$archive_post = archticframe_get_archive_post( $post_type );

	if ( ! $archive_post instanceof WP_Post || '' === trim( $archive_post->post_content ) ) {
		return '';
	}

	$content = $archive_post->post_content;

	if ( $apply_content_filters ) {
		$content = apply_filters( 'the_content', $content );
	}

	return is_string( $content ) ? $content : '';
}

/**
 * Echo the managed archive post content.
 *
 * @param string $post_type             Optional post type slug.
 * @param bool   $apply_content_filters Whether to apply the_content filters.
 * @return void
 */
function archticframe_archive_content( $post_type = '', $apply_content_filters = true ) {
	echo archticframe_get_archive_content( $post_type, $apply_content_filters ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get the managed archive post ID for the current archive.
 *
 * @param string $post_type Optional post type slug.
 * @return int
 */
function archticframe_archive_id( $post_type = '' ) {
	if ( '' === $post_type ) {
		$post_type = archticframe_get_current_archive_post_type();
	}

	if ( '' === $post_type ) {
		return 0;
	}

	return archticframe_get_archive_post_id( $post_type );
}

/**
 * Get a field value from the managed archive post.
 *
 * Requires ACF.
 *
 * @param string $field     Field name.
 * @param string $post_type Optional post type slug.
 * @return mixed|null
 */
function archticframe_get_archive_field( $field, $post_type = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	$post_id = archticframe_archive_id( $post_type );

	if ( ! $post_id || ! is_string( $field ) || '' === $field ) {
		return null;
	}

	return get_field( $field, $post_id );
}

/**
 * Get the managed archive post title.
 *
 * @param string $post_type Optional post type slug.
 * @return string
 */
function archticframe_get_archive_title( $post_type = '' ) {
	$post_id = archticframe_archive_id( $post_type );

	if ( ! $post_id ) {
		return '';
	}

	return get_the_title( $post_id );
}

/*
|--------------------------------------------------------------------------
| Theme shorthand helpers
|--------------------------------------------------------------------------
|
| These are convenience wrappers for theme developers.
| They are only declared if the function name does not already exist.
|
*/

/**
 * Echo managed archive content.
 *
 * @param string $post_type Optional post type slug.
 * @return void
 */
if ( ! function_exists( 'archtic_content' ) ) {
	function archtic_content( $post_type = '' ) {
		archticframe_archive_content( $post_type, true );
	}
}

/**
 * Get managed archive post ID.
 *
 * @param string $post_type Optional post type slug.
 * @return int
 */
if ( ! function_exists( 'archtic_id' ) ) {
	function archtic_id( $post_type = '' ) {
		return archticframe_archive_id( $post_type );
	}
}

/**
 * Get a field from the managed archive post.
 *
 * @param string $field     Field name.
 * @param string $post_type Optional post type slug.
 * @return mixed|null
 */
if ( ! function_exists( 'archtic_field' ) ) {
	function archtic_field( $field, $post_type = '' ) {
		return archticframe_get_archive_field( $field, $post_type );
	}
}

/**
 * Get the managed archive post title.
 *
 * @param string $post_type Optional post type slug.
 * @return string
 */
if ( ! function_exists( 'archtic_title' ) ) {
	function archtic_title( $post_type = '' ) {
		return archticframe_get_archive_title( $post_type );
	}
}