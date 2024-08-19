<?php

namespace YouSaidItCards\Modules\SocialAuth;

use YouSaidItCards\Modules\SocialAuth\Providers\GoogleServiceProvider;

/**
 * LoginWithGoogle class
 */
class LoginWithGoogle {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return LoginWithGoogle|null
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			if ( GoogleServiceProvider::init()->has_required_settings() ) {
				add_action( 'yousaidit_toolkit/social_auth_buttons', [ self::$instance, 'social_auth_wrap' ] );
			}
			add_filter( 'yousaidit_toolkit/settings/sections', [ self::$instance, 'add_setting_sections' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'add_setting_fields' ] );
			add_action( 'yousaidit_toolkit/social_auth/validate_auth_code', [ self::$instance, 'validate_auth_code' ] );
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
				'id'       => 'section_google_auth',
				'title'    => __( 'Google Auth', 'yousaidit-toolkit' ),
				'priority' => 60,
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
			'id'       => 'google_auth_client_id',
			'type'     => 'text',
			'title'    => __( 'Client ID', 'yousaidit-toolkit' ),
			'section'  => 'section_google_auth',
			'priority' => 10,
		];
		$fields[] = [
			'id'       => 'google_auth_client_secret',
			'type'     => 'text',
			'title'    => __( 'Client Secret', 'yousaidit-toolkit' ),
			'section'  => 'section_google_auth',
			'priority' => 20,
		];

		$html = '<div>';
		foreach ( GoogleServiceProvider::get_redirect_uris() as $redirect_uri ) {
			$html .= sprintf( '<pre><code>%s</code></pre>', esc_url( $redirect_uri ) );
		}
		$html .= '</div>';

		$fields[] = [
			'id'          => 'google_auth_redirect_uri',
			'type'        => 'html',
			'title'       => __( 'Redirect URI', 'yousaidit-toolkit' ),
			'description' => __( 'Add this URI on google cloud console as "Authorized redirect URI".',
				'yousaidit-toolkit' ),
			'section'     => 'section_google_auth',
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
        <a class="button--login-with-google" href="<?php echo GoogleServiceProvider::get_consent_url(); ?>"
           tabindex="-1">
			<span class="button-icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="24" height="24">
					<path fill="#EA4335"
                          d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
					<path fill="#4285F4"
                          d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
					<path fill="#FBBC05"
                          d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
					<path fill="#34A853"
                          d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
					<path fill="none" d="M0 0h48v48H0z"></path>
				</svg>
			</span>
            <span class="screen-reader-text"><?php esc_html_e( 'Login with Google', 'yousaidit-toolkit' ); ?></span>
        </a>
		<?php
	}

	public function validate_auth_code( $provider ) {
		if ( GoogleServiceProvider::PROVIDER !== $provider ) {
			return;
		}
		$code = $_GET['code'] ?? '';
		if ( ! empty( $code ) && GoogleServiceProvider::validate_nonce() ) {
			$response = GoogleServiceProvider::exchange_code_for_token( rawurldecode( $code ) );
			if ( is_wp_error( $response ) ) {
				add_filter(
					'wp_login_errors',
					function () use ( $response ) {
						return $response;
					}
				);

				var_dump( $response );
				die();

				return;
			}

			var_dump( $response );
			die();
		}
	}
}
