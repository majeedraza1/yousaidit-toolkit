<?php

namespace YouSaidItCards\Modules\SocialAuth\Interfaces;

interface UserInfoInterface {
	/**
	 * Get provider name
	 * @return string
	 */
	public function get_provider(): string;

	/**
	 * User unique id on social provider
	 *
	 * @return string
	 */
	public function get_provider_uuid(): string;

	/**
	 * Get user full name
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Get user first name
	 *
	 * @return string
	 */
	public function get_first_name(): string;

	/**
	 * Get user last name
	 *
	 * @return string
	 */
	public function get_last_name(): string;

	/**
	 * Get email
	 *
	 * @return string
	 */
	public function get_email(): string;

	/**
	 * Get picture url
	 *
	 * @return string
	 */
	public function get_picture_url(): string;
}