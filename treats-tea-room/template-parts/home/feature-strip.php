<?php
/**
 * The reassurance strip that sits directly beneath the hero.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$features = array(
	array(
		'icon'  => 'cake',
		'title' => __( 'Baked every morning', 'treats' ),
		'text'  => __( 'Cakes, scones and traybakes made in our own kitchen.', 'treats' ),
	),
	array(
		'icon'  => 'leaf',
		'title' => __( 'Vegan & gluten free', 'treats' ),
		'text'  => __( 'A full plant-based menu, not an afterthought.', 'treats' ),
	),
	array(
		'icon'  => 'pin',
		'title' => __( 'A minute from the Market Place', 'treats' ),
		'text'  => __( 'At the foot of Silver Street, right in the middle of it all.', 'treats' ),
	),
	array(
		'icon'  => 'accessible',
		'title' => __( 'Step-free ground floor', 'treats' ),
		'text'  => __( 'Accessible seating and facilities. Assistance dogs welcome.', 'treats' ),
	),
);
?>
<section class="feature-strip" id="welcome">
	<div class="container">
		<ul class="feature-strip__list">
			<?php foreach ( $features as $index => $feature ) : ?>
				<li class="feature-strip__item"<?php treats_reveal( $index * 70 ); ?>>
					<?php treats_icon( $feature['icon'], array( 'size' => 22 ) ); ?>
					<span>
						<strong><?php echo esc_html( $feature['title'] ); ?></strong>
						<?php echo esc_html( $feature['text'] ); ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
