<?php
class SitePressQueryFilterMock {
	private $mock_title;

	function __construct( $post_title ) {
		$this->mock_title = $post_title;
	}

	function filter_single_type_join( $join, $post_type ) {
		return $join;
	}

	function filter_single_type_where( $where, $post_type ) {
		return $where . " AND post_title='{$this->mock_title}'";
	}
}