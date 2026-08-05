<?php
/**
 * Archives: categories, tags, dates and custom taxonomies.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$treats_is_menu_tax = is_tax( 'treats_menu_category' ) || is_post_type_archive( 'treats_menu_item' ) || is_tax( 'treats_dietary' );

treats_page_hero(
	array(
		'eyebrow' => $treats_is_menu_tax ? __( 'Menu', 'treats' ) : __( 'Archive', 'treats' ),
		'title'   => get_the_archive_title(),
		'intro'   => wp_strip_all_tags( (string) get_the_archive_description() ),
	)
);

if ( $treats_is_menu_tax ) {
	// Menu archives get the interactive menu treatment rather than cards.
	$treats_term = get_queried_object();
	$treats_slug = ( $treats_term instanceof WP_Term ) ? $treats_term->slug : '';
	?>
	<section class="section" style="padding-top:0">
		<?php
		get_template_part(
			'template-parts/menu/menu-list',
			null,
			array(
				'category'     => is_tax( 'treats_dietary' ) ? '' : $treats_slug,
				'show_filters' => ! is_tax( 'treats_dietary' ),
			)
		);
		?>
	</section>
	<?php
} else {
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
}

get_template_part( 'template-parts/components/cta-banner' );

get_footer();
