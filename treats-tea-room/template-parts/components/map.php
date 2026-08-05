<?php
/**
 * Google Maps embed.
 *
 * The iframe is not loaded until the visitor clicks: it keeps a third-party
 * request (and its cookies) off the initial page load, which is both faster
 * and better for privacy.
 *
 * @package Treats
 *
 * @var array $args {
 *     @type bool $tall
 * }
 */

defined( 'ABSPATH' ) || exit;

$config = wp_parse_args(
	$args ?? array(),
	array( 'tall' => false )
);
?>
<div class="map<?php echo $config['tall'] ? ' map--tall' : ''; ?>"
	data-map-src="<?php echo esc_url( treats_map_embed_url() ); ?>"
	data-map-title="<?php echo esc_attr( sprintf( /* translators: %s: business name. */ __( 'Map showing %s', 'treats' ), treats_get_business_name() ) ); ?>">

	<div class="map__placeholder" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Load the interactive map', 'treats' ); ?>">
		<div class="map__placeholder-inner">
			<?php treats_icon( 'pin', array( 'size' => 32 ) ); ?>
			<strong><?php echo esc_html( treats_get_address_line() ); ?></strong>
			<span class="btn btn--secondary btn--sm"><?php esc_html_e( 'Load the map', 'treats' ); ?></span>
			<span class="map__note"><?php esc_html_e( 'We load Google Maps only when you ask, so the page stays fast and no third-party cookies are set before then.', 'treats' ); ?></span>
		</div>
	</div>
</div>
