<?php

namespace YouSaidItCards;

class FreePdfExtended extends \tFPDF {

	protected int $angle = 0;
	protected float $FontSpacingPt = 0; // current font spacing in points
	protected float $FontSpacing = 0; // current font spacing in user units
	protected $extgstates = array();

	// alpha: real value from 0 (transparent) to 1 (opaque)
	// bm:    blend mode, one of the following:
	//          Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
	//          HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity
	function SetAlpha( $alpha, $bm = 'Normal' ) {
		// set alpha for stroking (CA) and non-stroking (ca) operations
		$gs = $this->AddExtGState( array( 'ca' => $alpha, 'CA' => $alpha, 'BM' => '/' . $bm ) );
		$this->SetExtGState( $gs );
	}

	function AddExtGState( $parms ) {
		$n                               = count( $this->extgstates ) + 1;
		$this->extgstates[ $n ]['parms'] = $parms;

		return $n;
	}

	function SetExtGState( $gs ) {
		$this->_out( sprintf( '/GS%d gs', $gs ) );
	}

	function _enddoc() {
		if ( ! empty( $this->extgstates ) && $this->PDFVersion < '1.4' ) {
			$this->PDFVersion = '1.4';
		}
		parent::_enddoc();
	}

	function _putextgstates() {
		for ( $i = 1; $i <= count( $this->extgstates ); $i ++ ) {
			$this->_newobj();
			$this->extgstates[ $i ]['n'] = $this->n;
			$this->_put( '<</Type /ExtGState' );
			$parms = $this->extgstates[ $i ]['parms'];
			$this->_put( sprintf( '/ca %.3F', $parms['ca'] ) );
			$this->_put( sprintf( '/CA %.3F', $parms['CA'] ) );
			$this->_put( '/BM ' . $parms['BM'] );
			$this->_put( '>>' );
			$this->_put( 'endobj' );
		}
	}

	function _putresourcedict() {
		parent::_putresourcedict();
		$this->_put( '/ExtGState <<' );
		foreach ( $this->extgstates as $k => $extgstate ) {
			$this->_put( '/GS' . $k . ' ' . $extgstate['n'] . ' 0 R' );
		}
		$this->_put( '>>' );
	}

	function _putresources() {
		$this->_putextgstates();
		parent::_putresources();
	}

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

	public function RotatedImage(
		string $image_url_or_path,
		float $x,
		float $y,
		float $w,
		float $h,
		float $angle = 0
	) {
		$angle = - $angle;
		$x     = ( $x + 20 );
		$this->Rotate( $angle, $x, $y );
		$this->Image( $image_url_or_path, $x, $y, $w, $h );
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
