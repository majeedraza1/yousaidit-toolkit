<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Exception;
use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use Stackonet\WP\Framework\Supports\Logger;
use YouSaidItCards\AWSElementalMediaConvert;

/**
 * BackgroundCopyVideoToServer
 */
class BackgroundCopyVideoToServer extends BackgroundProcess {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	public static $instance = null;

	/**
	 * Action
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_copy_video_to_server';

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'shutdown', [ self::$instance, 'dispatch_data' ] );
		}

		return self::$instance;
	}

	/**
	 * Save and run background on shutdown of all code
	 */
	public function dispatch_data() {
		if ( ! empty( $this->data ) ) {
			$this->save()->dispatch();
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function task( $item ) {
		$job_id = $item['job_id'] ?? null;
		try {
			$job      = AWSElementalMediaConvert::get_job( $job_id );
			$data     = AWSElementalMediaConvert::format_job_result( $job );
			$video_id = VideoEditor::copy_video( $data['output'], $job_id );
		} catch ( Exception $e ) {
			Logger::log( 'Could not copy video for job: ' . $job_id );
			Logger::log( $e );
		}

		return false;
	}
}
