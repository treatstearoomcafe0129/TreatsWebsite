<?php
/**
 * JSON-LD structured data.
 *
 * Emits a single @graph so Google sees one coherent description of the
 * business, the current page, its breadcrumbs and — where relevant — the
 * menu, FAQ and review data.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stable @id for the organisation node.
 *
 * @return string
 */
function treats_schema_org_id() {
	return home_url( '/#business' );
}

/**
 * The CafeOrCoffeeShop node.
 *
 * @return array<string,mixed>
 */
function treats_schema_business() {
	$address = treats_get_address();
	$phone   = treats_get_phone_link();
	$hours   = array();

	foreach ( treats_get_opening_hours() as $index => $day ) {
		if ( $day['closed'] || '' === $day['open'] || '' === $day['close'] ) {
			continue;
		}

		$hours[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => 'https://schema.org/' . treats_weekday_schema( $index ),
			'opens'     => $day['open'],
			'closes'    => $day['close'],
		);
	}

	$node = array(
		'@type'      => array( 'CafeOrCoffeeShop', 'Restaurant' ),
		'@id'        => treats_schema_org_id(),
		'name'       => treats_get_business_name(),
		'url'        => home_url( '/' ),
		'image'      => treats_get_share_image(),
		'logo'       => treats_get_share_image(),
		'description' => (string) get_theme_mod( 'treats_seo_description', get_bloginfo( 'description' ) ),
		'address'    => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $address['street'],
			'addressLocality' => $address['locality'],
			'addressRegion'   => $address['region'],
			'postalCode'      => $address['postcode'],
			'addressCountry'  => 'GB',
		),
		'servesCuisine' => array( 'British', 'Cafe', 'Afternoon Tea', 'Vegetarian', 'Vegan' ),
		'priceRange'    => (string) get_theme_mod( 'treats_seo_price_range', '££' ),
		'currenciesAccepted' => 'GBP',
		'paymentAccepted'    => 'Cash, Credit Card, Debit Card, Contactless',
		'acceptsReservations' => treats_booking_url(),
		'publicAccess'  => true,
	);

	$founded = (string) get_theme_mod( 'treats_seo_founding_year', '' );

	if ( '' !== $founded ) {
		$node['foundingDate'] = $founded;
	}

	if ( '' !== $phone ) {
		$node['telephone'] = $phone;
	}

	$email = treats_get_email();

	if ( '' !== $email ) {
		$node['email'] = $email;
	}

	if ( $hours ) {
		$node['openingHoursSpecification'] = $hours;
	}

	$social = wp_list_pluck( treats_get_social_links(), 'url' );

	if ( $social ) {
		$node['sameAs'] = array_values( $social );
	}

	$menu_url = treats_get_template_page_url( 'page-templates/template-menu.php' );

	if ( $menu_url && home_url( '/' ) !== $menu_url ) {
		$node['hasMenu'] = $menu_url;
	}

	$rating = treats_schema_aggregate_rating();

	if ( $rating ) {
		$node['aggregateRating'] = $rating;
	}

	return $node;
}

/**
 * Aggregate rating built from published reviews.
 *
 * @return array<string,mixed>|null
 */
function treats_schema_aggregate_rating() {
	$reviews = treats_get_reviews( 100 );

	if ( count( $reviews ) < 3 ) {
		return null;
	}

	$total = 0;
	$count = 0;

	foreach ( $reviews as $review ) {
		$value = (float) get_post_meta( $review->ID, '_treats_rating', true );

		if ( $value > 0 ) {
			$total += $value;
			++$count;
		}
	}

	if ( $count < 3 ) {
		return null;
	}

	return array(
		'@type'       => 'AggregateRating',
		'ratingValue' => round( $total / $count, 1 ),
		'reviewCount' => $count,
		'bestRating'  => 5,
		'worstRating' => 1,
	);
}

/**
 * Review nodes.
 *
 * @return array<int,array<string,mixed>>
 */
function treats_schema_reviews() {
	$nodes = array();

	foreach ( treats_get_reviews( 12 ) as $review ) {
		$rating = (float) get_post_meta( $review->ID, '_treats_rating', true );
		$author = (string) get_post_meta( $review->ID, '_treats_author', true );
		$body   = wp_strip_all_tags( (string) $review->post_content );

		if ( '' === $body || '' === $author ) {
			continue;
		}

		$node = array(
			'@type'        => 'Review',
			'itemReviewed' => array( '@id' => treats_schema_org_id() ),
			'author'       => array(
				'@type' => 'Person',
				'name'  => $author,
			),
			'reviewBody'   => $body,
		);

		if ( $rating > 0 ) {
			$node['reviewRating'] = array(
				'@type'       => 'Rating',
				'ratingValue' => $rating,
				'bestRating'  => 5,
				'worstRating' => 1,
			);
		}

		$date = (string) get_post_meta( $review->ID, '_treats_review_date', true );

		if ( '' !== $date ) {
			$node['datePublished'] = $date;
		}

		$nodes[] = $node;
	}

	return $nodes;
}

/**
 * Menu node for the current menu page.
 *
 * @return array<string,mixed>|null
 */
function treats_schema_menu() {
	if ( ! is_page_template( 'page-templates/template-menu.php' ) ) {
		return null;
	}

	$category = (string) treats_meta( 'menu_category' );
	$items    = treats_get_menu_items( $category );

	if ( ! $items ) {
		return null;
	}

	$sections = array();

	foreach ( treats_group_menu_items( $items, treats_menu_category_term_id( $category ) ) as $group ) {
		$entries = array();

		foreach ( $group['items'] as $item ) {
			$entry = array(
				'@type' => 'MenuItem',
				'name'  => get_the_title( $item ),
			);

			$description = wp_strip_all_tags( (string) $item->post_content );

			if ( '' !== $description ) {
				$entry['description'] = $description;
			}

			$price = get_post_meta( $item->ID, '_treats_price', true );

			if ( is_numeric( $price ) ) {
				$entry['offers'] = array(
					'@type'         => 'Offer',
					'price'         => number_format( (float) $price, 2, '.', '' ),
					'priceCurrency' => 'GBP',
				);
			}

			$entries[] = $entry;
		}

		$sections[] = array(
			'@type'          => 'MenuSection',
			'name'           => $group['term'] ? $group['term']->name : get_the_title(),
			'hasMenuItem'    => $entries,
		);
	}

	return array(
		'@type'          => 'Menu',
		'@id'            => get_permalink() . '#menu',
		'name'           => get_the_title(),
		'url'            => get_permalink(),
		'inLanguage'     => get_bloginfo( 'language' ),
		'hasMenuSection' => $sections,
	);
}

/**
 * Resolve a menu category slug to a term ID.
 *
 * @param string $slug Term slug.
 * @return int
 */
function treats_menu_category_term_id( $slug ) {
	if ( '' === $slug ) {
		return 0;
	}

	$term = get_term_by( 'slug', $slug, 'treats_menu_category' );

	return $term instanceof WP_Term ? (int) $term->term_id : 0;
}

/**
 * FAQPage node for the FAQ template.
 *
 * @return array<string,mixed>|null
 */
function treats_schema_faq() {
	if ( ! is_page_template( 'page-templates/template-faq.php' ) ) {
		return null;
	}

	$faqs = treats_get_faqs();

	if ( ! $faqs ) {
		return null;
	}

	$entities = array();

	foreach ( $faqs as $faq ) {
		$answer = wp_strip_all_tags( apply_filters( 'the_content', $faq->post_content ) );

		if ( '' === trim( $answer ) ) {
			continue;
		}

		$entities[] = array(
			'@type'          => 'Question',
			'name'           => get_the_title( $faq ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}

	if ( ! $entities ) {
		return null;
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => get_permalink() . '#faq',
		'mainEntity' => $entities,
	);
}

/**
 * BreadcrumbList node.
 *
 * @return array<string,mixed>|null
 */
function treats_schema_breadcrumbs() {
	$items = treats_get_breadcrumb_items();

	if ( count( $items ) < 2 ) {
		return null;
	}

	$elements = array();

	foreach ( $items as $index => $item ) {
		$elements[] = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => $item['label'],
			'item'     => $item['url'],
		);
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => treats_get_canonical() . '#breadcrumbs',
		'itemListElement' => $elements,
	);
}

/**
 * WebPage / WebSite nodes.
 *
 * @return array<int,array<string,mixed>>
 */
function treats_schema_page() {
	$canonical = treats_get_canonical();

	$website = array(
		'@type'           => 'WebSite',
		'@id'             => home_url( '/#website' ),
		'url'             => home_url( '/' ),
		'name'            => treats_get_business_name(),
		'publisher'       => array( '@id' => treats_schema_org_id() ),
		'inLanguage'      => get_bloginfo( 'language' ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	$page = array(
		'@type'      => is_front_page() ? 'WebPage' : 'WebPage',
		'@id'        => $canonical . '#webpage',
		'url'        => $canonical,
		'name'       => wp_get_document_title(),
		'description' => treats_get_meta_description(),
		'isPartOf'   => array( '@id' => home_url( '/#website' ) ),
		'about'      => array( '@id' => treats_schema_org_id() ),
		'inLanguage' => get_bloginfo( 'language' ),
	);

	if ( is_singular() ) {
		$page['datePublished'] = get_the_date( DATE_W3C );
		$page['dateModified']  = get_the_modified_date( DATE_W3C );
	}

	$image = treats_get_share_image();

	if ( $image ) {
		$page['primaryImageOfPage'] = array(
			'@type' => 'ImageObject',
			'url'   => $image,
		);
	}

	return array( $website, $page );
}

/**
 * Article node for blog posts.
 *
 * @return array<string,mixed>|null
 */
function treats_schema_article() {
	if ( ! is_singular( 'post' ) ) {
		return null;
	}

	return array(
		'@type'            => 'Article',
		'@id'              => get_permalink() . '#article',
		'headline'         => get_the_title(),
		'datePublished'    => get_the_date( DATE_W3C ),
		'dateModified'     => get_the_modified_date( DATE_W3C ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => get_the_author(),
		),
		'publisher'        => array( '@id' => treats_schema_org_id() ),
		'mainEntityOfPage' => array( '@id' => treats_get_canonical() . '#webpage' ),
		'image'            => treats_get_share_image(),
	);
}

/**
 * Print the JSON-LD graph.
 *
 * @return void
 */
function treats_schema_output() {
	$graph = treats_schema_page();

	$graph[] = treats_schema_business();

	$optional = array(
		treats_schema_breadcrumbs(),
		treats_schema_article(),
		treats_schema_menu(),
		treats_schema_faq(),
	);

	foreach ( $optional as $node ) {
		if ( $node ) {
			$graph[] = $node;
		}
	}

	if ( is_front_page() ) {
		foreach ( treats_schema_reviews() as $review ) {
			$graph[] = $review;
		}
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => array_values( array_filter( $graph ) ),
	);

	/**
	 * Filter the JSON-LD graph before output.
	 *
	 * @param array $data Structured data.
	 */
	$data = apply_filters( 'treats_schema_graph', $data );

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
add_action( 'wp_head', 'treats_schema_output', 30 );
