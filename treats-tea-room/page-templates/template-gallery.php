<?php
/**
 * Template Name: Gallery
 * Template Post Type: page
 *
 * Any image uploaded to this page appears in the grid, so the owner can
 * curate it simply by managing the page's media.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	treats_page_hero(
		array(
			'eyebrow'  => (string) treats_meta( 'eyebrow', 0, __( 'The tea room', 'treats' ) ),
			'title'    => get_the_title(),
			'intro'    => (string) treats_meta( 'intro' ),
			'image_id' => (int) get_post_thumbnail_id(),
		)
	);

	$images = treats_get_gallery_images( 40 );
	?>

	<section class="section">
		<div class="container container--wide">
			<?php
			$treats_content = trim( get_the_content() );

			if ( '' !== $treats_content ) {
				?>
				<div class="entry-content" style="max-width:var(--w-content);margin:0 auto 3rem">
					<?php the_content(); ?>
				</div>
				<?php
			}
			?>

			<?php if ( $images ) : ?>
				<div class="gallery-grid"<?php treats_reveal(); ?>>
					<?php foreach ( $images as $index => $image_id ) : ?>
						<a class="gallery-item<?php echo 0 === $index % 7 ? ' gallery-item--wide' : ''; ?>"
							href="<?php echo esc_url( (string) wp_get_attachment_image_url( $image_id, 'full' ) ); ?>"
							data-lightbox="<?php echo esc_url( (string) wp_get_attachment_image_url( $image_id, 'treats-hero' ) ); ?>"
							data-caption="<?php echo esc_attr( (string) wp_get_attachment_caption( $image_id ) ); ?>">
							<?php
							echo wp_get_attachment_image(
								$image_id,
								'treats-square',
								false,
								array(
									'loading'  => $index < 4 ? 'eager' : 'lazy',
									'decoding' => 'async',
									'sizes'    => '(max-width: 600px) 50vw, (max-width: 1024px) 33vw, 25vw',
								)
							);
							?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="lede text-center">
					<?php esc_html_e( 'Photographs are on their way. In the meantime, come and see the real thing.', 'treats' ); ?>
				</p>
			<?php endif; ?>
		</div>
	</section>

	<?php
endwhile;

get_template_part( 'template-parts/components/cta-banner' );

get_footer();
