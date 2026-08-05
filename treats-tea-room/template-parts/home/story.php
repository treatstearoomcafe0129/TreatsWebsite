<?php
/**
 * "Our story" split section.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$image    = (string) get_theme_mod( 'treats_story_image', '' );
$image_id = '' !== $image ? attachment_url_to_postid( $image ) : 0;
$about    = treats_get_template_page_url( 'page-templates/template-about.php' );
$founded  = (int) get_theme_mod( 'treats_seo_founding_year', 1991 );
$years    = max( 1, (int) gmdate( 'Y' ) - $founded );
?>
<section class="section">
	<div class="container container--wide">
		<div class="split split--wide-right">
			<div<?php treats_reveal( 0, 'scale' ); ?>>
				<?php if ( $image_id ) : ?>
					<?php
					treats_image(
						$image_id,
						'treats-card-tall',
						array(
							'class' => 'media--round media--soft',
							'ratio' => '4 / 5',
							'sizes' => '(max-width: 899px) 100vw, 45vw',
							'alt'   => __( 'Inside Treats Tea Room, Durham', 'treats' ),
						)
					);
					?>
				<?php else : ?>
					<div class="media media--placeholder media--round" style="aspect-ratio:4 / 5" aria-hidden="true">
						<?php treats_icon( 'cup', array( 'size' => 56 ) ); ?>
					</div>
				<?php endif; ?>
			</div>

			<div<?php treats_reveal( 80 ); ?>>
				<p class="eyebrow"><?php esc_html_e( 'Our story', 'treats' ); ?></p>

				<h2 style="margin-bottom:1.5rem">
					<?php echo esc_html( get_theme_mod( 'treats_story_title', __( 'Thirty years of good food at good prices', 'treats' ) ) ); ?>
				</h2>

				<p class="lede" style="margin-bottom:1.5rem">
					<?php echo esc_html( get_theme_mod( 'treats_story_text', __( 'Treats has been part of Durham life for three decades. We bake every morning, cook everything to order and keep prices honest — the same welcome for students, families and visitors alike.', 'treats' ) ) ); ?>
				</p>

				<div class="stats" style="margin:2.5rem 0;text-align:left">
					<div class="stat">
						<div class="stat__value" data-count-to="<?php echo esc_attr( (string) $years ); ?>"><?php echo esc_html( (string) $years ); ?></div>
						<div class="stat__label"><?php esc_html_e( 'Years on Silver Street', 'treats' ); ?></div>
					</div>
					<div class="stat">
						<div class="stat__value" data-count-to="900" data-count-suffix="+">900+</div>
						<div class="stat__label"><?php esc_html_e( 'Tripadvisor reviews', 'treats' ); ?></div>
					</div>
					<div class="stat">
						<div class="stat__value">4.5</div>
						<div class="stat__label"><?php esc_html_e( 'Average rating', 'treats' ); ?></div>
					</div>
				</div>

				<a class="link-arrow" href="<?php echo esc_url( $about ); ?>">
					<?php esc_html_e( 'More about us', 'treats' ); ?>
					<?php treats_icon( 'arrow-right', array( 'size' => 16 ) ); ?>
				</a>
			</div>
		</div>
	</div>
</section>
