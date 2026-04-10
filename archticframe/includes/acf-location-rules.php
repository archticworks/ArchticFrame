<?php
/**
 * ACF location rules for ArchticFrame.
 *
 * Adds a custom ACF location rule so field groups can target
 * ArchticFrame archive posts by their linked target post type.
 *
 * @package ArchticFrame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register ArchticFrame ACF location types.
 *
 * @return void
 */
function archticframe_register_acf_location_types() {
	if ( ! function_exists( 'acf_register_location_type' ) || ! class_exists( 'ACF_Location' ) ) {
		return;
	}

	acf_register_location_type( 'ArchticFrame_ACF_Location_Archive_Page' );
}
add_action( 'acf/init', 'archticframe_register_acf_location_types' );

if ( class_exists( 'ACF_Location' ) && ! class_exists( 'ArchticFrame_ACF_Location_Archive_Page' ) ) {

	/**
	 * ACF location rule: Archive Page.
	 */
	class ArchticFrame_ACF_Location_Archive_Page extends ACF_Location {

		/**
		 * Initialize the location type.
		 *
		 * @return void
		 */
		public function initialize() {
			$this->name        = 'archticframe_archive_page';
			$this->label       = __( 'Archive Page', 'archticframe' );
			$this->category = __( 'Archive', 'archticframe' );
			$this->object_type = 'post';
		}

		/**
		 * Return available values for the rule dropdown.
		 *
		 * @param array $rule Rule data.
		 * @return array
		 */
		public function get_values( $rule ) {
			unset( $rule );

			$choices    = array();
			$post_types = archticframe_get_eligible_post_types();

			foreach ( $post_types as $post_type => $post_type_object ) {
				if ( ! archticframe_is_enabled_for_post_type( $post_type ) ) {
					continue;
				}

				$label = ! empty( $post_type_object->labels->name )
					? $post_type_object->labels->name
					: ucfirst( $post_type );

				$choices[ $post_type ] = $label;
			}

			return $choices;
		}

		/**
		 * Match the rule against the current screen.
		 *
		 * @param array $rule        Rule data.
		 * @param array $screen      Screen args.
		 * @param array $field_group Field group data.
		 * @return bool
		 */
		public function match( $rule, $screen, $field_group ) {
			unset( $field_group );

			if ( empty( $screen['post_id'] ) ) {
				return false;
			}

			$post_id = $screen['post_id'];

			if ( is_string( $post_id ) ) {
				$post_id = wp_unslash( $post_id );
			}

			$post_id = absint( $post_id );

			if ( ! $post_id ) {
				return false;
			}

			if ( ! archticframe_is_archive_post( $post_id ) ) {
				return false;
			}

			$current_target = archticframe_get_archive_target_post_type( $post_id );

			$rule_value = '';
			if ( isset( $rule['value'] ) && is_string( $rule['value'] ) ) {
				$rule_value = sanitize_key( wp_unslash( $rule['value'] ) );
			}

			if ( '' === $current_target || '' === $rule_value ) {
				return false;
			}

			$match = ( $current_target === $rule_value );

			if ( isset( $rule['operator'] ) && '!=' === $rule['operator'] ) {
				return ! $match;
			}

			return $match;
		}

		/**
		 * Return object subtype for admin context.
		 *
		 * @param array $rule Rule data.
		 * @return string
		 */
		public function get_object_subtype( $rule ) {
			unset( $rule );

			return archticframe_archive_cpt_slug();
		}
	}
}