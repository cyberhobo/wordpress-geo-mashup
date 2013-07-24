<?php

GM_Post_Query::load();

class GM_Post_Query {

	static public function load() {
		add_action( 'parse_query', array( __CLASS__, 'action_parse_query' ) );
		add_filter( 'posts_clauses', array( __CLASS__, 'filter_posts_clauses' ), 10, 2 );
	}

	static public function action_parse_query( $wp_query ) {
		$query_var = $wp_query->get( 'geo_mashup_query' );
		if ( $query_var ) {
			$wp_query->_gm_query = new GM_Location_Query( $query_var );
			if ( 'distance' == $wp_query->get( 'orderby' ) )
				$wp_query->_gm_orderby = 'distance_km';
		}
	}

	static public function filter_posts_clauses( $clauses, $wp_query ) {
		global $wpdb;

		if ( !isset( $wp_query->_gm_query ) )
			return $clauses;

		list( $fields, $join, $where, $groupby ) = $wp_query->_gm_query->get_sql( $wpdb->posts, 'ID' );
		$clauses['fields'] .= $fields;
		$clauses['join'] .= $join;
		$clauses['where'] .= $where;
		$clauses['groupby'] = $groupby;

		if ( isset( $wp_query->_gm_orderby ) )
			$clauses['orderby'] = $wp_query->_gm_orderby;

		return $clauses;
	}
}