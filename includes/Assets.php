<?php

namespace YouSaidItCards;

// If this file is called directly, abort.
use Stackonet\WP\Framework\Supports\Validate;
use YouSaidItCards\Admin\SettingPage;
use YouSaidItCards\Modules\DispatchTimer\Settings;
use YouSaidItCards\Modules\InnerMessage\Fonts;
use YouSaidItCards\Modules\StabilityAi\Settings as StabilityAiSettings;
use YouSaidItCards\OpenAI\Setting;
use YouSaidItCards\Utilities\FreePdfBase;

defined( 'ABSPATH' ) || exit;

class Assets {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Plugin name slug
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'wp_loaded', [ self::$instance, 'register' ] );

			add_action( 'admin_head', [ self::$instance, 'localize_data' ], 9 );
			add_action( 'wp_head', [ self::$instance, 'localize_data' ], 9 );
		}

		return self::$instance;
	}

	/**
	 * Check if script debugging is enabled
	 *
	 * @return bool
	 */
	private function is_script_debug_enabled() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	}

	/**
	 * Checks to see if the site has SSL enabled or not.
	 *
	 * @return bool
	 */
	public static function is_ssl() {
		if ( is_ssl() ) {
			return true;
		} elseif ( 0 === stripos( get_option( 'siteurl' ), 'https://' ) ) {
			return true;
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Get assets path
	 *
	 * @param  string  $path  Get assets path
	 *
	 * @return string
	 */
	public static function get_asset_path( string $path = '' ): string {
		$base_path = rtrim( YOUSAIDIT_TOOLKIT_PATH, '/' ) . '/assets';
		if ( ! empty( $path ) ) {
			return join( '/', [ $base_path, ltrim( $path, '/' ) ] );
		}

		return $base_path;
	}

	/**
	 * Get assets URL
	 *
	 * @param  string  $path
	 *
	 * @return string
	 */
	public static function get_assets_url( $path = '' ) {
		$url = YOUSAIDIT_TOOLKIT_ASSETS;

		if ( static::is_ssl() && 0 === stripos( $url, 'http://' ) ) {
			$url = str_replace( 'http://', 'https://', $url );
		}

		if ( ! empty( $path ) ) {
			return rtrim( $url, '/' ) . '/' . ltrim( $path, '/' );
		}

		return $url;
	}

	/**
	 * Get static assets url
	 *
	 * @param  string  $path  Static asset path.
	 *
	 * @return string
	 */
	public static function get_static_asset_url( string $path ): string {
		$path          = ltrim( $path, '/' );
		$absolute_path = join( '/', [ WP_CONTENT_DIR, 'uploads', 'yousaidit-static-assets', $path ] );
		if ( file_exists( $absolute_path ) ) {
			return str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $absolute_path );
		}

		$path = 'static-images/' . $path;

		return static::get_assets_url( $path );
	}

	/**
	 * Get version
	 *
	 * @param  array  $script  Script data.
	 *
	 * @return string
	 */
	public function get_version( array $script = [] ): string {
		// Return version number for third party scripts.
		if ( isset( $script['version'] ) ) {
			return $script['version'];
		}

		// Get version from file modification time.
		$file_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $script['src'] );
		if ( file_exists( $file_path ) ) {
			return gmdate( 'Y.m.d.Gi', filemtime( $file_path ) );
		}

		return $this->version;
	}

	/**
	 * Register our app scripts and styles
	 *
	 * @return void
	 */
	public function register() {
		$this->plugin_name = YOUSAIDIT_TOOLKIT;
		$this->version     = YOUSAIDIT_TOOLKIT_VERSION;

		if ( $this->is_script_debug_enabled() ) {
			$this->version = $this->version . '-' . time();
		}

		$this->register_scripts( $this->get_scripts() );
		$this->register_styles( $this->get_styles() );
	}

	/**
	 * Register scripts
	 *
	 * @param  array  $scripts
	 *
	 * @return void
	 */
	private function register_scripts( $scripts ) {
		foreach ( $scripts as $handle => $script ) {
			$deps      = $script['deps'] ?? false;
			$in_footer = $script['in_footer'] ?? true;
			wp_register_script( $handle, $script['src'], $deps, $this->get_version( $script ), $in_footer );
		}
	}

	/**
	 * Register styles
	 *
	 * @param  array  $styles
	 *
	 * @return void
	 */
	public function register_styles( $styles ) {
		foreach ( $styles as $handle => $style ) {
			$deps = $style['deps'] ?? false;
			wp_register_style( $handle, $style['src'], $deps, $this->get_version( $style ) );
		}
	}

	/**
	 * Get all registered scripts
	 *
	 * @return array
	 */
	public function get_scripts(): array {
		return [
			'yousaidit-toolkit-web-components' => [
				'src'       => static::get_assets_url() . '/js/web-components.js',
				'in_footer' => true
			],
			'yousaidit-toolkit-frontend'       => [
				'src'       => static::get_assets_url() . '/js/frontend.js',
				'deps'      => [ 'jquery', 'yousaidit-toolkit-web-components' ],
				'in_footer' => true
			],
			'yousaidit-designer-dashboard'     => [
				'src'       => static::get_assets_url() . '/js/designer-dashboard.js',
				'in_footer' => true
			],
			'yousaidit-toolkit-admin'          => [
				'src'       => static::get_assets_url() . '/js/admin.js',
				'deps'      => [ 'jquery', 'yousaidit-toolkit-web-components' ],
				'in_footer' => true
			],
		];
	}

	/**
	 * Get registered styles
	 *
	 * @return array
	 */
	public function get_styles(): array {
		return [
			'yousaidit-toolkit-frontend'   => [
				'src' => static::get_assets_url() . '/css/frontend.css'
			],
			'yousaidit-designer-dashboard' => [
				'src' => static::get_assets_url() . '/css/designer-dashboard.css'
			],
			'yousaidit-toolkit-admin'      => [
				'src'  => static::get_assets_url() . '/css/admin.css',
				'deps' => [ 'wp-color-picker' ],
			],
		];
	}

	/**
	 * Global localize data both for admin and frontend
	 */
	public static function localize_data() {
		$user              = wp_get_current_user();
		$is_user_logged_in = $user->exists();

		$data = [
			'homeUrl'                => home_url(),
			'designerProfileBaseUrl' => site_url( 'designer' ),
			'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
			'restRoot'               => esc_url_raw( rest_url( 'yousaidit/v1' ) ),
			'isUserLoggedIn'         => $is_user_logged_in,
			'privacyPolicyUrl'       => get_privacy_policy_url(),
			'termsAndConditionsUrl'  => get_privacy_policy_url(),
			'lostPasswordUrl'        => wp_lostpassword_url(),
			'signupUrl'              => wp_registration_url(),
			'logOutUrl'              => wp_logout_url( get_permalink() ),
			'placeholderUrlIM'       => self::get_static_asset_url( 'placeholder--inner-message.jpg' ),
			'placeholderUrlIML'      => self::get_static_asset_url( 'inside-left.svg' ),
			'placeholderUrlIMR'      => self::get_static_asset_url( 'inside-right.svg' ),
			'videoMessagePrice'      => (float) SettingPage::get_option( 'video_inner_message_price' ),
			'videoMessagePriceHTML'  => wc_price( (float) SettingPage::get_option( 'video_inner_message_price' ) ),
			'maxUploadLimitText'     => SettingPage::get_option( 'max_upload_limit_text' ),
			'fileUploaderTermsHTML'  => SettingPage::get_option( 'file_uploader_terms_and_condition' ),
			'qrCodePlayInfo'         => SettingPage::get_option( 'video_message_qr_code_info_for_customer' ),
			'isRecordingEnabled'     => Validate::checked( SettingPage::get_option( 'show_recording_option_for_video_message' ) ),
		];

		if ( function_exists( 'wc_terms_and_conditions_page_id' ) ) {
			$data['termsAndConditionsUrl'] = get_permalink( wc_terms_and_conditions_page_id() );
		}

		$data['pdfSizes'] = FreePdfBase::get_sizes();
		$data['fonts']    = Fonts::get_list_for_web();

		if ( ! $is_user_logged_in ) {
			$data['loginUrl']        = wp_login_url( get_permalink() );
			$data['lostPasswordUrl'] = wp_lostpassword_url();
		}

		if ( $is_user_logged_in ) {
			$data['restNonce'] = wp_create_nonce( 'wp_rest' );

			$data['user'] = [
				'name'      => $user->display_name,
				'avatarUrl' => get_avatar_url( $user->user_email ),
			];

			$data['logoutUrl'] = wp_logout_url( get_the_permalink() );
		}

		$data['occasions']  = Setting::get_occasions();
		$data['topics']     = Setting::get_topics();
		$data['recipients'] = Setting::get_recipients();

		$data['common_holidays']  = Settings::get_common_public_holidays();
		$data['special_holidays'] = Settings::get_special_holidays();

		$data['stability_ai_enabled'] = true;
		$data['stability_ai']         = [
			'occasions'     => StabilityAiSettings::get_occasions(),
			'recipients'    => StabilityAiSettings::get_recipients(),
			'moods'         => StabilityAiSettings::get_moods(),
			'style_presets' => StabilityAiSettings::get_style_presets(),
		];

		echo '<script>window.StackonetToolkit = ' . wp_json_encode( $data ) . '</script>' . PHP_EOL;
	}
}
