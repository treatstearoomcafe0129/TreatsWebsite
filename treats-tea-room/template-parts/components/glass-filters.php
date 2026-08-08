<?php
/**
 * The optical filter used by the glass panels.
 *
 * One filter, referenced from `css/main.css`. It displaces the frosted rim
 * band drawn around every glass panel, so that band's outline wanders in and
 * out instead of tracing a perfect rounded rectangle — which is the
 * difference between a ground glass edge and a line someone drew.
 *
 * It is inert decoration: `aria-hidden`, no pointer events, zero layout. It
 * filters a pseudo-element's own few pixels, not the page behind it, so it
 * is cheap and it works in every engine that renders SVG filters at all.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Filters whether the optical filter is printed at all.
 *
 * Turning it off leaves a smooth frosted rim, which is a perfectly good
 * fallback — useful if a site is running on very modest hardware.
 *
 * @param bool $enabled Whether to print the filter.
 */
if ( ! apply_filters( 'treats_glass_filters', true ) ) {
	return;
}
?>
<svg class="glass-filters" width="0" height="0" aria-hidden="true" focusable="false">
	<filter id="treats-glass-rough" x="-14%" y="-14%" width="128%" height="128%" color-interpolation-filters="sRGB">
		<feTurbulence type="fractalNoise" baseFrequency="0.026 0.032" numOctaves="3" seed="31" result="edge" />
		<feDisplacementMap in="SourceGraphic" in2="edge" scale="9" xChannelSelector="R" yChannelSelector="G" />
	</filter>
</svg>
