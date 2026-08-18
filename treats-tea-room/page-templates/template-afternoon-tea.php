<?php
/**
 * Template Name: Afternoon Tea
 * Template Post Type: page
 *
 * Afternoon tea is the thing people travel for and book ahead for, so it gets
 * a page that sells it rather than a row in a list: what arrives on the
 * stand, what you choose between, what it costs, and how to book.
 *
 * The cream teas, scones and everything else in the afternoon tea category
 * still list underneath, so the page is both the pitch and the menu.
 *
 * Every figure comes from the Customizer.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

/**
 * Split a comma separated Customizer field into a list.
 *
 * @param string $value Raw setting.
 * @return string[]
 */
function treats_tea_list( $value ) {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ) ) );
}

$treats_tea_price   = (string) get_theme_mod( 'treats_tea_price', '44' );
$treats_tea_serves  = (string) get_theme_mod( 'treats_tea_serves', __( 'for two', 'treats' ) );
$treats_tea_stand   = treats_tea_list( get_theme_mod( 'treats_tea_includes', '2 afternoon tea dainty sandwiches on white or brown, 2 mini corned beef pies, 2 mini quiches, 2 scones with preserve and clotted cream, 2 cakes from the display, Any 2 hot drinks' ) );
$treats_tea_fillings = treats_tea_list( get_theme_mod( 'treats_tea_sandwiches', 'Cheese savoury, Smoked salmon cream cheese, Hummus cucumber tomato, Ham, Tuna cucumber, Prawn Marie Rose, Cheese and pickle' ) );
$treats_tea_upgrade = (string) get_theme_mod( 'treats_tea_upgrade', __( 'Upgrade to an alcoholic beverage for £8 extra.', 'treats' ) );
$treats_tea_note    = (string) get_theme_mod( 'treats_tea_note', __( 'Booking is recommended, especially at weekends. Vegan and gluten free afternoon teas are available with 24 hours’ notice.', 'treats' ) );
$treats_tea_image   = (string) get_theme_mod( 'treats_tea_image', '' );
$treats_tea_cat     = (string) treats_meta( 'menu_category', 0, 'afternoon-tea' );

while ( have_posts() ) :
	the_post();

	treats_page_hero(
		array(
			'eyebrow'  => (string) treats_meta( 'eyebrow', 0, __( 'Booking recommended', 'treats' ) ),
			'title'    => get_the_title(),
			'intro'    => (string) treats_meta( 'intro', 0, '' ),
			'image_id' => (int) get_post_thumbnail_id(),
			'actions'  => array(
				array(
					'label' => __( 'Book a table', 'treats' ),
					'url'   => treats_booking_url(),
				),
			),
		)
	);
	?>

	<section class="section">
		<div class="container container--wide">
			<div class="split split--wide-left" style="align-items:start">

				<div<?php treats_reveal(); ?>>
					<?php if ( $treats_tea_image ) : ?>
						<img class="tea-image" src="<?php echo esc_url( $treats_tea_image ); ?>" alt="" loading="lazy" decoding="async">
					<?php endif; ?>

					<h2 class="glass-title tea-heading"><?php esc_html_e( 'What arrives on the stand', 'treats' ); ?></h2>
					<span class="gold-rule" aria-hidden="true"></span>

					<ul class="tea-stand">
						<?php foreach ( $treats_tea_stand as $treats_line ) : ?>
							<li><?php echo esc_html( $treats_line ); ?></li>
						<?php endforeach; ?>
					</ul>

					<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
						<div class="entry-content" style="margin-top:var(--sp-6)">
							<?php the_content(); ?>
						</div>
					<?php endif; ?>
				</div>

				<aside<?php treats_reveal( 100 ); ?>>
					<div class="info-card tea-price-card">
						<p class="tea-price-card__label"><?php esc_html_e( 'Afternoon tea', 'treats' ); ?></p>
						<p class="tea-price-card__price"><?php echo esc_html( treats_format_price( $treats_tea_price ) ); ?></p>
						<p class="tea-price-card__serves"><?php echo esc_html( $treats_tea_serves ); ?></p>

						<?php if ( '' !== $treats_tea_upgrade ) : ?>
							<p class="tea-price-card__upgrade"><?php echo esc_html( $treats_tea_upgrade ); ?></p>
						<?php endif; ?>

						<a class="btn btn--primary btn--lg" href="<?php echo esc_url( treats_booking_url() ); ?>">
							<?php esc_html_e( 'Book a table', 'treats' ); ?>
						</a>

						<?php if ( '' !== treats_get_phone() ) : ?>
							<p class="tea-price-card__phone">
								<?php esc_html_e( 'Or ring us on', 'treats' ); ?>
								<a href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>"><?php echo esc_html( treats_get_phone() ); ?></a>
							</p>
						<?php endif; ?>
					</div>
				</aside>
			</div>
		</div>
	</section>

	<?php if ( $treats_tea_fillings ) : ?>
		<section class="section section--sm">
			<div class="container container--wide">
				<?php
				treats_section_heading(
					array(
						'eyebrow' => __( 'Your choice', 'treats' ),
						'title'   => __( 'Choose your sandwiches', 'treats' ),
						'text'    => __( 'Two rounds each, cut small, on white or brown. Tell us on the day.', 'treats' ),
						'center'  => true,
					)
				);
				?>

				<ul class="tea-fillings">
					<?php foreach ( $treats_tea_fillings as $treats_index => $treats_filling ) : ?>
						<li class="tea-filling"<?php treats_reveal( min( $treats_index * 50, 250 ) ); ?>>
							<?php echo esc_html( $treats_filling ); ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if ( '' !== $treats_tea_note ) : ?>
					<p class="tea-note"><?php echo esc_html( $treats_tea_note ); ?></p>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="section section--sm">
		<div class="container container--wide">
			<?php
			treats_section_heading(
				array(
					'eyebrow' => __( 'The full list', 'treats' ),
					'title'   => __( 'Afternoon tea, cream teas and scones', 'treats' ),
					'center'  => true,
				)
			);
			?>
		</div>

		<?php
		get_template_part(
			'template-parts/menu/menu-list',
			null,
			array( 'category' => $treats_tea_cat )
		);
		?>
	</section>

	<?php
endwhile;

get_template_part(
	'template-parts/components/cta-banner',
	null,
	array(
		'eyebrow' => __( 'Save yourself a wait', 'treats' ),
		'title'   => __( 'Book your afternoon tea', 'treats' ),
		'text'    => __( 'Weekends fill up. A table takes a minute to reserve, and we will have the stand ready for you.', 'treats' ),
		'actions' => array(
			array(
				'label' => __( 'Book a table', 'treats' ),
				'url'   => treats_booking_url(),
				'style' => 'btn--light',
			),
			array(
				'label' => __( 'See all menus', 'treats' ),
				'url'   => treats_menus_url(),
				'style' => 'btn--outline-light',
			),
		),
	)
);

get_footer();
