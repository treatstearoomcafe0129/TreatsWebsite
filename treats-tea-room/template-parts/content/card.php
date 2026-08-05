<?php
/**
 * A post card used in listings.
 *
 * @package Treats
 *
 * @var array $args {
 *     @type int $index Position in the list, used to stagger the reveal.
 * }
 */

defined( 'ABSPATH' ) || exit;

$index = (int) ( $args['index'] ?? 0 );
?>
<article <?php post_class( 'card' ); ?><?php treats_reveal( min( $index * 70, 280 ) ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<?php
		treats_image(
			get_post_thumbnail_id(),
			'treats-card',
			array(
				'ratio'    => '4 / 3',
				'sizes'    => '(max-width: 767px) 100vw, (max-width: 1199px) 50vw, 33vw',
				'priority' => 0 === $index,
				'alt'      => get_the_title(),
			)
		);
		?>
	<?php endif; ?>

	<div class="card__body">
		<?php treats_post_meta(); ?>

		<h2 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

		<p class="card__text"><?php echo esc_html( get_the_excerpt() ); ?></p>

		<div class="card__footer">
			<span class="link-arrow" style="border:0;pointer-events:none">
				<?php esc_html_e( 'Read more', 'treats' ); ?>
				<?php treats_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
			</span>
		</div>
	</div>
</article>
