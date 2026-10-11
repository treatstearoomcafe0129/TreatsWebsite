<?php
/**
 * The fallback template — used for the blog index and anything without a
 * more specific template.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$treats_blog_id = (int) get_option( 'page_for_posts' );

treats_page_hero(
	array(
		'eyebrow'  => __( 'Journal', 'treats' ),
		'title'    => $treats_blog_id ? get_the_title( $treats_blog_id ) : __( 'From the tea room', 'treats' ),
		'intro'    => $treats_blog_id ? (string) treats_meta( 'intro', $treats_blog_id ) : __( 'Seasonal menus, new bakes and news from Silver Street.', 'treats' ),
		'image_id' => $treats_blog_id ? (int) get_post_thumbnail_id( $treats_blog_id ) : 0,
	)
);
?>

<section class="section">
	<div class="container container--wide">
		<?php if ( have_posts() ) : ?>
			<div class="post-list">
				<?php
				$treats_index = 0;

				while ( have_posts() ) :
					the_post();

					get_template_part( 'template-parts/content/card', get_post_type(), array( 'index' => $treats_index ) );

					++$treats_index;
				endwhile;
				?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'class'     => 'pagination',
					'prev_text' => __( 'Previous', 'treats' ),
					'next_text' => __( 'Next', 'treats' ),
				)
			);
			?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content/none' ); ?>
		<?php endif; ?>
	</div>
</section>

<?php
get_template_part( 'template-parts/components/cta-banner' );

get_footer();
