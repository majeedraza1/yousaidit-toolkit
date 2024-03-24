<?php

namespace YouSaidItCards\Modules\Designers\Admin;

use YouSaidItCards\Modules\Designers\Models\DesignerCard;

/**
 * ExportSettings class
 */
class ExportCard {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'export_filters', array( self::$instance, 'custom_export' ) );
			add_action( 'export_wp', array( self::$instance, 'export_wp' ) );
		}

		return self::$instance;
	}

	/**
	 * Add radio input on export list
	 *
	 * @return void
	 */
	public function custom_export() {
		$content = isset( $_GET['content'] ) ? sanitize_text_field( $_GET['content'] ) : '';
		?>
        <p>
            <label>
                <input type="radio" name="content" value="yousaidit-designer-card"
					<?php checked( $content, 'yousaidit-designer-card' ); ?>>
				<?php esc_html_e( 'Yousaidit Toolkit: Designer Card', 'shaplatools' ); ?>
            </label>
        </p>
        <ul id="yousaidit-designer-card-filters" class="export-filters" style="">
            <li>
                <fieldset>
                    <label for="yousaidit-designer-card-id" class="label-responsive">Designer Card Id:</label>
                    <input type="text" name="yousaidit-designer-card-id" id="yousaidit-designer-card-id">
                </fieldset>
            </li>
        </ul>
		<?php
		add_action( 'admin_footer', function () {
			?>
            <script>
                let filterEl = document.querySelector('#yousaidit-designer-card-filters');
                let inputEls = document.querySelectorAll('input[name="content"]');
                inputEls.forEach(el => {
                    el.addEventListener('change', event => {
                        setTimeout(() => {
                            if ('yousaidit-designer-card' === event.target.value) {
                                filterEl.style.display = 'block';
                            } else {
                                filterEl.style.display = 'none';
                            }
                        }, 100)
                    })
                })
            </script>
			<?php
		} );
	}

	/**
	 * Export settings as JSON
	 *
	 * @param  array  $args  The arguments.
	 *
	 * @return void
	 */
	public function export_wp( array $args ) {
		if ( 'yousaidit-designer-card' === $args['content'] ) {
			$card_id = isset( $_REQUEST['yousaidit-designer-card-id'] ) ? absint( $_REQUEST['yousaidit-designer-card-id'] ) : 0;
			$card    = DesignerCard::find_single( $card_id );
			if ( ! $card instanceof DesignerCard ) {
				wp_safe_redirect( admin_url( 'export.php' ) );
			}
			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( empty( $sitename ) ) {
				$sitename = uniqid();
			}
			$date     = gmdate( 'Y-m-d' );
			$filename = sprintf( '%s.DesignerCard-%s.WordPress.%s.json', $sitename, $card_id, $date );

			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
			echo wp_json_encode( static::get_data_to_export( $card ), \JSON_PRETTY_PRINT );
			exit();
		}
	}

	/**
	 * Get settings data
	 *
	 * @return array
	 */
	public static function get_data_to_export( DesignerCard $card ): array {
		$card_array          = $card->get_data();
		$card_array['image'] = new \ArrayObject();
		if ( $card->is_static_card() ) {
			$image = $card->get_image();
			if ( isset( $image['url'] ) ) {
				$card_array['image']['url']           = $image['url'];
				$card_array['image']['base64_string'] = base64_encode( file_get_contents( $image['url'] ) );
			}
		}
		if ( $card->is_dynamic_card() ) {
			$payload = $card->get_dynamic_card_payload();
			foreach ( $payload['card_items'] as $index => $card_item ) {
				$section_type = $card_item['section_type'] ?? '';
				if ( in_array( $section_type, [ 'static-image', 'input-image' ], true ) ) {
					$image_id = isset( $card_item['imageOptions']['img']['id'] ) ? intval( $card_item['imageOptions']['img']['id'] ) : 0;
					$image    = wp_get_attachment_image_src( $image_id, 'full' );
					if ( is_array( $image ) ) {
						$card_array['dynamic_card_payload']['card_items'][ $index ]['imageOptions']['base64_string'] = base64_encode( file_get_contents( $image[0] ) );
					}
				}
			}
		}
		$cards = [
			$card_array
		];

		return [
			'plugin_version' => YOUSAIDIT_TOOLKIT_VERSION,
			'cards'          => $cards,
		];
	}
}
