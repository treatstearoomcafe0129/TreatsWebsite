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

/* -------------------------------------------------------------------------
 * Grouping the menu pages under "Menu"
 * ---------------------------------------------------------------------- */

/**
 * Fold the individual menu pages into the "Menu" item as it renders.
 *
 * This used to be done by rewriting the saved navigation on upgrade, which
 * depended on the menu being stored in a particular shape and failed silently
 * when it was not. Doing it here instead means the bar reads correctly
 * whatever is in the database, and nothing about the saved menu is altered —
 * dragging the items back out in Appearance → Menus is not undone, because
 * nothing was written in the first place.
 *
 * @param array  $items Menu item objects.
 * @param object $args  wp_nav_menu arguments.
 * @return array
 */
function treats_group_menu_pages_in_nav( $items, $args ) {
	if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $items;
	}

	if ( ! get_theme_mod( 'treats_nav_group_menus', true ) ) {
		return $items;
	}

	// The pages that make up the menu, by URL — the one identifier that holds
	// whether an item is a page link or a plain custom link.
	$targets = array();

	foreach ( treats_menu_pages() as $page ) {
		$targets[ untrailingslashit( $page['url'] ) ] = true;
	}

	if ( ! $targets ) {
		return $items;
	}

	$overview = untrailingslashit( treats_menus_url() );
	$parent   = null;
	$children = array();
	$rest     = array();

	foreach ( $items as $item ) {
		$url = untrailingslashit( (string) $item->url );

		if ( null === $parent && ( $url === $overview || in_array( strtolower( trim( $item->title ) ), array( 'menu', 'menus' ), true ) ) ) {
			$parent = $item;
			continue;
		}

		// Only top-level items are folded in; anything already arranged into
		// a submenu is somebody's decision and is left alone.
		if ( ! (int) $item->menu_item_parent && isset( $targets[ $url ] ) ) {
			$children[] = $item;
			continue;
		}

		$rest[] = $item;
	}

	if ( ! $parent || ! $children ) {
		return $items;
	}

	foreach ( $children as $child ) {
		$child->menu_item_parent = (string) $parent->ID;
	}

	if ( ! in_array( 'menu-item-has-children', (array) $parent->classes, true ) ) {
		$parent->classes[] = 'menu-item-has-children';
	}

	// On a touch screen the parent opens the dropdown rather than following
	// its own link, so the overview page would otherwise have nowhere to be
	// reached from. It goes in as the first entry in the list it heads.
	$overview_item = clone $parent;

	$overview_item->ID               = $parent->ID * -1;
	$overview_item->db_id            = $overview_item->ID;
	$overview_item->menu_item_parent = (string) $parent->ID;
	$overview_item->title            = __( 'All menus', 'treats' );
	$overview_item->classes          = array( 'menu-item' );

	array_unshift( $children, $overview_item );

	// Rebuild in order, with the children immediately behind their parent.
	$ordered = array();

	foreach ( $items as $item ) {
		if ( $item === $parent ) {
			$ordered[] = $parent;

			foreach ( $children as $child ) {
				$ordered[] = $child;
			}

			continue;
		}

		if ( in_array( $item, $children, true ) ) {
			continue;
		}

		$ordered[] = $item;
	}

	return $ordered;
}
add_filter( 'wp_nav_menu_objects', 'treats_group_menu_pages_in_nav', 10, 2 );
