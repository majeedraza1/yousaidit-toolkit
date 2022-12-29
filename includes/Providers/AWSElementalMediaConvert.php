<?php

namespace YouSaidItCards\Providers;

use Aws\Credentials\Credentials;
use Aws\MediaConvert\Exception\MediaConvertException;
use Aws\MediaConvert\MediaConvertClient;
use Exception;
use WP_Error;

/**
 * AWSElementalMediaConvert class
 */
class AWSElementalMediaConvert {
	/**
	 * Get client
	 */
	private static $client;

	/**
	 * Get settings
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function get_settings(): array {
		if ( ! defined( 'AWS_MEDIA_CONVERT_SETTINGS' ) ) {
			throw new Exception( 'AWS MediaConvert setting is not available.' );
		}

		return unserialize( AWS_MEDIA_CONVERT_SETTINGS );
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
	 * @throws Exception|MediaConvertException
	 */
	public static function get_credentials(): Credentials {
		$settings = static::get_settings();

		return new Credentials( $settings['key'], $settings['secret'] );
	}

	/**
	 * @throws Exception
	 */
	public static function get_client(): ?MediaConvertClient {
		if ( is_null( static::$client ) ) {
			static::$client = new MediaConvertClient( [
				'version'     => static::get_setting( 'version' ),
				'region'      => static::get_setting( 'region' ),
				'endpoint'    => static::get_setting( 'endpoint' ),
				'credentials' => static::get_credentials(),
			] );
		}

		return static::$client;
	}

	/**
	 * Get endpoint url
	 *
	 * @return string|WP_Error
	 */
	public static function get_single_endpoint_url() {
		try {
			$client = self::get_client();
			$result = $client->describeEndpoints( [] );

			return $result['Endpoints'][0]['Url'] ?? '';
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Create a new job
	 *
	 * @return string|WP_Error
	 */
	public static function create_job( ?string $input_file ) {
		try {
			$result = static::get_client()->createJob( [
				"JobTemplate"          => static::get_setting( 'job_template' ),
				"Queue"                => static::get_setting( 'queue' ),
				"Role"                 => static::get_setting( 'role' ),
				"UserMetadata"         => [],
				"AccelerationSettings" => [
					"Mode" => "DISABLED"
				],
				"StatusUpdateInterval" => "SECONDS_10",
				"Priority"             => 0,
				"HopDestinations"      => [],
				"Settings"             => static::get_job_settings( $input_file )
			] );

			return $result->get( 'Job' )['Id'];
		} catch ( MediaConvertException $e ) {
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
	 * @return mixed|WP_Error|null
	 */
	public static function get_job( string $job_id ) {
		try {
			$result = static::get_client()->getJob( [
				"Id" => $job_id,
			] );

			return $result->get( 'Job' );
		} catch ( MediaConvertException $e ) {
			return new WP_Error( $e->getAwsErrorCode(), $e->getAwsErrorMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Get job settings
	 *
	 * @param string $input_file AWS S3 file path or HTTP URL
	 *
	 * @return array
	 * @throws Exception
	 */
	private static function get_job_settings( string $input_file ): array {
		return [
			"TimecodeConfig" => [
				"Source" => "ZEROBASED"
			],
			"OutputGroups"   => [
				[
					"Name"                => "File Group",
					"Outputs"             => [
						[
							"ContainerSettings" => [
								"Container"   => "MP4",
								"Mp4Settings" => [
								]
							],
							"VideoDescription"  => [
								"CodecSettings" => [
									"Codec"        => "H_264",
									"H264Settings" => [
										"MaxBitrate"        => 5000000,
										"RateControlMode"   => "QVBR",
										"SceneChangeDetect" => "TRANSITION_DETECTION"
									]
								]
							],
							"AudioDescriptions" => [
								[
									"AudioSourceName" => "Audio Selector 1",
									"CodecSettings"   => [
										"Codec"       => "AAC",
										"AacSettings" => [
											"Bitrate"    => 96000,
											"CodingMode" => "CODING_MODE_2_0",
											"SampleRate" => 48000
										]
									]
								]
							]
						]
					],
					"OutputGroupSettings" => [
						"Type"              => "FILE_GROUP_SETTINGS",
						"FileGroupSettings" => [
							"Destination" => static::get_setting( 'output_destination' )
						]
					]
				]
			],
			"Inputs"         => [
				[
					"AudioSelectors" => [
						"Audio Selector 1" => [
							"DefaultSelection" => "DEFAULT"
						]
					],
					"VideoSelector"  => [
					],
					"TimecodeSource" => "ZEROBASED",
					"FileInput"      => $input_file
				]
			]
		];
	}

	/**
	 * Format job result
	 *
	 * @param array $data The job data.
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function format_job_result( array $data ): array {
		$setting       = rtrim( static::get_setting( 's3_bucket_base_url' ), '/' );
		$OutputDetails = $data['OutputGroupDetails'][0]['OutputDetails'][0] ?? [];
		$status        = $data['Status'];
		$file_input    = $data['Settings']['Inputs'][0]['FileInput'];
		$filename      = pathinfo( $file_input, PATHINFO_FILENAME );
		$url           = join( '/', [ $setting, sprintf( '%s.%s', $filename, 'mp4' ) ] );

		return [
			'id'       => $data['Id'],
			'status'   => strtolower( $status ),
			'filename' => $filename,
			'input'    => $file_input,
			'output'   => $url,
			'duration' => (int) ( $OutputDetails['DurationInMs'] ?? 0 ) / 1000,
			'width'    => $OutputDetails['VideoDetails']['WidthInPx'] ?? 0,
			'height'   => $OutputDetails['VideoDetails']['HeightInPx'] ?? 0,
		];
	}

	/**
	 * Get video id from job id
	 *
	 * @param string $job_id
	 *
	 * @return int
	 */
	public static function job_id_to_video_id( string $job_id ): int {
		$meta_key = '_aws_media_convert_job_id';
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
				$meta_key,
				$job_id
			),
			ARRAY_A
		);

		return is_array( $row ) && isset( $row['post_id'] ) ? intval( $row['post_id'] ) : 0;
	}
}
