<?php
/**
 * The home page.
 *
 * Sections are individual template parts so the order can be changed — or a
 * section removed — without touching anything else.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

/**
 * Filter the home page section order.
 *
 * @param array<int,string> $sections Template part slugs, relative to
 *                                    template-parts/home/.
 */
$treats_sections = apply_filters(
	'treats_home_sections',
	array(
		'hero',
		'values',
		'actions',
		'contact-bar',
	)
);

foreach ( $treats_sections as $treats_section ) {
	get_template_part( 'template-parts/home/' . $treats_section );
}

// Any content added to the Home page in the editor renders beneath the
// designed sections, so the owner can add a seasonal note without code.
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();

		$treats_content = trim( get_the_content() );

		if ( '' !== $treats_content ) {
			?>
			<section class="section section--sm">
				<div class="container container--content entry-content">
					<?php the_content(); ?>
				</div>
			</section>
			<?php
		}
	}
}

get_footer();
