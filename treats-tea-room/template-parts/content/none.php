<?php
/**
 * Shown when a query returns nothing.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="no-results">
	<?php if ( is_search() ) : ?>
		<h2 style="margin-bottom:1rem"><?php esc_html_e( 'Nothing found for that search', 'treats' ); ?></h2>
		<p class="lede" style="margin-bottom:2rem">
			<?php esc_html_e( 'Try a different word, or take a look at our menus — most people are after the cakes.', 'treats' ); ?>
		</p>

		<?php get_search_form(); ?>

		<div class="actions" style="margin-top:2rem">
			<a class="btn btn--secondary btn--sm" href="<?php echo esc_url( treats_get_template_page_url( 'page-templates/template-menu.php' ) ); ?>">
				<?php esc_html_e( 'Our menus', 'treats' ); ?>
			</a>
			<a class="btn btn--secondary btn--sm" href="<?php echo esc_url( treats_booking_url() ); ?>">
				<?php esc_html_e( 'Book a table', 'treats' ); ?>
			</a>
		</div>
	<?php else : ?>
		<h2 style="margin-bottom:1rem"><?php esc_html_e( 'Nothing here just yet', 'treats' ); ?></h2>
		<p class="lede"><?php esc_html_e( 'Check back soon — we will be writing about what is coming out of the kitchen.', 'treats' ); ?></p>
	<?php endif; ?>
</div>
