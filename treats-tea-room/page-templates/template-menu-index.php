<?php
/**
 * Template Name: Menus Overview
 * Template Post Type: page
 *
 * The page "Menu" in the navigation should land on. Without it that item
 * pointed at Breakfast & Brunch, and "See our menus" on the About page
 * pointed at Drinks — whichever menu page happened to be newest. Neither was
 * a decision anybody made.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$menus = treats_menu_pages();

while ( have_posts() ) :
	the_post();

	treats_page_hero(
		array(
			'eyebrow'  => (string) treats_meta( 'eyebrow', 0, __( 'What we serve', 'treats' ) ),
			'title'    => get_the_title(),
			'intro'    => (string) treats_meta( 'intro', 0, '' ),
			'image_id' => (int) get_post_thumbnail_id(),
		)
	);
	?>

	<section class="section">
		<div class="container container--wide">
			<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
				<div class="container container--content entry-content" style="margin-bottom:var(--sp-7)">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>

			<ul class="menu-index">
				<?php foreach ( $menus as $index => $menu ) : ?>
					<li class="card menu-index__item"<?php treats_reveal( min( $index * 70, 280 ) ); ?>>
						<a class="menu-index__link" href="<?php echo esc_url( $menu['url'] ); ?>">
							<span class="icon-badge" aria-hidden="true">
								<?php treats_icon( $menu['icon'], array( 'size' => 30 ) ); ?>
							</span>

							<h2 class="glass-title menu-index__title"><?php echo esc_html( $menu['title'] ); ?></h2>
							<span class="gold-rule" aria-hidden="true"></span>

							<?php if ( '' !== $menu['note'] ) : ?>
								<p class="menu-index__note"><?php echo esc_html( $menu['note'] ); ?></p>
							<?php endif; ?>

							<?php if ( $menu['count'] ) : ?>
								<p class="menu-index__count">
									<?php
									printf(
										/* translators: %d: number of dishes. */
										esc_html( _n( '%d dish', '%d dishes', (int) $menu['count'], 'treats' ) ),
										(int) $menu['count']
									);
									?>
								</p>
							<?php endif; ?>

							<span class="btn btn--secondary btn--sm menu-index__cta" aria-hidden="true">
								<?php esc_html_e( 'View', 'treats' ); ?>
								<?php treats_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<?php
	get_template_part(
		'template-parts/components/cta-banner',
		null,
		array(
			'eyebrow' => __( 'Hungry now?', 'treats' ),
			'title'   => __( 'Come and eat with us', 'treats' ),
			'text'    => __( 'Walk-ins are always welcome. Booking is worth it for afternoon tea and at weekends.', 'treats' ),
			'actions' => array(
				array(
					'label' => __( 'Book a table', 'treats' ),
					'url'   => treats_booking_url(),
					'style' => 'btn--light',
				),
				array(
					'label' => __( 'Find us', 'treats' ),
					'url'   => treats_get_template_page_url( 'page-templates/template-contact.php' ),
					'style' => 'btn--outline-light',
				),
			),
		)
	);

endwhile;

get_footer();
