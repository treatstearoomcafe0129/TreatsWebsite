<?php
/**
 * The Treats menu, July 2025.
 *
 * Transcribed from the printed menu. This is the single source of truth for
 * the importer in `menu-import.php` — nothing here writes to the database on
 * its own.
 *
 * Prices are plain numbers so `treats_format_price()` renders them with the
 * currency symbol and two decimals. Where a dish has a second price for a
 * larger serving, the base price stays numeric (so Click & Collect can still
 * total it) and the alternative goes in `note`.
 *
 * Dietary slugs: v (vegetarian), ve (vegan), gf (gluten free),
 * gfa (gluten free available), df (dairy free), n (contains nuts).
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * The menu category tree: five top-level pages, each with its own sections.
 *
 * @return array<string,array{name:string,children:array<string,string>}>
 */
function treats_menu_taxonomy() {
	return array(
		'breakfast-brunch' => array(
			'name'     => __( 'Breakfast & Brunch', 'treats' ),
			'children' => array(
				'morning-menu'      => __( 'Morning Menu', 'treats' ),
				'morning-offers'    => __( 'Morning Special Offers', 'treats' ),
				'all-day-breakfast' => __( 'Breakfast & Brunch, Served All Day', 'treats' ),
			),
		),
		'lunch'            => array(
			'name'     => __( 'Lunch', 'treats' ),
			'children' => array(
				'house-specialities' => __( 'House Specialities', 'treats' ),
				'gourmet-burgers'    => __( 'Gourmet Burgers', 'treats' ),
				'toasted-sandwiches' => __( 'Toasted Sandwiches', 'treats' ),
				'cold-sandwiches'    => __( 'Cold Sandwiches', 'treats' ),
				'wraps'              => __( 'Wraps', 'treats' ),
				'jacket-potato'      => __( 'Jacket Potatoes', 'treats' ),
				'loaded-chips'       => __( 'Loaded Chips', 'treats' ),
				'salads'             => __( 'Salads', 'treats' ),
				'sides'              => __( 'Sides', 'treats' ),
				'childrens-menu'     => __( "Children's Menu", 'treats' ),
			),
		),
		'afternoon-tea'    => array(
			'name'     => __( 'Afternoon Tea', 'treats' ),
			'children' => array(
				'afternoon-tea-for-two' => __( 'Afternoon Tea', 'treats' ),
				'afternoon-offers'      => __( 'Afternoon Offers, After 2pm', 'treats' ),
				'scones-cakes'          => __( 'Scones & Cakes', 'treats' ),
			),
		),
		'cakes-desserts'   => array(
			'name'     => __( 'Cakes & Desserts', 'treats' ),
			'children' => array(
				'american-pancakes'  => __( 'American Pancakes', 'treats' ),
				'cakes-to-take-home' => __( 'Cakes to Take Home', 'treats' ),
			),
		),
		'drinks'           => array(
			'name'     => __( 'Drinks', 'treats' ),
			'children' => array(
				'hot-beverages'            => __( 'Hot Beverages', 'treats' ),
				'coffee'                   => __( 'Coffee', 'treats' ),
				'house-special-beverages'  => __( 'House Special Beverages', 'treats' ),
				'iced-coffee'              => __( 'Iced Coffee', 'treats' ),
				'iced-teas'                => __( 'Iced Teas', 'treats' ),
				'juices'                   => __( 'Juices', 'treats' ),
				'glass-bottles'            => __( 'Glass Bottles', 'treats' ),
				'smoothies'                => __( 'Smoothies', 'treats' ),
				'milkshakes'               => __( 'Milkshakes', 'treats' ),
				'childrens-drinks'         => __( "Children's Drinks", 'treats' ),
				'alcoholic'                => __( 'Alcoholic Beverages', 'treats' ),
			),
		),
	);
}

/**
 * Every dish on the July 2025 menu.
 *
 * @return array<int,array<string,mixed>> Items in the order they should appear.
 */
function treats_menu_items() {
	$sandwich_bread = __( 'Choice of sourdough, white or brown bread. Served with salad and coleslaw.', 'treats' );

	return array(

		/* ---------------------------------------------- Morning menu ----- */

		array(
			'title'       => __( 'Eggs on Toast', 'treats' ),
			'description' => __( 'A choice of 2 poached or 2 fried eggs on sourdough, white, brown or gluten free toast. £1 extra for scrambled egg.', 'treats' ),
			'price'       => '10',
			'categories'  => array( 'breakfast-brunch', 'morning-menu' ),
			'dietary'     => array( 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Avocado Poached Eggs', 'treats' ),
			'description' => __( 'Sourdough, white, brown or gluten free toast spread with smashed avocado, with 2 seasoned poached eggs.', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'breakfast-brunch', 'morning-menu' ),
			'dietary'     => array( 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Breakfast Wrap', 'treats' ),
			'description' => __( 'Sausage, bacon, hashbrown, scrambled egg and a pot of beans.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'breakfast-brunch', 'morning-menu' ),
		),
		array(
			'title'       => __( 'Veggie Breakfast Wrap', 'treats' ),
			'description' => __( 'Scrambled egg, hashbrown, halloumi and a pot of beans.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'breakfast-brunch', 'morning-menu' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Greek Yoghurt', 'treats' ),
			'description' => __( 'Served with blueberries, banana and honey.', 'treats' ),
			'price'       => '8',
			'categories'  => array( 'breakfast-brunch', 'morning-menu' ),
			'dietary'     => array( 'v' ),
		),

		/* ------------------------------------- Morning special offers ---- */

		array(
			'title'       => __( 'Create Your Own Stottie', 'treats' ),
			'description' => __( 'A one item breakfast stottie served with any free hot drink. Additional meat items — bacon, sausage or veggie sausage — £1.50 each. Black pudding, hash brown, tomato, egg, halloumi, mushroom, avocado or beans £1 each.', 'treats' ),
			'price'       => '7.5',
			'note'        => __( 'until 10.30am', 'treats' ),
			'categories'  => array( 'breakfast-brunch', 'morning-offers' ),
			'badge'       => __( 'Includes a hot drink', 'treats' ),
		),
		array(
			'title'       => __( '6 Item Breakfast', 'treats' ),
			'description' => __( 'Sausage, bacon, hashbrown, egg, beans and sourdough, served with any hot drink. Gluten free option replaces the sausage with an extra piece of bacon. £1 extra for scrambled egg.', 'treats' ),
			'price'       => '11',
			'note'        => __( 'until 10.30am', 'treats' ),
			'categories'  => array( 'breakfast-brunch', 'morning-offers' ),
			'dietary'     => array( 'gfa' ),
			'badge'       => __( 'Includes a hot drink', 'treats' ),
			'featured'    => true,
		),
		array(
			'title'       => __( 'Vegetarian 6 Item Breakfast', 'treats' ),
			'description' => __( 'Vegetarian sausage, hash brown, fried egg, halloumi, beans and sourdough, served with any hot drink. £1 extra for scrambled egg.', 'treats' ),
			'price'       => '11',
			'note'        => __( 'until 10.30am', 'treats' ),
			'categories'  => array( 'breakfast-brunch', 'morning-offers' ),
			'dietary'     => array( 'v' ),
			'badge'       => __( 'Includes a hot drink', 'treats' ),
		),
		array(
			'title'       => __( 'Vegan 6 Item Breakfast', 'treats' ),
			'description' => __( 'Vegan sausage, hashbrown, tomato, mushroom, beans and sourdough, served with any hot drink.', 'treats' ),
			'price'       => '11',
			'note'        => __( 'until 10.30am', 'treats' ),
			'categories'  => array( 'breakfast-brunch', 'morning-offers' ),
			'dietary'     => array( 've', 'v' ),
			'badge'       => __( 'Includes a hot drink', 'treats' ),
		),
		array(
			'title'       => __( 'Morning Scone Offer', 'treats' ),
			'description' => __( 'Any scone or toasted teacake with butter and a choice of strawberry, blackcurrant or raspberry preserve, or onion chutney, served with any hot drink. Scone options: fruit, cheese or the daily special. Add clotted cream for £1.25.', 'treats' ),
			'price'       => '6.5',
			'note'        => __( 'until 10.30am', 'treats' ),
			'categories'  => array( 'breakfast-brunch', 'morning-offers' ),
			'dietary'     => array( 'v' ),
			'badge'       => __( 'Includes a hot drink', 'treats' ),
		),

		/* ------------------------------- Breakfast & brunch, all day ----- */

		array(
			'title'       => __( 'Traditional Full English Breakfast', 'treats' ),
			'description' => __( 'Sausage, 2 bacon, 2 hashbrown, mushroom, tomato, black pudding, fried egg, beans and sourdough.', 'treats' ),
			'price'       => '15',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'featured'    => true,
		),
		array(
			'title'       => __( 'Vegetarian Full English Breakfast', 'treats' ),
			'description' => __( 'Vegetarian sausage, 2 hashbrown, halloumi, mushroom, tomato, avocado, fried egg, beans and sourdough.', 'treats' ),
			'price'       => '15',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Vegan Full English Breakfast', 'treats' ),
			'description' => __( '2 vegan sausages, 2 hashbrown, 2 mushroom, tomato, avocado, beans and sourdough.', 'treats' ),
			'price'       => '15',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Eggs Benedict', 'treats' ),
			'description' => __( '2 bacon and 2 poached eggs on sourdough, covered with hollandaise and seasoned with chives.', 'treats' ),
			'price'       => '14',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
		),
		array(
			'title'       => __( 'Miners Benedict', 'treats' ),
			'description' => __( '2 slices of streaky bacon, crumbled black pudding and 2 poached eggs on sourdough, covered with hollandaise.', 'treats' ),
			'price'       => '15',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
		),
		array(
			'title'       => __( 'Eggs Royal', 'treats' ),
			'description' => __( 'Smoked salmon on sourdough with 2 poached eggs, covered with hollandaise sauce and seasoned with chives.', 'treats' ),
			'price'       => '14.5',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
		),
		array(
			'title'       => __( 'Eggs Florentine', 'treats' ),
			'description' => __( 'Wilted spinach with avocado on sourdough and 2 poached eggs, covered with hollandaise and seasoned with chives.', 'treats' ),
			'price'       => '14.2',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Welsh Rarebit', 'treats' ),
			'description' => __( 'Grilled cheese, mustard and Worcester mix on sourdough, topped with 2 poached eggs and seasoned with chives.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Rarebit Plus', 'treats' ),
			'description' => __( 'Our Welsh rarebit with 2 streaky bacon and a black pudding crumb.', 'treats' ),
			'price'       => '14.5',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
		),
		array(
			'title'       => __( 'Avocado Breakfast Toast', 'treats' ),
			'description' => __( 'Avocado on sourdough with sausage, bacon, fried egg and grilled halloumi.', 'treats' ),
			'price'       => '14.5',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
		),
		array(
			'title'       => __( 'Summer Avocado', 'treats' ),
			'description' => __( 'Avocado on brioche toast with a choice of strawberries, blueberries or both, drizzled with sweet chilli infused maple syrup.', 'treats' ),
			'price'       => '12.50',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Avocado Vegan Sausage', 'treats' ),
			'description' => __( 'Avocado, sundried tomato and cherry tomato on sourdough, served with vegan sausage, mushroom and 2 hashbrown.', 'treats' ),
			'price'       => '14',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Avocado Goats Cheese', 'treats' ),
			'description' => __( 'Avocado on sourdough sprinkled with goats cheese, poached egg, halloumi, olives and sundried tomato.', 'treats' ),
			'price'       => '14',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Avocado Salmon', 'treats' ),
			'description' => __( 'Avocado on sourdough with a side of smoked salmon, spinach, poached egg and grilled halloumi.', 'treats' ),
			'price'       => '15',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
		),
		array(
			'title'       => __( 'Prawn Marie Rose Avocado', 'treats' ),
			'description' => __( 'Avocado on sourdough topped with baby prawns and sundried tomatoes, drizzled with Marie Rose sauce.', 'treats' ),
			'price'       => '14',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
		),
		array(
			'title'       => __( 'Goats Cheese & Hummus', 'treats' ),
			'description' => __( 'Goats cheese, olives, sundried tomatoes and roasted red pepper hummus on sourdough.', 'treats' ),
			'price'       => '13.7',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Mushroom Hummus Toast', 'treats' ),
			'description' => __( 'Red pepper hummus on sourdough with mushroom, halloumi and sundried tomatoes.', 'treats' ),
			'price'       => '13.7',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Sweet Avocado Bacon Brioche', 'treats' ),
			'description' => __( 'Avocado on brioche toast with 2 slices of streaky bacon and 2 poached eggs, drizzled with chilli jam.', 'treats' ),
			'price'       => '13.8',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
		),
		array(
			'title'       => __( 'Breakfast Pancakes', 'treats' ),
			'description' => __( '2 pancakes served with sausage, bacon, fried egg, hashbrown, beans and maple syrup.', 'treats' ),
			'price'       => '14.7',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
		),
		array(
			'title'       => __( 'Veggie Breakfast Pancakes', 'treats' ),
			'description' => __( 'Served with veggie sausage, hashbrown, fried egg, beans and maple syrup.', 'treats' ),
			'price'       => '14.5',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Sweet Brioche Banana Bacon', 'treats' ),
			'description' => __( 'Cinnamon sugar toasted brioche served with streaky bacon and banana, drizzled in maple syrup.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'breakfast-brunch', 'all-day-breakfast' ),
		),

		/* ------------------------------------------ House specialities --- */

		array(
			'title'       => __( 'Fish Goujon Bun', 'treats' ),
			'description' => __( 'Cod goujons in a bun with lettuce, tomato and tartar sauce, or prawn Marie Rose. Served with salad and coleslaw.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'lunch', 'house-specialities' ),
		),
		array(
			'title'       => __( 'Quiche of the Day', 'treats' ),
			'description' => __( 'Homemade quiche, served with salad and coleslaw.', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'lunch', 'house-specialities' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Pie of the Day', 'treats' ),
			'description' => __( 'Homemade pie of the day, served with salad and coleslaw. £16 with chips, gravy and beans or garden peas.', 'treats' ),
			'price'       => '12',
			'note'        => __( 'or £16 with chips', 'treats' ),
			'categories'  => array( 'lunch', 'house-specialities' ),
			'collect'     => false,
		),
		array(
			'title'       => __( 'Hot Brisket Bun', 'treats' ),
			'description' => __( 'Slow cooked beef brisket bun with a choice of caramelised onion or horseradish, served with salad and coleslaw. £17 served with beans, chips and gravy.', 'treats' ),
			'price'       => '13.70',
			'note'        => __( 'or £17 with chips', 'treats' ),
			'categories'  => array( 'lunch', 'house-specialities' ),
			'collect'     => false,
			'featured'    => true,
		),
		array(
			'title'       => __( 'Ham, Egg & Chips', 'treats' ),
			'description' => __( '3 slices of Wiltshire ham and 2 fried eggs, served with garden peas.', 'treats' ),
			'price'       => '14',
			'categories'  => array( 'lunch', 'house-specialities' ),
		),
		array(
			'title'       => __( 'BBQ Pulled Pork Bun', 'treats' ),
			'description' => __( 'BBQ pulled pork and black pudding in a bun with cheese, served with salad and coleslaw. £16.50 with chips, beans or garden peas and gravy.', 'treats' ),
			'price'       => '13.70',
			'note'        => __( 'or £16.50 with chips', 'treats' ),
			'categories'  => array( 'lunch', 'house-specialities' ),
			'collect'     => false,
		),
		array(
			'title'       => __( 'Chicken, Stuffing & Apple Sauce Bun', 'treats' ),
			'description' => __( 'Served with salad and coleslaw. £16.30 served with chips, gravy and peas.', 'treats' ),
			'price'       => '13.50',
			'note'        => __( 'or £16.30 with chips', 'treats' ),
			'categories'  => array( 'lunch', 'house-specialities' ),
			'collect'     => false,
		),
		array(
			'title'       => __( 'Soup of the Day', 'treats' ),
			'description' => __( 'Please ask a member of staff. Served with a choice of bread.', 'treats' ),
			'price'       => '8.5',
			'categories'  => array( 'lunch', 'house-specialities' ),
		),

		/* --------------------------------------------- Gourmet burgers --- */

		array(
			'title'       => __( 'Cheeseburger', 'treats' ),
			'description' => __( 'Cheeseburger in a brioche bun with lettuce, tomato and pickled gherkin. Served with chips and any dip.', 'treats' ),
			'price'       => '14',
			'categories'  => array( 'lunch', 'gourmet-burgers' ),
		),
		array(
			'title'       => __( 'Breakfast Burger', 'treats' ),
			'description' => __( 'Beef burger topped with bacon, cheese, hashbrown and fried egg in a brioche bun. Served with chips, a side of beans and any dip.', 'treats' ),
			'price'       => '16',
			'categories'  => array( 'lunch', 'gourmet-burgers' ),
			'featured'    => true,
		),
		array(
			'title'       => __( 'Bacon Cheese Burger', 'treats' ),
			'description' => __( 'Beef burger served with bacon, cheese, lettuce and tomato in a brioche bun. Served with chips and any dip.', 'treats' ),
			'price'       => '15',
			'categories'  => array( 'lunch', 'gourmet-burgers' ),
		),
		array(
			'title'       => __( 'Pulled Pork Burger', 'treats' ),
			'description' => __( 'Beef burger topped with BBQ pulled pork and cheese in a brioche bun. Served with chips and any dip.', 'treats' ),
			'price'       => '15.5',
			'categories'  => array( 'lunch', 'gourmet-burgers' ),
		),
		array(
			'title'       => __( 'Fajita Melt Burger', 'treats' ),
			'description' => __( 'Beef burger topped with chicken fajita and cheese in a brioche bun. Served with chips and any dip.', 'treats' ),
			'price'       => '15.5',
			'categories'  => array( 'lunch', 'gourmet-burgers' ),
		),
		array(
			'title'       => __( 'Sweet Chilli Chicken Burger', 'treats' ),
			'description' => __( 'Panko chicken burger in a brioche bun with lettuce, tomato and peppers, topped with streaky bacon. Served with chips and any dip.', 'treats' ),
			'price'       => '14.8',
			'categories'  => array( 'lunch', 'gourmet-burgers' ),
		),
		array(
			'title'       => __( 'Hot Honey Chicken Burger', 'treats' ),
			'description' => __( 'Panko chicken burger with lettuce, tomato and brie, drizzled in hot honey in a brioche bun. Served with chips and any dip.', 'treats' ),
			'price'       => '14.8',
			'categories'  => array( 'lunch', 'gourmet-burgers' ),
		),
		array(
			'title'       => __( 'Vegan Cheeseburger', 'treats' ),
			'description' => __( 'Premium vegan plant based burger in a vegan brioche with lettuce and tomato, topped with vegan cheese. Served with chips and any dip.', 'treats' ),
			'price'       => '14.5',
			'categories'  => array( 'lunch', 'gourmet-burgers' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Tofu Burger', 'treats' ),
			'description' => __( 'Tofu burger with avocado, lettuce and tomato in a brioche bun. Served with chips and any dip.', 'treats' ),
			'price'       => '14.5',
			'categories'  => array( 'lunch', 'gourmet-burgers' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Spicy Bean Burger', 'treats' ),
			'description' => __( 'Spicy Mexican bean burger with lettuce, tomato and hummus in a brioche bun. Served with chips and any dip.', 'treats' ),
			'price'       => '14.5',
			'categories'  => array( 'lunch', 'gourmet-burgers' ),
			'dietary'     => array( 'v' ),
		),

		/* ------------------------------------------ Toasted sandwiches --- */

		array(
			'title'       => __( 'Brie & Cranberry', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '12.5',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Bacon, Brie & Cranberry', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Chicken Fajita & Cheese', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'BBQ Pulled Pork & Cheese', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Steak & Cream Cheese', 'treats' ),
			'description' => __( 'With caramelised onion. Choice of sourdough, white or brown bread. Served with salad and coleslaw.', 'treats' ),
			'price'       => '14',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Steak, Cheese & Dijon Mustard', 'treats' ),
			'description' => __( 'With pickled gherkin. Choice of sourdough, white or brown bread. Served with salad and coleslaw.', 'treats' ),
			'price'       => '14',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Chicken, Brie & Honey', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Brie & Honey', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '13',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Ham & Cheese Toastie', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '11',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Grilled Chicken', 'treats' ),
			'description' => __( 'With lettuce, tomato and hummus. Choice of sourdough, white or brown bread. Served with salad and coleslaw.', 'treats' ),
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Grilled Tofu', 'treats' ),
			'description' => __( 'With lettuce, tomato and chilli jam. Choice of sourdough, white or brown bread. Served with salad and coleslaw.', 'treats' ),
			'price'       => '12.5',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 've', 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Tofu, Hummus & Tomato', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '12.5',
			'categories'  => array( 'lunch', 'toasted-sandwiches' ),
			'dietary'     => array( 've', 'v', 'gfa' ),
		),

		/* --------------------------------------------- Cold sandwiches --- */

		array(
			'title'       => __( 'Tuna & Cucumber', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '8.8',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Cheese Savoury', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '8.8',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Smoked Salmon & Cream Cheese', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '9.5',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Prawn Marie Rose', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '9.2',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Prawn Cocktail', 'treats' ),
			'description' => __( 'Lettuce, cucumber, tomato and prawn Marie Rose. Choice of sourdough, white or brown bread. Served with salad and coleslaw.', 'treats' ),
			'price'       => '9.5',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Deli Sandwich', 'treats' ),
			'description' => __( 'Beef, lettuce, pickled gherkin and Dijon. Choice of sourdough, white or brown bread. Served with salad and coleslaw.', 'treats' ),
			'price'       => '9.5',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'BLT', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '10',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Ham, Cheese & Pickle', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '9.2',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Ham Salad Sandwich', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '8.8',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Ham & Cheese', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '8.8',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Hummus, Cucumber & Tomato', 'treats' ),
			'description' => $sandwich_bread,
			'price'       => '8.5',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 've', 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Cheese & Pickle', 'treats' ),
			'description' => __( 'Vegan option available. Choice of sourdough, white or brown bread. Served with salad and coleslaw.', 'treats' ),
			'price'       => '8.5',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Cheese, Cucumber & Tomato', 'treats' ),
			'description' => __( 'Vegan option available. Choice of sourdough, white or brown bread. Served with salad and coleslaw.', 'treats' ),
			'price'       => '8.5',
			'categories'  => array( 'lunch', 'cold-sandwiches' ),
			'dietary'     => array( 'v', 'gfa' ),
		),

		/* ------------------------------------------------------- Wraps --- */

		array(
			'title'       => __( 'Twisted Chicken Wrap', 'treats' ),
			'description' => __( 'Breaded chicken, smoked streaky bacon, cheese, lettuce, tomato and chipotle mayonnaise. Served with salad and coleslaw.', 'treats' ),
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'wraps' ),
		),
		array(
			'title'       => __( 'Chicken Caesar Wrap', 'treats' ),
			'description' => __( 'Breaded or grilled chicken with lettuce, tomato, parmesan cheese and Caesar sauce. Served with salad and coleslaw.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'lunch', 'wraps' ),
		),
		array(
			'title'       => __( 'Avocado Chicken Wrap', 'treats' ),
			'description' => __( 'Avocado, grilled chicken, lettuce, tomato and mayonnaise. Served with salad and coleslaw.', 'treats' ),
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'wraps' ),
		),
		array(
			'title'       => __( 'Sweet Chilli Chicken Wrap', 'treats' ),
			'description' => __( 'Breaded or grilled chicken, lettuce, peppers and sweet chilli sauce. Served with salad and coleslaw.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'lunch', 'wraps' ),
		),
		array(
			'title'       => __( 'Hunters Chicken Wrap', 'treats' ),
			'description' => __( 'Grilled chicken, bacon, cheese and BBQ sauce. Served with salad and coleslaw.', 'treats' ),
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'wraps' ),
		),

		/* ---------------------------------------------- Jacket potato --- */

		array(
			'title'       => __( 'Beans or Cheese', 'treats' ),
			'description' => __( 'Vegan cheese option available. Served with salad and coleslaw.', 'treats' ),
			'price'       => '8.8',
			'categories'  => array( 'lunch', 'jacket-potato' ),
			'dietary'     => array( 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Cheese & Beans', 'treats' ),
			'description' => __( 'Served with salad and coleslaw.', 'treats' ),
			'price'       => '9.5',
			'categories'  => array( 'lunch', 'jacket-potato' ),
			'dietary'     => array( 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Bacon, Brie & Cranberry Jacket', 'treats' ),
			'description' => __( 'Served with salad and coleslaw.', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'lunch', 'jacket-potato' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Prawns in Marie Rose', 'treats' ),
			'description' => __( 'Served with salad and coleslaw.', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'lunch', 'jacket-potato' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Cheese Savoury Jacket', 'treats' ),
			'description' => __( 'Served with salad and coleslaw.', 'treats' ),
			'price'       => '11',
			'categories'  => array( 'lunch', 'jacket-potato' ),
			'dietary'     => array( 'v', 'gfa' ),
		),
		array(
			'title'       => __( 'Chicken Fajita with Cheese', 'treats' ),
			'description' => __( 'Served with salad and coleslaw.', 'treats' ),
			'price'       => '12.5',
			'categories'  => array( 'lunch', 'jacket-potato' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'BBQ Pulled Pork with Cheese', 'treats' ),
			'description' => __( 'Served with salad and coleslaw.', 'treats' ),
			'price'       => '12.5',
			'categories'  => array( 'lunch', 'jacket-potato' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Bacon & Cheese Savoury', 'treats' ),
			'description' => __( 'Served with salad and coleslaw.', 'treats' ),
			'price'       => '12.5',
			'categories'  => array( 'lunch', 'jacket-potato' ),
			'dietary'     => array( 'gfa' ),
		),
		array(
			'title'       => __( 'Tuna Mayonnaise', 'treats' ),
			'description' => __( 'Served with salad and coleslaw.', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'lunch', 'jacket-potato' ),
			'dietary'     => array( 'gfa' ),
		),

		/* ----------------------------------------------- Loaded chips --- */

		array(
			'title'       => __( 'Steak, Cheese, Chives & Chipotle Mayo', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'lunch', 'loaded-chips' ),
		),
		array(
			'title'       => __( 'Tuna & Cheese Loaded Chips', 'treats' ),
			'price'       => '9',
			'categories'  => array( 'lunch', 'loaded-chips' ),
		),
		array(
			'title'       => __( 'Chicken Fajita & Cheese Loaded Chips', 'treats' ),
			'price'       => '11.5',
			'categories'  => array( 'lunch', 'loaded-chips' ),
		),
		array(
			'title'       => __( 'BBQ Pulled Pork, Cheese & Chives', 'treats' ),
			'price'       => '11.5',
			'categories'  => array( 'lunch', 'loaded-chips' ),
		),
		array(
			'title'       => __( 'Mixed Cheese Loaded Chips', 'treats' ),
			'price'       => '9',
			'categories'  => array( 'lunch', 'loaded-chips' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Cheese Savoury & Bacon Loaded Chips', 'treats' ),
			'price'       => '11',
			'categories'  => array( 'lunch', 'loaded-chips' ),
		),
		array(
			'title'       => __( 'Vegan Cheese Loaded Chips', 'treats' ),
			'price'       => '11.5',
			'categories'  => array( 'lunch', 'loaded-chips' ),
			'dietary'     => array( 've', 'v' ),
		),

		/* ------------------------------------------------------ Salads --- */

		array(
			'title'       => __( 'Avocado Salad', 'treats' ),
			'description' => __( 'Lettuce, tomato, cucumber, sundried tomato, boiled egg, olives, strawberry, blueberry and lemon. Served with honey mustard or balsamic glaze. Vegan option available.', 'treats' ),
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'salads' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Goats Cheese Salad', 'treats' ),
			'description' => __( 'Lettuce, tomato, cucumber, sundried tomato, boiled egg, olives, strawberry, blueberry and lemon. Served with honey mustard or balsamic glaze.', 'treats' ),
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'salads' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Halloumi Salad', 'treats' ),
			'description' => __( 'Lettuce, tomato, cucumber, sundried tomato, boiled egg, olives, strawberry, blueberry and lemon. Served with honey mustard or balsamic glaze.', 'treats' ),
			'price'       => '13.5',
			'categories'  => array( 'lunch', 'salads' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Seafood Salad, Salmon & Prawn', 'treats' ),
			'description' => __( 'Lettuce, tomato, cucumber, sundried tomato, boiled egg, olives, strawberry, blueberry and lemon. Served with honey mustard, balsamic glaze or prawn Marie Rose.', 'treats' ),
			'price'       => '14.5',
			'categories'  => array( 'lunch', 'salads' ),
		),
		array(
			'title'       => __( 'Chicken Caesar Salad', 'treats' ),
			'description' => __( 'Choose between grilled chicken or chicken goujon. Served with lettuce, tomato, cucumber, sundried tomato, boiled egg, chopped bacon and Caesar sauce, sprinkled with parmesan cheese.', 'treats' ),
			'price'       => '14.5',
			'categories'  => array( 'lunch', 'salads' ),
		),
		array(
			'title'       => __( 'Tuna Salad', 'treats' ),
			'description' => __( 'Lettuce, tomato, cucumber, sundried tomato, boiled egg, olives, strawberry, blueberry and lemon. Served with honey mustard or balsamic glaze.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'lunch', 'salads' ),
		),
		array(
			'title'       => __( 'Tofu Salad', 'treats' ),
			'description' => __( 'Lettuce, tomato, cucumber, sundried tomato, olives, strawberry, blueberry and lemon. Served with balsamic glaze.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'lunch', 'salads' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Grilled Chicken & Avocado Salad', 'treats' ),
			'description' => __( 'Lettuce, tomato, cucumber, sundried tomato, boiled egg, olives, strawberry, blueberry and lemon. Served with honey mustard or balsamic glaze.', 'treats' ),
			'price'       => '14.5',
			'categories'  => array( 'lunch', 'salads' ),
		),

		/* ------------------------------------------------------- Sides --- */

		array(
			'title'       => __( '5 Chicken Goujons', 'treats' ),
			'description' => __( 'Served with any dip.', 'treats' ),
			'price'       => '8',
			'categories'  => array( 'lunch', 'sides' ),
		),
		array(
			'title'       => __( 'Chunky Chips', 'treats' ),
			'description' => __( 'Served with any dip.', 'treats' ),
			'price'       => '4.5',
			'categories'  => array( 'lunch', 'sides' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Halloumi Fries', 'treats' ),
			'description' => __( 'Served with any dip or hummus.', 'treats' ),
			'price'       => '8.5',
			'categories'  => array( 'lunch', 'sides' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( '5 Hash Browns', 'treats' ),
			'description' => __( 'Served with any dip.', 'treats' ),
			'price'       => '5',
			'categories'  => array( 'lunch', 'sides' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Olives', 'treats' ),
			'price'       => '3.5',
			'categories'  => array( 'lunch', 'sides' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Dips', 'treats' ),
			'description' => __( 'BBQ, Caesar, honey mustard, sweet chilli, garlic, chilli jam or hot honey.', 'treats' ),
			'price'       => '',
			'categories'  => array( 'lunch', 'sides' ),
			'collect'     => false,
		),

		/* -------------------------------------------- Children\'s menu --- */

		array(
			'title'       => __( "Children's Meal", 'treats' ),
			'description' => __( 'A main, an ice cream dessert and a drink, for ages 13 and under. Mains: sausage, chips and beans; cheeseburger and chips; cod goujons, chips and beans; chicken goujons, chips and beans; grilled chicken, chips and beans; cheese or ham sandwich with chips; or a one stack pancake. Ice cream: vanilla, strawberry or salted caramel. Drink: orange or blackcurrant fruit shoot, milk or apple juice.', 'treats' ),
			'price'       => '10',
			'categories'  => array( 'lunch', 'childrens-menu' ),
		),

		/* ----------------------------------------------- Afternoon tea --- */

		array(
			'title'       => __( 'Afternoon Tea for Two', 'treats' ),
			'description' => __( '2 afternoon tea dainty sandwiches on white or brown, 2 mini corned beef pies, 2 mini quiches, 2 scones with preserve and clotted cream, and 2 cakes, served with any 2 hot drinks. Sandwich options: cheese savoury, smoked salmon cream cheese, hummus cucumber tomato, ham, tuna cucumber, prawn Marie Rose, or cheese and pickle. For cakes please see the cake display. Upgrade to an alcoholic beverage for £8 extra.', 'treats' ),
			'price'       => '44',
			'note'        => __( 'for two', 'treats' ),
			'categories'  => array( 'afternoon-tea', 'afternoon-tea-for-two' ),
			'featured'    => true,
			'badge'       => __( 'Booking recommended', 'treats' ),
		),

		/* -------------------------------------------- Afternoon offers --- */

		array(
			'title'       => __( 'Cake & Hot Drink', 'treats' ),
			'description' => __( 'Choose any one of our homemade cakes and any hot drink.', 'treats' ),
			'price'       => '8',
			'note'        => __( 'after 2pm', 'treats' ),
			'categories'  => array( 'afternoon-tea', 'afternoon-offers' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Scone or Toasted Tea Cake', 'treats' ),
			'description' => __( 'Any scone or toasted teacake with butter and a choice of strawberry, blackcurrant or raspberry preserve, served with any hot drink. Scone options: fruit, cheese or the daily special. Add clotted cream for £1.25.', 'treats' ),
			'price'       => '6.5',
			'note'        => __( 'after 2pm', 'treats' ),
			'categories'  => array( 'afternoon-tea', 'afternoon-offers' ),
			'dietary'     => array( 'v' ),
		),

		/* ------------------------------------------------ Scones/cakes --- */

		array(
			'title'       => __( 'Cheese Scone', 'treats' ),
			'price'       => '4',
			'categories'  => array( 'afternoon-tea', 'scones-cakes' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Fruit Scone', 'treats' ),
			'price'       => '4',
			'categories'  => array( 'afternoon-tea', 'scones-cakes' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Toasted Tea Cake', 'treats' ),
			'price'       => '4',
			'categories'  => array( 'afternoon-tea', 'scones-cakes' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Clotted Cream', 'treats' ),
			'price'       => '1.25',
			'categories'  => array( 'afternoon-tea', 'scones-cakes' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Homemade Cakes', 'treats' ),
			'description' => __( 'Please see the display fridge for today’s selection.', 'treats' ),
			'price'       => '6',
			'categories'  => array( 'afternoon-tea', 'scones-cakes', 'cakes-desserts', 'cakes-to-take-home' ),
			'dietary'     => array( 'v' ),
		),

		/* ------------------------------------------- American pancakes --- */

		array(
			'title'       => __( 'Pancake Stack', 'treats' ),
			'description' => __( 'Served with streaky bacon, strawberries, blueberries and maple syrup.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'cakes-desserts', 'american-pancakes' ),
		),
		array(
			'title'       => __( 'Brownie Pancakes', 'treats' ),
			'description' => __( 'Served with Nutella, brownie pieces and squirty cream.', 'treats' ),
			'price'       => '13',
			'categories'  => array( 'cakes-desserts', 'american-pancakes' ),
			'dietary'     => array( 'v', 'n' ),
		),
		array(
			'title'       => __( 'Nutella Pancakes', 'treats' ),
			'description' => __( 'Served with strawberries and blueberries.', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'cakes-desserts', 'american-pancakes' ),
			'dietary'     => array( 'v', 'n' ),
		),
		array(
			'title'       => __( 'Biscoff Pancakes', 'treats' ),
			'description' => __( 'Served with Biscoff spread, Biscoff crumb and squirty cream.', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'cakes-desserts', 'american-pancakes' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Fruit Pancakes', 'treats' ),
			'description' => __( 'Served with strawberries, blueberries and maple syrup.', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'cakes-desserts', 'american-pancakes' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Cinnamon Apple Pancakes', 'treats' ),
			'description' => __( 'Served with pouring cream or squirty cream.', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'cakes-desserts', 'american-pancakes' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Oreo Pancakes', 'treats' ),
			'description' => __( 'Served with Nutella, crushed Oreo bits and squirty cream.', 'treats' ),
			'price'       => '12',
			'categories'  => array( 'cakes-desserts', 'american-pancakes' ),
			'dietary'     => array( 'v', 'n' ),
		),

		/* ------------------------------------------ Cakes to take home --- */

		array(
			'title'       => __( 'Full Cakes', 'treats' ),
			'description' => __( 'Whole homemade cakes to order. Two day lead time, collection only — please ask a member of staff.', 'treats' ),
			'price'       => '40',
			'categories'  => array( 'cakes-desserts', 'cakes-to-take-home' ),
			'collect'     => false,
			'featured'    => true,
		),
		array(
			'title'       => __( 'Take Out Cake Offer', 'treats' ),
			'description' => __( 'Five cakes to take away.', 'treats' ),
			'price'       => '20',
			'note'        => __( '5 for £20', 'treats' ),
			'categories'  => array( 'cakes-desserts', 'cakes-to-take-home' ),
			'collect'     => false,
		),

		/* ----------------------------------------------- Hot beverages --- */

		array(
			'title'       => __( 'Breakfast Tea', 'treats' ),
			'price'       => '3.15',
			'categories'  => array( 'drinks', 'hot-beverages' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Loose Leaf Tea', 'treats' ),
			'description' => __( 'Earl Grey, raspberry and lavender, masala chai, jasmine green tea, white tea with mango, sweet mate, tropical rooibos, lemongrass ginger papaya, red berry infusion, calming camomile, strawberry and mint, proper peppermint, or hibiscus rose apple.', 'treats' ),
			'price'       => '4.25',
			'categories'  => array( 'drinks', 'hot-beverages' ),
			'dietary'     => array( 've', 'v' ),
		),

		/* ------------------------------------------------------ Coffee --- */

		array(
			'title'       => __( 'Americano', 'treats' ),
			'price'       => '3.9',
			'categories'  => array( 'drinks', 'coffee' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Latte', 'treats' ),
			'price'       => '4',
			'categories'  => array( 'drinks', 'coffee' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Flat White', 'treats' ),
			'price'       => '4',
			'categories'  => array( 'drinks', 'coffee' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Cappuccino', 'treats' ),
			'price'       => '4',
			'categories'  => array( 'drinks', 'coffee' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Milk & Syrup Options', 'treats' ),
			'description' => __( 'Full fat, semi skimmed, oat or soya milk. Caramel or vanilla syrup £1. Deluxe upgrade 50p for cream and marshmallows.', 'treats' ),
			'price'       => '',
			'categories'  => array( 'drinks', 'coffee' ),
			'collect'     => false,
		),

		/* --------------------------------- House special beverages ------- */

		array(
			'title'       => __( 'Mocha', 'treats' ),
			'price'       => '4.5',
			'categories'  => array( 'drinks', 'house-special-beverages' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Chai Latte', 'treats' ),
			'price'       => '4.5',
			'categories'  => array( 'drinks', 'house-special-beverages' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Hot Chocolate', 'treats' ),
			'price'       => '4.5',
			'categories'  => array( 'drinks', 'house-special-beverages' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Matcha Latte', 'treats' ),
			'price'       => '4.5',
			'categories'  => array( 'drinks', 'house-special-beverages' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Mint Hot Chocolate Deluxe', 'treats' ),
			'price'       => '4.5',
			'categories'  => array( 'drinks', 'house-special-beverages' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Hot Chocolate Orange Deluxe', 'treats' ),
			'price'       => '4.5',
			'categories'  => array( 'drinks', 'house-special-beverages' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Smores Latte', 'treats' ),
			'price'       => '5',
			'categories'  => array( 'drinks', 'house-special-beverages' ),
			'dietary'     => array( 'v' ),
		),

		/* ------------------------------------------------- Iced coffee --- */

		array(
			'title'       => __( 'Iced Americano', 'treats' ),
			'price'       => '6',
			'categories'  => array( 'drinks', 'iced-coffee' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Iced Latte', 'treats' ),
			'price'       => '6',
			'categories'  => array( 'drinks', 'iced-coffee' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Iced Chai Latte', 'treats' ),
			'price'       => '6',
			'categories'  => array( 'drinks', 'iced-coffee' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Iced Matcha Latte', 'treats' ),
			'price'       => '6',
			'categories'  => array( 'drinks', 'iced-coffee' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Dirty Iced Chai Latte', 'treats' ),
			'price'       => '6',
			'categories'  => array( 'drinks', 'iced-coffee' ),
			'dietary'     => array( 'v' ),
		),

		/* --------------------------------------------------- Iced teas --- */

		array(
			'title'       => __( 'Peach Iced Tea', 'treats' ),
			'price'       => '5.7',
			'categories'  => array( 'drinks', 'iced-teas' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Lemon Iced Tea', 'treats' ),
			'price'       => '5.7',
			'categories'  => array( 'drinks', 'iced-teas' ),
			'dietary'     => array( 've', 'v' ),
		),

		/* ------------------------------------------------------ Juices --- */

		array(
			'title'       => __( 'Freshly Squeezed Orange', 'treats' ),
			'price'       => '5.5',
			'categories'  => array( 'drinks', 'juices' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Cloudy Apple Juice', 'treats' ),
			'price'       => '4.5',
			'categories'  => array( 'drinks', 'juices' ),
			'dietary'     => array( 've', 'v' ),
		),

		/* ----------------------------------------------- Glass bottles --- */

		array(
			'title'       => __( 'Coca Cola', 'treats' ),
			'price'       => '3.8',
			'categories'  => array( 'drinks', 'glass-bottles' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Diet Coca Cola', 'treats' ),
			'price'       => '3.8',
			'categories'  => array( 'drinks', 'glass-bottles' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Fanta', 'treats' ),
			'price'       => '3.8',
			'categories'  => array( 'drinks', 'glass-bottles' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Fentimans Rose Lemonade', 'treats' ),
			'price'       => '3.8',
			'categories'  => array( 'drinks', 'glass-bottles' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Ginger Beer', 'treats' ),
			'price'       => '3.8',
			'categories'  => array( 'drinks', 'glass-bottles' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Bottled Water', 'treats' ),
			'price'       => '2.9',
			'categories'  => array( 'drinks', 'glass-bottles' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Sparkling Water', 'treats' ),
			'price'       => '2.9',
			'categories'  => array( 'drinks', 'glass-bottles' ),
			'dietary'     => array( 've', 'v' ),
		),

		/* --------------------------------------------------- Smoothies --- */

		array(
			'title'       => __( 'Blueberry Banana Smoothie', 'treats' ),
			'description' => __( 'Banana, blueberries, Greek yoghurt and milk with a drizzle of honey and cinnamon.', 'treats' ),
			'price'       => '6.5',
			'categories'  => array( 'drinks', 'smoothies' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Blueberry Smoothie', 'treats' ),
			'description' => __( 'Blueberries, Greek yoghurt, milk and honey.', 'treats' ),
			'price'       => '6.5',
			'categories'  => array( 'drinks', 'smoothies' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Banana Smoothie', 'treats' ),
			'description' => __( 'Banana, Greek yoghurt, milk and honey.', 'treats' ),
			'price'       => '6.5',
			'categories'  => array( 'drinks', 'smoothies' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Passion Shoot Smoothie', 'treats' ),
			'description' => __( 'Mango, pineapple, passionfruit, Greek yoghurt and honey.', 'treats' ),
			'price'       => '6.5',
			'categories'  => array( 'drinks', 'smoothies' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Smoothie Options', 'treats' ),
			'description' => __( 'Full fat, semi skimmed, oat or soya milk. Vegan options made without honey or Greek yoghurt.', 'treats' ),
			'price'       => '',
			'categories'  => array( 'drinks', 'smoothies' ),
			'collect'     => false,
		),

		/* -------------------------------------------------- Milkshakes --- */

		array(
			'title'       => __( 'Milkshake', 'treats' ),
			'description' => __( 'Made with ice cream. Strawberry, vanilla, Biscoff, Oreo or salted caramel. Vegan: Biscoff, Oreo or vanilla.', 'treats' ),
			'price'       => '6.5',
			'categories'  => array( 'drinks', 'milkshakes' ),
			'dietary'     => array( 'v' ),
		),

		/* -------------------------------------------- Children\'s drinks -- */

		array(
			'title'       => __( 'Fruit Shoot', 'treats' ),
			'description' => __( 'Blackcurrant or orange.', 'treats' ),
			'price'       => '1.8',
			'categories'  => array( 'drinks', 'childrens-drinks' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Glass of Milk', 'treats' ),
			'price'       => '1.8',
			'categories'  => array( 'drinks', 'childrens-drinks' ),
			'dietary'     => array( 'v' ),
		),
		array(
			'title'       => __( 'Apple Juice', 'treats' ),
			'price'       => '1.8',
			'categories'  => array( 'drinks', 'childrens-drinks' ),
			'dietary'     => array( 've', 'v' ),
		),
		array(
			'title'       => __( 'Baby Chino', 'treats' ),
			'price'       => '1.8',
			'categories'  => array( 'drinks', 'childrens-drinks' ),
			'dietary'     => array( 'v' ),
		),

		/* ------------------------------------------------- Alcoholic ----- */

		array(
			'title'       => __( 'Moretti', 'treats' ),
			'price'       => '6',
			'categories'  => array( 'drinks', 'alcoholic' ),
		),
		array(
			'title'       => __( 'White Wine', 'treats' ),
			'price'       => '5.6',
			'categories'  => array( 'drinks', 'alcoholic' ),
		),
		array(
			'title'       => __( 'Red Wine', 'treats' ),
			'price'       => '5.6',
			'categories'  => array( 'drinks', 'alcoholic' ),
		),
		array(
			'title'       => __( 'Prosecco', 'treats' ),
			'price'       => '7',
			'categories'  => array( 'drinks', 'alcoholic' ),
		),
	);
}
