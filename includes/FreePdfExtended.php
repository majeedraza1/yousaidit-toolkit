<?php

namespace YouSaidItCards;

class FreePdfExtended extends \tFPDF {

	protected int $angle = 0;
	protected float $FontSpacingPt = 0; // current font spacing in points
	protected float $FontSpacing = 0; // current font spacing in user units

	public function SetFontSpacing( float $size ) {
		if ( $this->FontSpacingPt == $size ) {
			return;
		}
		$this->FontSpacingPt = $size;
		$this->FontSpacing   = $size / $this->k;
		if ( $this->page > 0 ) {
			$this->_out( sprintf( 'BT %.3f Tc ET', $size ) );
		}
	}

	protected function _dounderline( $x, $y, $txt ) {
		// Underline text
		$up = $this->CurrentFont['up'];
		$ut = $this->CurrentFont['ut'];
		$w  = $this->GetStringWidth( $txt ) + $this->ws * substr_count( $txt,
				' ' ) + ( strlen( $txt ) - 1 ) * $this->FontSpacing;

		return sprintf( '%.2F %.2F %.2F %.2F re f', $x * $this->k,
			( $this->h - ( $y - $up / 1000 * $this->FontSize ) ) * $this->k, $w * $this->k,
			- $ut / 1000 * $this->FontSizePt );
	}

	public function RotatedText( $x, $y, $txt, $angle = 0 ) {
		$angle = - $angle;
		$this->Rotate( $angle, $x, $y );
		$this->Text( $x, $y, $txt );
		$this->Rotate( 0 );
	}

	public function RotatedImage( $file, $x, $y, $w, $h, $angle ) {
		$this->Rotate( $angle, $x, $y );
		$this->Image( $file, $x, $y, $w, $h );
		$this->Rotate( 0 );
	}

	public function Rotate( $angle, $x = - 1, $y = - 1 ) {
		if ( $x == - 1 ) {
			$x = $this->x;
		}
		if ( $y == - 1 ) {
			$y = $this->y;
		}
		if ( $this->angle != 0 ) {
			$this->_out( 'Q' );
		}
		$this->angle = $angle;
		if ( $angle != 0 ) {
			$angle *= M_PI / 180;
			$c     = cos( $angle );
			$s     = sin( $angle );
			$cx    = $x * $this->k;
			$cy    = ( $this->h - $y ) * $this->k;
			$this->_out( sprintf( 'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, - $s, $c, $cx, $cy,
				- $cx, - $cy ) );
		}
	}

	public function _endpage() {
		if ( $this->angle != 0 ) {
			$this->angle = 0;
			$this->_out( 'Q' );
		}
		parent::_endpage();
	}
}
