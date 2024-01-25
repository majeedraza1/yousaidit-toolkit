<?php

namespace YouSaidItCards\REST;

use Stackonet\WP\Framework\Traits\ApiResponse;
use Stackonet\WP\Framework\Traits\ApiUtils;
use WP_REST_Controller;

defined( 'ABSPATH' ) || exit;

class LegacyApiController extends WP_REST_Controller {
	use ApiResponse, ApiUtils;

	/**
	 * The namespace of this controller's route.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $namespace = 'yousaidit/v1';

	/**
	 * Generate pagination metadata
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function getPaginationMetadata( array $args ) {
		_deprecated_function( __METHOD__, '2.0.0', static::class . '::get_pagination_data' );
		$data = wp_parse_args( $args, array(
			"totalCount"     => 0,
			"limit"          => 10,
			"currentPage"    => 1,
			"offset"         => 0,
			"previousOffset" => null,
			"nextOffset"     => null,
			"pageCount"      => 0,
		) );

		if ( ! isset( $args['currentPage'] ) && isset( $args['offset'] ) ) {
			$data['currentPage'] = ( $args['offset'] / $data['limit'] ) + 1;
		}

		if ( ! isset( $args['offset'] ) && isset( $args['currentPage'] ) ) {
			$offset         = ( $data['currentPage'] - 1 ) * $data['limit'];
			$data['offset'] = max( $offset, 0 );
		}

		$previousOffset         = ( $data['currentPage'] - 2 ) * $data['limit'];
		$nextOffset             = $data['currentPage'] * $data['limit'];
		$data['previousOffset'] = ( $previousOffset < 0 || $previousOffset > $data['totalCount'] ) ? null : $previousOffset;
		$data['nextOffset']     = ( $nextOffset < 0 || $nextOffset > $data['totalCount'] ) ? null : $nextOffset;
		$data['pageCount']      = ceil( $data['totalCount'] / $data['limit'] );

		return $data;
	}
}
