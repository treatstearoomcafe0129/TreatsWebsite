<?php
/**
 * Template Name: Menu
 * Template Post Type: page
 *
 * Drives every menu page. Pick the menu category in the "Treats details" panel
 * on the page edit screen; leave it blank to show everything.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$treats_category = (string) treats_meta( 'menu_category' );
	$treats_pdf      = (string) treats_meta( 'menu_pdf' );

	treats_page_hero(
		array(
			'eyebrow'  => (string) treats_meta( 'eyebrow' ),
			'title'    => get_the_title(),
			'intro'    => (string) treats_meta( 'intro' ),
			'image_id' => (int) get_post_thumbnail_id(),
			'actions'  => array(
				array(
					'label' => __( 'Book a table', 'treats' ),
					'url'   => treats_booking_url(),
					'style' => has_post_thumbnail() ? 'btn--light' : 'btn--primary',
				),
			),
		)
	);

	$treats_intro_content = trim( get_the_content() );

	if ( '' !== $treats_intro_content ) {
		?>
		<section class="section section--sm">
			<div class="container container--content entry-content">
				<?php the_content(); ?>
			</div>
		</section>
		<?php
	}
	?>

	<?php // The sticky filter bar sits flush under the hero, so no top padding. ?>
	<section class="section" style="padding-top:0">
		<?php
		get_template_part(
			'template-parts/menu/menu-list',
			null,
			array( 'category' => $treats_category )
		);
		?>

		<?php if ( '' !== $treats_pdf ) : ?>
			<div class="container container--wide" style="margin-top:2rem">
				<a class="menu-download" href="<?php echo esc_url( $treats_pdf ); ?>" target="_blank" rel="noopener">
					<?php treats_icon( 'arrow-right', array( 'size' => 16 ) ); ?>
					<?php esc_html_e( 'Download this menu as a PDF', 'treats' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</section>

	<?php
endwhile;

/*
 * The other menus, on every menu page. Without this the only way from Lunch
 * to Drinks was back up to the navigation — the five menus were five dead
 * ends sitting next to each other.
 */
$treats_here  = get_permalink();
$treats_other = array_values(
	array_filter(
		treats_menu_pages(),
		static function ( $menu ) use ( $treats_here ) {
			return untrailingslashit( $menu['url'] ) !== untrailingslashit( $treats_here );
		}
	)
);

if ( $treats_other ) :
	?>
	<section class="section section--sm">
		<div class="container container--wide">
			<h2 class="menu-more__heading"><?php esc_html_e( 'The other menus', 'treats' ); ?></h2>

			<ul class="menu-more">
				<?php foreach ( $treats_other as $treats_menu ) : ?>
					<li>
						<a class="menu-more__link" href="<?php echo esc_url( $treats_menu['url'] ); ?>">
							<?php treats_icon( $treats_menu['icon'], array( 'size' => 20 ) ); ?>
							<span><?php echo esc_html( $treats_menu['title'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
endif;

get_template_part(
	'template-parts/components/cta-banner',
	null,
	array(
		'eyebrow' => __( 'Hungry now?', 'treats' ),
		// The old title promised "or order for collection" while offering a
		// gift voucher button. Collection is the basket on this page.
		'title'   => __( 'Come and eat with us', 'treats' ),
		'text'    => __( 'Walk-ins are always welcome. Booking is worth it for afternoon tea and at weekends. Anything on this page marked “add to order” can be collected.', 'treats' ),
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

get_footer();
