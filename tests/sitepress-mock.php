<?php
class SitePressMock {
	private $mock_title;

	function __construct( $post_title ) {
		$this->mock_title = $post_title;
	}

	function posts_join_filter( $join ) {
		return $join;
	}

	function posts_where_filter( $where ) {
		return $where . " AND post_title='{$this->mock_title}'";
	}
}