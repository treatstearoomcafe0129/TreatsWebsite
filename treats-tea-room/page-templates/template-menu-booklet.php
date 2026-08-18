<?php
/**
 * Template Name: Menu Booklet
 * Template Post Type: page
 *
 * The printed menu, shown as the printed menu.
 *
 * The itemised version — five pages of individually typed dishes — is a
 * better web page in the abstract, but it is not the menu the tea room
 * designed, and keeping the two in step means editing everything twice. This
 * shows the artwork itself and gets out of the way.
 *
 * Pages come from whatever is attached to this page in the media library, so
 * the menu can be replaced without touching a theme file. The July artwork
 * shipped with the theme is the fallback until something is attached.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * Prefixed, and deliberately so: `$pages` and `$page` are WordPress globals,
 * and `the_post()` overwrites `$pages` with the post's content chunks. A
 * template that stores its own data in either loses it the moment the loop
 * starts — silently, as an array of strings.
 */
$treats_booklet = treats_menu_booklet_pages();
$treats_pdf     = treats_menu_booklet_pdf();

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
				<div class="container container--content entry-content" style="margin-bottom:var(--sp-6)">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>

			<?php if ( $treats_booklet ) : ?>
				<p class="booklet-hint">
					<?php esc_html_e( 'Tap any page to read it full screen.', 'treats' ); ?>
				</p>

				<ul class="booklet">
					<?php foreach ( $treats_booklet as $treats_index => $treats_page ) : ?>
						<li class="booklet__page"<?php treats_reveal( min( $treats_index * 50, 200 ) ); ?>>
							<a class="booklet__link" href="<?php echo esc_url( $treats_page['full'] ); ?>" data-lightbox="<?php echo esc_url( $treats_page['full'] ); ?>">
								<img
									src="<?php echo esc_url( $treats_page['src'] ); ?>"
									width="<?php echo (int) $treats_page['width']; ?>"
									height="<?php echo (int) $treats_page['height']; ?>"
									alt="<?php
									printf(
										/* translators: 1: page number, 2: total pages. */
										esc_attr__( 'Menu page %1$d of %2$d', 'treats' ),
										(int) $treats_index + 1,
										count( $treats_booklet )
									);
									?>"
									loading="<?php echo $treats_index < 2 ? 'eager' : 'lazy'; ?>"
									decoding="async">
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="booklet-hint">
					<?php esc_html_e( 'The menu is being updated. Please ask a member of staff, or give us a ring.', 'treats' ); ?>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $treats_pdf ) : ?>
				<p class="booklet-download">
					<a class="btn btn--secondary" href="<?php echo esc_url( $treats_pdf ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Download the menu as a PDF', 'treats' ); ?>
						<?php treats_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
					</a>
				</p>
			<?php endif; ?>
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
