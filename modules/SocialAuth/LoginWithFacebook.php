<?php

namespace YouSaidItCards\Modules\SocialAuth;

use YouSaidItCards\Modules\SocialAuth\Interfaces\UserInfoInterface;
use YouSaidItCards\Modules\SocialAuth\Providers\FacebookServiceProvider;

/**
 * LoginWithGoogle class
 */
class LoginWithFacebook {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return LoginWithFacebook|null
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			if ( FacebookServiceProvider::init()->has_required_settings() ) {
				add_action( 'yousaidit_toolkit/social_auth_buttons', [ self::$instance, 'social_auth_wrap' ] );
			}

			add_filter( 'yousaidit_toolkit/settings/sections', [ self::$instance, 'add_setting_sections' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'add_setting_fields' ] );
			add_action( 'yousaidit_toolkit/social_auth/validate_auth_code', [ self::$instance, 'validate_auth_code' ],
				10, 2 );
		}

		return self::$instance;
	}

	/**
	 * Add setting sections
	 *
	 * @param  array  $sections  List of setting sections.
	 *
	 * @return array
	 */
	public function add_setting_sections( array $sections ): array {
		$sections[] =
			[
				'id'       => 'section_facebook_auth',
				'title'    => __( 'Facebook Auth', 'yousaidit-toolkit' ),
				'priority' => 10,
				'panel'    => 'panel_social_auth',
			];

		return $sections;
	}

	/**
	 * Add setting fields
	 *
	 * @param  array  $fields  List  of setting fields.
	 *
	 * @return array
	 */
	public function add_setting_fields( array $fields ): array {

		$fields[] = [
			'id'       => 'facebook_app_id',
			'type'     => 'text',
			'title'    => __( 'App ID', 'yousaidit-toolkit' ),
			'section'  => 'section_facebook_auth',
			'priority' => 10,
		];
		$fields[] = [
			'id'       => 'facebook_auth_client_secret',
			'type'     => 'text',
			'title'    => __( 'Client Secret', 'yousaidit-toolkit' ),
			'section'  => 'section_facebook_auth',
			'priority' => 20,
		];

		$html = '<div>';
		foreach ( FacebookServiceProvider::get_redirect_uris() as $redirect_uri ) {
			$html .= sprintf( '<pre><code>%s</code></pre>', esc_url( $redirect_uri ) );
		}
		$html .= '</div>';

		$fields[] = [
			'id'          => 'facebook_auth_redirect_uri',
			'type'        => 'html',
			'title'       => __( 'Redirect URI', 'yousaidit-toolkit' ),
			'description' => __( 'Add this URI on facebook developer console as "Authorized redirect URI".',
				'yousaidit-toolkit' ),
			'section'     => 'section_facebook_auth',
			'priority'    => 30,
			'html'        => $html,
		];

		return $fields;
	}

	/**
	 * Show social auth wrapper
	 *
	 * @return void
	 */
	public function social_auth_wrap() {
		?>
        <a class="button--login-with-facebook" tabindex="-1"
           href="<?php echo FacebookServiceProvider::get_consent_url() ?>">
			<span class="button-icon">
				<svg fill="none" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
					<path d="m12 2c-2.65216 0-5.1957 1.05357-7.07107 2.92893-1.87536 1.87537-2.92893 4.41891-2.92893 7.07107 0 2.6522 1.05357 5.1957 2.92893 7.0711 1.87537 1.8753 4.41891 2.9289 7.07107 2.9289 2.6522 0 5.1957-1.0536 7.0711-2.9289 1.8753-1.8754 2.9289-4.4189 2.9289-7.0711 0-2.65216-1.0536-5.1957-2.9289-7.07107-1.8754-1.87536-4.4189-2.92893-7.0711-2.92893z"
                          fill="#1877f2"/>
					<path d="m13.3537 14.6506h2.5879l.4063-2.629h-2.9947v-1.4368c0-1.09212.3568-2.06054 1.3784-2.06054h1.6416v-2.29421c-.2884-.03895-.8984-.12422-2.0511-.12422-2.4068 0-3.8179 1.27106-3.8179 4.16687v1.7489h-2.47417v2.629h2.47417v7.2258c.49.0736.9864.1236 1.4958.1236.4606 0 .91-.0421 1.3537-.1021z"
                          fill="#ffffff"/>
				</svg>
			</span>
            <span class="screen-reader-text"><?php esc_html_e( 'Login with Facebook', 'yousaidit-toolkit' ); ?></span>
        </a>
		<?php
	}

	public function validate_auth_code( string $provider, string $code ) {
		if ( FacebookServiceProvider::PROVIDER !== $provider ) {
			return;
		}

		if ( ! FacebookServiceProvider::validate_nonce() ) {
			return;
		}
		$response = FacebookServiceProvider::exchange_code_for_token( $code );
		if ( is_wp_error( $response ) ) {
			add_filter(
				'wp_login_errors',
				function () use ( $response ) {
					return $response;
				}
			);

			return;
		}

		if ( is_array( $response ) && isset( $response['access_token'] ) ) {
			$user_info = FacebookServiceProvider::get_userinfo( $response['access_token'] );
			if ( $user_info instanceof UserInfoInterface ) {
				do_action( 'yousaidit_toolkit/social_auth/validate_user_info', $user_info );
			}
		}
	}
}
