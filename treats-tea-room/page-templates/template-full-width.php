<?php
/**
 * Template Name: Full Width
 * Template Post Type: page
 *
 * A blank canvas for pages built entirely with blocks.
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
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</article>

	<?php
endwhile;

get_footer();
