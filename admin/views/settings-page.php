<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_types = archticframe_get_eligible_post_types();
$settings   = archticframe_get_archive_settings();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'ArchticFrame', 'archticframe' ); ?></h1>

	<p>
		<?php esc_html_e( 'Enable ArchticFrame archive management for supported custom post types. When enabled, ArchticFrame will create and manage a dedicated archive source post automatically.', 'archticframe' ); ?>
	</p>

	<?php if ( empty( $post_types ) ) : ?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'No eligible public custom post types were found.', 'archticframe' ); ?></p>
		</div>
	<?php else : ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'archticframe_settings_group' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<?php foreach ( $post_types as $post_type => $object ) : ?>
						<?php
						$has_archive = ! empty( $object->has_archive );
						$is_enabled  = ! empty( $settings[ $post_type ]['enabled'] );
						$post_id     = ! empty( $settings[ $post_type ]['post_id'] ) ? absint( $settings[ $post_type ]['post_id'] ) : 0;
						$edit_link   = $post_id ? get_edit_post_link( $post_id, '' ) : '';
						$post_label  = ! empty( $object->labels->name ) ? $object->labels->name : $post_type;
						?>
						<tr>
							<th scope="row">
								<label for="archticframe-<?php echo esc_attr( $post_type ); ?>">
									<?php echo esc_html( $post_label ); ?>
								</label>
							</th>
							<td>
								<label for="archticframe-<?php echo esc_attr( $post_type ); ?>">
									<input
										type="checkbox"
										name="archticframe_archives[<?php echo esc_attr( $post_type ); ?>]"
										id="archticframe-<?php echo esc_attr( $post_type ); ?>"
										value="1"
										<?php checked( $is_enabled ); ?>
										<?php disabled( ! $has_archive ); ?>
									/>
									<?php esc_html_e( 'Enable archive management', 'archticframe' ); ?>
								</label>

								<?php if ( $has_archive ) : ?>
									<p class="description">
										<?php esc_html_e( 'Archive support is enabled for this post type.', 'archticframe' ); ?>
									</p>

									<?php if ( $is_enabled && $post_id && $edit_link ) : ?>
										<p class="description">
											<a href="<?php echo esc_url( $edit_link ); ?>">
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