<?php
/**
 * Settings registration and archive state syncing for ArchticFrame.
 *
 * @package ArchticFrame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles ArchticFrame settings registration and archive object state syncing.
 */
class ArchticFrame_Settings {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'init', array( __CLASS__, 'sync_disabled_archives_with_post_type_support' ), 20 );

		add_action( 'trashed_post', array( __CLASS__, 'sync_settings_when_archive_trashed' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'sync_settings_when_archive_restored' ) );
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
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_archive_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize and persist archive settings.
	 *
	 * @param mixed $input Raw option input.
	 * @return array
	 */
	public static function sanitize_archive_settings( $input ) {
		$output     = array();
		$post_types = archticframe_get_eligible_post_types();
		$input      = is_array( $input ) ? $input : array();

		if ( ! defined( 'ARCHTICFRAME_DOING_SETTINGS_SAVE' ) ) {
			define( 'ARCHTICFRAME_DOING_SETTINGS_SAVE', true );
		}

		foreach ( $post_types as $post_type => $object ) {
			$has_archive = ! empty( $object->has_archive );
			$is_enabled  = $has_archive && ! empty( $input[ $post_type ] );

			if ( $is_enabled ) {
				$archive_id = archticframe_create_archive_post( $post_type );

				if ( $archive_id > 0 ) {
					$output[ $post_type ] = array(
						'enabled' => 1,
					);
				}

				continue;
			}

			archticframe_trash_archive_post( $post_type );
		}

		return $output;
	}

	/**
	 * If a managed post type no longer supports archives, disable ArchticFrame for it
	 * and move its linked archive object to the trash.
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

			archticframe_trash_archive_post( $post_type );

			unset( $settings[ $post_type ] );
			$changed = true;
		}

		if ( $changed ) {
			update_option( 'archticframe_archives', $settings );
		}
	}

	/**
	 * Sync settings when an Archive Page is trashed manually.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function sync_settings_when_archive_trashed( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || ! archticframe_is_archive_post( $post_id ) ) {
			return;
		}

		$post_type = archticframe_get_archive_target_post_type( $post_id );

		if ( '' === $post_type ) {
			return;
		}

		self::set_post_type_enabled_state( $post_type, false );
	}

	/**
	 * Sync settings when an Archive Page is restored manually.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function sync_settings_when_archive_restored( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || ! archticframe_is_archive_post( $post_id ) ) {
			return;
		}

		$post_type = archticframe_get_archive_target_post_type( $post_id );

		if ( '' === $post_type ) {
			return;
		}

		$post_types = archticframe_get_eligible_post_types();
		$object     = isset( $post_types[ $post_type ] ) ? $post_types[ $post_type ] : null;

		if ( ! $object || empty( $object->has_archive ) ) {
			return;
		}

		self::set_post_type_enabled_state( $post_type, true );
	}

	/**
	 * Enable or disable ArchticFrame for a single post type in stored settings.
	 *
	 * @param string $post_type Post type slug.
	 * @param bool   $enabled   Whether the post type should be enabled.
	 * @return void
	 */
	protected static function set_post_type_enabled_state( $post_type, $enabled ) {
		$post_type = is_string( $post_type ) ? sanitize_key( $post_type ) : '';

		if ( '' === $post_type ) {
			return;
		}

		$settings = archticframe_get_archive_settings();

		if ( $enabled ) {
			$settings[ $post_type ] = array(
				'enabled' => 1,
			);
		} else {
			unset( $settings[ $post_type ] );
		}

		update_option( 'archticframe_archives', $settings );
	}
}