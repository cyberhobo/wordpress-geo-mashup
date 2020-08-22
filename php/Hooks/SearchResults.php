<?php

namespace GeoMashup\Hooks;

use GeoMashup\Search;

class SearchResults extends Base {

	public function add() {
		if ( isset( $_POST['location_text'] ) ) {
			add_filter( 'the_content', [ $this, 'the_content' ] );
		}
	}

	public function remove() {
		remove_filter( 'the_content', [ $this, 'the_content' ] );
	}

	public function the_content( $content ) {

		// Ignore unless a search was posted for this page
		$ignore = true;

		// Older search forms did not include result page id, but always location text
		if ( ! isset( $_POST['results_page_id'] ) && isset( $_POST['location_text'] ) ) {
			$ignore = false;
		}

		if ( isset( $_POST['results_page_id'] ) ) {
			$results_page_id = apply_filters( 'geo_mashup_results_page_id', (int) $_POST['results_page_id'] );
			$ignore          = ( $results_page_id !== (int) get_the_ID() );
		}

		if ( $ignore ) {
			return $content;
		}

		// Remove slashes added to form input
		$_POST = stripslashes_deep( $_POST );

		// Remove this filter to prevent recursion
		remove_filter( 'the_content', [ $this, 'the_content' ] );

		$geo_search = new Search( $_POST );

		// Buffer template results and append to content
		ob_start();
		$geo_search->load_template( 'search-results' );
		$content .= ob_get_clean();

		// Add the filter back - it's possible that content preprocessors will cause it to be run again
		add_filter( 'the_content', [ $this, 'the_content' ] );

		return $content;
	}
}
