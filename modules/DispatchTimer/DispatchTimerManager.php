<?php

namespace YouSaidItCards\Modules\DispatchTimer;

use Stackonet\WP\Framework\Supports\Sanitize;

class DispatchTimerManager {
	private static $instance = null;

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
			add_action( 'wp_ajax_yousaidit_dispatch_timer', [ self::$instance, 'dispatch_timer' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_dispatch_timer', [ self::$instance, 'dispatch_timer' ] );
			add_filter( 'woocommerce_short_description', [ self::$instance, 'short_description' ] );
			add_action( 'wp_footer', [ self::$instance, 'add_scripts' ], 0 );

			add_filter( 'yousaidit_toolkit/settings/panels', [ self::$instance, 'add_settings_panels' ] );
			add_filter( 'yousaidit_toolkit/settings/sections', [ self::$instance, 'add_settings_section' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'add_settings_fields' ] );

			add_action( 'rest_api_init', [ new AdminRestController(), 'register_routes' ] );
		}

		return self::$instance;
	}

	public function short_description( $short_description ) {
		if ( is_singular( 'product' ) ) {
			try {
				$short_description = Settings::get_next_dispatch_timer_message();
			} catch ( \Exception $e ) {
			}
		}

		return $short_description;
	}

	public function dispatch_timer() {
		// Nonce verification is not required
		wp_send_json( [
			'nextDispatchTime' => Settings::get_next_dispatch_timer_message(),
		] );
		wp_die();
	}

	/**
	 * Add scripts to update dispatch timer via javascript for un-authenticated user
	 *
	 * @return void
	 */
	public function add_scripts() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}
		$url = add_query_arg( [ 'action' => 'yousaidit_dispatch_timer' ], admin_url( 'admin-ajax.php' ) );
		$url = wp_nonce_url( $url, 'yousaidit_dispatch_timer_nonce', '_token' );
		?>
        <script type="text/javascript">
            var xhr = new XMLHttpRequest();
            xhr.addEventListener("load", function () {
                var data = JSON.parse(xhr.responseText);
                var el = document.querySelector('.woocommerce-product-details__short-description');
                if (el) {
                    el.innerHTML = data.nextDispatchTime;
                }
            });
            xhr.open("GET", "<?php echo esc_url( $url ); ?>");
            xhr.send();
        </script>
		<?php
	}

	/**
	 * Add settings panels
	 *
	 * @param  array  $panels  The panels.
	 *
	 * @return array
	 */
	public function add_settings_panels( array $panels ): array {
		$panels[] = [
			'id'       => 'dispatch_timer',
			'title'    => __( 'Dispatch Timer', 'yousaidit-toolkit' ),
			'priority' => 32
		];

		return $panels;
	}

	/**
	 * Add settings sections
	 *
	 * @param  array  $sections  Array of sections.
	 *
	 * @return array
	 */
	public function add_settings_section( array $sections ): array {
		return $sections;
	}

	/**
	 * Add settings fields
	 *
	 * @param  array  $fields  Array of fields.
	 *
	 * @return array
	 */
	public function add_settings_fields( array $fields ): array {
		$fields[] = [
			'id'                => 'dispatch_timer_weekly_holiday',
			'type'              => 'multi_checkbox',
			'title'             => __( 'Weekly Holiday', 'yousaidit-toolkit' ),
			'priority'          => 5,
			'panel'             => 'dispatch_timer',
			'multiple'          => true,
			'options'           => Settings::DAYS_OF_WEEK,
			'sanitize_callback' => function ( $value ) {
				return $value ? array_map( 'intval', $value ) : '';
			},
		];

		$fields[] = [
			'id'                => 'dispatch_timer_get_cut_off_time',
			'type'              => 'time',
			'title'             => __( 'Cut off time', 'yousaidit-toolkit' ),
			'priority'          => 6,
			'panel'             => 'dispatch_timer',
			'sanitize_callback' => [ Sanitize::class, 'text' ],
		];

		$fields[] = [
			'id'          => 'dispatch_timer_common_public_holidays',
			'type'        => 'html',
			'title'       => __( 'Common Public Holidays', 'yousaidit-toolkit' ),
			'description' => __( 'Public Holidays that are same on every year. e.g. Christmas Day',
				'yousaidit-toolkit' ),
			'priority'    => 10,
			'panel'       => 'dispatch_timer',
			'html'        => '<div id="dispatch_timer_common_public_holidays_app">Loading via javaScript...</div>',
		];

		$fields[] = [
			'id'          => 'dispatch_timer_special_holidays',
			'type'        => 'html',
			'title'       => __( 'Other Holidays', 'yousaidit-toolkit' ),
			'description' => __( 'Public Holidays that are not same on every year. Only keep current year and next year dates.',
				'yousaidit-toolkit' ),
			'priority'    => 10,
			'panel'       => 'dispatch_timer',
			'html'        => '<div id="dispatch_timer_special_holidays_app">Loading via javaScript...</div>',
		];

		return $fields;
	}
}