<?php

namespace Yousaidit\Modules\Designers\Models;

use JsonSerializable;
use WP_Comment;
use WP_User;

class CardComment implements JsonSerializable {
	/**
	 * @var string
	 */
	protected static $comment_agent = 'Stackonet';

	/**
	 * @var string
	 */
	protected static $comment_type = 'DesignerCard';

	/**
	 * @var array
	 */
	protected $comment_meta = [
		'comment_author_role' => '',
	];

	/**
	 * @var array
	 */
	protected $comment = [
		'comment_ID'           => 0,
		'comment_author'       => '',
		'comment_author_email' => '',
		'comment_content'      => '',
		'comment_date'         => '',
	];

	/**
	 * CardComment constructor.
	 *
	 * @param WP_Comment $comment
	 */
	public function __construct( $comment = null ) {
		if ( $comment instanceof WP_Comment ) {
			$comment = $comment->to_array();
			foreach ( $comment as $key => $value ) {
				if ( array_key_exists( $key, $this->comment ) ) {
					$this->comment[ $key ] = $value;
				}
			}
		}
	}

	public function get_data( $key, $default = '' ) {
		if ( isset( $this->comment[ $key ] ) ) {
			return $this->comment[ $key ];
		}
		if ( isset( $this->comment_meta[ $key ] ) ) {
			return $this->comment_meta[ $key ];
		}

		return $default;
	}

	public function to_array() {
		return [
			'id'           => intval( $this->get_data( 'comment_ID' ) ),
			'author'       => $this->get_data( 'comment_author' ),
			'author_email' => $this->get_data( 'comment_author_email' ),
			'author_role'  => $this->get_data( 'comment_author_role' ),
			'content'      => $this->get_data( 'comment_content' ),
			'date'         => $this->get_data( 'comment_date' ),
		];
	}

	/**
	 * Read comment meta
	 */
	public function read_meta_data() {
		$comment_id = $this->comment['comment_ID'];
		foreach ( $this->comment_meta as $key => $default ) {
			$this->comment_meta[ $key ] = get_comment_meta( $comment_id, '_' . $key, true );
		}
	}

	/**
	 * @param int $card_id
	 *
	 * @return array|int
	 */
	public static function get_comments_for_card( $card_id ) {
		$_comments = get_comments( [
			'post_id' => $card_id,
			'agent'   => static::$comment_agent,
			'type'    => static::$comment_type,
		] );

		$comments = [];
		foreach ( $_comments as $comment ) {
			$comments[] = new self( $comment );
		}

		return $comments;
	}

	/**
	 * @param string $content
	 * @param int $card_id
	 * @param null|WP_User $user
	 *
	 * @return false|int
	 */
	public static function insert_comment( $content, $card_id, $user = null ) {
		if ( ! $user instanceof WP_User ) {
			$user = wp_get_current_user();
		}
		$comment_id = wp_insert_comment( [
			'user_id'              => $user->ID,
			'comment_author_email' => $user->user_email,
			'comment_author'       => $user->display_name,
			'comment_content'      => wp_filter_post_kses( $content ),
			'comment_post_ID'      => $card_id,
			'comment_agent'        => static::$comment_agent,
			'comment_type'         => static::$comment_type,
		] );

		return $comment_id;
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}
}
