<?php
/**
 * First-run scaffolding.
 *
 * On activation the theme builds the page structure, navigation menus and
 * starter content described in the design, so the site is complete the moment
 * it is switched on. Everything created here is ordinary WordPress content and
 * can be edited or deleted freely; the routine never runs twice.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run the scaffolding once, after the theme is activated.
 *
 * @return void
 */
function treats_after_switch_theme() {
	if ( get_option( 'treats_scaffolded' ) ) {
		flush_rewrite_rules();

		return;
	}

	treats_register_post_types();
	treats_register_taxonomies();
	treats_seed_dietary_terms();

	$pages = treats_create_pages();

	treats_seed_menu_content();
	treats_seed_reviews();
	treats_seed_faqs();
	treats_create_menus( $pages );
	treats_configure_reading( $pages );

	update_option( 'treats_scaffolded', TREATS_VERSION, false );

	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'treats_after_switch_theme' );

/**
 * The site's page structure.
 *
 * @return array<string,array<string,mixed>>
 */
function treats_page_blueprint() {
	return array(
		'home'      => array(
			'title'    => __( 'Home', 'treats' ),
			'template' => 'front-page.php',
			'content'  => '',
		),
		'breakfast' => array(
			'title'    => __( 'Breakfast & Brunch', 'treats' ),
			'template' => 'page-templates/template-menu.php',
			'category' => 'breakfast-brunch',
			'eyebrow'  => __( 'Served from opening', 'treats' ),
			'intro'    => __( 'Proper breakfasts cooked to order, from a full English to eggs on sourdough — served all day, every day.', 'treats' ),
		),
		'lunch'     => array(
			'title'    => __( 'Lunch', 'treats' ),
			'template' => 'page-templates/template-menu.php',
			'category' => 'lunch',
			'eyebrow'  => __( 'From 11.30am', 'treats' ),
			'intro'    => __( 'Homemade pies, quiches, soups, salads and sandwiches, made fresh in our kitchen each morning.', 'treats' ),
		),
		'afternoon' => array(
			'title'    => __( 'Afternoon Tea', 'treats' ),
			'template' => 'page-templates/template-menu.php',
			'category' => 'afternoon-tea',
			'eyebrow'  => __( 'Booking recommended', 'treats' ),
			'intro'    => __( 'Finger sandwiches, warm scones with jam and clotted cream, and a tier of our own cakes. The Durham afternoon, done properly.', 'treats' ),
		),
		'cakes'     => array(
			'title'    => __( 'Cakes & Desserts', 'treats' ),
			'template' => 'page-templates/template-menu.php',
			'category' => 'cakes-desserts',
			'eyebrow'  => __( 'Baked every morning', 'treats' ),
			'intro'    => __( 'The counter that Durham knows us for. Traybakes, layer cakes, scones and puddings, all made in-house.', 'treats' ),
		),
		'drinks'    => array(
			'title'    => __( 'Drinks', 'treats' ),
			'template' => 'page-templates/template-menu.php',
			'category' => 'drinks',
			'eyebrow'  => __( 'Loose leaf & speciality coffee', 'treats' ),
			'intro'    => __( 'Loose leaf teas by the pot, speciality coffee, hot chocolate worth the walk, and cold drinks for the summer.', 'treats' ),
		),
		'booking'   => array(
			'title'    => __( 'Book a Table', 'treats' ),
			'template' => 'page-templates/template-booking.php',
			'eyebrow'  => __( 'Reservations', 'treats' ),
			'intro'    => __( 'Tell us when you would like to come and we will confirm your table by email or phone.', 'treats' ),
		),
		'vouchers'  => array(
			'title'    => __( 'Gift Vouchers', 'treats' ),
			'template' => 'page-templates/template-vouchers.php',
			'eyebrow'  => __( 'Gifts', 'treats' ),
			'intro'    => __( 'A Treats voucher is the easiest way to send someone a proper afternoon in Durham.', 'treats' ),
		),
		'about'     => array(
			'title'    => __( 'About Us', 'treats' ),
			'template' => 'page-templates/template-about.php',
			'eyebrow'  => __( 'Our story', 'treats' ),
			'intro'    => __( 'A family tea room on Silver Street, serving Durham since 1984.', 'treats' ),
		),
		'contact'   => array(
			'title'    => __( 'Contact', 'treats' ),
			'template' => 'page-templates/template-contact.php',
			'eyebrow'  => __( 'Find us', 'treats' ),
			'intro'    => __( 'At the foot of Silver Street, a minute from Durham Market Place.', 'treats' ),
		),
		'faq'       => array(
			'title'    => __( 'FAQ', 'treats' ),
			'template' => 'page-templates/template-faq.php',
			'eyebrow'  => __( 'Good to know', 'treats' ),
			'intro'    => __( 'Everything visitors usually ask us before they arrive.', 'treats' ),
		),
		'gallery'   => array(
			'title'    => __( 'Gallery', 'treats' ),
			'template' => 'page-templates/template-gallery.php',
			'eyebrow'  => __( 'The tea room', 'treats' ),
			'intro'    => __( 'A look inside Treats, and what comes out of our kitchen.', 'treats' ),
		),
		'journal'   => array(
			'title'    => __( 'Journal', 'treats' ),
			'template' => '',
			'content'  => '',
		),
		'privacy'   => array(
			'title'    => __( 'Privacy Policy', 'treats' ),
			'template' => '',
			'content'  => treats_privacy_content(),
		),
		'accessibility' => array(
			'title'    => __( 'Accessibility', 'treats' ),
			'template' => '',
			'content'  => treats_accessibility_content(),
		),
	);
}

/**
 * Create the pages, skipping any that already exist by title.
 *
 * @return array<string,int> Map of blueprint key to page ID.
 */
function treats_create_pages() {
	$created = array();
	$order   = 0;

	foreach ( treats_page_blueprint() as $key => $page ) {
		$order += 10;

		$existing = get_page_by_path( sanitize_title( $page['title'] ), OBJECT, 'page' );

		if ( $existing instanceof WP_Post ) {
			// WordPress creates the Privacy Policy page as a draft during
			// install; publish it so the footer link is not a dead end.
			if ( 'publish' !== $existing->post_status ) {
				wp_update_post(
					array(
						'ID'          => $existing->ID,
						'post_status' => 'publish',
					)
				);
			}

			$created[ $key ] = (int) $existing->ID;
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page['title'],
				'post_name'    => sanitize_title( $page['title'] ),
				'post_content' => $page['content'] ?? '',
				'menu_order'   => $order,
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		if ( ! empty( $page['template'] ) && 'front-page.php' !== $page['template'] ) {
			update_post_meta( $post_id, '_wp_page_template', $page['template'] );
		}

		foreach ( array( 'eyebrow', 'intro', 'category' ) as $meta_key ) {
			if ( ! empty( $page[ $meta_key ] ) ) {
				$stored = 'category' === $meta_key ? '_treats_menu_category' : '_treats_' . $meta_key;
				update_post_meta( $post_id, $stored, $page[ $meta_key ] );
			}
		}

		$created[ $key ] = (int) $post_id;
	}

	return $created;
}

/**
 * Point WordPress at the new home and journal pages.
 *
 * @param array<string,int> $pages Created page IDs.
 * @return void
 */
function treats_configure_reading( $pages ) {
	if ( empty( $pages['home'] ) ) {
		return;
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $pages['home'] );

	if ( ! empty( $pages['journal'] ) ) {
		update_option( 'page_for_posts', $pages['journal'] );
	}

	if ( ! empty( $pages['privacy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', $pages['privacy'] );
	}

	// Pretty permalinks, which the menu and dietary archives depend on.
	if ( '' === get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
}

/**
 * Build the navigation menus.
 *
 * @param array<string,int> $pages Created page IDs.
 * @return void
 */
function treats_create_menus( $pages ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );

	// --- Primary, with a Menus dropdown ---------------------------------- //
	if ( empty( $locations['primary'] ) ) {
		$menu_id = wp_create_nav_menu( __( 'Primary', 'treats' ) );

		if ( ! is_wp_error( $menu_id ) ) {
			if ( ! empty( $pages['home'] ) ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $pages['home'],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-title'     => __( 'Home', 'treats' ),
						'menu-item-status'    => 'publish',
					)
				);
			}

			// "Menu" holds the five menu pages beneath it.
			$parent = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => __( 'Menu', 'treats' ),
					'menu-item-url'    => empty( $pages['breakfast'] ) ? home_url( '/' ) : get_permalink( $pages['breakfast'] ),
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				)
			);

			foreach ( array( 'breakfast', 'lunch', 'afternoon', 'cakes', 'drinks' ) as $child ) {
				if ( empty( $pages[ $child ] ) ) {
					continue;
				}

				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $pages[ $child ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-parent-id' => is_wp_error( $parent ) ? 0 : $parent,
					)
				);
			}

			$top = array(
				'afternoon' => __( 'Afternoon Tea', 'treats' ),
				'about'     => __( 'About', 'treats' ),
				'booking'   => __( 'Bookings', 'treats' ),
				'contact'   => __( 'Contact', 'treats' ),
			);

			foreach ( $top as $key => $label ) {
				if ( empty( $pages[ $key ] ) ) {
					continue;
				}

				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $pages[ $key ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-title'     => $label,
						'menu-item-status'    => 'publish',
					)
				);
			}

			$locations['primary'] = $menu_id;
		}
	}

	// --- Footer ---------------------------------------------------------- //
	if ( empty( $locations['footer'] ) ) {
		$footer_id = wp_create_nav_menu( __( 'Footer', 'treats' ) );

		if ( ! is_wp_error( $footer_id ) ) {
			foreach ( array( 'breakfast', 'lunch', 'afternoon', 'cakes', 'drinks', 'gallery', 'journal' ) as $key ) {
				if ( empty( $pages[ $key ] ) ) {
					continue;
				}

				wp_update_nav_menu_item(
					$footer_id,
					0,
					array(
						'menu-item-object-id' => $pages[ $key ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}

			$locations['footer'] = $footer_id;
		}
	}

	// --- Legal ----------------------------------------------------------- //
	if ( empty( $locations['legal'] ) ) {
		$legal_id = wp_create_nav_menu( __( 'Legal', 'treats' ) );

		if ( ! is_wp_error( $legal_id ) ) {
			foreach ( array( 'privacy', 'accessibility' ) as $key ) {
				if ( empty( $pages[ $key ] ) ) {
					continue;
				}

				wp_update_nav_menu_item(
					$legal_id,
					0,
					array(
						'menu-item-object-id' => $pages[ $key ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}

			$locations['legal'] = $legal_id;
		}
	}

	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Seed menu categories and a representative set of items.
 *
 * @return void
 */
function treats_seed_menu_content() {
	$categories = array(
		'breakfast-brunch' => __( 'Breakfast & Brunch', 'treats' ),
		'lunch'            => __( 'Lunch', 'treats' ),
		'afternoon-tea'    => __( 'Afternoon Tea', 'treats' ),
		'cakes-desserts'   => __( 'Cakes & Desserts', 'treats' ),
		'drinks'           => __( 'Drinks', 'treats' ),
	);

	$term_ids = array();

	foreach ( $categories as $slug => $name ) {
		$existing = get_term_by( 'slug', $slug, 'treats_menu_category' );

		if ( $existing instanceof WP_Term ) {
			$term_ids[ $slug ] = (int) $existing->term_id;
			continue;
		}

		$term = wp_insert_term( $name, 'treats_menu_category', array( 'slug' => $slug ) );

		if ( ! is_wp_error( $term ) ) {
			$term_ids[ $slug ] = (int) $term['term_id'];
		}
	}

	// Sub-sections give each menu page its structure.
	$subsections = array(
		'breakfast-brunch' => array(
			'the-full-works'   => __( 'The Full Works', 'treats' ),
			'lighter-mornings' => __( 'Lighter Mornings', 'treats' ),
			'on-toast'         => __( 'On Toast', 'treats' ),
		),
		'lunch'            => array(
			'from-the-kitchen' => __( 'From the Kitchen', 'treats' ),
			'sandwiches'       => __( 'Sandwiches & Toasties', 'treats' ),
			'salads-soups'     => __( 'Salads & Soups', 'treats' ),
		),
		'afternoon-tea'    => array(
			'afternoon-teas'   => __( 'Afternoon Teas', 'treats' ),
			'scones'           => __( 'Cream Teas & Scones', 'treats' ),
		),
		'cakes-desserts'   => array(
			'the-cake-counter' => __( 'The Cake Counter', 'treats' ),
			'traybakes'        => __( 'Traybakes & Slices', 'treats' ),
			'puddings'         => __( 'Warm Puddings', 'treats' ),
		),
		'drinks'           => array(
			'loose-leaf-tea'   => __( 'Loose Leaf Tea', 'treats' ),
			'coffee'           => __( 'Coffee', 'treats' ),
			'cold-drinks'      => __( 'Cold Drinks', 'treats' ),
		),
	);

	foreach ( $subsections as $parent_slug => $children ) {
		if ( empty( $term_ids[ $parent_slug ] ) ) {
			continue;
		}

		foreach ( $children as $slug => $name ) {
			if ( get_term_by( 'slug', $slug, 'treats_menu_category' ) ) {
				continue;
			}

			wp_insert_term(
				$name,
				'treats_menu_category',
				array(
					'slug'   => $slug,
					'parent' => $term_ids[ $parent_slug ],
				)
			);
		}
	}

	if ( get_option( 'treats_menu_seeded' ) ) {
		return;
	}

	foreach ( treats_starter_menu_items() as $order => $item ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'treats_menu_item',
				'post_status'  => 'publish',
				'post_title'   => $item['title'],
				'post_content' => $item['description'],
				'menu_order'   => ( $order + 1 ) * 10,
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		wp_set_object_terms( $post_id, $item['categories'], 'treats_menu_category' );

		if ( ! empty( $item['dietary'] ) ) {
			wp_set_object_terms( $post_id, $item['dietary'], 'treats_dietary' );
		}

		update_post_meta( $post_id, '_treats_price', $item['price'] );
		update_post_meta( $post_id, '_treats_collectable', '1' );

		if ( ! empty( $item['badge'] ) ) {
			update_post_meta( $post_id, '_treats_badge', $item['badge'] );
		}

		if ( ! empty( $item['featured'] ) ) {
			update_post_meta( $post_id, '_treats_featured', '1' );
		}

		if ( ! empty( $item['note'] ) ) {
			update_post_meta( $post_id, '_treats_price_note', $item['note'] );
		}
	}

	update_option( 'treats_menu_seeded', 1, false );
}

/**
 * Starter menu items.
 *
 * Representative of what the café serves; prices and wording are meant to be
 * reviewed and adjusted by the owner before launch.
 *
 * @return array<int,array<string,mixed>>
 */
function treats_starter_menu_items() {
	return array(
		array(
			'title'       => __( 'The Full Treats Breakfast', 'treats' ),
			'description' => __( 'Two rashers of dry-cured bacon, Cumberland sausage, free-range egg, black pudding, grilled tomato, mushrooms, baked beans and toast.', 'treats' ),
			'price'       => '11.95',
			'categories'  => array( 'breakfast-brunch', 'the-full-works' ),
			'badge'       => __( 'Signature', 'treats' ),
			'featured'    => true,
		),
		array(
			'title'       => __( 'The Vegan Full Works', 'treats' ),
			'description' => __( 'Plant-based sausages, smoked tofu, sautéed mushrooms, grilled tomato, avocado, baked beans and sourdough toast.', 'treats' ),
			'price'       => '11.50',
			'categories'  => array( 'breakfast-brunch', 'the-full-works' ),
			'dietary'     => array( 've', 'v' ),
			'featured'    => true,
		),
		array(
			'title'       => __( 'Eggs Royale', 'treats' ),
			'description' => __( 'Scottish smoked salmon, poached free-range eggs and hollandaise on a toasted muffin.', 'treats' ),
			'price'       => '10.50',
			'categories'  => array( 'breakfast-brunch', 'lighter-mornings' ),
		),
		array(
			'title'       => __( 'Smashed Avocado & Poached Eggs', 'treats' ),
			'description' => __( 'On toasted sourdough with chilli, lemon and toasted seeds.', 'treats' ),
			'price'       => '9.25',
			'categories'  => array( 'breakfast-brunch', 'lighter-mornings' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Buttermilk Pancakes', 'treats' ),
			'description' => __( 'A tall stack with maple syrup, seasonal berries and crème fraîche. Add streaky bacon for £2.', 'treats' ),
			'price'       => '8.95',
			'categories'  => array( 'breakfast-brunch', 'lighter-mornings' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Warm Cinnamon Toast', 'treats' ),
			'description' => __( 'Thick-cut white bloomer, cinnamon butter, a pot of tea alongside.', 'treats' ),
			'price'       => '4.50',
			'categories'  => array( 'breakfast-brunch', 'on-toast' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Homemade Steak & Ale Pie', 'treats' ),
			'description' => __( 'Slow-cooked in a local ale gravy, shortcrust pastry, buttered mash and garden peas.', 'treats' ),
			'price'       => '13.50',
			'categories'  => array( 'lunch', 'from-the-kitchen' ),
			'badge'       => __( 'House favourite', 'treats' ),
			'featured'    => true,
		),
		array(
			'title'       => __( 'Quiche of the Day', 'treats' ),
			'description' => __( 'Baked each morning, served warm with dressed leaves and our house slaw.', 'treats' ),
			'price'       => '10.95',
			'categories'  => array( 'lunch', 'from-the-kitchen' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Croque Monsieur', 'treats' ),
			'description' => __( 'Wiltshire ham, mature cheddar and béchamel on toasted sourdough. Add a fried egg to make it a Madame.', 'treats' ),
			'price'       => '9.50',
			'categories'  => array( 'lunch', 'sandwiches' ),
		),
		array(
			'title'       => __( 'Coronation Chickpea Sandwich', 'treats' ),
			'description' => __( 'Lightly spiced, with sultanas, coriander and little gem on granary bread.', 'treats' ),
			'price'       => '8.25',
			'categories'  => array( 'lunch', 'sandwiches' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Soup of the Day', 'treats' ),
			'description' => __( 'Made in our kitchen every morning, with warm bread and butter.', 'treats' ),
			'price'       => '6.95',
			'categories'  => array( 'lunch', 'salads-soups' ),
			'dietary'     => array( 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Roast Beetroot & Whipped Feta Salad', 'treats' ),
			'description' => __( 'With candied walnuts, orange and a honey dressing.', 'treats' ),
			'price'       => '10.25',
			'categories'  => array( 'lunch', 'salads-soups' ),
			'dietary'     => array( 'v', 'gf', 'n' ),
		),
		array(
			'title'       => __( 'The Treats Afternoon Tea', 'treats' ),
			'description' => __( 'Finger sandwiches, a warm fruit scone with Yorkshire jam and clotted cream, a tier of our own cakes, and a pot of loose leaf tea.', 'treats' ),
			'price'       => '24.95',
			'note'        => __( 'per person', 'treats' ),
			'categories'  => array( 'afternoon-tea', 'afternoon-teas' ),
			'badge'       => __( 'Most booked', 'treats' ),
			'featured'    => true,
		),
		array(
			'title'       => __( 'Sparkling Afternoon Tea', 'treats' ),
			'description' => __( 'Our full afternoon tea with a glass of English sparkling wine.', 'treats' ),
			'price'       => '32.95',
			'note'        => __( 'per person', 'treats' ),
			'categories'  => array( 'afternoon-tea', 'afternoon-teas' ),
		),
		array(
			'title'       => __( 'Vegan Afternoon Tea', 'treats' ),
			'description' => __( 'Every tier made plant-based, including our vegan scones and coconut cream. Please order 24 hours ahead.', 'treats' ),
			'price'       => '24.95',
			'note'        => __( 'per person', 'treats' ),
			'categories'  => array( 'afternoon-tea', 'afternoon-teas' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Traditional Cream Tea', 'treats' ),
			'description' => __( 'Two warm scones, strawberry jam, clotted cream and a pot of tea.', 'treats' ),
			'price'       => '9.95',
			'categories'  => array( 'afternoon-tea', 'scones' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Victoria Sponge', 'treats' ),
			'description' => __( 'The one we are known for. Raspberry jam, vanilla buttercream, a dusting of icing sugar.', 'treats' ),
			'price'       => '4.75',
			'categories'  => array( 'cakes-desserts', 'the-cake-counter' ),
			'dietary'     => array( 'v' ),
			'featured'    => true,
		),
		array(
			'title'       => __( 'Salted Caramel Chocolate Cake', 'treats' ),
			'description' => __( 'Four layers, dark chocolate ganache, sea salt caramel.', 'treats' ),
			'price'       => '5.25',
			'categories'  => array( 'cakes-desserts', 'the-cake-counter' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Lemon & Elderflower Drizzle', 'treats' ),
			'description' => __( 'Soaked while still warm, finished with a crunchy sugar crust.', 'treats' ),
			'price'       => '4.50',
			'categories'  => array( 'cakes-desserts', 'the-cake-counter' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Millionaire’s Shortbread', 'treats' ),
			'description' => __( 'Proper caramel, thick chocolate, buttery shortbread base.', 'treats' ),
			'price'       => '3.95',
			'categories'  => array( 'cakes-desserts', 'traybakes' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Vegan Chocolate & Orange Brownie', 'treats' ),
			'description' => __( 'Fudgy in the middle, with candied orange.', 'treats' ),
			'price'       => '4.25',
			'categories'  => array( 'cakes-desserts', 'traybakes' ),
			'dietary'     => array( 've', 'v', 'gf' ),
		),
		array(
			'title'       => __( 'Sticky Toffee Pudding', 'treats' ),
			'description' => __( 'Warm, with toffee sauce and a jug of cream or a scoop of vanilla ice cream.', 'treats' ),
			'price'       => '7.25',
			'categories'  => array( 'cakes-desserts', 'puddings' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Treats House Blend', 'treats' ),
			'description' => __( 'Our own malty breakfast blend, served by the pot.', 'treats' ),
			'price'       => '3.50',
			'categories'  => array( 'drinks', 'loose-leaf-tea' ),
			'dietary'     => array( 've', 'v', 'gf' ),
		),
		array(
			'title'       => __( 'Earl Grey Blue Flower', 'treats' ),
			'description' => __( 'Bergamot and cornflower, delicate and floral.', 'treats' ),
			'price'       => '3.75',
			'categories'  => array( 'drinks', 'loose-leaf-tea' ),
			'dietary'     => array( 've', 'v', 'gf' ),
		),
		array(
			'title'       => __( 'Flat White', 'treats' ),
			'description' => __( 'Double ristretto, silky milk. Oat, soya and almond available at no extra charge.', 'treats' ),
			'price'       => '3.60',
			'categories'  => array( 'drinks', 'coffee' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Real Hot Chocolate', 'treats' ),
			'description' => __( 'Melted Belgian chocolate, steamed milk, cream and marshmallows if you like.', 'treats' ),
			'price'       => '4.25',
			'categories'  => array( 'drinks', 'coffee' ),
			'dietary'     => array( 'v' ),
			'featured'    => true,
		),
		array(
			'title'       => __( 'Homemade Lemonade', 'treats' ),
			'description' => __( 'Pressed in-house, over ice with mint.', 'treats' ),
			'price'       => '3.75',
			'categories'  => array( 'drinks', 'cold-drinks' ),
			'dietary'     => array( 've', 'v', 'gf' ),
		),
	);
}

/**
 * Seed a few reviews so the testimonial section is populated.
 *
 * @return void
 */
function treats_seed_reviews() {
	if ( get_option( 'treats_reviews_seeded' ) ) {
		return;
	}

	$reviews = array(
		array(
			'title'  => __( 'Worth the queue', 'treats' ),
			'body'   => __( 'The cake counter is a sight in itself and the afternoon tea was beautifully presented. Staff could not have been more welcoming on a busy Saturday.', 'treats' ),
			'author' => __( 'Helen M.', 'treats' ),
			'source' => 'Tripadvisor',
			'rating' => '5',
		),
		array(
			'title'  => __( 'Best breakfast in Durham', 'treats' ),
			'body'   => __( 'Generous portions, everything cooked properly and a genuinely good vegan option. We come back every time we visit our daughter at the university.', 'treats' ),
			'author' => __( 'Andrew P.', 'treats' ),
			'source' => 'Google',
			'rating' => '5',
		),
		array(
			'title'  => __( 'A proper tea room', 'treats' ),
			'body'   => __( 'Bright, spotless and full of locals — always a good sign. The scones were still warm and the loose leaf tea came in a proper pot.', 'treats' ),
			'author' => __( 'Rachel D.', 'treats' ),
			'source' => 'Tripadvisor',
			'rating' => '5',
		),
		array(
			'title'  => __( 'Kind to coeliacs', 'treats' ),
			'body'   => __( 'They took my gluten intolerance seriously and talked me through the whole menu. So rare, and so appreciated.', 'treats' ),
			'author' => __( 'Sofia L.', 'treats' ),
			'source' => 'Google',
			'rating' => '5',
		),
		array(
			'title'  => __( 'Our family tradition', 'treats' ),
			'body'   => __( 'Three generations of us have had birthday afternoon teas here. Prices are honest and the welcome never changes.', 'treats' ),
			'author' => __( 'John & Margaret W.', 'treats' ),
			'source' => 'Facebook',
			'rating' => '5',
		),
		array(
			'title'  => __( 'Somewhere to sit and think', 'treats' ),
			'body'   => __( 'I revised for finals in the corner by the window for a fortnight. Nobody rushed me, the pot kept being refilled, and the cake got me through it.', 'treats' ),
			'author' => __( 'Priya S.', 'treats' ),
			'source' => 'Google',
			'rating' => '5',
		),
	);

	foreach ( $reviews as $order => $review ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'treats_review',
				'post_status'  => 'publish',
				'post_title'   => $review['title'],
				'post_content' => $review['body'],
				'menu_order'   => ( $order + 1 ) * 10,
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		update_post_meta( $post_id, '_treats_author', $review['author'] );
		update_post_meta( $post_id, '_treats_source', $review['source'] );
		update_post_meta( $post_id, '_treats_rating', $review['rating'] );
	}

	update_option( 'treats_reviews_seeded', 1, false );
}

/**
 * Seed the FAQ content.
 *
 * @return void
 */
function treats_seed_faqs() {
	if ( get_option( 'treats_faqs_seeded' ) ) {
		return;
	}

	$topics = array(
		'visiting' => __( 'Visiting', 'treats' ),
		'food'     => __( 'Food & Dietary', 'treats' ),
		'booking'  => __( 'Bookings & Groups', 'treats' ),
	);

	foreach ( $topics as $slug => $name ) {
		if ( ! get_term_by( 'slug', $slug, 'treats_faq_topic' ) ) {
			wp_insert_term( $name, 'treats_faq_topic', array( 'slug' => $slug ) );
		}
	}

	$faqs = array(
		array(
			'q'     => __( 'Do I need to book a table?', 'treats' ),
			'a'     => __( 'Walk-ins are always welcome and most of the time you will not wait long. We do recommend booking for afternoon tea, for groups of six or more, and for weekend brunch.', 'treats' ),
			'topic' => 'booking',
		),
		array(
			'q'     => __( 'Where exactly are you, and where can I park?', 'treats' ),
			'a'     => __( 'We are at 10–11 Silver Street, at the foot of the hill between Durham Market Place and Framwellgate Bridge. Silver Street is pedestrianised, so the nearest car parks are Prince Bishops and Walkergate, both around a five-minute walk.', 'treats' ),
			'topic' => 'visiting',
		),
		array(
			'q'     => __( 'Is the tea room accessible?', 'treats' ),
			'a'     => __( 'The ground floor is step-free from Silver Street with accessible seating and an accessible toilet. Assistance dogs are welcome throughout. If you would like us to hold a particular table, please call ahead and we will do our best.', 'treats' ),
			'topic' => 'visiting',
		),
		array(
			'q'     => __( 'Do you cater for vegans, vegetarians and gluten-free diets?', 'treats' ),
			'a'     => __( 'Yes — there is a full vegan menu, plenty of vegetarian dishes and gluten-free options across breakfast, lunch and the cake counter. Our vegan afternoon tea needs 24 hours notice so we can bake for you.', 'treats' ),
			'topic' => 'food',
		),
		array(
			'q'     => __( 'Can you handle allergies?', 'treats' ),
			'a'     => __( 'Please tell us when you order and we will talk you through the options. We prepare food in a busy kitchen that handles all fourteen major allergens, so we cannot guarantee a dish is entirely free from traces.', 'treats' ),
			'topic' => 'food',
		),
		array(
			'q'     => __( 'Is breakfast served all day?', 'treats' ),
			'a'     => __( 'It is. The full breakfast menu runs from the moment we open until last orders.', 'treats' ),
			'topic' => 'food',
		),
		array(
			'q'     => __( 'Can I take cakes away, or order a whole one?', 'treats' ),
			'a'     => __( 'Everything on the counter is available to take away, and we bake whole celebration cakes to order with about a week’s notice. Use Click & Collect on any menu page, or give us a ring.', 'treats' ),
			'topic' => 'food',
		),
		array(
			'q'     => __( 'Do you take large groups?', 'treats' ),
			'a'     => __( 'We can usually seat groups of up to twelve online. For anything larger, including private hire of the upstairs room, please call us and we will arrange it properly.', 'treats' ),
			'topic' => 'booking',
		),
		array(
			'q'     => __( 'Are dogs allowed?', 'treats' ),
			'a'     => __( 'Assistance dogs are welcome everywhere in the tea room. Well-behaved dogs are welcome at our outside tables.', 'treats' ),
			'topic' => 'visiting',
		),
		array(
			'q'     => __( 'Do you sell gift vouchers?', 'treats' ),
			'a'     => __( 'Yes. Choose any amount or one of our afternoon tea experiences, and we will post a card to you or email a printable voucher the same day.', 'treats' ),
			'topic' => 'booking',
		),
	);

	foreach ( $faqs as $order => $faq ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'treats_faq',
				'post_status'  => 'publish',
				'post_title'   => $faq['q'],
				'post_content' => $faq['a'],
				'menu_order'   => ( $order + 1 ) * 10,
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		wp_set_object_terms( $post_id, $faq['topic'], 'treats_faq_topic' );
	}

	update_option( 'treats_faqs_seeded', 1, false );
}

/**
 * Starter privacy copy.
 *
 * @return string
 */
function treats_privacy_content() {
	return implode(
		"\n\n",
		array(
			'<!-- wp:paragraph --><p>' . esc_html__( 'This page explains what we do with the information you give us through this website. It is a starting point written for a small café — please have it reviewed before launch so it reflects exactly how you operate.', 'treats' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>' . esc_html__( 'What we collect', 'treats' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'When you book a table, place a Click & Collect order, buy a gift voucher, contact us or join our newsletter, we collect the details you type into that form — typically your name, email address and phone number, plus the details of your request.', 'treats' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>' . esc_html__( 'Why we hold it', 'treats' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'We use it to answer you, to hold your table or order, and — only if you have ticked the box — to send you occasional news about the tea room. We never sell your details.', 'treats' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>' . esc_html__( 'How long we keep it', 'treats' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'Booking and order records are kept for up to 24 months so we can look back on a reservation. Newsletter subscriptions are kept until you unsubscribe, which you can do from any email we send.', 'treats' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>' . esc_html__( 'Your rights', 'treats' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'You can ask us for a copy of what we hold about you, ask us to correct it, or ask us to delete it. Write to us at the tea room or email us and we will respond within one month.', 'treats' ) . '</p><!-- /wp:paragraph -->',
		)
	);
}

/**
 * Starter accessibility statement.
 *
 * @return string
 */
function treats_accessibility_content() {
	return implode(
		"\n\n",
		array(
			'<!-- wp:paragraph --><p>' . esc_html__( 'We want everyone to be able to use this website and to enjoy the tea room itself.', 'treats' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>' . esc_html__( 'This website', 'treats' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'The site is built to meet WCAG 2.2 AA: it can be used with a keyboard alone, works with screen readers, respects your device’s reduced-motion and dark-mode settings, and keeps text contrast well above the minimum. If you find something that does not work for you, please tell us and we will fix it.', 'treats' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>' . esc_html__( 'The tea room', 'treats' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'Our ground floor is step-free from Silver Street, with accessible seating and an accessible toilet. Assistance dogs are welcome. Large-print menus are available — just ask. Call us before you visit and we will hold a table that suits you.', 'treats' ) . '</p><!-- /wp:paragraph -->',
		)
	);
}

/**
 * A dismissible pointer to the Customizer after activation.
 *
 * @return void
 */
function treats_admin_welcome_notice() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( get_option( 'treats_welcome_dismissed' ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Simple dismissal link.
	if ( isset( $_GET['treats_dismiss'] ) && check_admin_referer( 'treats_dismiss_welcome' ) ) {
		update_option( 'treats_welcome_dismissed', 1, false );

		return;
	}

	$dismiss_url = wp_nonce_url( add_query_arg( 'treats_dismiss', '1' ), 'treats_dismiss_welcome' );
	?>
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'Treats Tea Room is ready.', 'treats' ); ?></strong></p>
		<p>
			<?php esc_html_e( 'Your pages, menus and starter content have been created. Next: add your opening hours, phone number and social links, then replace the starter menu items and photography with your own.', 'treats' ); ?>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><?php esc_html_e( 'Open the Customizer', 'treats' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=treats_menu_item' ) ); ?>"><?php esc_html_e( 'Edit the menu', 'treats' ); ?></a>
			<a class="button-link" href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'treats' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'treats_admin_welcome_notice' );
