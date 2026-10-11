<?php
/**
 * Featured dishes — anything flagged "Feature on the home page".
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$featured = get_posts(
	array(
		'post_type'      => 'treats_menu_item',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'no_found_rows'  => true,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'   => '_treats_featured',
				'value' => '1',
			),
		),
	)
);

if ( ! $featured ) {
	return;
}
?>
<section class="section">
	<div class="container container--wide">
		<?php
		treats_section_heading(
			array(
				'eyebrow' => __( 'What people order', 'treats' ),
				'title'   => __( 'A few of our regulars', 'treats' ),
				'intro'   => __( 'The dishes that come back to the pass most often.', 'treats' ),
			)
		);
		?>

		<div class="dish-list">
			<?php foreach ( $featured as $index => $item ) : ?>
				<article class="dish"<?php treats_reveal( min( $index * 60, 240 ) ); ?>>
					<?php if ( has_post_thumbnail( $item ) ) : ?>
						<?php
						treats_image(
							get_post_thumbnail_id( $item ),
							'treats-thumb',
							array(
								'class' => 'dish__media',
								'ratio' => '1 / 1',
								'sizes' => '84px',
								'alt'   => get_the_title( $item ),
							)
						);
						?>
					<?php endif; ?>

					<div class="dish__body">
						<div class="dish__head">
							<h3 class="dish__title"><?php echo esc_html( get_the_title( $item ) ); ?></h3>
							<?php $price = treats_meta( 'price', $item->ID ); ?>
							<?php if ( '' !== $price ) : ?>
								<span class="dish__price"><?php echo esc_html( treats_format_price( $price ) ); ?></span>
							<?php endif; ?>
						</div>

						<p class="dish__text"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $item->post_content ), 20 ) ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
