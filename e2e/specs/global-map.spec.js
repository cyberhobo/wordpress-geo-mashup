// @ts-check
const { test, expect } = require( '@playwright/test' );

/**
 * Global map behaviour for each supported map provider.
 *
 * Fixture content comes from `wp geo-mashup-e2e seed` (e2e/fixtures): six posts
 * at five distinct locations tagged with `test_tax` terms, and one page per
 * provider containing a global [geo_mashup_map] shortcode at zoom 10.
 *
 * Ported from the retired QUnit suite (js/qunit-tests.js).
 */

const LOCATION_COUNT = 5;

/** Map centre should be within this many degrees of the expected point. */
const CENTER_TOLERANCE = 0.005;

const APIS = [ 'leaflet', 'openlayers', 'googlev3' ];

/**
 * Skip Google Maps unless a key was provided when seeding the site.
 *
 * @param {string} api
 */
function skipWithoutGoogleKey( api ) {
	test.skip(
		api === 'googlev3' && ! process.env.GOOGLE_MAPS_API_KEY,
		'Set GOOGLE_MAPS_API_KEY (before wp-env start) to run Google Maps tests'
	);
}

/**
 * Open a provider's global map page and wait until every marker is created.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string}                          api
 * @return {Promise<import('@playwright/test').Frame>} The map iframe.
 */
async function openGlobalMap( page, api ) {
	// Map logic doesn't need tiles; skipping them keeps tests fast and offline-tolerant.
	const siteOrigin = new URL( test.info().project.use.baseURL || '' ).origin;
	await page.route( '**/*', ( route ) => {
		const request = route.request();
		if ( request.resourceType() === 'image' && ! request.url().startsWith( siteOrigin ) ) {
			return route.abort();
		}
		return route.continue();
	} );

	await page.goto( `/e2e-global-map-${ api }/` );
	const iframe = page.getByTitle( 'Geo Mashup Map' );
	await expect( iframe ).toBeVisible();
	const frame = await ( await iframe.elementHandle() )?.contentFrame();
	if ( ! frame ) {
		throw new Error( 'Map iframe has no content frame' );
	}
	await frame.waitForFunction(
		( count ) => window.GeoMashup?.map?.markers?.length >= count,
		LOCATION_COUNT
	);
	return frame;
}

/**
 * Get a test_tax term ID by name from the map's term properties.
 *
 * @param {import('@playwright/test').Frame} frame
 * @param {string}                          name
 * @return {Promise<string>} Term ID.
 */
function termId( frame, name ) {
	return frame.evaluate( ( termName ) => {
		const terms = window.GeoMashup.opts.term_properties.test_tax.terms;
		const id = Object.keys( terms ).find( ( key ) => terms[ key ].name === termName );
		if ( ! id ) {
			throw new Error( `No test_tax term named ${ termName }` );
		}
		return id;
	}, name );
}

/**
 * @param {import('@playwright/test').Frame} frame
 * @return {Promise<{lat: number, lon: number}>} Current map centre.
 */
function mapCenter( frame ) {
	return frame.evaluate( () => {
		const center = window.GeoMashup.map.getCenter();
		return { lat: center.lat, lon: center.lon };
	} );
}

for ( const api of APIS ) {
	test.describe( `${ api } global map`, () => {
		test.beforeEach( () => skipWithoutGoogleKey( api ) );

		test( 'loads markers, lines and initial view', async ( { page } ) => {
			const frame = await openGlobalMap( page, api );
			const childTermId = await termId( frame, 'Term One Child' );

			const state = await frame.evaluate( ( childId ) => {
				const gm = window.GeoMashup;
				const center = gm.map.getCenter();
				const first = gm.map.markers[ 0 ].location;
				return {
					markerCount: gm.map.markers.length,
					polylineCount: gm.map.polylines.length,
					termVisible: gm.term_manager.isTermVisible( childId, 'test_tax' ),
					termLineVisible: gm.term_manager.isTermLineVisible( childId, 'test_tax' ),
					zoom: gm.map.getZoom(),
					center: { lat: center.lat, lon: center.lon },
					first: { lat: first.lat, lon: first.lon },
				};
			}, childTermId );

			expect( state.markerCount, 'one marker per location' ).toBe( LOCATION_COUNT );
			expect( state.polylineCount, 'one line for the term with line_zoom set' ).toBe( 1 );
			expect( state.termVisible ).toBe( true );
			expect( state.termLineVisible ).toBe( true );
			expect( state.zoom ).toBe( 10 );
			expect( Math.abs( state.center.lat - state.first.lat ) ).toBeLessThan( CENTER_TOLERANCE );
			expect( Math.abs( state.center.lon - state.first.lon ) ).toBeLessThan( CENTER_TOLERANCE );
		} );

		test( 'selecting a marker centres the map on it', async ( { page } ) => {
			const frame = await openGlobalMap( page, api );

			for ( let i = 0; i < LOCATION_COUNT; i++ ) {
				const target = await frame.evaluate( ( index ) => {
					const gm = window.GeoMashup;
					const marker = gm.map.markers[ index ];
					marker.click.fire();
					return {
						selected: gm.selected_marker === marker,
						lat: marker.location.lat,
						lon: marker.location.lon,
					};
				}, i );

				expect( target.selected, `marker ${ i } is selected` ).toBe( true );
				// Providers may animate the pan, so wait for it to settle.
				await expect
					.poll( async () => {
						const center = await mapCenter( frame );
						return Math.max(
							Math.abs( center.lat - target.lat ),
							Math.abs( center.lon - target.lon )
						);
					}, { message: `map centres on marker ${ i }` } )
					.toBeLessThan( CENTER_TOLERANCE );
			}
		} );

		test( 'term line hides when zoomed past line_zoom', async ( { page } ) => {
			const frame = await openGlobalMap( page, api );
			const childTermId = await termId( frame, 'Term One Child' );

			const after = await frame.evaluate(
				( childId ) =>
					new Promise( ( resolve ) => {
						const gm = window.GeoMashup;
						// In case a provider doesn't fire an initial zoom change event.
						gm.adjustZoom();
						const handler = () => {
							gm.map.changeZoom.removeHandler( handler );
							resolve( {
								termVisible: gm.term_manager.isTermVisible( childId, 'test_tax' ),
								termLineVisible: gm.term_manager.isTermLineVisible( childId, 'test_tax' ),
							} );
						};
						gm.map.changeZoom.addHandler( handler );
						gm.map.setZoom( 11 );
					} ),
				childTermId
			);

			expect( after.termVisible ).toBe( true );
			expect( after.termLineVisible ).toBe( false );
		} );
	} );
}

test.describe( 'googlev3 clustering', () => {
	test.beforeEach( () => skipWithoutGoogleKey( 'googlev3' ) );

	test( 'markers cluster at cluster_max_zoom', async ( { page } ) => {
		const frame = await openGlobalMap( page, 'googlev3' );

		const before = await frame.evaluate( () => {
			const gm = window.GeoMashup;
			return {
				clusterMaxZoomOption: gm.opts.cluster_max_zoom,
				zoom: gm.map.getZoom(),
				hasClusterer: !! gm.clusterer,
				totalMarkers: gm.clusterer?.getTotalMarkers(),
				clustererMaxZoom: gm.clusterer?.getMaxZoom(),
			};
		} );

		expect( before.clusterMaxZoomOption ).toBe( '9' );
		expect( before.zoom ).toBeGreaterThan( 9 );
		expect( before.hasClusterer ).toBe( true );
		expect( before.totalMarkers ).toBe( LOCATION_COUNT );
		expect( before.clustererMaxZoom ).toBe( 9 );

		const after = await frame.evaluate(
			() =>
				new Promise( ( resolve ) => {
					const gm = window.GeoMashup;
					const handler = () => {
						gm.map.changeZoom.removeHandler( handler );
						// Clusters are computed asynchronously after the zoom change.
						window.google.maps.event.addListenerOnce( gm.map.getMap(), 'idle', () => {
							const clusters = gm.clusterer.clusters_;
							resolve( {
								clusterCount: clusters.length,
								clusterOnMap: !! clusters[ 0 ]?.map_,
								clusteredMarkers: clusters[ 0 ]?.getMarkers().length,
								firstMarkerOnMap: !! gm.map.markers[ 0 ].proprietary_marker.getMap(),
								lastMarkerOnMap: !! gm.map.markers[ LOCATION_COUNT - 1 ].proprietary_marker.getMap(),
							} );
						} );
					};
					gm.map.changeZoom.addHandler( handler );
					gm.map.setZoom( 9 );
				} )
		);

		expect( after.clusterCount ).toBe( 1 );
		expect( after.clusterOnMap ).toBe( true );
		expect( after.clusteredMarkers ).toBe( LOCATION_COUNT );
		expect( after.firstMarkerOnMap ).toBe( false );
		expect( after.lastMarkerOnMap ).toBe( false );
	} );
} );

test( 'leaflet renders a visible marker for each location', async ( { page } ) => {
	const frame = await openGlobalMap( page, 'leaflet' );
	await expect( frame.locator( '.leaflet-marker-icon:visible' ) ).toHaveCount( LOCATION_COUNT );
} );
