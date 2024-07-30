<?php

namespace YouSaidItCards\Modules\SocialAuth;

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

			LoginWithGoogle::init();
			LoginWithFacebook::init();
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
			do_action( 'yousaidit_toolkit/social_auth/validate_auth_code' );
		}
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
