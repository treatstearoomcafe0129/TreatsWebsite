<?php
/**
 * A single menu item row.
 *
 * @package Treats
 *
 * @var array $args {
 *     @type WP_Post $item      Menu item post.
 *     @type bool    $show_image
 *     @type bool    $collect   Whether Click & Collect is available.
 * }
 */

defined( 'ABSPATH' ) || exit;

$config = wp_parse_args(
	$args ?? array(),
	array(
		'item'       => null,
		'show_image' => true,
		'collect'    => false,
	)
);

$item = $config['item'];

if ( ! $item instanceof WP_Post ) {
	return;
}

$price      = treats_meta( 'price', $item->ID );
$price_note = treats_meta( 'price_note', $item->ID );
$badge      = treats_meta( 'badge', $item->ID );
$allergens  = treats_meta( 'allergens', $item->ID );
$collect_ok = $config['collect'] && '1' === treats_meta( 'collectable', $item->ID ) && is_numeric( $price );

$categories = wp_get_object_terms( $item->ID, 'treats_menu_category', array( 'fields' => 'slugs' ) );
$dietary    = wp_get_object_terms( $item->ID, 'treats_dietary' );

if ( is_wp_error( $categories ) ) {
	$categories = array();
}

if ( is_wp_error( $dietary ) ) {
	$dietary = array();
}

$diet_slugs = wp_list_pluck( $dietary, 'slug' );
$search_key = wp_strip_all_tags( $item->post_title . ' ' . $item->post_content . ' ' . implode( ' ', wp_list_pluck( $dietary, 'name' ) ) );
?>
<li class="menu-entry"
	data-categories="<?php echo esc_attr( implode( ' ', $categories ) ); ?>"
	data-diet="<?php echo esc_attr( implode( ' ', $diet_slugs ) ); ?>"
	data-search="<?php echo esc_attr( $search_key ); ?>">

	<?php if ( $config['show_image'] && has_post_thumbnail( $item ) ) : ?>
		<?php
		treats_image(
			get_post_thumbnail_id( $item ),
			'treats-thumb',
			array(
				'class' => 'menu-entry__media',
				'ratio' => '1 / 1',
				'sizes' => '92px',
				'alt'   => get_the_title( $item ),
			)
		);
		?>
	<?php endif; ?>

	<div class="menu-entry__body">
		<div class="menu-entry__head">
			<h3 class="menu-entry__title"><?php echo esc_html( get_the_title( $item ) ); ?></h3>
			<span class="menu-entry__dots" aria-hidden="true"></span>

			<?php if ( '' !== $price ) : ?>
				<span class="menu-entry__price">
					<?php echo esc_html( treats_format_price( $price ) ); ?>
					<?php if ( '' !== $price_note ) : ?>
						<span class="menu-entry__price-note"><?php echo esc_html( $price_note ); ?></span>
					<?php endif; ?>
				</span>
			<?php endif; ?>
		</div>

		<?php if ( trim( (string) $item->post_content ) ) : ?>
			<p class="menu-entry__text"><?php echo esc_html( wp_strip_all_tags( $item->post_content ) ); ?></p>
		<?php endif; ?>

		<?php if ( $dietary || '' !== $badge || '' !== $allergens ) : ?>
			<div class="menu-entry__tags">
				<?php if ( '' !== $badge ) : ?>
					<span class="menu-entry__badge"><?php echo esc_html( $badge ); ?></span>
				<?php endif; ?>

				<?php foreach ( $dietary as $term ) : ?>
					<span class="diet-tag diet-tag--<?php echo esc_attr( $term->slug ); ?>" title="<?php echo esc_attr( $term->name ); ?>">
						<?php echo esc_html( strtoupper( $term->slug ) ); ?>
						<span class="screen-reader-text"><?php echo esc_html( $term->name ); ?></span>
					</span>
				<?php endforeach; ?>

				<?php if ( '' !== $allergens ) : ?>
					<span class="menu-entry__text"><?php echo esc_html( $allergens ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $collect_ok ) : ?>
			<button
				class="menu-entry__add"
				type="button"
				data-collect-add="<?php echo esc_attr( (string) $item->ID ); ?>"
				data-title="<?php echo esc_attr( get_the_title( $item ) ); ?>"
				data-price="<?php echo esc_attr( (string) (float) $price ); ?>"
				data-add-label="<?php esc_attr_e( 'Add to order', 'treats' ); ?>">
				<?php treats_icon( 'plus', array( 'size' => 14 ) ); ?>
				<span data-collect-label><?php esc_html_e( 'Add to order', 'treats' ); ?></span>
			</button>
		<?php endif; ?>
	</div>
</li>
