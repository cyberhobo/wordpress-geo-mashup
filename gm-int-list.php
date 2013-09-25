<?php

/**
 * Utility class for compressing and expanding ID lists for inclusion in URLs.
 *
 * Class GM_Int_List
 */
class GM_Int_List {

	private $digits;
	private $base;
	private $short_list;
	private $long_list;
	private $sep = '.';

	public function __construct( $list ) {

		if ( is_array( $list ) )
			$list = implode( ',', $list );

		$this->short_list = $this->long_list = $list;

		if ( '' == $list )
			return;

		$digits = array_map( 'strval', range( 0, 9 ) );
		$digits = array_merge( $digits, range( 'a', 'z' ) );
		$digits = array_merge( $digits, range( 'A', 'Z' ) );
		$this->digits = array_merge( $digits, array( '-', '_' ) );
		$this->base = count( $this->digits );

		if ( substr( $list, 0, 1 ) == $this->sep )
			$this->expand_list( $list );
		else
			$this->compress_list( $list );
	}

	public function compressed() {
		return $this->short_list;
	}

	public function expanded() {
		return $this->long_list;
	}

	private function compress_int( $value ) {
		$value = intval( $value );
		if ( $value === 0 )
			return '0';
		$short_value = '';
		while( $value > 0 ) {
			$index = $value % $this->base;
			$short_value = $this->digits[$index] . $short_value;
			$value = floor( $value / $this->base );
		}
		return $short_value;
	}

	private function expand_int( $value ) {
		$long_value = 0;
		$digits = str_split( $value );
		foreach ( $digits as $digit ) {
			$long_value = $long_value * $this->base + array_search( $digit, $this->digits );
		}
		return $long_value;
	}

	private function compress_list( $list ) {
		$values = explode( ',', $list );
		if ( empty( $values ) )
			return;

		$short_values = array();
		foreach ( $values as $value ) {
			$short_values[] = $this->compress_int( $value );
		}
		$this->short_list = $this->sep . implode( $this->sep, $short_values );
	}

	private function expand_list( $list ) {
		$values = explode( $this->sep, substr( $list, 1 ) );
		$long_values = array();
		foreach ( $values as $value ) {
			$long_values[] = $this->expand_int( $value );
		}
		$this->long_list = implode( ',', $long_values );
	}

}
