<?php
/**
 * A single search result.
 *
 * Menu items get their price shown, since that is usually what someone
 * searching for "scone" actually wants.
 *
 * @package Treats
 *
 * @var array $args {
 *     @type int $index
 * }
 */

defined( 'ABSPATH' ) || exit;

$index     = (int) ( $args['index'] ?? 0 );
$post_type = get_post_type();
$price     = 'treats_menu_item' === $post_type ? treats_meta( 'price' ) : '';

$labels = array(
	'treats_menu_item' => __( 'Menu', 'treats' ),
	'page'             => __( 'Page', 'treats' ),
	'post'             => __( 'Journal', 'treats' ),
);
?>
<article <?php post_class( 'card' ); ?><?php treats_reveal( min( $index * 60, 240 ) ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<?php
		treats_image(
			get_post_thumbnail_id(),
			'treats-card',
			array(
				'ratio' => '4 / 3',
				'sizes' => '(max-width: 767px) 100vw, 33vw',
				'alt'   => get_the_title(),
			)
		);
		?>
	<?php endif; ?>

	<div class="card__body">
		<?php if ( isset( $labels[ $post_type ] ) ) : ?>
			<span class="pill pill--outline" style="align-self:flex-start"><?php echo esc_html( $labels[ $post_type ] ); ?></span>
		<?php endif; ?>

		<h2 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

		<p class="card__text"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 24 ) ); ?></p>

		<?php if ( '' !== $price ) : ?>
			<div class="card__footer">
				<span class="dish__price"><?php echo esc_html( treats_format_price( $price ) ); ?></span>
			</div>
		<?php endif; ?>
	</div>
</article>
