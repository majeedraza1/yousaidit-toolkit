<?php

class LocalDevelopment {
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();

			add_action( 'admin_footer', [ self::$instance, 'load_vite_client_on_admin_footer' ], 99 );
			add_action( 'wp_footer', [ self::$instance, 'load_vite_client_on_wp_footer' ], 99 );
		}

		return self::$instance;
	}

	/**
	 * Get local development url
	 *
	 * @return string
	 */
	public static function get_local_dev_url(): string {
		if ( defined( 'WP_LOCAL_DEV_URL' ) && is_string( WP_LOCAL_DEV_URL ) ) {
			return WP_LOCAL_DEV_URL;
		}

		return 'http://localhost:5173';
	}

	/**
	 * Is it local development environment?
	 *
	 * @return bool
	 */
	public static function is_local_development(): bool {
		if ( 'local' === wp_get_environment_type() ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$headers = @get_headers( static::get_local_dev_url() );

			return is_array( $headers );
		}

		return false;
	}

	/**
	 * Load vite client on admin footer
	 *
	 * @return void
	 */
	public function load_vite_client_on_admin_footer(): void {
		if ( ! static::is_local_development() ) {
			return;
		}
		$script = '<!-- Development Scripts Start -->' . PHP_EOL;
		$script .= static::get_common_script();
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
		$script .= '<script type="module" src="' . static::get_local_dev_url() . '/resources/admin.ts"></script>' . PHP_EOL;
		$script .= '<!-- Development Scripts End -->' . PHP_EOL;

		echo $script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function load_vite_client_on_wp_footer(): void {
		if ( ! static::is_local_development() ) {
			return;
		}
		$script = '<!-- Development Scripts Start -->' . PHP_EOL;
		$script .= static::get_common_script();
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
		$script .= '<script type="module" src="' . static::get_local_dev_url() . '/resources/frontend.ts"></script>' . PHP_EOL;
		// $script .= '<script type="module" src="http://localhost:5174/resources/main.ts"></script>' . PHP_EOL;
		$script .= '<!-- Development Scripts End -->' . PHP_EOL;

		echo $script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get development script common part
	 *
	 * @return string
	 */
	private static function get_common_script(): string {
		$dev_url = static::get_local_dev_url();
		$script  = '<script type="module">' . PHP_EOL;
		$script  .= 'import "' . $dev_url . '/@vite/client";' . PHP_EOL;
		$script  .= 'window.process = {env: {NODE_ENV: "development"}}' . PHP_EOL;
		$script  .= '</script>' . PHP_EOL;

		return $script;
	}
}

LocalDevelopment::instance();

function is_local_development(): bool {
	return LocalDevelopment::is_local_development();
}
