<?php
/**
 * The basket button and its slide-in drawer.
 *
 * Printed once in the footer so it is available from every page.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$count = treats_basket_count();
?>
<button class="basket-fab<?php echo $count ? ' basket-fab--full' : ''; ?>" type="button" data-basket-open aria-haspopup="dialog" aria-expanded="false" aria-controls="treats-basket">
	<?php treats_icon( 'bag', array( 'size' => 18 ) ); ?>
	<span class="basket-fab__label"><?php esc_html_e( 'Basket', 'treats' ); ?></span>
	<span class="basket-fab__count" data-basket-count><?php echo esc_html( (string) $count ); ?></span>
</button>

<div class="basket-drawer" id="treats-basket" role="dialog" aria-modal="true" aria-labelledby="treats-basket-title" hidden>
	<div class="basket-drawer__scrim" data-basket-close></div>

	<div class="basket-drawer__panel">
		<header class="basket-drawer__header">
			<h2 class="basket-drawer__title" id="treats-basket-title"><?php esc_html_e( 'Your basket', 'treats' ); ?></h2>

			<button class="basket-drawer__close" type="button" data-basket-close aria-label="<?php esc_attr_e( 'Close the basket', 'treats' ); ?>">
				<?php treats_icon( 'close', array( 'size' => 20 ) ); ?>
			</button>
		</header>

		<div class="basket-drawer__body" data-basket-body>
			<?php get_template_part( 'template-parts/shop/basket-panel' ); ?>
		</div>
	</div>
</div>

<p class="basket-toast" role="status" aria-live="polite" data-basket-toast hidden></p>
