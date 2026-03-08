<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin and front-end behaviour for managed archive posts.
 */
class ArchticFrame_Archive_Post {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'display_post_states', [ __CLASS__, 'add_archive_post_state' ], 10, 2 );
		add_filter( 'post_row_actions', [ __CLASS__, 'filter_post_row_actions' ], 10, 2 );
		add_filter( 'page_row_actions', [ __CLASS__, 'filter_post_row_actions' ], 10, 2 );
		add_filter( 'post_updated_messages', [ __CLASS__, 'filter_post_updated_messages' ] );

		add_action( 'template_redirect', [ __CLASS__, 'redirect_archive_post_single' ] );
		add_action( 'pre_get_posts', [ __CLASS__, 'order_archive_post_first_in_admin' ] );
		add_action( 'pre_get_posts', [ __CLASS__, 'exclude_archive_post_from_frontend_loop' ] );

		add_action( 'trashed_post', [ __CLASS__, 'disable_settings_when_archive_post_trashed' ] );
		add_action( 'untrashed_post', [ __CLASS__, 'enable_settings_when_archive_post_restored' ] );
		add_action( 'before_delete_post', [ __CLASS__, 'cleanup_settings_when_archive_post_deleted' ] );

		add_action( 'admin_notices', [ __CLASS__, 'render_archive_post_notice' ] );
		add_action( 'admin_bar_menu', [ __CLASS__, 'update_admin_bar_view_link' ], 999 );
	}

	/**
	 * Add an "Archive" state label in the post list.
	 *
	 * @param array   $post_states Existing post states.
	 * @param WP_Post $post        Current post.
	 * @return array
	 */
	public static function add_archive_post_state( $post_states, $post ) {
		if ( archticframe_is_archive_post( $post ) ) {
			$post_states['archticframe_archive'] = __( 'Archive', 'archticframe' );
		}

		return $post_states;
	}

	/**
	 * Adjust row actions for managed archive posts.
	 *
	 * @param array   $actions Existing row actions.
	 * @param WP_Post $post    Current post.
	 * @return array
	 */
	public static function filter_post_row_actions( $actions, $post ) {
		if ( ! archticframe_is_archive_post( $post ) ) {
			return $actions;
		}

		$archive_url = self::get_archive_url( $post );

		if ( isset( $actions['edit'] ) ) {
			$actions['edit'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( $post->ID ) ),
				esc_html__( 'Edit Archive', 'archticframe' )
			);
		}

		if ( isset( $actions['inline hide-if-no-js'] ) ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		if ( isset( $actions['view'] ) && $archive_url ) {
			$actions['view'] = sprintf(
				'<a href="%s" rel="bookmark">%s</a>',
				esc_url( $archive_url ),
				esc_html__( 'View Archive', 'archticframe' )
			);
		}

		return $actions;
	}

	/**
	 * Update post updated messages for managed archive posts.
	 *
	 * @param array $messages Updated messages.
	 * @return array
	 */
	public static function filter_post_updated_messages( $messages ) {
		$post = get_post();

		if ( ! $post || ! archticframe_is_archive_post( $post ) ) {
			return $messages;
		}

		$archive_url = self::get_archive_url( $post );

		if ( ! $archive_url || empty( $messages[ $post->post_type ] ) || ! is_array( $messages[ $post->post_type ] ) ) {
			return $messages;
		}

		$archive_label = sprintf(
			/* translators: %s: post type singular label */
			__( 'View %s Archive', 'archticframe' ),
			self::get_post_type_singular_label( $post->post_type )
		);

		foreach ( $messages[ $post->post_type ] as $key => $message ) {
			if ( empty( $message ) || ! is_string( $message ) ) {
				continue;
			}

			$messages[ $post->post_type ][ $key ] = preg_replace(
				'/<a href="[^"]*">[^<]*<\/a>/',
				'<a href="' . esc_url( $archive_url ) . '">' . esc_html( $archive_label ) . '</a>',
				$message
			);
		}

		return $messages;
	}

	/**
	 * Show an admin notice on the archive post edit screen.
	 *
	 * @return void
	 */
	public static function render_archive_post_notice() {
		if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		global $pagenow;

		if ( 'post.php' !== $pagenow ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading current post ID on edit screen only.
		$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || ! archticframe_is_archive_post( $post ) ) {
			return;
		}

		$archive_url = self::get_archive_url( $post );
		$label       = self::get_post_type_label( $post->post_type );
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'ArchticFrame:', 'archticframe' ); ?></strong>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: post type label */
						__( 'This post is currently being used as the archive page for %s.', 'archticframe' ),
						$label
					)
				);
				?>
				<?php if ( $archive_url ) : ?>
					<a href="<?php echo esc_url( $archive_url ); ?>">
						<?php esc_html_e( 'View Archive', 'archticframe' ); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Update the admin bar "View" link to point to the archive URL.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	public static function update_admin_bar_view_link( $wp_admin_bar ) {
		if ( ! is_admin() ) {
			return;
		}

		global $pagenow, $post;

		if ( 'post.php' !== $pagenow || ! $post instanceof WP_Post ) {
			return;
		}

		if ( ! archticframe_is_archive_post( $post ) ) {
			return;
		}

		$archive_url = self::get_archive_url( $post );

		if ( ! $archive_url ) {
			return;
		}

		$wp_admin_bar->add_node(
			[
				'id'    => 'view',
				'title' => sprintf(
					/* translators: %s: post type singular label */
					__( 'View %s Archive', 'archticframe' ),
					self::get_post_type_singular_label( $post->post_type )
				),
				'href'  => $archive_url,
			]
		);
	}

	/**
	 * Redirect direct access to the archive post single URL back to the archive.
	 *
	 * @return void
	 */
	public static function redirect_archive_post_single() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();

		if ( ! $post instanceof WP_Post || 'trash' === $post->post_status ) {
			return;
		}

		if ( ! archticframe_is_archive_post( $post ) ) {
			return;
		}

		$archive_url = self::get_archive_url( $post );

		if ( $archive_url ) {
			wp_safe_redirect( $archive_url, 301 );
			exit;
		}
	}

	/**
	 * Pin the archive post to the top of the admin list table.
	 *
	 * @param WP_Query $query Query instance.
	 * @return void
	 */
	public static function order_archive_post_first_in_admin( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		global $pagenow;

		if ( 'edit.php' !== $pagenow ) {
			return;
		}

		$post_type = $query->get( 'post_type' );

		if ( empty( $post_type ) || ! is_string( $post_type ) ) {
			return;
		}

		if ( ! archticframe_is_enabled_for_post_type( $post_type ) ) {
			return;
		}

		$archive_id = archticframe_get_archive_post_id( $post_type );

		if ( ! $archive_id ) {
			return;
		}

		add_filter(
			'posts_orderby',
			function( $orderby, $wp_query ) use ( $query, $archive_id ) {
				if ( $wp_query !== $query ) {
					return $orderby;
				}

				global $wpdb;

				$archive_id = absint( $archive_id );

				return "CASE WHEN {$wpdb->posts}.ID = {$archive_id} THEN 0 ELSE 1 END, {$wpdb->posts}.post_date DESC";
			},
			10,
			2
		);
	}

	/**
	 * Exclude the managed archive post from the front-end archive loop.
	 *
	 * @param WP_Query $query Query instance.
	 * @return void
	 */
	public static function exclude_archive_post_from_frontend_loop( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );

		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}

		if ( empty( $post_type ) || ! is_string( $post_type ) ) {
			$post_type = archticframe_get_current_archive_post_type();
		}

		if ( ! $post_type || ! archticframe_is_enabled_for_post_type( $post_type ) ) {
			return;
		}

		$archive_post_id = archticframe_get_archive_post_id( $post_type );

		if ( ! $archive_post_id ) {
			return;
		}

		$post__not_in   = (array) $query->get( 'post__not_in', [] );
		$post__not_in[] = absint( $archive_post_id );

		$query->set( 'post__not_in', array_values( array_unique( array_filter( $post__not_in ) ) ) );
	}

	/**
	 * Disable settings when a managed archive post is moved to trash.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function disable_settings_when_archive_post_trashed( $post_id ) {
		if ( defined( 'ARCHTICFRAME_DOING_SETTINGS_SAVE' ) && ARCHTICFRAME_DOING_SETTINGS_SAVE ) {
			return;
		}

		$post_id = absint( $post_id );

		if ( ! self::is_managed_archive_post_id( $post_id ) ) {
			return;
		}

		self::remove_post_from_settings( $post_id );
	}

	/**
	 * Re-enable settings when a managed archive post is restored from trash.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function enable_settings_when_archive_post_restored( $post_id ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );

		if ( ! $post || ! self::is_managed_archive_post_id( $post_id ) ) {
			return;
		}

		$settings = archticframe_get_archive_settings();

		$settings[ $post->post_type ] = [
			'enabled' => 1,
			'post_id' => $post_id,
		];

		update_option( 'archticframe_archives', $settings );
		flush_rewrite_rules();
	}

	/**
	 * Clean up settings when a managed archive post is permanently deleted.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function cleanup_settings_when_archive_post_deleted( $post_id ) {
		if ( defined( 'ARCHTICFRAME_DOING_SETTINGS_SAVE' ) && ARCHTICFRAME_DOING_SETTINGS_SAVE ) {
			return;
		}

		$post_id = absint( $post_id );

		if ( ! self::is_managed_archive_post_id( $post_id ) ) {
			return;
		}

		self::remove_post_from_settings( $post_id );
	}

	/**
	 * Check whether a post ID belongs to a managed archive post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	protected static function is_managed_archive_post_id( $post_id ) {
		if ( ! $post_id ) {
			return false;
		}

		return '1' === (string) get_post_meta( $post_id, '_archticframe_archive_post', true );
	}

	/**
	 * Remove a managed archive post from saved settings.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	protected static function remove_post_from_settings( $post_id ) {
		$settings = archticframe_get_archive_settings();
		$changed  = false;

		foreach ( $settings as $post_type => $data ) {
			$saved_post_id = ! empty( $data['post_id'] ) ? absint( $data['post_id'] ) : 0;

			if ( $saved_post_id === $post_id ) {
				unset( $settings[ $post_type ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			update_option( 'archticframe_archives', $settings );
			flush_rewrite_rules();
		}
	}

	/**
	 * Get the archive URL for a managed archive post.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	protected static function get_archive_url( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$url = get_post_type_archive_link( $post->post_type );

		return $url ? $url : '';
	}

	/**
	 * Get plural post type label.
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	protected static function get_post_type_label( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );

		if ( $post_type_object && ! empty( $post_type_object->labels->name ) ) {
			return $post_type_object->labels->name;
		}

		return ucfirst( $post_type );
	}

	/**
	 * Get singular post type label.
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	protected static function get_post_type_singular_label( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );

		if ( $post_type_object && ! empty( $post_type_object->labels->singular_name ) ) {
			return $post_type_object->labels->singular_name;
		}

		return ucfirst( $post_type );
	}
}