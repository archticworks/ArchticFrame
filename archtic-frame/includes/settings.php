<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles ArchticFrame settings registration and archive post state syncing.
 */
class ArchticFrame_Settings {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'init', [ __CLASS__, 'sync_disabled_archives_with_post_type_support' ], 20 );
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'archticframe_settings_group',
			'archticframe_archives',
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_archive_settings' ],
				'default'           => [],
			]
		);
	}

	/**
	 * Sanitize and persist archive settings.
	 *
	 * For each eligible post type:
	 * - if enabled and archive support exists, restore or create the managed archive post
	 * - if disabled, move the managed archive post to the trash
	 *
	 * @param mixed $input Raw option input.
	 * @return array
	 */
	public static function sanitize_archive_settings( $input ) {
		$output       = [];
		$post_types   = archticframe_get_eligible_post_types();
		$old_settings = archticframe_get_archive_settings();
		$input        = is_array( $input ) ? $input : [];

		if ( ! defined( 'ARCHTICFRAME_DOING_SETTINGS_SAVE' ) ) {
			define( 'ARCHTICFRAME_DOING_SETTINGS_SAVE', true );
		}

		foreach ( $post_types as $post_type => $object ) {
			$has_archive = ! empty( $object->has_archive );
			$is_enabled  = $has_archive && ! empty( $input[ $post_type ] );
			$old_post_id = self::get_saved_post_id( $old_settings, $post_type );

			if ( $is_enabled ) {
				$post_id = self::maybe_restore_or_validate_archive_post( $old_post_id, $post_type );

				if ( ! $post_id ) {
					$post_id = archticframe_create_archive_post( $post_type );
				}

				if ( $post_id ) {
					$output[ $post_type ] = [
						'enabled' => 1,
						'post_id' => absint( $post_id ),
					];
				}

				continue;
			}

			if ( $old_post_id ) {
				self::maybe_trash_archive_post( $old_post_id, $post_type );
			}
		}

		return $output;
	}

	/**
	 * If a managed CPT no longer supports archives, disable ArchticFrame for it
	 * and move its managed archive post to the trash.
	 *
	 * @return void
	 */
	public static function sync_disabled_archives_with_post_type_support() {
		$settings = archticframe_get_archive_settings();

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return;
		}

		$post_types = archticframe_get_eligible_post_types();
		$changed    = false;

		foreach ( $settings as $post_type => $data ) {
			$object = isset( $post_types[ $post_type ] ) ? $post_types[ $post_type ] : null;

			if ( $object && ! empty( $object->has_archive ) ) {
				continue;
			}

			$post_id = ! empty( $data['post_id'] ) ? absint( $data['post_id'] ) : 0;

			if ( $post_id ) {
				self::maybe_trash_archive_post( $post_id, $post_type );
			}

			unset( $settings[ $post_type ] );
			$changed = true;
		}

		if ( $changed ) {
			update_option( 'archticframe_archives', $settings );
		}
	}

	/**
	 * Get the saved archive post ID for a post type from stored settings.
	 *
	 * @param array  $settings  Settings array.
	 * @param string $post_type Post type slug.
	 * @return int
	 */
	protected static function get_saved_post_id( $settings, $post_type ) {
		if ( empty( $settings[ $post_type ]['post_id'] ) ) {
			return 0;
		}

		return absint( $settings[ $post_type ]['post_id'] );
	}

	/**
	 * Validate an existing managed archive post and restore it if trashed.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Post type slug.
	 * @return int
	 */
	protected static function maybe_restore_or_validate_archive_post( $post_id, $post_type ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return 0;
		}

		$post = get_post( $post_id );

		if ( ! $post || $post_type !== $post->post_type ) {
			return 0;
		}

		if ( 'trash' === $post->post_status ) {
			wp_untrash_post( $post_id );
		}

		return $post_id;
	}

	/**
	 * Move a managed archive post to the trash if it is valid and not already trashed.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Expected post type slug.
	 * @return void
	 */
	protected static function maybe_trash_archive_post( $post_id, $post_type ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post || $post_type !== $post->post_type ) {
			return;
		}

		if ( 'trash' === $post->post_status ) {
			return;
		}

		wp_trash_post( $post_id );
	}
}