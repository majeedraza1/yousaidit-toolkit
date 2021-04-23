<?php

namespace YouSaidItCards\Modules\WooCommerce;

use WP_Term;

defined( 'ABSPATH' ) || exit;

class VariationSwatch {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Meta key name
	 *
	 * @var string
	 */
	private $meta_key = 'swatches_id';

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_filter( 'product_attributes_type_selector', [ self::$instance, 'add_attribute_types' ] );
			add_action( 'admin_init', [ self::$instance, 'init_attribute_hooks' ] );
			add_action( 'yousaidit_swatch/attribute_fields', [ self::$instance, 'product_attribute_field' ], 10, 3 );
			add_action( 'woocommerce_product_option_terms', [ self::$instance, 'product_option_terms' ], 10, 2 );

			// frontend
			add_filter( 'woocommerce_dropdown_variation_attribute_options_html',
				[ self::$instance, 'variation_attribute_options_html' ], 100, 2 );
			add_filter( 'yousaidit_swatch/swatch_html', [ self::$instance, 'swatch_html' ], 10, 4 );
		}

		return self::$instance;
	}

	/**
	 * @return array
	 */
	public function get_types(): array {
		return [
			'radio' => esc_html__( 'Radio', 'yousaidit-toolkit' ),
			'color' => esc_html__( 'Color', 'yousaidit-toolkit' ),
			'image' => esc_html__( 'Image', 'yousaidit-toolkit' ),
			'label' => esc_html__( 'Label', 'yousaidit-toolkit' ),
		];
	}

	/**
	 * Get swatch html
	 *
	 * @param string $html
	 * @param array $args
	 *
	 * @return string
	 */
	public function variation_attribute_options_html( string $html, array $args ): string {
		$attribute_type = self::get_attribute_type( $args['attribute'] );

		// Return if this is normal attribute
		if ( ! array_key_exists( $attribute_type, $this->get_types() ) ) {
			return $html;
		}

		$options   = $args['options'];
		$product   = $args['product'];
		$attribute = $args['attribute'];
		$class     = "variation-selector variation-select-{$attribute_type}";
		$swatches  = '';

		// Add new option for tooltip to $args variable.
		$args['tooltip'] = wc_string_to_bool( get_option( 'variation_swatch_enable_tooltip', 'yes' ) );

		if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
			$attributes = $product->get_variation_attributes();
			$options    = $attributes[ $attribute ];
		}

		if ( ! empty( $options ) && $product && taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

			foreach ( $terms as $term ) {
				if ( ! in_array( $term->slug, $options ) ) {
					continue;
				}
				$swatches .= apply_filters( 'yousaidit_swatch/swatch_html', '', $term, $attribute_type, $args );
			}
		}

		if ( ! empty( $swatches ) ) {
			$class    .= ' hidden';
			$swatches = '<div class="tawcvs-swatches" data-attribute_name="attribute_' . esc_attr( $attribute ) . '">' . $swatches . '</div>';
			$html     = '<div class="' . esc_attr( $class ) . '">' . $html . '</div>' . $swatches;
		}

		return $html;
	}

	/**
	 * Get swatch html
	 *
	 * @param string $html
	 * @param WP_Term $term
	 * @param string $attribute_type
	 * @param array $args
	 *
	 * @return string
	 */
	public function swatch_html( string $html, WP_Term $term, string $attribute_type, array $args ): string {
		$selected = sanitize_title( $args['selected'] ) == $term->slug ? 'selected' : '';
		$checked  = sanitize_title( $args['selected'] ) == $term->slug ? 'checked' : '';
		$name     = esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) );
		$tooltip  = '';

		if ( ! empty( $args['tooltip'] ) ) {
			// $tooltip = '<span class="swatch__tooltip">' . ( $term->description ?: $name ) . '</span>';
		}

		switch ( $attribute_type ) {
			case 'color':
				$color = get_term_meta( $term->term_id, 'color', true );
				list( $r, $g, $b ) = sscanf( $color, "#%02x%02x%02x" );
				$html = sprintf(
					'<span class="swatch swatch-color swatch-%s %s" style="background-color:%s;color:%s;" data-value="%s">%s%s</span>',
					esc_attr( $term->slug ),
					$selected,
					esc_attr( $color ),
					"rgba($r,$g,$b,0.5)",
					esc_attr( $term->slug ),
					$name,
					$tooltip
				);
				break;

			case 'image':
				$image = get_term_meta( $term->term_id, 'image', true );
				$image = $image ? wp_get_attachment_image_src( $image ) : '';
				$image = $image ? $image[0] : WC()->plugin_url() . '/assets/images/placeholder.png';
				$html  = sprintf(
					'<span class="swatch swatch-image swatch-%s %s" data-value="%s"><img src="%s" alt="%s">%s%s</span>',
					esc_attr( $term->slug ),
					$selected,
					esc_attr( $term->slug ),
					esc_url( $image ),
					esc_attr( $name ),
					$name,
					$tooltip
				);
				break;

			case 'radio':
				$label = get_term_meta( $term->term_id, 'label', true );
				$label = $label ?: $name;
				$html  = sprintf(
					'<div class="swatch swatch-radio swatch-%s %s" data-value="%s">
							<input type="radio" %s />
							<label>%s</label>
							</div>',
					esc_attr( $term->slug ), $selected, $term->slug, $checked, $label
				);
				break;
			case 'label':
				$label = get_term_meta( $term->term_id, 'label', true );
				$label = $label ?: $name;
				$html  = sprintf(
					'<span class="swatch swatch-label swatch-%s %s" data-value="%s">%s%s</span>',
					esc_attr( $term->slug ),
					$selected,
					esc_attr( $term->slug ),
					esc_html( $label ),
					$tooltip
				);
				break;
		}

		return $html;
	}

	/**
	 * @param $attribute_name
	 *
	 * @return string
	 */
	public static function get_attribute_type( $attribute_name ): string {
		$attribute_name       = str_replace( 'pa_', '', $attribute_name );
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$attribute_type       = 'select';
		foreach ( $attribute_taxonomies as $attribute_taxonomy ) {
			if ( $attribute_taxonomy->attribute_name == $attribute_name ) {
				$attribute_type = $attribute_taxonomy->attribute_type;
			}
		}

		return $attribute_type;
	}

	/**
	 * Add custom attribute type
	 *
	 * @param array $types
	 *
	 * @return array
	 */
	public function add_attribute_types( array $types ): array {
		return array_merge( $types, $this->get_types() );
	}

	/**
	 * Initiate attribute hooks
	 */
	public function init_attribute_hooks() {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		if ( ! $attribute_taxonomies ) {
			return;
		}

		foreach ( $attribute_taxonomies as $tax ) {
			$taxonomy = sprintf( "pa_%s", $tax->attribute_name );

			add_action( $taxonomy . '_add_form_fields', [ $this, 'add_attribute_thumbnail_field' ] );
			add_action( $taxonomy . '_edit_form_fields', [ $this, 'edit_attribute_thumbnail_field' ], 10, 2 );

			add_filter( 'manage_edit-' . $taxonomy . '_columns', [ $this, 'product_attribute_columns' ] );
			add_filter( 'manage_' . $taxonomy . '_custom_column', [ $this, 'product_attribute_column' ], 10, 3 );
		}

		add_action( 'created_term', [ $this, 'save_term_meta' ], 10, 2 );
		add_action( 'edit_term', [ $this, 'save_term_meta' ], 10, 2 );
	}

	/**
	 * Registers a column for this attribute taxonomy for this image
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function product_attribute_columns( array $columns ): array {
		if ( isset( $columns['cb'] ) ) {
			$new_columns                    = [];
			$new_columns['cb']              = $columns['cb'];
			$new_columns[ $this->meta_key ] = '';
			unset( $columns['cb'] );
			$columns = array_merge( $new_columns, $columns );
		}

		return $columns;
	}

	/**
	 * Renders the custom column as defined in woocommerce_product_attribute_columns
	 *
	 * @param string $string Blank string.
	 * @param string $column_name Name of the column.
	 * @param int $term_id Term ID.
	 */
	public function product_attribute_column( string $string, string $column_name, int $term_id ) {
		if ( $column_name != $this->meta_key ) {
			return;
		}

		$term           = get_term( $term_id );
		$attribute_type = self::get_attribute_type( $term->taxonomy );
		$value          = get_term_meta( $term_id, $attribute_type, true );

		if ( 'color' == $attribute_type ) {
			$string = sprintf(
				'<div class="swatch-preview swatch-color" style="background-color:%s;"></div>',
				esc_attr( $value )
			);
		}

		if ( 'image' == $attribute_type ) {
			$image  = $value ? wp_get_attachment_image_src( $value ) : '';
			$image  = $image ? $image[0] : WC()->plugin_url() . '/assets/images/placeholder.png';
			$string = sprintf(
				'<img class="swatch-preview swatch-image" src="%s" width="44px" height="44px">',
				esc_url( $image )
			);
		}

		if ( 'label' == $attribute_type ) {
			$string = sprintf( '<div class="swatch-preview swatch-label">%s</div>', esc_html( $value ) );
		}

		echo $string;
	}

	/**
	 * The field used when adding a new term to an attribute taxonomy
	 *
	 * @param string $taxonomy
	 */
	public function add_attribute_thumbnail_field( string $taxonomy ) {
		$attribute_type = self::get_attribute_type( $taxonomy );

		do_action( 'yousaidit_swatch/attribute_fields', $attribute_type, '', 'add' );
	}

	/**
	 * The field used when editing an existing product attribute taxonomy term
	 *
	 * @param WP_Term $term
	 * @param string $taxonomy
	 */
	public function edit_attribute_thumbnail_field( WP_Term $term, string $taxonomy ) {
		$attribute_type = self::get_attribute_type( $taxonomy );
		$value          = get_term_meta( $term->term_id, $attribute_type, true );

		do_action( 'yousaidit_swatch/attribute_fields', $attribute_type, $value, 'edit' );
	}

	/**
	 * @param string $attribute_type
	 * @param mixed $value
	 * @param string $mode
	 */
	public function product_attribute_field( string $attribute_type, $value, string $mode ) {
		$types      = $this->get_types();
		$start_html = 'edit' == $mode ? '<tr class="form-field"><th><label for="term-%s">%s</label></th><td>' :
			'<div class="form-field"><label for="term-%s">%s</label>';

		$html = sprintf( $start_html, esc_attr( $attribute_type ), esc_html( $types[ $attribute_type ] ) );

		if ( 'image' == $attribute_type ) {
			$image = is_numeric( $value ) ? wp_get_attachment_image_src( $value ) : '';
			$image = $image ? $image[0] : WC()->plugin_url() . '/assets/images/placeholder.png';

			$html .= '<div id="product_cat_thumbnail" style="float: left; margin-right: 10px;">';
			$html .= '<img class="thumbnail-image" src="' . esc_url( $image ) . '" width="60px" height="60px"/>';
			$html .= '</div>';
			$html .= '<div style="line-height: 60px;">';
			$html .= sprintf( '<input type="hidden" id="term-%1$s" class="hidden_image_id" name="%1$s" value="%2$s" />',
				esc_attr( $attribute_type ), esc_attr( $value ) );
			$html .= '<button type="button"	class="upload_image_button button">' . esc_html__( 'Upload / Add image' ) . '</button>';
			$html .= '<button type="button"	class="remove_image_button button">' . esc_html__( 'Remove image' ) . '</button>';
			$html .= '</div>';
			$html .= '<div class="clear"></div>';
		} else {
			$html .= sprintf( '<input type="text" id="term-%1$s" name="%1$s" value="%2$s" />',
				esc_attr( $attribute_type ), esc_attr( $value ) );
		}

		$html .= 'edit' == $mode ? '</td></tr>' : '</div>';

		echo $html;
	}

	/**
	 * Add selector for extra attribute types
	 *
	 * @param $taxonomy
	 * @param $index
	 */
	public function product_option_terms( $taxonomy, $index ) {
		if ( ! array_key_exists( $taxonomy->attribute_type, $this->get_types() ) ) {
			return;
		}

		$taxonomy_name = wc_attribute_taxonomy_name( $taxonomy->attribute_name );
		global $thepostid;

		$product_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : $thepostid;
		?>

		<select multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select terms', 'wcvs' ); ?>"
				class="multiselect attribute_values wc-enhanced-select"
				name="attribute_values[<?php echo $index; ?>][]">
			<?php

			$all_terms = get_terms( $taxonomy_name, apply_filters( 'woocommerce_product_attribute_terms', array(
				'orderby'    => 'name',
				'hide_empty' => false
			) ) );
			if ( $all_terms ) {
				foreach ( $all_terms as $term ) {
					echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( has_term( absint( $term->term_id ), $taxonomy_name, $product_id ), true, false ) . '>' . esc_attr( apply_filters( 'woocommerce_product_attribute_term_name', $term->name, $term ) ) . '</option>';
				}
			}
			?>
		</select>
		<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'wcvs' ); ?></button>
		<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'wcvs' ); ?></button>
		<button class="button fr plus tawcvs_add_new_attribute"
				data-type="<?php echo $taxonomy->attribute_type ?>"><?php esc_html_e( 'Add new', 'wcvs' ); ?></button>

		<?php
	}

	/**
	 * Save term meta
	 *
	 * @param int $term_id
	 */
	public function save_term_meta( int $term_id ) {
		foreach ( array_keys( $this->get_types() ) as $type ) {
			if ( isset( $_POST[ $type ] ) ) {
				update_term_meta( $term_id, $type, sanitize_text_field( $_POST[ $type ] ) );
			}
		}
	}
}
