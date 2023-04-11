<?php

namespace YouSaidItCards\OpenAI;

use Stackonet\WP\Framework\Abstracts\Data;
use Stackonet\WP\Framework\Supports\Validate;

/**
 * CardOption class
 */
class CardOption extends Data {
	const OCCASIONS = [
		"birthday"            => "Birthday",
		"christmas"           => "Christmas",
		"valentines_day"      => "Valentines",
		"wedding_anniversary" => "Wedding anniversary",
		"mothers_day"         => "Mother's day",
		"fathers_day"         => "Father's day",
		"new_baby"            => "New baby",
		"get_well"            => "Get well",
		"thank_you"           => "Thank you",
		"congratulations"     => "Congratulations",
		"break_up"            => "Break up",
	];

	const RECIPIENTS = [
		"friend"        => "Friend",
		"husband"       => "Husband",
		"wife"          => "Wife",
		"mother"        => "Mother",
		"father"        => "Father",
		"daughter"      => "Daughter",
		"son"           => "Son",
		"grandmother"   => "Grandmother",
		"grandfather"   => "Grandfather",
		"granddaughter" => "Granddaughter",
		"grandson"      => "Grandson",
		"sister"        => "Sister",
		"brother"       => "Brother",
		"aunt"          => "Aunt",
		"uncle"         => "Uncle",
		"cousin"        => "Cousin",
		"nephew"        => "Nephew",
		"niece"         => "Niece",
		"colleague"     => "Colleague",
		"boss"          => "Boss",
		"teacher"       => "Teacher",
	];

	const TOPICS = [
		"sun_moon_and_stars" => "Sun, moon and stars",
		"animals"            => "Animals",
		"flowers"            => "Flowers",
		"food"               => "Food",
		"nature"             => "Nature",
		"travel"             => "Travel",
		"music"              => "Music",
		"sports"             => "Sports",
		"star_wars"          => "Star Wars",
		"marvel"             => "Marvel",
		"pokemon"            => "Pokemon",
	];

	/**
	 * Get instruction for OpenAI model
	 *
	 * @return string
	 */
	public function get_instruction(): string {
		$type = $this->is_poem() ? 'poem' : 'message';
		$text = sprintf(
			'Write me a %s %s for my %s',
			strtolower( static::OCCASIONS[ $this->get_occasion() ] ),
			$type,
			strtolower( static::RECIPIENTS[ $this->get_recipient() ] )
		);

		if ( ! empty( $this->get_topic() ) ) {
			$text .= sprintf( ' who likes %s', strtolower( static::TOPICS[ $this->get_topic() ] ) );
		}

		$text .= '. Max number of words is 60';

		return $text;
	}

	/**
	 * Get occasion
	 *
	 * @return string|null
	 */
	public function get_occasion(): ?string {
		return $this->get_prop( 'occasion' );
	}

	/**
	 * Set occasion
	 *
	 * @param  mixed  $occasion  Occasion.
	 *
	 * @return void
	 */
	public function set_occasion( $occasion ) {
		if ( array_key_exists( $occasion, static::OCCASIONS ) ) {
			$this->data['occasion'] = $occasion;
		}
	}

	/**
	 * Get recipient
	 *
	 * @return string|null
	 */
	public function get_recipient(): ?string {
		return $this->get_prop( 'recipient' );
	}

	/**
	 * Set recipient
	 *
	 * @param  mixed  $recipient  Recipient.
	 *
	 * @return void
	 */
	public function set_recipient( $recipient ) {
		if ( array_key_exists( $recipient, static::RECIPIENTS ) ) {
			$this->data['recipient'] = $recipient;
		}
	}

	/**
	 * Get topic
	 *
	 * @return string|null
	 */
	public function get_topic(): ?string {
		return $this->get_prop( 'topic' );
	}

	/**
	 * Set topic
	 *
	 * @param  mixed  $topic  Topic.
	 *
	 * @return void
	 */
	public function set_topic( $topic ) {
		if ( array_key_exists( $topic, static::TOPICS ) ) {
			$this->data['topic'] = $topic;
		} else {
			$this->data['topic'] = '';
		}
	}

	/**
	 * Is it poem?
	 *
	 * @return bool
	 */
	public function is_poem(): bool {
		return Validate::checked( $this->get_prop( 'poem' ) );
	}

	/**
	 * Set message type
	 *
	 * @param  string  $type  Message type.
	 *
	 * @return void
	 */
	public function set_type( string $type ) {
		$this->data['poem'] = 'poem' === $type;
	}

	/**
	 * How long have you been together? (optional)
	 * @return float
	 */
	public function get_time_together(): float {
		return (float) $this->get_prop( 'time_together' );
	}

	public function get_time_together_unit(): ?string {
		return $this->get_prop( 'time_together_unit' );
	}

	public function set_time_together( float $time, string $unit ) {
		if ( in_array( $unit, [ 'years', 'months', 'weeks' ], true ) ) {
			$this->data['time_together']      = $time;
			$this->data['time_together_unit'] = $unit;
		}
	}

	public static function dd() {
		$data = [];
		foreach ( array_keys( static::OCCASIONS ) as $occasion ) {
			foreach ( array_keys( static::RECIPIENTS ) as $recipient ) {
				foreach ( array_keys( static::TOPICS ) as $topic ) {
					$option = new static();
					$option->set_occasion( $occasion );
					$option->set_recipient( $recipient );
					$option->set_topic( $topic );
					// $option->set_type( 'poem' );

					$data[] = [
						'option'      => wp_json_encode( $option ),
						'instruction' => $option->get_instruction(),
					];
				}
			}
		}

		return $data;
	}
}
