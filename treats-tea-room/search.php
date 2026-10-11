<?php
/**
 * Search results.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;

treats_page_hero(
	array(
		'eyebrow' => __( 'Search', 'treats' ),
		'title'   => sprintf(
			/* translators: %s: search term. */
			__( 'Results for “%s”', 'treats' ),
			esc_html( get_search_query() )
		),
		'intro'   => sprintf(
			/* translators: %s: number of results. */
			_n( '%s result found.', '%s results found.', (int) $wp_query->found_posts, 'treats' ),
			number_format_i18n( (int) $wp_query->found_posts )
		),
		'compact' => true,
	)
);
?>

<section class="section">
	<div class="container container--wide">
		<div style="margin-bottom:3rem">
			<?php get_search_form(); ?>
		</div>

		<?php if ( have_posts() ) : ?>
			<div class="post-list">
				<?php
				$treats_index = 0;

				while ( have_posts() ) :
					the_post();

					get_template_part( 'template-parts/content/search-result', null, array( 'index' => $treats_index ) );

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
get_footer();
