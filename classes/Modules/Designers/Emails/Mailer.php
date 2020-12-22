<?php

namespace Yousaidit\Modules\Designers\Emails;

defined( 'ABSPATH' ) || exit;

class Mailer extends \Stackonet\WP\Framework\Emails\Mailer {

	/**
	 * Generate table using user submitted data
	 *
	 * @param array $form_fields
	 *
	 * @return string
	 */
	public function all_fields_table( $form_fields ) {
		ob_start(); ?>
		<table style="width: auto; min-width: 300px; max-width: 600px; margin: 0 auto; padding: 0;" align="center"
		       width="600" cellpadding="0" cellspacing="0">
			<?php foreach ( $form_fields as $all_field ) {
				echo $this->build_table_row( $all_field['label'], $all_field['value'] );
			} ?>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param string $label
	 * @param string $value
	 *
	 * @return string
	 */
	protected function build_table_row( $label, $value ) {
		ob_start();
		$value = str_replace( array( "\r\n", "\r", "\n" ), "<br>", $value );
		?>
		<tr>
			<td style="font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;background: #F2F4F6; font-weight: bold; padding: 8px 10px;">
				<?php echo esc_html( $label ); ?>
			</td>
		</tr>
		<tr>
			<td style="font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;padding: 8px 10px 35px;">
				<?php echo $value; ?>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}
}
