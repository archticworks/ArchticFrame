<?php
/**
 * Default ArchticFrame archive template fallback.
 *
 * This template is used when the active theme does not provide:
 * - archtic-{post_type}.php
 * - archtic.php
 *
 * @package ArchticFrame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$archticframe_post_type       = archticframe_get_current_archive_post_type();
$archticframe_archive_post    = $archticframe_post_type ? archticframe_get_archive_post( $archticframe_post_type ) : null;
$archticframe_archive_content = $archticframe_post_type ? archticframe_get_archive_content( $archticframe_post_type, true ) : '';
$archticframe_is_editor       = current_user_can( 'edit_posts' );
?>

<div class="main archticframe-archive-fallback">
	<?php if ( ! empty( $archticframe_archive_content ) ) : ?>

		<?php echo wp_kses_post( $archticframe_archive_content ); ?>

	<?php elseif ( $archticframe_is_editor ) : ?>

		<section class="archticframe-notice">
			<div class="archticframe-container">
				<div class="archticframe-content-wrap">
					<h1 class="archticframe-notice-title">
						<?php esc_html_e( 'ArchticFrame', 'archticframe' ); ?>
					</h1>

					<?php if ( ! $archticframe_archive_post ) : ?>
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