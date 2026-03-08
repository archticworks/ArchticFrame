<?php
/**
 * Archive object resolver functions for ArchticFrame.
 *
 * Handles finding, creating, restoring, and removing internal archive objects
 * linked to managed post type archives.
 *
 * @package ArchticFrame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get eligible public post types that ArchticFrame can manage.
 *
 * @return array<string,WP_Post_Type>
 */
function archticframe_get_eligible_post_types() {
	$post_types = get_post_types(
		array(
			'public' => true,
		),
		'objects'
	);

	unset(
		$post_types['post'],
		$post_types['page'],
		$post_types['attachment'],
		$post_types[ archticframe_archive_cpt_slug() ]
	);

	return $post_types;
}

/**
 * Get the current archive post type slug.
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

	return is_string( $post_type ) ? sanitize_key( $post_type ) : '';
}

/**
 * Get the target post type slug assigned to an archive object.
 *
 * @param int|WP_Post|null $post Post object or ID.
 * @return string
 */
function archticframe_get_archive_target_post_type( $post = null ) {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	if ( archticframe_archive_cpt_slug() !== $post->post_type ) {
		return '';
	}

	$target = get_post_meta( $post->ID, archticframe_archive_meta_key(), true );

	return is_string( $target ) ? sanitize_key( $target ) : '';
}

/**
 * Check whether a post is an ArchticFrame archive object.
 *
 * @param int|WP_Post|null $post Post object or ID.
 * @return bool
 */
function archticframe_is_archive_post( $post = null ) {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	return archticframe_archive_cpt_slug() === $post->post_type;
}

/**
 * Find an archive object ID for a target post type.
 *
 * Includes trashed posts so existing archive objects can be restored.
 *
 * @param string $post_type Post type slug.
 * @return int
 */
function archticframe_find_archive_post_id( $post_type ) {
	$post_type = is_string( $post_type ) ? sanitize_key( $post_type ) : '';

	if ( '' === $post_type ) {
		return 0;
	}

	$posts = get_posts(
		array(
			'post_type'              => archticframe_archive_cpt_slug(),
			'post_status'            => array( 'publish', 'draft', 'private', 'pending', 'future', 'trash' ),
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'meta_key'               => archticframe_archive_meta_key(),
			'meta_value'             => $post_type,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => false,
		)
	);

	return ! empty( $posts ) ? absint( $posts[0] ) : 0;
}

/**
 * Get the published archive object ID for a target post type.
 *
 * @param string $post_type Post type slug.
 * @return int
 */
function archticframe_get_archive_id( $post_type ) {
	$post_type = is_string( $post_type ) ? sanitize_key( $post_type ) : '';

	if ( '' === $post_type ) {
		return 0;
	}

	$posts = get_posts(
		array(
			'post_type'              => archticframe_archive_cpt_slug(),
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'meta_key'               => archticframe_archive_meta_key(),
			'meta_value'             => $post_type,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => false,
		)
	);

	return ! empty( $posts ) ? absint( $posts[0] ) : 0;
}

/**
 * Get the published archive object post for a target post type.
 *
 * @param string $post_type Post type slug.
 * @return WP_Post|null
 */
function archticframe_get_archive_post( $post_type ) {
	$post_id = archticframe_get_archive_id( $post_type );

	if ( ! $post_id ) {
		return null;
	}

	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post ) {
		return null;
	}

	if ( archticframe_archive_cpt_slug() !== $post->post_type ) {
		return null;
	}

	return $post;
}

/**
 * Create or restore an archive object for a target post type.
 *
 * @param string $post_type Post type slug.
 * @return int
 */
function archticframe_create_archive_post( $post_type ) {
	static $created_in_request = array();

	$post_type = is_string( $post_type ) ? sanitize_key( $post_type ) : '';

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

	$existing_id = archticframe_find_archive_post_id( $post_type );

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
		/* translators: %s: post type singular label. */
		__( '%s Archive', 'archticframe' ),
		$label
	);

	$post_id = wp_insert_post(
		array(
			'post_type'    => archticframe_archive_cpt_slug(),
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => '',
			'meta_input'   => array(
				archticframe_archive_meta_key() => $post_type,
			),
		),
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
 * Trash the archive object linked to a target post type.
 *
 * @param string $post_type Post type slug.
 * @return bool
 */
function archticframe_trash_archive_post( $post_type ) {
	$post_id = archticframe_find_archive_post_id( $post_type );

	if ( ! $post_id ) {
		return false;
	}

	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	if ( 'trash' === $post->post_status ) {
		return true;
	}

	$trashed = wp_trash_post( $post_id );

	return ( $trashed instanceof WP_Post );
}