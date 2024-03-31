<?php

namespace YouSaidItCards\Modules\Designers\Admin;

use Stackonet\WP\Framework\Media\UploadedFile;
use WP_Error;
use YouSaidItCards\Modules\Designers\DynamicCard;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;

/**
 * ExportImportSettings
 */
class ImportCard {
	const IMPORT_PAGE_ID = 'yousaidit-card-import-card';

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

			add_action( 'admin_init', array( self::$instance, 'register_importer' ) );
			// add_action( 'admin_post_yousaidit_import_designer_card', array( self::$instance, 'import_faqs' ) );
			add_action( 'admin_init', array( self::$instance, 'import_faqs' ) );
		}

		return self::$instance;
	}

	/**
	 * Handle form submission
	 *
	 * @return void
	 */
	public function import_faqs() {
		if ( ! wp_verify_nonce( $_REQUEST['_nonce_token'] ?? '', 'yousaidit-import-designer-card' ) ) {
			return;
		}
		$file = $_FILES['import'] ?? false;
		if ( is_array( $file ) && isset( $file['tmp_name'] ) ) {
			$content = file_get_contents( $file['tmp_name'] );
			if ( is_string( $content ) ) {
				$content = json_decode( $content, true );
			}
			if ( is_array( $content ) ) {
				$cards = $content['cards'] ?? [];
				foreach ( $cards as $card ) {
					if ( isset( $card['id'] ) ) {
						unset( $card['id'] );
					}
					$card_type = $card['card_type'] ?? 'static';
					if ( 'static' === $card_type ) {
						if ( isset( $card['image']['base64_string'] ) ) {
							$image_id = static::import_image_from_base64_string(
								$card['image']['base64_string'],
								$card['image']['url']
							);
							if ( is_numeric( $image_id ) ) {
								$card['image_id'] = $image_id;
							}
						}
					}
					if ( 'dynamic' === $card_type ) {
						foreach ( $card['dynamic_card_payload']['card_items'] as $index => $card_item ) {
							$section_type = $card_item['section_type'] ?? '';
							if ( in_array( $section_type, [ 'static-image', 'input-image' ], true ) ) {
								$image_src     = $card_item['imageOptions']['img']['src'] ?? '';
								$base64_string = $card_item['imageOptions']['base64_string'] ?? '';
								if ( $image_src && $base64_string ) {
									$image_id = static::import_image_from_base64_string(
										$base64_string,
										$image_src
									);
									if ( is_numeric( $image_id ) ) {
										$img = wp_get_attachment_image_src( $image_id, 'full' );
										if ( is_array( $img ) ) {
											$card['dynamic_card_payload']['card_items'][ $index ]['imageOptions']['img'] = [
												'id'     => $image_id,
												'src'    => $img[0],
												'width'  => $img[1],
												'height' => $img[2],
											];
										}
									}
								}
							}
						}

						$card['dynamic_card_payload'] = DynamicCard::sanitize_card_payload( $card['dynamic_card_payload'] );
					}
					DesignerCard::create( $card );
				}
			}

			unlink( $file['tmp_name'] );
		}
		$redirect_url = admin_url( sprintf( 'admin.php?import=%s&step=1', self::IMPORT_PAGE_ID ) );
		wp_safe_redirect( $redirect_url, 302 );
		exit();
	}

	/**
	 * Registers importer for WordPress.
	 *
	 * @return void
	 */
	public function register_importer() {
		register_importer(
			self::IMPORT_PAGE_ID,
			'Yousaidit Toolkit: Import Card',
			'Import designer card from JSON',
			array( $this, 'importer_callback' )
		);
	}

	/**
	 * Import settings from json file
	 *
	 * @return void
	 */
	public function importer_callback() {
		$step = isset( $_REQUEST['step'] ) ? intval( $_REQUEST['step'] ) : 0;
		?>
        <div class="wrap">
            <h2><?php esc_html_e( 'Yousaidit Toolkit: Import Designer Card', 'shaplatools' ); ?></h2>
			<?php
			if ( 0 === $step ) {
				$this->do_import_form();
			}
			if ( 1 === $step ) {
				$this->thank_you();
			}
			?>
        </div>
		<?php
	}

	/**
	 * Show import form
	 *
	 * @return void
	 */
	public function do_import_form() {
		$action_url = admin_url( sprintf( 'admin.php?import=%s', self::IMPORT_PAGE_ID ) );
		?>
        <div class="narrow">
            <p>Choose a JSON (.json) file to upload, then click Upload file and import.</p>
            <form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form"
                  action="<?php echo esc_url( $action_url ); ?>">
				<?php wp_nonce_field( 'yousaidit-import-designer-card', '_nonce_token' ); ?>
                <input type="hidden" name="action" value="yousaidit_import_designer_card">
                <p>
                    <label for="upload">Choose a file from your computer:</label>
                    <input type="file" id="upload" name="import" size="25" accept=".json">
                </p>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="Upload file and import" disabled="">
                </p>
            </form>
        </div>
		<?php
	}

	/**
	 * Show thank you message
	 *
	 * @return void
	 */
	public function thank_you() {
		?>
        <p>
            <strong>Designer card have been updated.</strong><br/>
            You man close this page.
        </p>
		<?php
	}

	public static function import_image_from_base64_string( string $base64_string, string $image_url ) {
		$directory = join(
			DIRECTORY_SEPARATOR,
			array( wp_upload_dir()['basedir'], gmdate( 'Y/m', time() ) )
		);
		$filename  = wp_unique_filename( $directory, basename( $image_url ) );
		$new_file  = join( DIRECTORY_SEPARATOR, [ $directory, $filename ] );
		file_put_contents( $new_file, base64_decode( $base64_string ) );

		// Set correct file permissions.
		$stat  = stat( dirname( $new_file ) );
		$perms = $stat['mode'] & 0000666;
		chmod( $new_file, $perms );

		return static::add_attachment_data( $new_file );
	}

	/**
	 * Add attachment data
	 *
	 * @param  UploadedFile  $file  The uploaded UploadedFile object.
	 * @param  string  $file_path  The uploaded file path.
	 *
	 * @return int|WP_Error
	 */
	public static function add_attachment_data( string $file_path ) {
		$upload_dir = wp_upload_dir();
		$data       = [
			'guid'           => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path ),
			'post_title'     => preg_replace( '/\.[^.]+$/', '', sanitize_text_field( basename( $file_path ) ) ),
			'post_status'    => 'inherit',
			'post_mime_type' => mime_content_type( $file_path ),
		];

		$attachment_id = wp_insert_attachment( $data, $file_path );

		if ( ! is_wp_error( $attachment_id ) ) {
			// Make sure that this file is included, as wp_read_video_metadata() depends on it.
			require_once ABSPATH . 'wp-admin/includes/media.php';
			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once ABSPATH . 'wp-admin/includes/image.php';

			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
			wp_update_attachment_metadata( $attachment_id, $attach_data );
		}

		return $attachment_id;
	}
}
