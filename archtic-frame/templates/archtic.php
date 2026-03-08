<?php
/**
 * Default ArchticFrame archive template fallback.
 *
 * This template is used when the active theme does not provide:
 * - archtic-{post_type}.php
 * - archtic.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$post_type        = archticframe_get_current_archive_post_type();
$archive_post     = $post_type ? archticframe_get_archive_post( $post_type ) : null;
$archive_content  = $post_type ? archticframe_get_archive_content( $post_type, true ) : '';
$is_editor        = current_user_can( 'edit_posts' );
?>

<div class="main archticframe-archive-fallback">
	<?php if ( ! empty( $archive_content ) ) : ?>

		<?php echo $archive_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php elseif ( $is_editor ) : ?>

		<section class="archticframe-notice">
			<div class="archticframe-container">
				<div class="archticframe-content-wrap">
					<h1 class="archticframe-notice-title">
						<?php esc_html_e( 'ArchticFrame', 'archticframe' ); ?>
					</h1>

					<?php if ( ! $archive_post ) : ?>
						<p>
							<?php esc_html_e( 'No managed archive post is currently assigned for this post type. Go to Settings → ArchticFrame to enable archive management.', 'archticframe' ); ?>
						</p>
					<?php else : ?>
						<p>
							<?php esc_html_e( 'The managed archive post exists, but it does not contain any content yet. Edit the archive post and add blocks, or disable archive management in Settings → ArchticFrame.', 'archticframe' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</section>

	<?php endif; ?>
</div>

<?php get_footer(); ?>