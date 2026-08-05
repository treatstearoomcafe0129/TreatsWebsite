<?php
/**
 * Accessible navigation walker.
 *
 * Submenus are exposed as real disclosure buttons so they work with keyboard
 * and screen readers, not just hover.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Primary navigation walker.
 */
class Treats_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Start a submenu level.
	 *
	 * @param string   $output Menu markup, passed by reference.
	 * @param int      $depth  Current depth.
	 * @param stdClass $args   Menu args.
	 * @return void
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$indent  = str_repeat( "\t", $depth );
		$output .= "\n{$indent}<ul class=\"sub-menu sub-menu--depth-{$depth}\">\n";
	}

	/**
	 * End a submenu level.
	 *
	 * @param string   $output Menu markup, passed by reference.
	 * @param int      $depth  Current depth.
	 * @param stdClass $args   Menu args.
	 * @return void
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$indent  = str_repeat( "\t", $depth );
		$output .= "{$indent}</ul>\n";
	}

	/**
	 * Start an element.
	 *
	 * @param string   $output            Menu markup, passed by reference.
	 * @param WP_Post  $data_object       Menu item.
	 * @param int      $depth             Current depth.
	 * @param stdClass $args              Menu args.
	 * @param int      $current_object_id Current object ID.
	 * @return void
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item    = $data_object;
		$indent  = str_repeat( "\t", $depth );
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;

		$classes[] = 'menu-item-' . $item->ID;
		$classes[] = 'nav__item';
		$classes[] = 'nav__item--depth-' . $depth;

		/**
		 * Run core's menu class filter, as Walker_Nav_Menu does. Without this
		 * the theme's own `is-cta` promotion — and any plugin filter — would be
		 * silently ignored.
		 */
		$classes = (array) apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth );

		$has_children = in_array( 'menu-item-has-children', $classes, true );

		if ( $has_children ) {
			$classes[] = 'has-dropdown';
		}

		if ( in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ) ) {
			$classes[] = 'is-current';
		}

		$class_names = implode( ' ', array_map( 'sanitize_html_class', array_filter( array_unique( $classes ) ) ) );

		$output .= $indent . '<li class="' . esc_attr( $class_names ) . '">';

		$atts = array(
			'title'  => ! empty( $item->attr_title ) ? $item->attr_title : '',
			'target' => ! empty( $item->target ) ? $item->target : '',
			'rel'    => ! empty( $item->xfn ) ? $item->xfn : '',
			'href'   => ! empty( $item->url ) ? $item->url : '',
			'class'  => 'nav__link nav__link--depth-' . $depth,
		);

		if ( '_blank' === $atts['target'] && empty( $atts['rel'] ) ) {
			$atts['rel'] = 'noopener';
		}

		if ( in_array( 'current-menu-item', $classes, true ) ) {
			$atts['aria-current'] = 'page';
		}

		// A "Book a table" item styled as a button, by adding the `is-cta` class.
		if ( in_array( 'is-cta', $classes, true ) ) {
			$atts['class'] .= ' btn btn--primary btn--sm';
		}

		$attributes = '';

		foreach ( $atts as $attr => $value ) {
			if ( '' === $value || false === $value ) {
				continue;
			}

			$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
			$attributes .= ' ' . $attr . '="' . $value . '"';
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );

		$link  = '<a' . $attributes . '><span class="nav__label">' . esc_html( $title ) . '</span></a>';

		if ( $has_children && 0 === $depth ) {
			$panel_id = 'nav-panel-' . (int) $item->ID;

			$link .= sprintf(
				'<button class="nav__toggle" type="button" aria-expanded="false" aria-controls="%1$s"><span class="screen-reader-text">%2$s</span>%3$s</button>',
				esc_attr( $panel_id ),
				/* translators: %s: menu item name. */
				esc_html( sprintf( __( 'Show submenu for %s', 'treats' ), $title ) ),
				treats_get_icon( 'chevron', array( 'size' => 16 ) )
			);
		}

		$output .= $args->before . $link . $args->after;
	}

	/**
	 * End an element.
	 *
	 * @param string   $output      Menu markup, passed by reference.
	 * @param WP_Post  $data_object Menu item.
	 * @param int      $depth       Current depth.
	 * @param stdClass $args        Menu args.
	 * @return void
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		$output .= "</li>\n";
	}
}

/**
 * Render the primary navigation, falling back to a page list.
 *
 * @param string $location Menu location.
 * @param array  $args     Extra wp_nav_menu args.
 * @return void
 */
function treats_nav_menu( $location, $args = array() ) {
	if ( ! has_nav_menu( $location ) ) {
		if ( 'primary' === $location ) {
			wp_page_menu(
				array(
					'menu_class'  => 'nav__list',
					'container'   => false,
					'depth'       => 1,
					'before'      => '',
					'after'       => '',
					'show_home'   => true,
					'item_spacing' => 'discard',
				)
			);
		}

		return;
	}

	wp_nav_menu(
		wp_parse_args(
			$args,
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'nav__list',
				'depth'          => 2,
				'walker'         => new Treats_Nav_Walker(),
				'fallback_cb'    => false,
			)
		)
	);
}

/**
 * Allow an `is-cta` class on menu items via the menu UI without extra setup.
 *
 * Any menu item whose URL matches the booking page is automatically promoted
 * to a button in the header.
 *
 * @param array<int,string> $classes Menu item classes.
 * @param WP_Post           $item    Menu item.
 * @param stdClass          $args    Menu args.
 * @return array<int,string>
 */
function treats_auto_cta_class( $classes, $item, $args ) {
	if ( ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $classes;
	}

	if ( in_array( 'is-cta', (array) $classes, true ) ) {
		return $classes;
	}

	$booking = untrailingslashit( treats_booking_url() );

	if ( $booking && untrailingslashit( (string) $item->url ) === $booking ) {
		$classes[] = 'is-cta';
	}

	return $classes;
}
add_filter( 'nav_menu_css_class', 'treats_auto_cta_class', 10, 3 );
