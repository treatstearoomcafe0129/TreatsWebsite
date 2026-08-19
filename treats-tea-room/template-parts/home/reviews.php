<?php
/**
 * Customer reviews.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

// Six fills the three-column desktop grid evenly; the mobile carousel is
// happy with any number.
$reviews = treats_get_reviews( 6 );

if ( ! $reviews ) {
	return;
}

$rating  = treats_schema_aggregate_rating();
$tripadv = (string) get_theme_mod( 'treats_social_tripadvisor', '' );
?>
<section class="section section--tint">
	<div class="container container--wide">
		<?php
		treats_section_heading(
			array(
				'eyebrow' => __( 'Kind words', 'treats' ),
				'title'   => __( 'What our regulars say', 'treats' ),
			)
		);
		?>

		<?php if ( $rating ) : ?>
			<p class="text-center" style="margin-top:-2rem;margin-bottom:3rem"<?php treats_reveal(); ?>>
				<span class="rating-summary">
					<?php treats_stars( $rating['ratingValue'] ); ?>
					<span>
						<?php
						printf(
							/* translators: 1: average rating, 2: number of reviews. */
							esc_html__( '%1$s out of 5 · %2$s reviews', 'treats' ),
							esc_html( number_format_i18n( $rating['ratingValue'], 1 ) ),
							esc_html( number_format_i18n( $rating['reviewCount'] ) )
						);
						?>
					</span>
				</span>
			</p>
		<?php endif; ?>

		<div class="reviews">
			<div class="reviews__track" tabindex="0" role="group" aria-label="<?php esc_attr_e( 'Customer reviews — scroll for more', 'treats' ); ?>">
				<?php foreach ( $reviews as $index => $review ) : ?>
					<?php
					$author = treats_meta( 'author', $review->ID );
					$source = treats_meta( 'source', $review->ID );
					$stars  = (float) treats_meta( 'rating', $review->ID, 5 );
					?>
					<article class="review"<?php treats_reveal( min( $index * 70, 210 ) ); ?>>
						<?php treats_stars( $stars ); ?>

						<blockquote class="review__quote" style="border:0;padding:0;font-style:normal">
							<?php echo esc_html( wp_strip_all_tags( $review->post_content ) ); ?>
						</blockquote>

						<footer class="review__meta">
							<span class="review__author"><?php echo esc_html( $author ); ?></span>
							<?php if ( '' !== $source ) : ?>
								<span class="review__source"><?php echo esc_html( $source ); ?></span>
							<?php endif; ?>
						</footer>
					</article>
				<?php endforeach; ?>
			</div>

			<div class="reviews__controls">
				<button class="reviews__btn reviews__btn--prev" type="button" aria-label="<?php esc_attr_e( 'Previous review', 'treats' ); ?>">
					<?php treats_icon( 'chevron-right', array( 'size' => 18 ) ); ?>
				</button>
				<button class="reviews__btn reviews__btn--next" type="button" aria-label="<?php esc_attr_e( 'Next review', 'treats' ); ?>">
					<?php treats_icon( 'chevron-right', array( 'size' => 18 ) ); ?>
				</button>
			</div>
		</div>

		<?php if ( '' !== $tripadv ) : ?>
			<p class="text-center" style="margin-top:2.5rem">
				<a class="link-arrow" href="<?php echo esc_url( $tripadv ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Read all our reviews', 'treats' ); ?>
					<?php treats_icon( 'arrow-right', array( 'size' => 16 ) ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</section>
