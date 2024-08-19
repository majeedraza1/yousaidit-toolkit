<?php

namespace YouSaidItCards\Modules\SocialAuth;

use WP_Error;
use WP_User;
use YouSaidItCards\Modules\Auth\CopyAvatarFromSocialProvider;
use YouSaidItCards\Modules\Auth\Models\SocialAuthProvider;
use YouSaidItCards\Modules\SocialAuth\Interfaces\UserInfoInterface;

class SocialAuthManager {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'login_form', [ self::$instance, 'social_auth_wrap' ] );
			add_action( 'register_form', [ self::$instance, 'social_auth_wrap' ] );
			add_action( 'login_enqueue_scripts', [ self::$instance, 'login_scripts' ] );
			add_action( 'login_init', [ self::$instance, 'validate_auth_code' ] );
			add_filter( 'yousaidit_toolkit/settings/panels', [ self::$instance, 'add_setting_panels' ] );
			add_filter( 'yousaidit_toolkit/settings/sections', [ self::$instance, 'add_setting_sections' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'add_setting_fields' ] );
			add_action( 'yousaidit_toolkit/social_auth/validate_user_info', [ self::$instance, 'validate_user_info' ] );

			if ( Setting::is_provider_enabled( 'google' ) ) {
				LoginWithGoogle::init();
			}
			if ( Setting::is_provider_enabled( 'facebook' ) ) {
				LoginWithFacebook::init();
			}
		}

		return self::$instance;
	}

	/**
	 * Validate auth code
	 *
	 * @return void
	 */
	public function validate_auth_code() {
		if ( isset( $_GET['action'], $_GET['provider'] ) && $_GET['action'] == 'social-login' ) {
			$code = $_GET['code'] ? rawurldecode( $_GET['code'] ) : '';
			if ( ! empty( $code ) ) {
				do_action( 'yousaidit_toolkit/social_auth/validate_auth_code', $_GET['provider'], $code );
			}
		}
	}

	/**
	 * Handle login and registration
	 *
	 * @param  UserInfoInterface  $user_info
	 *
	 * @return void
	 */
	public function validate_user_info( UserInfoInterface $user_info ) {
		$provider    = str_replace( '.com', '', $user_info->get_provider() );
		$social_auth = SocialAuthProvider::find_for( $provider, $user_info->get_provider_uuid() );
		if ( $social_auth instanceof SocialAuthProvider ) {
			$user = get_user_by( 'id', $social_auth->get_user_id() );
			if ( $user instanceof WP_User ) {
				static::set_auth_cookie_and_redirect( $user, $user_info, false );
			} else {
				// Create wp user
			}
		}

		// Check if user already registered
		$email = $user_info->get_email();
		$user  = get_user_by( 'email', $email );
		if ( $user instanceof WP_User ) {
			static::set_auth_cookie_and_redirect( $user, $user_info );
		}
		$user = get_user_by( 'login', $email );
		if ( $user instanceof WP_User ) {
			static::set_auth_cookie_and_redirect( $user, $user_info );
		}
		$social_auth = SocialAuthProvider::find_by_email( $user_info->get_email() );
		if ( $social_auth instanceof SocialAuthProvider ) {
			$user = get_user_by( 'id', $social_auth->get_user_id() );
			static::set_auth_cookie_and_redirect( $user, $user_info, false );
		}

		// Handle new user
		if ( get_option( 'users_can_register' ) ) {
			$user_id = static::create_wp_user( $user_info );
			if ( is_numeric( $user_id ) ) {
				$user = get_user_by( 'id', $user_id );
				static::set_auth_cookie_and_redirect( $user, $user_info );
			}
		}
	}

	/**
	 * Create WordPress user
	 *
	 * @param  UserInfoInterface  $user_info
	 *
	 * @return int|WP_Error
	 */
	private static function create_wp_user( UserInfoInterface $user_info ) {
		$user_id = wp_insert_user( [
			'user_email' => $user_info->get_email(),
			'user_login' => $user_info->get_email(),
			'user_pass'  => wp_generate_password( 20, true, true ),
			'first_name' => $user_info->get_first_name(),
			'last_name'  => $user_info->get_last_name(),
		] );
		if ( is_numeric( $user_id ) ) {
			new CopyAvatarFromSocialProvider( $user_id, $user_info->get_picture_url() );
		}

		return $user_id;
	}

	private static function set_auth_cookie_and_redirect(
		WP_User $user,
		UserInfoInterface $user_info,
		bool $update = true
	) {
		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID, false );

		if ( $update ) {
			$provider = str_replace( '.com', '', $user_info->get_provider() );
			SocialAuthProvider::create_or_update( [
				'user_id'       => $user->ID,
				'provider'      => $provider,
				'provider_id'   => $user_info->get_provider_uuid(),
				'email_address' => $user_info->get_email(),
				'first_name'    => $user_info->get_first_name(),
				'last_name'     => $user_info->get_last_name(),
			] );
		}
		if ( $user->has_cap( 'manage_options' ) ) {
			wp_safe_redirect( admin_url() );
		} else {
			if ( function_exists( 'wc_get_page_permalink' ) ) {
				wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			} else {
				wp_safe_redirect( home_url() );
			}
		}
		exit();
	}


	/**
	 * Add setting panels
	 *
	 * @param  array  $panels  List of panels.
	 *
	 * @return array
	 */
	public function add_setting_panels( array $panels ): array {
		$panels[] = [
			'id'       => 'panel_social_auth',
			'title'    => __( 'Social Auth', 'yousaidit-toolkit' ),
			'priority' => 70,
		];

		return $panels;
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
				'id'       => 'section_social_auth',
				'title'    => __( 'Social Auth Settings', 'yousaidit-toolkit' ),
				'priority' => 5,
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
			'id'                => 'social_auth_providers',
			'type'              => 'multi_checkbox',
			'title'             => __( 'Social Auth Providers', 'yousaidit-toolkit' ),
			'description'       => __( 'Check to enable social auth providers', 'yousaidit-toolkit' ),
			'priority'          => 5,
			'section'           => 'section_social_auth',
			'multiple'          => true,
			'options'           => [
				'facebook' => __( 'Facebook Auth', 'yousaidit-toolkit' ),
				'google'   => __( 'Google Auth', 'yousaidit-toolkit' ),
			],
			'sanitize_callback' => function ( $value ) {
				return $value ? array_map( 'sanitize_text_field', $value ) : '';
			},
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
        <div class="social-auth-wrap">
            <div class="social-auth-wrap__heading">
				<?php esc_html_e( 'Or, Continue with', 'shaplatools' ); ?>
            </div>
            <div class="social-auth-wrap__content">
				<?php do_action( 'yousaidit_toolkit/social_auth_buttons' ); ?>
            </div>
        </div>
		<?php
	}

	/**
	 * Add login page scripts
	 *
	 * @return void
	 */
	public function login_scripts() {
		?>
        <style type="text/css">
            #login form {
                display: flex;
                flex-direction: column;
            }

            #login form .social-auth-wrap {
                order: 10;
                padding-top: 1rem;
            }

            #login form .social-auth-wrap__heading {
                display: flex;
                justify-content: center;
                margin-bottom: 8px;
                font-weight: 500;
            }

            #login form .social-auth-wrap__content {
                display: flex;
                justify-content: center;
                align-items: center;
            }

            #login form .social-auth-wrap__content > * + * {
                margin-left: .5rem;
            }

            #login form .social-auth-wrap__content button,
            #login form .social-auth-wrap__content a {
                background-color: #ffffff;
                display: inline-flex;
                justify-content: center;
                align-items: center;
                width: 40px;
                height: 40px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                transition: background-color .218s, border-color .218s, box-shadow .218s;
            }

            #login form .social-auth-wrap__content button:not(:disabled):hover,
            #login form .social-auth-wrap__content a:hover {
                border-color: #303030;
                cursor: pointer;
            }

            #login form .social-auth-wrap__content button img,
            #login form .social-auth-wrap__content a img {
                max-width: 100%;
                height: auto;
            }

            #login form .social-auth-wrap__content .button-icon {
                display: inline-flex;
            }
        </style>
		<?php
	}
}
