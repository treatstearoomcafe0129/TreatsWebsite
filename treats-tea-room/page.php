<?php
/**
 * The default page template.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	if ( '1' !== treats_meta( 'hide_hero' ) ) {
		treats_page_hero(
			array(
				'eyebrow'  => (string) treats_meta( 'eyebrow' ),
				'title'    => get_the_title(),
				'intro'    => (string) treats_meta( 'intro' ),
				'image_id' => (int) get_post_thumbnail_id(),
			)
		);
	}
	?>

	<article <?php post_class( 'section' ); ?>>
		<div class="container container--content entry-content">
			<?php
			the_content();

			wp_link_pages(
				array(
					'before' => '<nav class="pagination" aria-label="' . esc_attr__( 'Page', 'treats' ) . '">',
					'after'  => '</nav>',
				)
			);
			?>
		</div>
	</article>

	<?php
	if ( comments_open() || get_comments_number() ) {
		?>
		<div class="container container--content" style="padding-bottom:var(--section-y)">
			<?php comments_template(); ?>
		</div>
		<?php
	}

endwhile;

get_footer();
