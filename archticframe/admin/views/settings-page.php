<?php
/**
 * Admin settings page view for ArchticFrame.
 *
 * @package ArchticFrame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$archticframe_post_types = archticframe_get_eligible_post_types();
$archticframe_settings   = archticframe_get_archive_settings();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'ArchticFrame', 'archticframe' ); ?></h1>

	<p>
		<?php esc_html_e( 'Enable ArchticFrame archive management for supported custom post types. When enabled, ArchticFrame will create and manage a dedicated archive source post automatically.', 'archticframe' ); ?>
	</p>

	<?php if ( empty( $archticframe_post_types ) ) : ?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'No eligible public custom post types were found.', 'archticframe' ); ?></p>
		</div>
	<?php else : ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'archticframe_settings_group' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<?php foreach ( $archticframe_post_types as $archticframe_post_type => $archticframe_object ) : ?>
						<?php
						$archticframe_has_archive = ! empty( $archticframe_object->has_archive );
						$archticframe_is_enabled  = ! empty( $archticframe_settings[ $archticframe_post_type ]['enabled'] );
						$archticframe_post_id     = $archticframe_is_enabled ? archticframe_get_archive_id( $archticframe_post_type ) : 0;
						$archticframe_edit_link   = $archticframe_post_id ? get_edit_post_link( $archticframe_post_id, '' ) : '';
						$archticframe_post_label  = ! empty( $archticframe_object->labels->name ) ? $archticframe_object->labels->name : $archticframe_post_type;
						?>
						<tr>
							<th scope="row">
								<label for="archticframe-<?php echo esc_attr( $archticframe_post_type ); ?>">
									<?php echo esc_html( $archticframe_post_label ); ?>
								</label>
							</th>
							<td>
								<label for="archticframe-<?php echo esc_attr( $archticframe_post_type ); ?>">
									<input
										type="checkbox"
										name="archticframe_archives[<?php echo esc_attr( $archticframe_post_type ); ?>]"
										id="archticframe-<?php echo esc_attr( $archticframe_post_type ); ?>"
										value="1"
										<?php checked( $archticframe_is_enabled ); ?>
										<?php disabled( ! $archticframe_has_archive ); ?>
									/>
									<?php esc_html_e( 'Enable archive management', 'archticframe' ); ?>
								</label>

								<?php if ( $archticframe_has_archive ) : ?>
									<p class="description">
										<?php esc_html_e( 'Archive support is enabled for this post type.', 'archticframe' ); ?>
									</p>

									<?php if ( $archticframe_is_enabled && $archticframe_post_id && $archticframe_edit_link ) : ?>
										<p class="description">
											<a href="<?php echo esc_url( $archticframe_edit_link ); ?>">
												<?php esc_html_e( 'Edit archive post', 'archticframe' ); ?>
											</a>
										</p>
									<?php endif; ?>

									<p class="description">
										<?php esc_html_e( 'Disabling this will move the managed archive post to the trash.', 'archticframe' ); ?>
									</p>
								<?php else : ?>
									<p class="description">
										<?php esc_html_e( 'This post type currently has archive support disabled. Enable has_archive in the post type registration before using ArchticFrame for this post type.', 'archticframe' ); ?>
									</p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button( __( 'Save Settings', 'archticframe' ) ); ?>
		</form>

	<?php endif; ?>
</div>