<?php
/** @var WP_User $author */
$author     = get_queried_object();
$designer   = new YouSaidItCards\Modules\Designers\Models\CardDesigner( $author );
$avatar_url = $designer->get_avatar_url();
$location   = $designer->get_location();
?>

<?php get_header(); ?>

<div id="primary" class="blog-content-area">

	<div class="page-header animated fadeIn">
		<div class="row"></div>
	</div>

	<div class="row">
		<div class="large-12 columns no-sidebar">
			<div id="content" class="site-content" role="main">

				<div class="yousaidit-designer-profile-header">
					<div class="yousaidit-designer-profile-header__cover"
						 style="background-image: url(<?php echo $designer->get_cover_photo_url() ?>);">
						<div class="yousaidit-designer-profile-header__profile">
							<div class="shapla-image-container is-rounded" style="width: 128px; height: 128px;">
								<img src="<?php echo $designer->get_avatar_url( 256 ) ?>" width="128" height="128"
									 alt="">
							</div>
						</div>
					</div>
					<div class="yousaidit-designer-profile-header__info mid-section"></div>
					<div class="mid-section">
						<div class="yousaidit-designer-profile-header__name name">
							<?php echo $designer->get_user()->display_name ?>
							<?php
							$instagram_logo = $designer->get_instagram_url();
							if ( $instagram_logo ) {
								?>
								<a href="<?php echo $instagram_logo ?>" target="_blank" class="shapla-icon">
									<img
										src="<?php echo YOUSAIDIT_TOOLKIT_ASSETS . '/static-images/instagram-logo.png' ?>"
										width="24" height="24" alt="Instagram" style="width: 1rem;height: 1rem;">
								</a>
								<?php
							}
							?>
						</div>
						<?php if ( ! empty( $location ) ) { ?>
							<div class="yousaidit-designer-profile-header__location place">
								<span class="shapla-icon">
									<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
										<path
											d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path>
										<path d="M0 0h24v24H0z" fill="none"></path>
									</svg>
								</span>
								<div><?php echo $location ?></div>
							</div>
						<?php } ?>
						<div class="yousaidit-designer-profile-header__bio description">
							<?php echo $designer->get_user()->description ?>
						</div>
					</div>
				</div>
				<div class="menu--social-share">
					<?php
					foreach ( $designer->get_social_share_url() as $key => $share_url ) {
						if ( 'facebook' == $key ) { ?>
							<a href="<?php echo esc_url( $share_url ) ?>">
							<span class="shapla-icon">
								<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32"
									 viewBox="0 0 32 32">
								<title>facebook</title>
								<path
									d="M19 6h5v-6h-5c-3.86 0-7 3.14-7 7v3h-4v6h4v16h6v-16h5l1-6h-6v-3c0-0.542 0.458-1 1-1z"></path>
								</svg>
							</span>
							</a>
						<?php } elseif ( 'twitter' == $key ) { ?>
							<a href="<?php echo esc_url( $share_url ) ?>">
							<span class="shapla-icon">
								<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32"
									 viewBox="0 0 32 32">
									<title>twitter</title>
									<path
										d="M32 7.075c-1.175 0.525-2.444 0.875-3.769 1.031 1.356-0.813 2.394-2.1 2.887-3.631-1.269 0.75-2.675 1.3-4.169 1.594-1.2-1.275-2.906-2.069-4.794-2.069-3.625 0-6.563 2.938-6.563 6.563 0 0.512 0.056 1.012 0.169 1.494-5.456-0.275-10.294-2.888-13.531-6.862-0.563 0.969-0.887 2.1-0.887 3.3 0 2.275 1.156 4.287 2.919 5.463-1.075-0.031-2.087-0.331-2.975-0.819 0 0.025 0 0.056 0 0.081 0 3.181 2.263 5.838 5.269 6.437-0.55 0.15-1.131 0.231-1.731 0.231-0.425 0-0.831-0.044-1.237-0.119 0.838 2.606 3.263 4.506 6.131 4.563-2.25 1.762-5.075 2.813-8.156 2.813-0.531 0-1.050-0.031-1.569-0.094 2.913 1.869 6.362 2.95 10.069 2.95 12.075 0 18.681-10.006 18.681-18.681 0-0.287-0.006-0.569-0.019-0.85 1.281-0.919 2.394-2.075 3.275-3.394z"></path>
								</svg>
							</span>
							</a>
						<?php } elseif ( 'mailto' == $key ) { ?>
							<a href="mailto:<?php echo esc_url( $share_url ) ?>">
							<span class="shapla-icon">
								<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32"
									 viewBox="0 0 32 32">
								<title>Email</title>
								<path
									d="M29 4h-26c-1.65 0-3 1.35-3 3v20c0 1.65 1.35 3 3 3h26c1.65 0 3-1.35 3-3v-20c0-1.65-1.35-3-3-3zM12.461 17.199l-8.461 6.59v-15.676l8.461 9.086zM5.512 8h20.976l-10.488 7.875-10.488-7.875zM12.79 17.553l3.21 3.447 3.21-3.447 6.58 8.447h-19.579l6.58-8.447zM19.539 17.199l8.461-9.086v15.676l-8.461-6.59z"></path>
								</svg>
							</span>
							</a>
						<?php } else {
							echo '<a href="' . esc_url( $share_url ) . '" target="_blank">' . esc_html( $key ) . '</a>';
						}
					}
					?>
				</div>

				<div class="top_bar_shop">
					<div class="catalog-ordering">
						<div class="shop-filter"><span>Filter</span></div>
						<?php do_action( 'woocommerce_before_shop_loop_result_count' ); ?>
					</div>
				</div>
				<div
					class="active_filters_ontop"><?php the_widget( 'WC_Widget_Layered_Nav_Filters', array(), array() ); ?></div>
				<p>&nbsp;</p>
				<?php do_action( 'designer_cards' ); ?>
			</div>
		</div><!-- .columns -->
	</div><!-- .row -->

</div>

<?php get_footer(); ?>
