<?php
/**
 * Single post.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<article <?php post_class(); ?>>
		<section class="page-hero page-hero--compact">
			<div class="container container--content">
				<div class="page-hero__inner">
					<?php treats_breadcrumbs(); ?>

					<header class="entry-header" style="margin-bottom:0">
						<h1 class="entry-title page-hero__title"><?php the_title(); ?></h1>
						<?php treats_post_meta(); ?>
					</header>
				</div>
			</div>
		</section>

		<div class="section">
			<div class="container container--content">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="entry-thumb">
						<?php
						treats_image(
							get_post_thumbnail_id(),
							'treats-hero',
							array(
								'ratio'    => '16 / 9',
								'sizes'    => '(max-width: 800px) 100vw, 760px',
								'priority' => true,
								'alt'      => get_the_title(),
							)
						);
						?>
					</div>
				<?php endif; ?>

				<div class="entry-content">
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

				<footer class="entry-footer">
					<?php
					$treats_tags = get_the_tag_list( '', '' );

					if ( $treats_tags ) :
						?>
						<div class="tag-list"><?php echo wp_kses_post( $treats_tags ); ?></div>
					<?php endif; ?>

					<a class="link-arrow" href="<?php echo esc_url( get_permalink( (int) get_option( 'page_for_posts' ) ) ); ?>">
						<?php esc_html_e( 'All journal entries', 'treats' ); ?>
						<?php treats_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
					</a>
				</footer>

				<?php
				the_post_navigation(
					array(
						'class'     => 'post-navigation',
						'prev_text' => '<span class="post-navigation__label">' . esc_html__( 'Previous', 'treats' ) . '</span><span class="post-navigation__title">%title</span>',
						'next_text' => '<span class="post-navigation__label">' . esc_html__( 'Next', 'treats' ) . '</span><span class="post-navigation__title">%title</span>',
					)
				);

				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</div>
		</div>
	</article>

	<?php
endwhile;

get_template_part( 'template-parts/components/cta-banner' );

get_footer();
