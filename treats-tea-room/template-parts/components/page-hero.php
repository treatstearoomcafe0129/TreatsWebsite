<?php
/**
 * Interior page hero.
 *
 * @package Treats
 *
 * @var array $args {
 *     @type string $eyebrow
 *     @type string $title
 *     @type string $intro
 *     @type int    $image_id
 *     @type array  $actions  Each: label, url, style.
 *     @type bool   $compact
 *     @type string $align    left|center
 * }
 */

defined( 'ABSPATH' ) || exit;

$hero = wp_parse_args(
	$args ?? array(),
	array(
		'eyebrow'  => '',
		'title'    => '',
		'intro'    => '',
		'image_id' => 0,
		'actions'  => array(),
		'compact'  => false,
		'align'    => 'left',
	)
);

$classes = array( 'page-hero' );

if ( $hero['image_id'] ) {
	$classes[] = 'page-hero--image';
}

if ( $hero['compact'] ) {
	$classes[] = 'page-hero--compact';
}

if ( 'center' === $hero['align'] ) {
	$classes[] = 'page-hero--center';
}
?>
<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php if ( $hero['image_id'] ) : ?>
		<div class="page-hero__bg">
			<?php
			echo wp_get_attachment_image(
				$hero['image_id'],
				'treats-hero',
				false,
				array(
					'alt'           => '',
					'fetchpriority' => 'high',
					'sizes'         => '100vw',
				)
			);
			?>
		</div>
	<?php endif; ?>

	<div class="container">
		<div class="page-hero__inner">
			<?php treats_breadcrumbs(); ?>

			<?php if ( '' !== $hero['eyebrow'] ) : ?>
				<p class="eyebrow page-hero__eyebrow"><?php echo esc_html( $hero['eyebrow'] ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $hero['title'] ) : ?>
				<h1 class="page-hero__title"><?php echo wp_kses_post( $hero['title'] ); ?></h1>
			<?php endif; ?>

			<?php if ( '' !== $hero['intro'] ) : ?>
				<p class="page-hero__intro"><?php echo wp_kses_post( $hero['intro'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $hero['actions'] ) ) : ?>
				<div class="page-hero__actions">
					<?php foreach ( $hero['actions'] as $action ) : ?>
						<a class="btn <?php echo esc_attr( $action['style'] ?? 'btn--primary' ); ?>" href="<?php echo esc_url( $action['url'] ); ?>">
							<?php echo esc_html( $action['label'] ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
