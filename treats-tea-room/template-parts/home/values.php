<?php
/**
 * The four-up values panel: one frosted glass bar, gold line icons,
 * hairline dividers between the columns.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$values = array(
	array(
		'icon'  => 'leaf',
		'title' => __( 'Locally Sourced', 'treats' ),
		'text'  => __( 'The finest local ingredients, supporting our community.', 'treats' ),
	),
	array(
		'icon'  => 'teapot',
		'title' => __( 'Made With Care', 'treats' ),
		'text'  => __( 'Everything is prepared fresh, every day.', 'treats' ),
	),
	array(
		'icon'  => 'cup',
		'title' => __( 'Award Winning', 'treats' ),
		'text'  => __( 'Recognised for quality, service and hospitality.', 'treats' ),
	),
	array(
		'icon'  => 'heart',
		'title' => __( 'Family Run', 'treats' ),
		'text'  => sprintf(
			/* translators: %s: year the café was established. */
			__( 'Independent and passionate since %s.', 'treats' ),
			get_theme_mod( 'treats_seo_founding_year', '1984' )
		),
	),
);
?>
<section class="section section--sm" id="welcome">
	<div class="container container--wide">
		<ul class="value-bar glass"<?php treats_reveal(); ?>>
			<?php foreach ( $values as $value ) : ?>
				<li class="value-bar__item">
					<span class="value-bar__icon"><?php treats_icon( $value['icon'], array( 'size' => 46 ) ); ?></span>
					<h2 class="glass-title value-bar__title"><?php echo esc_html( $value['title'] ); ?></h2>
					<span class="gold-rule" aria-hidden="true"></span>
					<p class="value-bar__text"><?php echo esc_html( $value['text'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
