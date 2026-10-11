<?php
/**
 * Gallery strip and Instagram feed.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$instagram = treats_get_instagram_feed();
$handle    = trim( (string) get_theme_mod( 'treats_instagram_handle', '' ) );
$images    = treats_get_gallery_images( 8 );

if ( ! $instagram && ! $images ) {
	return;
}

$profile = '' !== $handle ? 'https://www.instagram.com/' . ltrim( $handle, '@' ) . '/' : (string) get_theme_mod( 'treats_social_instagram', '' );
?>
<section class="section section--sm">
	<div class="container container--wide">
		<?php
		treats_section_heading(
			array(
				'eyebrow' => $instagram ? __( 'From our Instagram', 'treats' ) : __( 'The tea room', 'treats' ),
				'title'   => $instagram ? __( 'What came out of the kitchen today', 'treats' ) : __( 'A look inside Treats', 'treats' ),
			)
		);
		?>

		<?php if ( $instagram ) : ?>
			<div class="instagram-grid"<?php treats_reveal(); ?>>
				<?php foreach ( $instagram as $post ) : ?>
					<a class="instagram-item" href="<?php echo esc_url( $post['link'] ); ?>" target="_blank" rel="noopener">
						<img src="<?php echo esc_url( $post['image'] ); ?>" alt="<?php echo esc_attr( $post['caption'] ); ?>" loading="lazy" decoding="async" width="400" height="400">
						<span class="instagram-item__icon"><?php treats_icon( 'instagram', array( 'size' => 26 ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="gallery-grid"<?php treats_reveal(); ?>>
				<?php foreach ( $images as $index => $image_id ) : ?>
					<a class="gallery-item<?php echo 0 === $index ? ' gallery-item--wide' : ''; ?>"
						href="<?php echo esc_url( (string) wp_get_attachment_image_url( $image_id, 'full' ) ); ?>"
						data-lightbox="<?php echo esc_url( (string) wp_get_attachment_image_url( $image_id, 'treats-hero' ) ); ?>">
						<?php
						echo wp_get_attachment_image(
							$image_id,
							'treats-square',
							false,
							array(
								'loading'  => 'lazy',
								'decoding' => 'async',
								'sizes'    => '(max-width: 600px) 50vw, 25vw',
							)
						);
						?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $profile ) : ?>
			<p class="text-center" style="margin-top:2rem">
				<a class="instagram-handle" href="<?php echo esc_url( $profile ); ?>" target="_blank" rel="noopener">
					<?php treats_icon( 'instagram', array( 'size' => 18 ) ); ?>
					<?php echo esc_html( '' !== $handle ? '@' . ltrim( $handle, '@' ) : __( 'Follow us on Instagram', 'treats' ) ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</section>
