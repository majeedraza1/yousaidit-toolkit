<?php

namespace YouSaidItCards\Modules\Reminders;

use Stackonet\WP\Framework\Emails\BillingEmailTemplate;
use YouSaidItCards\Modules\Reminders\Models\Reminder;
use YouSaidItCards\Modules\Reminders\Models\ReminderGroup;

class ReminderEmailTemplate extends BillingEmailTemplate {

	/**
	 * @var ReminderGroup
	 */
	protected $reminder_group = null;

	/**
	 * @var string
	 */
	protected $user_display_name = '';

	/**
	 * @var string
	 */
	protected $reminder_title = '';
	/**
	 * @var mixed|string
	 */
	protected $occasion_date = '';

	public function __construct( ReminderGroup $reminder_group, $reminder = null ) {
		$this->reminder_group = $reminder_group;
		if ( $reminder instanceof Reminder ) {
			$this->user_display_name = $reminder->get_user()->display_name;
			$this->reminder_title    = $reminder->get( 'name' );
			$this->occasion_date     = date(
				get_option( 'date_format' ),
				strtotime( $reminder->get( 'occasion_date' ) )
			);
		} else {
			$this->user_display_name = '{{user_display_name}}';
			$this->reminder_title    = '{{reminder_title}}';
			$this->occasion_date     = '{{occasion_date}}';
		}
	}

	/**
	 * Get footer
	 */
	public function get_footer_html(): string {
		$html = $this->add_paragraph( '© You Said It ™ (Bluezon Limited) 2022.' );
		$html .= $this->add_paragraph( 'Registered company address is<br> Polar House, East Norfolk Street, Carlisle CA2 5JL, UK.' );
		$html .= $this->add_paragraph(
			sprintf(
				'To stop these emails you can edit or delete your reminders, %sclick here%s.',
				'<a href="' . site_url( 'my-account/reminders' ) . '">',
				'</a>'
			)
		);

		return $html;
	}

	/**
	 * @return string
	 */
	public function get_content_html(): string {
		$this->set_box_mode( false );

		$html = $this->before_content();

		$html .= $this->add_paragraph(
			sprintf( 'Hello %s!', $this->user_display_name ),
			'text-align:center;font-size:16px;margin-bottom:8px;'
		);

		$html .= $this->add_paragraph(
			'It\'s A Friendly Reminder',
			'text-align:center;font-size:24px;margin-bottom:8px;'
		);

		$html .= $this->add_paragraph(
			'for',
			'text-align:center;font-size:14px;margin-bottom:8px;'
		);

		$html .= $this->add_paragraph(
			sprintf(
				'%s %s %s',
				$this->add_span( $this->reminder_title, 'font-size:24px;' ),
				$this->add_span( 'on', 'font-size:14px;' ),
				$this->add_span( $this->occasion_date, 'font-size:24px;' )
			),
			'text-align:center;'
		);

		$html .= $this->row_start( 'background-color:#d2d3d5;margin:0 -15px 15px;' );
		$html .= $this->column_start( 'padding:15px;' );
		$html .= $this->add_paragraph(
			sprintf( 'Browse %s', $this->reminder_group->get( 'title' ) ),
			'text-align:center;font-size:28px;font-weight:bold;margin-bottom:0;'
		);
		$html .= $this->column_end();
		$html .= $this->row_end();

		$products = $this->reminder_group->get_products();

		$products_chunk = array_chunk( $products, 2 );
		foreach ( $products_chunk as $_products ) {
			$html .= $this->row_start( 'margin:0 -15px 15px;' );
			foreach ( $_products as $info ) {
				/** @var \WC_Product $product */
				$product = $info['product'];
				if ( ! $product instanceof \WC_Product ) {
					continue;
				}
				/** @var \WP_Term $category */
				$category = $info['category'];

				$img = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' );

				$html .= $this->column_start( 'padding:15px;width:300px;' );
				$html .= sprintf(
					'<a href="%s" target="_blank" style="display: block;text-align: center;max-width: 270px;">
<img src="%s" width="%s" height="%s" style="max-width: 270px;height:auto;"/>%s</a>',
					esc_url( get_term_link( $category ) ),
					esc_url( $img[0] ),
					esc_attr( $img[1] ),
					esc_attr( $img[2] ),
					esc_html( $category->name )
				);
				$html .= $this->column_end();
			}
			if ( count( $_products ) < 2 ) {
				$html .= $this->column_start( 'padding:15px;width:300px;' );
				$html .= $this->column_end();
			}
			$html .= $this->row_end();
		}

		$html .= $this->after_content();

		return $html;
	}
}
