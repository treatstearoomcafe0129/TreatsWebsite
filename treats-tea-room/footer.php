<?php
/**
 * Site footer.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$treats_address = treats_get_address();
$treats_social  = treats_get_social_links();
?>
</main><!-- #main -->

<footer class="site-footer">
	<div class="container">
		<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
			<div class="site-footer__widgets">
				<?php dynamic_sidebar( 'footer-1' ); ?>
			</div>
		<?php endif; ?>

		<div class="site-footer__grid">
			<div class="site-footer__col">
				<?php treats_the_logo(); ?>

				<p class="site-footer__blurb">
					<?php
					echo esc_html(
						get_theme_mod(
							'treats_footer_blurb',
							__( 'A family tea room on Silver Street, serving Durham good food at good prices since 1984.', 'treats' )
						)
					);
					?>
				</p>

				<?php if ( $treats_social ) : ?>
					<ul class="social-links">
						<?php foreach ( $treats_social as $treats_key => $treats_link ) : ?>
							<li>
								<a href="<?php echo esc_url( $treats_link['url'] ); ?>" target="_blank" rel="noopener me" aria-label="<?php echo esc_attr( $treats_link['label'] ); ?>">
									<?php treats_icon( $treats_key, array( 'size' => 18 ) ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="site-footer__col">
				<h2 class="site-footer__title"><?php esc_html_e( 'Explore', 'treats' ); ?></h2>
				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'menu_class'     => 'footer-menu',
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
				}
				?>
			</div>

			<div class="site-footer__col">
				<h2 class="site-footer__title"><?php esc_html_e( 'Find us', 'treats' ); ?></h2>

				<address style="font-style:normal;line-height:1.9">
					<?php echo esc_html( $treats_address['street'] ); ?><br>
					<?php echo esc_html( $treats_address['locality'] ); ?><br>
					<?php echo esc_html( $treats_address['postcode'] ); ?>
				</address>

				<p style="margin-top:1rem;line-height:2">
					<?php if ( treats_get_phone() ) : ?>
						<a href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>"><?php echo esc_html( treats_get_phone() ); ?></a><br>
					<?php endif; ?>

					<?php if ( treats_get_email() ) : ?>
						<a href="mailto:<?php echo esc_attr( treats_get_email() ); ?>"><?php echo esc_html( treats_get_email() ); ?></a>
					<?php endif; ?>
				</p>

				<p style="margin-top:1rem">
					<a class="link-arrow" href="<?php echo esc_url( treats_map_directions_url() ); ?>" target="_blank" rel="noopener" style="color:inherit">
						<?php esc_html_e( 'Get directions', 'treats' ); ?>
						<?php treats_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
					</a>
				</p>
			</div>

			<div class="site-footer__col">
				<h2 class="site-footer__title"><?php esc_html_e( 'Newsletter', 'treats' ); ?></h2>
				<?php get_template_part( 'template-parts/components/newsletter' ); ?>
			</div>
		</div>

		<div class="site-footer__bar">
			<p>
				<?php
				printf(
					/* translators: 1: year, 2: business name. */
					esc_html__( '© %1$s %2$s. All rights reserved.', 'treats' ),
					esc_html( gmdate( 'Y' ) ),
					esc_html( treats_get_business_name() )
				);
				?>
			</p>

			<?php
			if ( has_nav_menu( 'legal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'legal',
						'container'      => false,
						'menu_class'     => 'site-footer__legal',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			}
			?>
		</div>
	</div>
</footer>

<button class="to-top" type="button" aria-label="<?php esc_attr_e( 'Back to top', 'treats' ); ?>">
	<?php treats_icon( 'arrow-up', array( 'size' => 20 ) ); ?>
</button>

<?php
// The Click & Collect basket lives outside the page flow so it survives
// navigation between menu pages.
if ( get_theme_mod( 'treats_collect_enabled', true ) && treats_needs_menu_assets() ) {
	get_template_part( 'template-parts/components/collect-drawer' );
}

wp_footer();
?>
</body>
</html>
