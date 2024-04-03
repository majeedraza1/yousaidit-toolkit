<?php

namespace YouSaidItCards\Modules\Designers\Supports;

use WP_Error;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Very simple WordPress Settings API wrapper class
 *
 * WordPress Option Page Wrapper class that implements WordPress Settings API and
 * give you easy way to create multi tabs admin menu and
 * add setting fields with build in validation.
 *
 * @author  Sayful Islam <sayful.islam001@gmail.com>
 * @link    https://sayfulislam.com
 */
class SettingHandler {
	/**
	 * Settings options array
	 */
	private $options = array();

	/**
	 * Settings menu fields array
	 */
	private $menu_fields = array();

	/**
	 * Settings menu fields array
	 *
	 * @var string
	 */
	private $option_name;

	/**
	 * Settings fields array
	 */
	private $fields = array();

	/**
	 * Settings tabs array
	 */
	private $panels = array();

	/**
	 * @var array
	 */
	private $sections = array();

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * @return SettingHandler
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get settings data as array
	 */
	public function to_array() {
		$data = [
			'panels'   => $this->get_panels(),
			'sections' => $this->get_sections(),
			'fields'   => $this->get_fields(),
			'options'  => $this->get_options(),
		];

		return $data;
	}

	/**
	 * Add new admin menu
	 *
	 * This method is accessible outside the class for creating menu
	 *
	 * @param array $menu_fields
	 *
	 * @return WP_Error|SettingHandler
	 */
	public function add_menu( array $menu_fields ) {
		if ( ! isset( $menu_fields['page_title'], $menu_fields['menu_title'], $menu_fields['menu_slug'] ) ) {
			return new WP_Error( 'field_not_set', 'Required key is not set properly for creating menu.' );
		}

		$this->menu_fields = $menu_fields;

		return $this;
	}

	/**
	 * @param mixed $panels
	 *
	 * @return self
	 */
	public function add_panels( $panels ) {
		foreach ( $panels as $panel ) {
			$this->add_panel( $panel );
		}

		return $this;
	}

	/**
	 * @param array $sections
	 *
	 * @return self
	 */
	public function add_sections( $sections ) {
		foreach ( $sections as $section ) {
			$this->add_section( $section );
		}

		return $this;
	}

	/**
	 * @param array $fields
	 *
	 * @return self
	 */
	public function add_fields( $fields ) {
		foreach ( $fields as $field ) {
			$this->add_field( $field );
		}

		return $this;
	}

	/**
	 * Add setting page tab
	 *
	 * This method is accessible outside the class for creating page tab
	 *
	 * @param array $panel
	 *
	 * @return WP_Error|$this
	 */
	public function add_panel( array $panel ) {
		if ( ! isset( $panel['id'], $panel['title'] ) ) {
			return new WP_Error( 'field_not_set', 'Required key is not set properly for creating section.' );
		}


		$this->panels[] = wp_parse_args( $panel, array(
			'id'       => 'general',
			'title'    => '',
			'priority' => 10,
		) );

		return $this;
	}

	/**
	 * Add Setting page section
	 *
	 * @param array $section
	 *
	 * @return $this|WP_Error
	 */
	public function add_section( array $section ) {
		if ( ! isset( $section['id'], $section['title'] ) ) {
			return new WP_Error( 'field_not_set', 'Required key is not set properly for creating section.' );
		}

		$this->sections[] = wp_parse_args( $section, array(
			'id'          => '',
			'panel'       => 'general',
			'title'       => '',
			'description' => '',
			'priority'    => 10,
		) );

		return $this;
	}

	/**
	 * Add new settings field
	 *
	 * This method is accessible outside the class for creating settings field
	 *
	 * @param array $field
	 *
	 * @return WP_Error|$this
	 */
	public function add_field( array $field ) {
		if ( ! isset( $field['id'], $field['title'] ) ) {
			return new WP_Error( 'field_not_set', 'Required key is not set properly for creating field.' );
		}

		$this->fields[ $field['id'] ] = wp_parse_args( $field, array(
			'type'              => 'text',
			'section'           => '',
			'id'                => '',
			'title'             => '',
			'description'       => '',
			'default'           => '',
			'sanitize_callback' => '',
			'priority'          => 10,
		) );

		return $this;
	}

	/**
	 * @param array $input
	 *
	 * @return array
	 */
	public function sanitize_options( array $input ) {
		$output_array = array();
		$fields       = $this->get_fields();
		$options      = (array) $this->get_options();
		foreach ( $fields as $field ) {
			$key     = $field['id'] ?? null;
			$default = $field['default'] ?? null;
			$type    = $field['type'] ?? 'text';
			$value   = $input[ $field['id'] ] ?? $options[ $field['id'] ];

			if ( ! empty( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$output_array[ $key ] = call_user_func( $field['sanitize_callback'], $value );
				continue;
			}

			if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
				$output_array[ $key ] = in_array( $value, array_keys( $field['options'] ) ) ? $value : $default;
				continue;
			}

			if ( 'checkbox' == $type ) {
				$output_array[ $key ] = in_array( $input, array( 'on', 'yes', '1', 1, 'true', true ) ) ? 1 : 0;
				continue;
			}

			$rule                 = empty( $field['validate'] ) ? $field['type'] : $field['validate'];
			$output_array[ $key ] = $this->sanitize( $value, $rule );
		}

		return $output_array;
	}

	/**
	 * @param array $options
	 * @param  bool  $individual
	 */
	public function update( array $options, bool $individual = false ) {
		$sanitized_options = $this->sanitize_options( $options );
		if ( $individual ) {
			foreach ( $sanitized_options as $option_name => $option_value ) {
				update_option( $option_name, $option_value );
			}
		} else {
			update_option( $this->option_name, $sanitized_options );
		}
	}

	/**
	 * Validate the option's value
	 *
	 * @param mixed $input
	 * @param string $validation_rule
	 *
	 * @return mixed
	 */
	private function sanitize( $input, $validation_rule = 'text' ) {
		switch ( $validation_rule ) {
			case 'text':
				return sanitize_text_field( $input );
				break;

			case 'number':
				return is_numeric( $input ) ? intval( $input ) : intval( $input );
				break;

			case 'url':
				return esc_url_raw( trim( $input ) );
				break;

			case 'email':
				return sanitize_email( $input );
				break;

			case 'date':
				return $this->is_date( $input ) ? date( 'F d, Y', strtotime( $input ) ) : '';
				break;

			case 'textarea':
				return _sanitize_text_fields( $input, true );
				break;

			case 'inlinehtml':
				return wp_filter_kses( force_balance_tags( $input ) );
				break;

			case 'linebreaks':
				return wp_strip_all_tags( $input );
				break;

			case 'wp_editor':
				return wp_kses_post( $input );
				break;

			default:
				return sanitize_text_field( $input );
				break;
		}
	}

	/**
	 * Get options parsed with default value
	 *
	 * @param bool $individual
	 *
	 * @return array
	 */
	public function get_options( $individual = false ) {

		if ( $individual ) {
			$options = [];
			foreach ( $this->get_fields() as $value ) {
				$default = isset( $value['default'] ) ? $value['default'] : '';

				$options[ $value['id'] ] = get_option( $value['id'], $default );
			}

			return $this->options = $options;
		}

		$defaults = array();

		foreach ( $this->get_fields() as $value ) {
			$defaults[ $value['id'] ] = isset( $value['default'] ) ? $value['default'] : '';
		}

		$options = wp_parse_args( get_option( $this->option_name ), $defaults );

		return $this->options = $options;
	}

	/**
	 * @return mixed
	 */
	public function get_panels() {
		usort( $this->panels, [ $this, 'sort_by_priority' ] );

		return $this->panels;
	}

	/**
	 * Get sections sorted by priority
	 * @return array
	 */
	public function get_sections() {
		usort( $this->sections, [ $this, 'sort_by_priority' ] );

		return $this->sections;
	}

	/**
	 * Get fields sort by priority
	 *
	 * @return mixed
	 */
	public function get_fields() {
		usort( $this->fields, [ $this, 'sort_by_priority' ] );

		return $this->fields;
	}

	/**
	 * Check if the given input is a valid date.
	 *
	 * @param mixed $value
	 *
	 * @return boolean
	 */
	private function is_date( $value ) {
		if ( $value instanceof \DateTime ) {
			return true;
		}

		if ( strtotime( $value ) === false ) {
			return false;
		}

		$date = date_parse( $value );

		return checkdate( $date['month'], $date['day'], $date['year'] );
	}

	/**
	 * @param string $option_name
	 *
	 * @return self
	 */
	public function set_option_name( $option_name ) {
		$this->option_name = $option_name;

		return $this;
	}

	/**
	 * Sort array by priority key value
	 *
	 * @param array $array1
	 * @param array $array2
	 *
	 * @return bool
	 */
	private function sort_by_priority( $array1, $array2 ) {
		return $array1['priority'] - $array2['priority'];
	}
}
