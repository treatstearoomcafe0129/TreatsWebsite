<?php
/**
 * Template Name: About
 * Template Post Type: page
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$founded = (int) get_theme_mod( 'treats_seo_founding_year', 1991 );
$years   = max( 1, (int) gmdate( 'Y' ) - $founded );
$images  = treats_get_gallery_images( 6 );

$values = array(
	array(
		'icon'  => 'cake',
		'title' => __( 'We bake it here', 'treats' ),
		'text'  => __( 'Every cake, scone and traybake on the counter is made in our own kitchen, most of it before the doors open.', 'treats' ),
	),
	array(
		'icon'  => 'leaf',
		'title' => __( 'Everyone gets a proper menu', 'treats' ),
		'text'  => __( 'Vegan and gluten-free dishes are cooked with the same care as everything else — never an afterthought on the side.', 'treats' ),
	),
	array(
		'icon'  => 'users',
		'title' => __( 'Good food at good prices', 'treats' ),
		'text'  => __( 'Students, families, regulars and visitors all get the same welcome and the same honest pricing.', 'treats' ),
	),
	array(
		'icon'  => 'cup',
		'title' => __( 'No rush', 'treats' ),
		'text'  => __( 'Your table is yours. The pot gets refilled, and nobody is going to hurry you along.', 'treats' ),
	),
);

while ( have_posts() ) :
	the_post();

	treats_page_hero(
		array(
			'eyebrow'  => (string) treats_meta( 'eyebrow', 0, __( 'Our story', 'treats' ) ),
			'title'    => get_the_title(),
			'intro'    => (string) treats_meta( 'intro' ),
			'image_id' => (int) get_post_thumbnail_id(),
		)
	);
	?>

	<section class="section">
		<div class="container container--content entry-content"<?php treats_reveal(); ?>>
			<?php
			$treats_content = trim( get_the_content() );

			if ( '' !== $treats_content ) {
				the_content();
			} else {
				?>
				<p class="lede">
					<?php
					printf(
						/* translators: %d: number of years trading. */
						esc_html__( 'Treats has sat at the foot of Silver Street for %d years. In that time Durham has changed a great deal around us — but what happens inside has not.', 'treats' ),
						(int) $years
					);
					?>
				</p>

				<p>
					<?php esc_html_e( 'We open early, bake through the morning and cook everything to order. The cake counter is filled before the first customer arrives, the soup is made that day, and the tea comes in a pot you can pour from twice. It is not complicated — it is just done properly, every day.', 'treats' ); ?>
				</p>

				<p>
					<?php esc_html_e( 'Over the years we have fed three generations of the same families, most of the university, and a good share of everyone who has ever walked down to Framwellgate Bridge. We would very much like to feed you too.', 'treats' ); ?>
				</p>
				<?php
			}
			?>
		</div>
	</section>

	<section class="section section--tint">
		<div class="container container--wide">
			<?php
			treats_section_heading(
				array(
					'eyebrow' => __( 'What we care about', 'treats' ),
					'title'   => __( 'Four things we will not compromise on', 'treats' ),
				)
			);
			?>

			<div class="grid grid--2">
				<?php foreach ( $values as $index => $value ) : ?>
					<article class="card card--flat"<?php treats_reveal( $index * 70 ); ?>>
						<div class="card__body" style="padding-left:0">
							<span style="color:var(--c-accent)"><?php treats_icon( $value['icon'], array( 'size' => 28 ) ); ?></span>
							<h3 class="card__title"><?php echo esc_html( $value['title'] ); ?></h3>
							<p class="card__text"><?php echo esc_html( $value['text'] ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section section--deep">
		<div class="container container--wide">
			<div class="stats">
				<div<?php treats_reveal(); ?>>
					<div class="stat__value" data-count-to="<?php echo esc_attr( (string) $years ); ?>"><?php echo esc_html( (string) $years ); ?></div>
					<div class="stat__label"><?php esc_html_e( 'Years on Silver Street', 'treats' ); ?></div>
				</div>
				<div<?php treats_reveal( 80 ); ?>>
					<div class="stat__value" data-count-to="900" data-count-suffix="+">900+</div>
					<div class="stat__label"><?php esc_html_e( 'Reviews and counting', 'treats' ); ?></div>
				</div>
				<div<?php treats_reveal( 160 ); ?>>
					<div class="stat__value" data-count-to="40" data-count-suffix="+">40+</div>
					<div class="stat__label"><?php esc_html_e( 'Cakes baked each morning', 'treats' ); ?></div>
				</div>
				<div<?php treats_reveal( 240 ); ?>>
					<div class="stat__value">7</div>
					<div class="stat__label"><?php esc_html_e( 'Days a week', 'treats' ); ?></div>
				</div>
			</div>
		</div>
	</section>

	<?php if ( $images ) : ?>
		<section class="section">
			<div class="container container--wide">
				<?php
				treats_section_heading(
					array(
						'eyebrow' => __( 'Inside Treats', 'treats' ),
						'title'   => __( 'Have a look around', 'treats' ),
					)
				);
				?>

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
			</div>
		</section>
	<?php endif; ?>

	<?php
endwhile;

get_template_part(
	'template-parts/components/cta-banner',
	null,
	array(
		'eyebrow' => __( 'Come and see us', 'treats' ),
		'title'   => __( 'The kettle is already on', 'treats' ),
		'text'    => __( 'Walk in whenever you like, or book ahead if you have your heart set on afternoon tea.', 'treats' ),
		'actions' => array(
			array(
				'label' => __( 'Book a table', 'treats' ),
				'url'   => treats_booking_url(),
				'style' => 'btn--light',
			),
			array(
				'label' => __( 'See our menus', 'treats' ),
				'url'   => treats_menus_url(),
				'style' => 'btn--outline-light',
			),
		),
	)
);

get_footer();
