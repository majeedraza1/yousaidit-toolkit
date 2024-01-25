<?php

namespace YouSaidItCards\Providers;

use Aws\Credentials\Credentials;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\Rekognition\RekognitionClient;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use Stackonet\WP\Framework\Media\UploadedFile;
use WP_Error;

/**
 * AWSRekognition class
 */
class AWSRekognition {
	/**
	 * Get client
	 */
	private static $client;

	/**
	 * Get s3 client
	 *
	 * @var S3Client|null
	 */
	private static $s3_client;

	/**
	 * Get settings
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function get_settings(): array {
		if ( ! defined( 'AWS_REKOGNITION_SETTINGS' ) ) {
			throw new Exception( 'AWS Rekognition setting is not available.' );
		}

		return unserialize( AWS_REKOGNITION_SETTINGS );
	}

	/**
	 * Get setting
	 *
	 * @param string $key The setting key.
	 * @param mixed $default The default value
	 *
	 * @return false|mixed
	 * @throws Exception
	 */
	public static function get_setting( string $key, $default = false ) {
		$settings = static::get_settings();

		return $settings[ $key ] ?? $default;
	}

	/**
	 * Get credentials
	 *
	 * @throws Exception|RekognitionException
	 */
	public static function get_credentials(): Credentials {
		return new Credentials(
			static::get_setting( 'key' ),
			static::get_setting( 'secret' )
		);
	}

	/**
	 * @throws Exception
	 */
	public static function get_client(): ?RekognitionClient {
		if ( is_null( static::$client ) ) {
			static::$client = new RekognitionClient( [
				'version'     => static::get_setting( 'version' ),
				'region'      => static::get_setting( 'region' ),
				'credentials' => static::get_credentials(),
			] );
		}

		return static::$client;
	}

	/**
	 * Get s3 client
	 *
	 * @return S3Client|null
	 * @throws Exception
	 */
	public static function get_s3_client(): ?S3Client {
		if ( is_null( static::$s3_client ) ) {
			static::$s3_client = new S3Client( [
				'version'     => static::get_setting( 'version' ),
				'region'      => static::get_setting( 'region' ),
				'credentials' => static::get_credentials(),
			] );
		}

		return static::$s3_client;
	}

	/**
	 * Put video to s3
	 *
	 * @return WP_Error|string
	 */
	public static function put_object( UploadedFile $file, ?string $file_name = '' ) {
		try {
			$file_name = ( ! empty( $file_name ) ? $file_name : $file->get_client_filename() );
			$result    = static::get_s3_client()->putObject( [
				'ACL'        => 'public-read',
				'Bucket'     => static::get_setting( 'source_bucket' ),
				'Key'        => static::get_setting( 'object_prefix' ) . $file_name,
				'SourceFile' => $file->get_file(),
			] );

			return $result->get( 'ObjectURL' );
		} catch ( S3Exception $e ) {
			return new WP_Error( 's3_error', $e->getAwsErrorMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( 'setting_error', $e->getMessage() );
		}
	}

	public static function delete_object( string $filename ) {
		try {
			return static::get_s3_client()->deleteObject( [
				'Bucket' => static::get_setting( 'source_bucket' ),
				'Key'    => static::get_setting( 'object_prefix' ) . $filename,
			] );
		} catch ( S3Exception $e ) {
			return new WP_Error( 's3_error', $e->getAwsErrorMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( 'setting_error', $e->getMessage() );
		}
	}

	/**
	 * Create a new job
	 *
	 * @return string|WP_Error
	 */
	public
	static function create_job(
		string $object_name
	) {
		try {
			$client = static::get_client();
			// Start content moderation for the web url provided $video_url
			$result = $client->startContentModeration( [
				'Video' => [
					'S3Object' => [
						'Bucket' => static::get_setting( 'source_bucket' ),
						'Name'   => $object_name,
					],
				],
			] );

			return $result->get( 'JobId' );
		} catch ( RekognitionException $e ) {
			return new WP_Error( $e->getAwsErrorCode(), $e->getAwsErrorMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Get job
	 *
	 * @param string $job_id The job id.
	 *
	 * @return WP_Error|Result
	 */
	public
	static function get_job(
		string $job_id
	) {
		try {
			return static::get_client()->getContentModeration( [
				"JobId" => $job_id,
			] );
		} catch ( RekognitionException $e ) {
			return new WP_Error( $e->getAwsErrorCode(), $e->getAwsErrorMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * @param Result $result
	 *
	 * @return bool
	 */
	public
	static function is_adult(
		Result $result
	): bool {
		$ModerationLabels = $result->get( 'ModerationLabels' );
		$labels           = [];
		foreach ( $ModerationLabels as $moderation_label ) {
			$name        = $moderation_label['ModerationLabel']['Name'];
			$parent_name = $moderation_label['ModerationLabel']['ParentName'];
			if ( empty( $parent_name ) ) {
				$parent_name = $name;
			}
			$labels[ $parent_name ][] = $name;
		}
		foreach ( $labels as $key => $value ) {
			$labels[ $key ] = array_values( array_unique( $value ) );
		}
		if ( in_array( 'Explicit Nudity', array_keys( $labels ), true ) ) {
			return true;
		}

		return in_array( 'Suggestive', array_keys( $labels ), true );
	}

	/**
	 * Get s3 base url
	 *
	 * @return string
	 * @throws Exception
	 */
	public
	static function get_base_url(): string {
		return sprintf(
			'https://%s.s3.%s.amazonaws.com/',
			static::get_setting( 'source_bucket' ),
			static::get_setting( 'region' )
		);
	}
}
