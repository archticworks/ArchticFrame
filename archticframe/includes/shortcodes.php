<?php
/**
 * Shortcodes for ArchticFrame.
 *
 * @package ArchticFrame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register ArchticFrame shortcodes.
 *
 * @return void
 */
function archticframe_register_shortcodes() {
	add_shortcode( 'archtic_listing', 'archticframe_listing_shortcode' );
}
add_action( 'init', 'archticframe_register_shortcodes' );

/**
 * Render an archive listing for the current ArchticFrame archive context.
 *
 * Supported attributes:
 * - posts_per_page
 * - show
 * - button_text
 * - link
 *
 * Example:
 * [archtic_listing posts_per_page="6" show="image,title,excerpt,button" button_text="View more" link="button"]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function archticframe_listing_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'posts_per_page' => 12,
			'show'           => 'image,title,excerpt,button',
			'button_text'    => 'Read more',
			'link'           => 'button',
		),
		$atts,
		'archtic_listing'
	);

	$archticframe_post_type = archticframe_get_current_archive_post_type();

	if ( '' === $archticframe_post_type ) {
		return '';
	}

	$archticframe_posts_per_page = absint( $atts['posts_per_page'] );
	if ( $archticframe_posts_per_page < 1 ) {
		$archticframe_posts_per_page = 12;
	}

	$archticframe_show = array_map( 'trim', explode( ',', (string) $atts['show'] ) );
	$archticframe_show = array_filter( $archticframe_show );

	$archticframe_button_text = is_string( $atts['button_text'] ) && '' !== $atts['button_text']
		? $atts['button_text']
		: 'Read more';

	$archticframe_link = is_string( $atts['link'] ) ? sanitize_key( $atts['link'] ) : 'button';

	if ( ! in_array( $archticframe_link, array( 'none', 'button', 'card', 'both' ), true ) ) {
		$archticframe_link = 'button';
	}

	$archticframe_query = new WP_Query(
		array(
			'post_type'           => $archticframe_post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $archticframe_posts_per_page,
			'ignore_sticky_posts' => true,
		)
	);

	if ( ! $archticframe_query->have_posts() ) {
		return '';
	}

	ob_start();
	?>
	<div class="archtic-listings">
		<div class="archtic-wrap">
			<div class="archtic-grid">
				<?php while ( $archticframe_query->have_posts() ) : ?>
					<?php
					$archticframe_query->the_post();

					$archticframe_permalink = get_permalink();
					$archticframe_title     = get_the_title();
					$archticframe_excerpt   = '';

					if ( in_array( 'excerpt', $archticframe_show, true ) ) {
						$archticframe_excerpt = has_excerpt()
							? get_the_excerpt()
							: wp_trim_words( wp_strip_all_tags( get_the_content() ), 20 );
					}
					?>
					<div class="archtic-col">
						<article class="archtic-item">
							<?php if ( 'card' === $archticframe_link || 'both' === $archticframe_link ) : ?>
								<a class="archtic-item__link" href="<?php echo esc_url( $archticframe_permalink ); ?>" aria-label="<?php echo esc_attr( $archticframe_title ); ?>"></a>
							<?php endif; ?>

							<?php if ( in_array( 'image', $archticframe_show, true ) && has_post_thumbnail() ) : ?>
								<div class="archtic-item__image">
									<?php if ( 'card' === $archticframe_link || 'both' === $archticframe_link ) : ?>
										<?php the_post_thumbnail( 'large' ); ?>
									<?php elseif ( 'button' === $archticframe_link ) : ?>
										<a href="<?php echo esc_url( $archticframe_permalink ); ?>">
											<?php the_post_thumbnail( 'large' ); ?>
										</a>
									<?php else : ?>
										<?php the_post_thumbnail( 'large' ); ?>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<?php if ( in_array( 'title', $archticframe_show, true ) ) : ?>
								<h3 class="archtic-item__title">
									<?php if ( 'card' === $archticframe_link || 'both' === $archticframe_link ) : ?>
										<?php echo esc_html( $archticframe_title ); ?>
									<?php elseif ( 'button' === $archticframe_link ) : ?>
										<a href="<?php echo esc_url( $archticframe_permalink ); ?>">
											<?php echo esc_html( $archticframe_title ); ?>
										</a>
									<?php else : ?>
										<?php echo esc_html( $archticframe_title ); ?>
									<?php endif; ?>
								</h3>
							<?php endif; ?>

							<?php if ( in_array( 'excerpt', $archticframe_show, true ) && '' !== $archticframe_excerpt ) : ?>
								<div class="archtic-item__excerpt">
									<?php echo esc_html( $archticframe_excerpt ); ?>
								</div>
							<?php endif; ?>

							<?php if ( in_array( 'content', $archticframe_show, true ) ) : ?>
								<div class="archtic-item__content">
									<?php echo wp_kses_post( apply_filters( 'the_content', get_the_content() ) ); ?>
								</div>
							<?php endif; ?>

							<?php if ( in_array( 'button', $archticframe_show, true ) && ( 'button' === $archticframe_link || 'both' === $archticframe_link ) ) : ?>
								<div class="archtic-item__actions">
									<a class="archtic-item__button" href="<?php echo esc_url( $archticframe_permalink ); ?>">
										<?php echo esc_html( $archticframe_button_text ); ?>
									</a>
								</div>
							<?php endif; ?>
						</article>
					</div>
				<?php endwhile; ?>
			</div>
		</div>
	</div>
	<?php
	wp_reset_postdata();

	return ob_get_clean();
}