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

	<section class="section" style="padding-top:<?php echo '' !== $treats_intro_content ? '0' : 'var(--section-y-sm)'; ?>">
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

get_template_part(
	'template-parts/components/cta-banner',
	null,
	array(
		'eyebrow' => __( 'Hungry now?', 'treats' ),
		'title'   => __( 'Book a table, or order for collection', 'treats' ),
		'text'    => __( 'Walk-ins are always welcome. Booking is worth it for afternoon tea and at weekends.', 'treats' ),
		'actions' => array(
			array(
				'label' => __( 'Book a table', 'treats' ),
				'url'   => treats_booking_url(),
				'style' => 'btn--light',
			),
			array(
				'label' => __( 'Gift vouchers', 'treats' ),
				'url'   => treats_vouchers_url(),
				'style' => 'btn--outline-light',
			),
		),
	)
);

get_footer();
