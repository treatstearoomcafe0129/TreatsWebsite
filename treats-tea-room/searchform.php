<?php
/**
 * Search form.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$treats_id = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div class="search-form__field">
		<label class="screen-reader-text" for="<?php echo esc_attr( $treats_id ); ?>">
			<?php esc_html_e( 'Search the site', 'treats' ); ?>
		</label>

		<span class="search-form__icon"><?php treats_icon( 'search', array( 'size' => 18 ) ); ?></span>

		<input
			type="search"
			id="<?php echo esc_attr( $treats_id ); ?>"
			class="search-field"
			placeholder="<?php esc_attr_e( 'Search menus, pages…', 'treats' ); ?>"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			name="s"
		>
	</div>

	<button class="btn btn--primary" type="submit"><?php esc_html_e( 'Search', 'treats' ); ?></button>
</form>
