<?php
/**
 * Internal archive CPT registration and admin UI for ArchticFrame.
 *
 * Registers the internal post type used to store managed archive content
 * for target post types.
 *
 * Example:
 * - post_type: archticframe_archive
 * - meta: _archticframe_target_post_type = projects
 *
 * The target post type relationship is managed automatically by
 * ArchticFrame settings/resolver logic and is not editable manually.
 *
 * @package ArchticFrame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the internal archive CPT slug.
 *
 * @return string
 */
function archticframe_archive_cpt_slug() {
	return 'archticframe_archive';
}

/**
 * Return the meta key storing the linked target post type.
 *
 * @return string
 */
function archticframe_archive_meta_key() {
	return '_archticframe_target_post_type';
}

/**
 * Register the internal archive CPT.
 *
 * @return void
 */
function archticframe_register_archive_cpt() {
	$labels = array(
		'name'                  => __( 'Archive Pages', 'archticframe' ),
		'singular_name'         => __( 'Archive Page', 'archticframe' ),
		'menu_name'             => __( 'Archive Pages', 'archticframe' ),
		'name_admin_bar'        => __( 'Archive Page', 'archticframe' ),
		'edit_item'             => __( 'Edit Archive Page', 'archticframe' ),
		'view_item'             => __( 'View Archive', 'archticframe' ),
		'search_items'          => __( 'Search Archive Pages', 'archticframe' ),
		'not_found'             => __( 'No archive pages found.', 'archticframe' ),
		'not_found_in_trash'    => __( 'No archive pages found in Trash.', 'archticframe' ),
		'all_items'             => __( 'Archive Pages', 'archticframe' ),
		'archives'              => __( 'Archive Page Archives', 'archticframe' ),
		'attributes'            => __( 'Archive Page Attributes', 'archticframe' ),
		'insert_into_item'      => __( 'Insert into archive page', 'archticframe' ),
		'uploaded_to_this_item' => __( 'Uploaded to this archive page', 'archticframe' ),
	);

	$args = array(
		'labels'              => $labels,
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => false,
		'show_in_rest'        => true,
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'menu_icon'           => 'dashicons-layout',
		'supports'            => array(
			'title',
			'editor',
			'revisions',
			'custom-fields',
		),
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'capabilities'        => array(
			'create_posts' => 'do_not_allow',
		),
	);

	register_post_type( archticframe_archive_cpt_slug(), $args );
}
add_action( 'init', 'archticframe_register_archive_cpt' );

/**
 * Authorisation callback for archive meta.
 *
 * @return bool
 */
function archticframe_archive_meta_auth_callback() {
	return current_user_can( 'edit_posts' );
}

/**
 * Sanitize the stored target post type meta value.
 *
 * This meta is internal and managed automatically by ArchticFrame.
 *
 * @param mixed $value Meta value.
 * @return string
 */
function archticframe_archive_meta_sanitize_callback( $value ) {
	$value      = is_string( $value ) ? sanitize_key( $value ) : '';
	$post_types = archticframe_get_eligible_post_types();

	if ( '' === $value || ! isset( $post_types[ $value ] ) ) {
		return '';
	}

	return $value;
}

/**
 * Register the archive target meta field.
 *
 * This is stored internally and is not exposed in the editor UI.
 *
 * @return void
 */
function archticframe_register_archive_meta() {
	register_post_meta(
		archticframe_archive_cpt_slug(),
		archticframe_archive_meta_key(),
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'archticframe_archive_meta_sanitize_callback',
			'auth_callback'     => 'archticframe_archive_meta_auth_callback',
		)
	);
}
add_action( 'init', 'archticframe_register_archive_meta' );

/**
 * Remove the "Add New" submenu for Archive Pages.
 *
 * @return void
 */
function archticframe_remove_archive_add_new_submenu() {
	remove_submenu_page(
		'edit.php?post_type=' . archticframe_archive_cpt_slug(),
		'post-new.php?post_type=' . archticframe_archive_cpt_slug()
	);
}
add_action( 'admin_menu', 'archticframe_remove_archive_add_new_submenu', 999 );

/**
 * Prevent direct access to the new Archive Page screen.
 *
 * Archive objects should only be created through plugin settings logic.
 *
 * @return void
 */
function archticframe_block_new_archive_page_screen() {
	global $pagenow;

	if ( ! is_admin() ) {
		return;
	}

	if ( 'post-new.php' !== $pagenow ) {
		return;
	}

	$post_type = '';
	if ( isset( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading admin screen context only.
		$post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading admin screen context only.
	}

	if ( archticframe_archive_cpt_slug() !== $post_type ) {
		return;
	}

	wp_safe_redirect( admin_url( 'edit.php?post_type=' . archticframe_archive_cpt_slug() ) );
	exit;
}
add_action( 'admin_init', 'archticframe_block_new_archive_page_screen' );

/**
 * Get the real archive URL for an Archive Page object.
 *
 * @param int|WP_Post|null $post Post object or ID.
 * @return string
 */
function archticframe_get_archive_page_view_url( $post = null ) {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	if ( archticframe_archive_cpt_slug() !== $post->post_type ) {
		return '';
	}

	$target_post_type = archticframe_get_archive_target_post_type( $post );

	if ( '' === $target_post_type || ! post_type_exists( $target_post_type ) ) {
		return '';
	}

	$archive_url = get_post_type_archive_link( $target_post_type );

	return is_string( $archive_url ) ? $archive_url : '';
}

/**
 * Filter updated messages for the internal archive CPT.
 *
 * @param array $messages Existing post updated messages.
 * @return array
 */
function archticframe_archive_updated_messages( $messages ) {
	$messages[ archticframe_archive_cpt_slug() ] = array(
		0  => '',
		1  => __( 'Archive Page updated.', 'archticframe' ),
		4  => __( 'Archive Page updated.', 'archticframe' ),
		6  => __( 'Archive Page published.', 'archticframe' ),
		7  => __( 'Archive Page saved.', 'archticframe' ),
		8  => __( 'Archive Page submitted.', 'archticframe' ),
		10 => __( 'Archive Page draft updated.', 'archticframe' ),
	);

	return $messages;
}
add_filter( 'post_updated_messages', 'archticframe_archive_updated_messages' );

/**
 * Filter row actions for Archive Pages.
 *
 * Removes irrelevant internal-object actions and adds a meaningful
 * "View Archive" link to the real archive URL.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Current post object.
 * @return array
 */
function archticframe_filter_archive_row_actions( $actions, $post ) {
	if ( ! $post instanceof WP_Post ) {
		return $actions;
	}

	if ( archticframe_archive_cpt_slug() !== $post->post_type ) {
		return $actions;
	}

	$archive_url = archticframe_get_archive_page_view_url( $post );

	unset( $actions['view'] );
	unset( $actions['inline'] );
	unset( $actions['duplicate'] );
	unset( $actions['duplicate_this'] );

	if ( '' !== $archive_url ) {
		$actions['archticframe_view_archive'] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $archive_url ),
			esc_html__( 'View Archive', 'archticframe' )
		);
	}

	return $actions;
}
add_filter( 'post_row_actions', 'archticframe_filter_archive_row_actions', 20, 2 );

/**
 * Add a custom admin column showing the linked target post type.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function archticframe_archive_columns( $columns ) {
	$updated = array();

	foreach ( $columns as $key => $label ) {
		$updated[ $key ] = $label;

		if ( 'title' === $key ) {
			$updated['archticframe_target_post_type'] = __( 'Archive For', 'archticframe' );
		}
	}

	return $updated;
}
add_filter( 'manage_' . archticframe_archive_cpt_slug() . '_posts_columns', 'archticframe_archive_columns' );

/**
 * Render custom admin column content for Archive Pages.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 * @return void
 */
function archticframe_render_archive_column( $column, $post_id ) {
	if ( 'archticframe_target_post_type' !== $column ) {
		return;
	}

	$target_post_type = archticframe_get_archive_target_post_type( $post_id );

	if ( '' === $target_post_type ) {
		echo '&mdash;';
		return;
	}

	$post_type_object = get_post_type_object( $target_post_type );

	if ( $post_type_object && ! empty( $post_type_object->labels->name ) ) {
		echo esc_html( $post_type_object->labels->name );
		return;
	}

	echo esc_html( $target_post_type );
}
add_action( 'manage_' . archticframe_archive_cpt_slug() . '_posts_custom_column', 'archticframe_render_archive_column', 10, 2 );

/**
 * Add a "View Archive" action to the admin bar when editing an Archive Page.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 * @return void
 */
function archticframe_add_admin_bar_view_archive_link( $wp_admin_bar ) {
	if ( ! is_admin() ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || archticframe_archive_cpt_slug() !== $screen->post_type ) {
		return;
	}

	if ( 'post' !== $screen->base ) {
		return;
	}

	$post_id = 0;
	if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading admin screen context only.
		$post_id = absint( wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading admin screen context only.
	}

	if ( ! $post_id ) {
		return;
	}

	$archive_url = archticframe_get_archive_page_view_url( $post_id );

	if ( '' === $archive_url ) {
		return;
	}

	$wp_admin_bar->add_node(
		array(
			'id'    => 'archticframe-view-archive',
			'title' => __( 'View Archive', 'archticframe' ),
			'href'  => $archive_url,
			'meta'  => array(
				'target' => '_blank',
				'rel'    => 'noopener noreferrer',
			),
		)
	);
}
add_action( 'admin_bar_menu', 'archticframe_add_admin_bar_view_archive_link', 80 );

/**
 * Hide the sample permalink and preview UI for Archive Pages.
 *
 * These objects are internal and should not present their own permalink UI.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return void
 */
function archticframe_hide_archive_permalink_ui( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || archticframe_archive_cpt_slug() !== $screen->post_type ) {
		return;
	}

	$css = '
		.post-type-archticframe_archive #edit-slug-box,
		.post-type-archticframe_archive #preview-action,
		.post-type-archticframe_archive #view-post-btn,
		.post-type-archticframe_archive .editor-post-preview,
		.post-type-archticframe_archive .components-button.editor-post-preview__button-toggle,
		.post-type-archticframe_archive .editor-post-url,
		.post-type-archticframe_archive .edit-post-post-link {
			display: none !important;
		}
	';

	wp_register_style(
		'archticframe-admin',
		false,
		array(),
		defined( 'ARCHTICFRAME_VERSION' ) ? ARCHTICFRAME_VERSION : '1.1.1'
	);

	wp_enqueue_style( 'archticframe-admin' );
	wp_add_inline_style( 'archticframe-admin', $css );
}
add_action( 'admin_enqueue_scripts', 'archticframe_hide_archive_permalink_ui' );