/**
 * @fileOverview 
 * Additions to GeoMashup for handling taxonomy terms.
 */

/*global GeoMashup, jQuery */
/*global customGeoMashupCategoryIcon, customGeoMashupColorIcon */

/*jslint browser: true, white: true, sloppy: true */

jQuery.extend( GeoMashup, {

	/**
	 * An object to manage taxonomy terms.
	 * @since 1.5
	 * @memberOf GeoMashup
	 */
	term_manager : (function() {
		var $ = jQuery, 

			// Public interface
			term_manager = {},

			// Loaded terms as { "taxonomy1": { term_count: 0, terms: { 
			// "3" : {
			//    icon : icon,
			//    points : [point],
			//    posts : [object_id],
			//    color : color_rgb,
			//    visible : true,
			//    max_line_zoom : max_line_zoom
			// } } }
			loaded_terms = {},
		
			// Structure and settings for terms in included taxonomies
			// e.g. { "taxonomy1": { "label": "Taxonomy One", "terms": { 
			//   "3": { name: "Term 3 Name", parent_id: "1", color: "red", line_zoom: "7" } 
			// } } }
			term_properties,
			
			// Each taxonomy gets a tree of term ids with null leaves
			// { "taxonomy1": { "1": { "3": null }, "2": null } }
			hierarchies = {}, 
			
			// { "taxonomy1": Element, "taxonomy2": Element }
			legend_elements = [];

		/**
		 * Search for a legend element for a taxonomy.
		 * 
		 * Without a context, the current document is searched first, 
		 * then the parent if available.
		 *
		 * @private
		 *
		 * @param {String} taxonomy 
		 * @param {String} widget_type 'legend' or 'tabbed-index'
		 * @param {Document} context
		 * @return {Element} The legend element or null if not found.
		 */
		function getWidgetElement( taxonomy, widget_type, context ) {
			var element = null;

			if ( typeof context === 'undefined' ) {

				element = getWidgetElement( taxonomy, widget_type, document );

				if ( !element && GeoMashup.have_parent_access ) {
					element = getWidgetElement( taxonomy, widget_type, parent.document );
				}

			} else {

				if ( GeoMashup.opts.name ) {

					element = context.getElementById( GeoMashup.opts.name + '-' + taxonomy + '-' + widget_type );

					if ( !element ) {
						element = context.getElementById( GeoMashup.opts.name + '-' + widget_type );
					}

				}

				if ( !element ) {
					element = context.getElementById( 'gm-term-' + widget_type );
				}

				// Back compat names
				if ( !element && 'category' === taxonomy ) {
					if ( 'legend' === widget_type ) {
						element = context.getElementById( 'gm-cat-legend' );
					} else {
						element = context.getElementById( 'gm-' + widget_type );
					}
				}

			}

			return element;
		}

		function buildTermHierarchy(taxonomy, term_id) {
			var children, child_count, top_id, child_id, properties = term_properties[taxonomy];

			if (term_id) { // This is a recursive call

				// Find children of this term and return them
				term_id = String( term_id );
				children = {};
				child_count = 0;

				$.each( properties.terms, function( child_id, term_data ) {
					if ( term_data.parent_id && term_data.parent_id === term_id ) {
						children[child_id] = buildTermHierarchy( taxonomy, child_id );
						child_count += 1;
					}
				});

				return (child_count > 0) ? children : null;

			} else { // Top-level call

				// Build a tree for each taxonomy's top level (no parent) terms
				hierarchies[taxonomy] = {};

				$.each( properties.terms, function( top_id, term_data ) {
					if ( !term_data.parent_id) {
						hierarchies[taxonomy][top_id] = buildTermHierarchy( taxonomy, top_id );
					}
				} );

			}
		}

		function createTermLines() {
			
			$.each( loaded_terms, function( taxonomy, tax_data ) {
				$.each( tax_data.terms, function ( term_id, term_data ) {
						
					if ( term_data.max_line_zoom >= 0 ) {
						GeoMashup.createTermLine( taxonomy, term_id, term_data );
					}

				} );
			} );
		}

		function sortTermLegendData( taxonomy, tax_data ) {
			var ordered_terms = [];

			$.each( tax_data.terms, function ( term_id, term_data ) {
				var order, sort_term = term_data;

				sort_term.term_id = term_id;
				sort_term.name = term_properties[taxonomy].terms[term_id].name;

				// Check for an explicit order field, otherwise use name
				if ( term_properties[taxonomy].terms[term_id].hasOwnProperty( 'order' ) ) {
					sort_term.order = term_properties[taxonomy].terms[term_id].order;
				} else {
					sort_term.order = sort_term.name.toLowerCase();
				}

				ordered_terms.push( sort_term );
			} );

			ordered_terms.sort( function( a, b ) {
				return ((a.order < b.order) ? -1 : ((a.order > b.order) ? 1 : 0));
			} );
			
			return ordered_terms;
		}

		function createTermLegends() {
			
			$.each( loaded_terms, function( taxonomy, tax_data ) {
				var $legend, list_tag, row_tag, term_tag, definition_tag, 
					$element, $title, format, format_match, interactive,
					add_check_all, $check_all, default_off,
					element = getWidgetElement( taxonomy, 'legend' );

				if ( !element ) {
					return;
				}
				$element = $( element );

				if ( $element.hasClass( 'noninteractive' ) ) {
					interactive = false;
				} else if ( typeof GeoMashup.opts.interactive_legend === 'undefined' ) {
					interactive = true;
				} else {
					interactive = GeoMashup.opts.interactive_legend;
				}

				if ( $element.hasClass( 'check-all-off' ) ) {
					default_off = false;
				} else if ( interactive ) {
					add_check_all = true;
				}

				if ( $element.hasClass( 'default-off' ) ) {
					default_off = true;
				} else {
					default_off = false;
				}

				format_match = /format-(\w+)/.exec( $element.attr( 'class' ) );
				if ( format_match ) {
					format = format_match[1];
				} else if ( GeoMashup.opts.legend_format ) {
					format = GeoMashup.opts.legend_format;
				} else {
					format = 'table';
				}
				if ( format === 'dl' ) { 
					list_tag = 'dl'; 
					row_tag = ''; 
					term_tag = 'dt'; 
					definition_tag = 'dd'; 
				} else if ( format === 'ul' ) { 
					list_tag = 'ul'; 
					row_tag = 'li'; 
					term_tag = 'span'; 
					definition_tag = 'span'; 
				} else { 
					list_tag = 'table'; 
					row_tag = 'tr'; 
					term_tag = 'td'; 
					definition_tag = 'td'; 
				} 
				
				if ( $element.hasClass( 'titles-on' ) || ( !$element.hasClass( 'titles-off' ) && GeoMashup.opts.include_taxonomies.length > 1 ) ) {

					$title = $( '<h2></h2>' )
						.addClass( 'gm-legend-title' )
						.addClass( taxonomy + '-legend-title' )
						.html( term_properties[taxonomy].label );
					/**
					 * A taxonomy legend title is being created
					 * @name GeoMashup#taxonomyLegendTitle
					 * @event
					 * @since 1.5
					 * @param {jQuery} $title Empty legend element with classes
					 * @param {String} taxonomy 
					 */
					GeoMashup.doAction( 'taxonomyLegendTitle', $title, taxonomy );

					$element.append( $title );

				}
				$legend = $( '<' + list_tag + ' class="gm-legend ' + taxonomy + '" />' );

				/**
				 * A taxonomy legend is being created
				 * @name GeoMashup#taxonomyLegend
				 * @event
				 * @since 1.5
				 * @param {jQuery} $legend Empty legend element with classes
				 * @param {String} taxonomy 
				 */
				GeoMashup.doAction( 'taxonomyLegend', $legend, taxonomy );

				if ( GeoMashup.opts.name && add_check_all ) {

					// Add check/uncheck all
					$check_all = $( '<label></label>' )
						.html( GeoMashup.opts.check_all_label )
						.attr( 'for', 'gm-' + taxonomy + '-check-all' )
						.prepend(
							$( '<input type="checkbox" />' ).attr( 'id', 'gm-' + taxonomy + '-check-all' )
								.attr( 'checked', ( default_off ? false : 'checked' ) )
								.change( function() {
									if ( $( this ).is( ':checked' ) ) {
										$legend.find( 'input.gm-' + taxonomy + '-checkbox:not(:checked)' ).click();
									} else {
										$legend.find( 'input.gm-' + taxonomy + '-checkbox:checked' ).click();
									}
								} )
						);	
					if ( row_tag ) {
						$legend.append( 
							$( '<' + row_tag + '/>' ).append( $( '<' + term_tag + '/>' ) )
								.append( $( '<' + definition_tag + '/>' ).append( $check_all ) )
						);
					} else {
						$legend.append( $( '<' + term_tag + '/>' ) ).append( $( '<' + definition_tag + '/>' )
							.append( $check_all ) );
					}
				}

				$.each( sortTermLegendData( taxonomy, tax_data ), function ( i, sort_term ) {
					var term_id, id, name, $entry, $key, $def, $label, $checkbox;

					term_id = sort_term.term_id;
					name = sort_term.name;

					if ( GeoMashup.opts.name && interactive ) {

						id = 'gm-' + taxonomy + '-checkbox-' + term_id;

						$checkbox = $( '<input type="checkbox" name="term_checkbox" />' )
							.attr( 'id', id )
							.addClass( 'gm-' + taxonomy + '-checkbox' )
							.change( function() {
								GeoMashup.term_manager.setTermVisibility( term_id, taxonomy, $( this ).is( ':checked' ) ); 
							});

						if ( default_off ) {
							GeoMashup.term_manager.setTermVisibility( term_id, taxonomy, false ); 
						} else {
							$checkbox.attr( 'checked', 'checked' );
						}

						$label = $( '<label/>' )
							.attr( 'for', id )
							.html( name )
							.prepend( $checkbox );

					} else {

						$label = $( '<span/>' ).html( name );

					}

					$key = $( '<' + term_tag + ' class="symbol"/>').append(
						$( '<img/>' )
							.attr( 'src', sort_term.icon.image )
							.attr( 'alt', term_id )
							.click( function() {
								// Pass clicks to the checkbox
								$label.click();
								return false;
							} )
					);

					$def = $( '<' + definition_tag + ' class="term"/>' ).append( $label );

					/**
					 * A taxonomy legend entry is being created
					 * @name GeoMashup#termLegendEntry
					 * @event
					 * @since 1.5
					 * @param {jQuery} $key Legend key node (td or dt)
					 * @param {jQuery} $def Legend definition node (td or dd)
					 * @param {String} taxonomy 
					 * @param {String} term_id 
					 */
					GeoMashup.doAction( 'taxonomyLegendEntry', $key, $def, taxonomy, term_id );

					if (row_tag) {

						$entry = $( '<' + row_tag + '/>' )
							.addClass( 'term-' + term_id )
							.append( $key )
							.append( $def );
						/**
						 * A taxonomy legend table row is being created
						 * @name GeoMashup#termLegendRow
						 * @event
						 * @since 1.5
						 * @param {jQuery} $entry Table row
						 * @param {String} taxonomy 
						 * @param {String} term_id 
						 */
						GeoMashup.doAction( 'taxonomyLegendRow', $entry, taxonomy, term_id );

						$legend.append( $entry );

					} else {

						$legend.append( $key ).append( $def );

					}

				}); // end each term

				$( element ).append( $legend );

			} );
		}

		term_manager.load = function() {

			term_properties = GeoMashup.opts.term_properties;

			if ( !term_properties ) {
				return;
			}

			$.each( term_properties, function( taxonomy ) {
				buildTermHierarchy( taxonomy );
			} );

		};

		term_manager.extendTerm = function(point, taxonomy, term_id, object) {
			var loaded_taxonomy,
				icon, 
				color_rgb, 
				color_name, 
				max_line_zoom;

			term_id = String( term_id );

			if ( !loaded_terms.hasOwnProperty( taxonomy ) ) {
				loaded_terms[taxonomy] = {terms: {}, term_count: 0};
			}

			loaded_taxonomy = loaded_terms[taxonomy];

			if ( !loaded_taxonomy.terms.hasOwnProperty( term_id ) ) {

				if ( term_properties[taxonomy].terms[term_id].color ) {
					color_name = term_properties[taxonomy].terms[term_id].color;
				} else {
					color_name = GeoMashup.color_names[ loaded_taxonomy.term_count % GeoMashup.color_names.length ];
				}
				color_rgb = GeoMashup.colors[color_name];

				// Back compat callbacks
				if ( typeof customGeoMashupCategoryIcon === 'function' ) {
					icon = customGeoMashupCategoryIcon( GeoMashup.opts, [term_id] );
				}
				if ( !icon && typeof customGeoMashupColorIcon === 'function' ) {
					icon = customGeoMashupColorIcon( GeoMashup.opts, color_name );
				}
				if (!icon) {
					icon = GeoMashup.colorIcon( color_name );
				}

				if ( 'category' === taxonomy ) {
					/**
					 * A category icon is being assigned.
					 * @name GeoMashup#categoryIcon
					 * @deprecated Use GeoMashup#termIcon
					 * @event
					 * @since 1.5
					 * @param {GeoMashupOptions} properties Geo Mashup configuration data
					 * @param {GeoMashupIcon} icon
					 * @param {String} term_id
					 */
					GeoMashup.doAction( 'categoryIcon', GeoMashup.opts, icon, term_id );
				}

				/**
				 * A term icon is being assigned.
				 * @name GeoMashup#termIcon
				 * @event
				 * @since 1.5
				 * @param {GeoMashupIcon} icon
				 * @param {String} taxonomy
				 * @param {String} term_id
				 */
				GeoMashup.doAction( 'termIcon', icon, taxonomy, term_id );

				/**
				 * A category icon is being assigned by color.
				 * @name GeoMashup#colorIcon
				 * @event
				 * @since 1.5
				 * @param {GeoMashupOptions} properties Geo Mashup configuration data
				 * @param {GeoMashupIcon} icon
				 * @param {String} color_name
				 */
				GeoMashup.doAction( 'colorIcon', GeoMashup.opts, icon, color_name );

				max_line_zoom = -1;
				if ( term_properties[taxonomy].terms[term_id].line_zoom ) {
					max_line_zoom = term_properties[taxonomy].terms[term_id].line_zoom;
				}

				loaded_taxonomy.terms[term_id] = {
					icon : icon,
					points : [point],
					objects : [object],
					color : color_rgb,
					visible : true,
					max_line_zoom : max_line_zoom
				};

				loaded_taxonomy.term_count += 1;

			} else { // taxonomy term exists

				loaded_taxonomy.terms[term_id].points.push( point );
				loaded_taxonomy.terms[term_id].objects.push( object );

			}
		};

		/**
		 * Enable more objects to be loaded.
		 * Consider it alpha - probably needs work.
		 * @methodOf GeoMashup
		 * @since 1.5
		 */
		term_manager.reset = function() {

			$.each( loaded_terms, function( taxonomy, tax_data ) {
				$.each( tax_data.terms, function( term_id, term_data ) {
					term_data.points.length = 0;
					if ( term_data.line ) {
						GeoMashup.hideLine( term_data.line );
					}
				} );
			} );

		};

		/**
		 * Get a property of a loaded term.
		 * @methodOf GeoMashup
		 * @since 1.5
		 * @param taxonomy
		 * @param term_id
		 * @param property Property name
		 * @returns {*} Property value
		 */
		term_manager.getTermData = function( taxonomy, term_id, property ) {
			return loaded_terms[taxonomy].terms[term_id][property];
		};

		/**
		 * Find a term in a hierarchy.
		 *
		 * @methodOf GeoMashup
		 * @since 1.5
		 * @param search_id
		 * @param {object|string} hierarchy Optional hierarchy structure or taxonomy name, defaults to 'category'.
		 * @returns {object} Hierarchy tree rooted at the search term or null if not found.
		 */
		term_manager.searchTermHierarchy = function( search_id, hierarchy ) {
			var child_search, term_id;

			if ( !hierarchy ) {
				hierarchy = hierarchies.category;
			} else if ( typeof hierarchy === 'string' ) {
				hierarchy = hierarchies[hierarchy];
			}
			// Use a regular loop, so it can return a value for this function
			for( term_id in hierarchy ) {
				if ( hierarchy.hasOwnProperty( term_id ) && typeof hierarchy[term_id] !== 'function' ) {
					if ( term_id === search_id ) {
						return hierarchy[term_id];
					} else if ( hierarchy[term_id] ) {
						child_search = term_manager.searchTermHierarchy( search_id, hierarchy[term_id] );
						if (child_search) {
							return child_search;
						}
					}
				}
			}
			return null;
		};

		term_manager.populateTermElements = function() {

			createTermLines();
			createTermLegends();
			// The tabbed index may hide markers, so it's created last
			term_manager.tabbed_index.create();

		};

		/**
		 * The tabbed index control object.
		 * @memberOf GeoMashup
		 * @since 1.5
		 */
		term_manager.tabbed_index = (function() {
			var tabbed_index = {},
				tab_term_ids = [],
				tab_hierarchy,
				tab_index_group_size,
				show_inactive_tab_markers, 
				$index;

			function hasLocatedChildren( term_id, taxonomy, hierarchy ) {
				var child_id;

				if ( loaded_terms[taxonomy].terms[term_id] ) {
					return true;
				}

				for ( child_id in hierarchy ) {
					if ( hierarchy.hasOwnProperty( child_id ) && hasLocatedChildren( child_id, taxonomy, hierarchy[child_id] ) ) {
						return true;
					}
				}
				return false;
			}

			function buildTermIndex( term_id, taxonomy, children, top_level) {
				var $term_index, $list, $sub_list, group_count, 
					// Back compat tax name
					tax = taxonomy.replace( 'category', 'cat' );  

				if ( typeof top_level === 'undefined' ) {
					top_level = true;
				}

				$term_index = $( '<div></div>' )
					.attr( 'id', tabbed_index.getTermIndexId( term_id, taxonomy ) )
					.addClass( 'gm-tabs-panel' )
					.addClass( 'gm-tabs-panel-' + term_id );
				if ( top_level ) {
					$term_index.addClass( 'gm-hidden' );
				}

				$list = $( '<ul></ul>' ).addClass( 'gm-index-posts' );

				if ( loaded_terms[taxonomy].terms[term_id] ) {

					loaded_terms[taxonomy].terms[term_id].objects.sort( function( a, b ) {
						var a_name = a.title,
							b_name = b.title;

						if (a_name === b_name) {
							return 0;
						} else {
							return a_name < b_name ? -1 : 1;
						}
					});

					$.each( loaded_terms[taxonomy].terms[term_id].objects, function( i, object ) {
						$list.append( 
							$( '<li></li>' ).append( 
								$( '<a></a>' )
									.attr( 'href', '#' + GeoMashup.opts.name )
									.text( object.title )
									.click( function() {
										GeoMashup.clickObjectMarker( object.object_id );
									})
							)
						);
					});
				}

				$term_index.append( $list );

				if ( children ) {

					group_count = 0;
					$sub_list = $( '<ul></ul>' ).addClass( 'gm-sub-' + tax + '-index');

					$.each( children, function( child_id, grandchildren ) {
						var $li = $( '<li></li>' ),
							loaded_term = loaded_terms[taxonomy].terms[child_id];

						if ( loaded_term ) {
							$li.append( $( '<img />' ).attr( 'src', loaded_term.icon.image ) )
								.append(
									$( '<span></span>' )
										.addClass( 'gm-sub-' + tax + '-title' )
										.text( term_properties[taxonomy].terms[child_id].name )
								);
						}

						$li.append( buildTermIndex( child_id, taxonomy, grandchildren, false ) ).appendTo( $sub_list );

						group_count += 1;
						if ( tab_index_group_size && group_count%tab_index_group_size === 0) {
							$term_index.append( $sub_list );
							$sub_list = $( '<ul></ul>' ).addClass( 'gm-sub-' + tax + '-index');
						}
					});
					$term_index.append( $sub_list );

				}


				return $term_index;
			}

			function buildTabbedIndex( hierarchy, taxonomy ) {
				var $list;

				$index = $( '<div></div>' ).attr( 'id', GeoMashup.opts.name + '-tab-index' );
				$list = $( '<ul class="gm-tabs-nav"></ul>' );

				$.each( hierarchy, function( term_id, children ) {
					if ( hasLocatedChildren( term_id, taxonomy, children ) ) {
						tab_term_ids.push( term_id );
					}
				} );

				tab_term_ids.sort( function( a, b ) {
					var a_name = term_properties[taxonomy].terms[a].name,
						b_name = term_properties[taxonomy].terms[b].name;

					if ( a_name === b_name ) {
						return 0;
					} else {
						return a_name < b_name ? -1 : 1;
					}
				} );
				
				$.each( tab_term_ids, function( i, term_id ) {
					var children = hierarchy[term_id],
						$li = $( '<li></li>' ).addClass( 'gm-tab-inactive' ).addClass( 'gm-tab-inactive-' + term_id ),
						$a = $( '<a></a>' ).attr( 'href', '#' + GeoMashup.opts.name ).click( function() {
							tabbed_index.selectTab( term_id, taxonomy );
							return false;
						});

					if ( loaded_terms[taxonomy] && loaded_terms[taxonomy].terms[term_id] ) {
						$a.append( $( '<img />' ).attr( 'src', loaded_terms[taxonomy].terms[term_id].icon.image ) );
					}
					$a.append( $( '<span></span>' ).text( term_properties[taxonomy].terms[term_id].name ) );
					$li.append( $a );
					$list.append( $li );

					if ( !show_inactive_tab_markers ) {
						term_manager.setHierarchyVisibility( term_id, children, taxonomy, false );
					}
				});

				$index.append( $list ); 

				$.each( hierarchy, function( term_id, children ) {
					$index.append( buildTermIndex( term_id, taxonomy, children ) );
				});

				return $index;
			}

			/**
			 * Get the DOM id of term index element
			 * @since 1.5
			 * @methodOf GeoMashup.term_manager
			 * @param term_id
			 * @param taxonomy
			 * @returns {string}
			 */
			tabbed_index.getTermIndexId = function( term_id, taxonomy ) {
				var tax = taxonomy.replace( 'category', 'cat' );
				return 'gm-' + tax + '-index-' + term_id;
			};

			tabbed_index.create = function() {
				var start_tab_term_id, start_tab_term_id_match, group_size_match, taxonomy,
					disable_tab_auto_select = false,
					$element = [];

				// Determine a taxonomy to use
				$.each( loaded_terms, function( check_taxonomy ) {
					var element = getWidgetElement( check_taxonomy, 'tabbed-index' );
					if ( element ) {
						taxonomy = check_taxonomy;
						$element = $( element );
						return true; // break
					}
				} );
				if ( $element.length === 0 ) {
					return;
				}
				tab_hierarchy = hierarchies[taxonomy];
				
				start_tab_term_id_match = /start-tab-term-(\d+)/.exec( $element.attr( 'class' ) );
				if ( start_tab_term_id_match ) {
					start_tab_term_id = start_tab_term_id_match[1];
				} else if ( 'category' === taxonomy ) {
					start_tab_term_id = GeoMashup.opts.start_tab_category_id;
				}

				group_size_match = /tab-index-group-size-(\d+)/.exec( $element.attr( 'class' ) );
				if ( group_size_match ) {
					tab_index_group_size = group_size_match[1];
				} else {
					tab_index_group_size = GeoMashup.opts.tab_index_group_size;
				}

				if ( $element.hasClass( 'show-inactive-tab-markers' ) ) {
					show_inactive_tab_markers = true;
				} else {
					show_inactive_tab_markers = GeoMashup.opts.show_inactive_tab_markers;
				}

				if ( $element.hasClass( 'disable-tab-auto-select' ) ) {
					disable_tab_auto_select = true;
				} else if ( 'category' === taxonomy ) {
					disable_tab_auto_select = GeoMashup.opts.disable_tab_auto_select;
				}
				
				if ( start_tab_term_id ) {
					tab_hierarchy = term_manager.searchTermHierarchy( start_tab_term_id, tab_hierarchy );
				}

				$element.append( buildTabbedIndex( tab_hierarchy, taxonomy ) );

				if ( !disable_tab_auto_select ) {
					// Select the first tab
					$.each( tab_term_ids, function( i, term_id ) {
						if ( hasLocatedChildren( term_id, taxonomy, tab_hierarchy ) ) {
							tabbed_index.selectTab( term_id, taxonomy );
							return false;
						}
					});
				}
			};

			/**
			 * Make a term tab active.
			 * @methodOf GeoMashup.term_manager
			 * @since 1.5
			 * @param term_id
			 * @param taxonomy
			 * @returns {boolean} success
			 */
			tabbed_index.selectTab = function( term_id, taxonomy ) {
				var $active_tab, hide_term_classes, hide_term_match, hide_term_id;

				if ( !$index ) {
					return false;
				}

				if ( $index.find( '.gm-tab-active-' + term_id ).length > 0 ) {
					// Requested tab is already selected
					return true;
				}
				
				$active_tab = $index.find( '.gm-tabs-nav .gm-tab-active' );
				if ( $active_tab.length > 0 ) {
					hide_term_match = /gm-tab-active-(\d+)/.exec( $active_tab.attr( 'class' ) );
					if ( hide_term_match ) {
						hide_term_id = hide_term_match[1];
						$active_tab.attr( 'class', 'gm-tab-inactive gm-tab-inactive-' + hide_term_id );
					}
				}
				$index.find( '.gm-tabs-nav .gm-tab-inactive-' + term_id )
					.attr( 'class', 'gm-tab-active gm-tab-active-' + term_id );

				// Hide previous active panel
				$index.find( '.gm-tabs-panel.gm-active' )
					.removeClass( 'gm-active' )
					.addClass( 'gm-hidden' );

				// Show selected panel
				$index.find( '.gm-tabs-panel-' + term_id ).removeClass( 'gm-hidden' ).addClass( 'gm-active' );

				if ( !show_inactive_tab_markers ) {
					// Hide previous active markers
					if ( hide_term_id ) {
						term_manager.setHierarchyVisibility( hide_term_id, tab_hierarchy[hide_term_id], taxonomy, false );
					}
					// Show selected markers second so none get re-hidden
					term_manager.setHierarchyVisibility( term_id, tab_hierarchy[term_id], taxonomy, true );
				}

			};

			return tabbed_index;
		}());

		/**
		 * Show or hide a term.
		 * @methodOf GeoMashup
		 * @since 1.5
		 * @param term_id
		 * @param taxonomy
		 * @param visible
		 * @returns {boolean} Whether visibility was set.
		 */
		term_manager.setTermVisibility = function( term_id, taxonomy, visible ) {
			var term_data;

			if ( !loaded_terms[taxonomy] || !loaded_terms[taxonomy].terms[term_id] ) {
				return false;
			}

			term_data = loaded_terms[taxonomy].terms[term_id];

			if ( GeoMashup.map.closeInfoWindow ) {
				GeoMashup.map.closeInfoWindow();
			}

			if ( term_data.line ) {
				if ( visible && GeoMashup.map.getZoom() <= term_data.max_line_zoom ) {
					GeoMashup.showLine( term_data.line );
				} else {
					GeoMashup.hideLine( term_data.line );
				}
			}

			// Check for other visible terms at this location
			loaded_terms[taxonomy].terms[term_id].visible = visible;

			$.each( loaded_terms[taxonomy].terms[term_id].points, function( i, point ) {
				GeoMashup.updateMarkerVisibility( GeoMashup.locations[point].marker );
			});
			
			GeoMashup.recluster();
			GeoMashup.updateVisibleList();

			return true;
		};

		/**
		 * Get the display name of a term.
		 * @methodOf GeoMashup
		 * @since 1.5
		 *
		 * @param taxonomy
		 * @param term_id
		 * @returns {String} The term name.
		 */
		term_manager.getTermName = function( taxonomy, term_id ) {
			return term_properties[taxonomy].terms[term_id].name;
		};

		/**
		 * Determine whether a term ID is an ancestor of another.
		 * 
		 * Works on the loadedMap action and after, when the term hierarchy has been
		 * determined.
		 *
		 * @methodOf GeoMashup
		 * @since 1.5
		 * @param {String} ancestor_id The term ID of the potential ancestor
		 * @param {String} child_id The term ID of the potential child
		 * @param {String} taxonomy The taxonomy of the terms.
		 * @returns {boolean}
		 */
		term_manager.isTermAncestor = function( ancestor_id, child_id, taxonomy ) {

			if ( !term_properties[taxonomy] ) {
				return false;
			}

			ancestor_id = ancestor_id.toString();
			child_id = child_id.toString();

			if ( term_properties[taxonomy].terms[child_id].parent_id ) {
				if ( term_properties[taxonomy].terms[child_id].parent_id === ancestor_id ) {
					return true;
				} else {
					return term_manager.isTermAncestor( ancestor_id, term_properties[taxonomy].terms[child_id].parent_id, taxonomy );
				}
			} else {
				return false;
			}
		};

		/**
		 * Show or hide category lines according to their max_line_zoom setting.
		 *
		 * @methodOf GeoMashup
		 * @since 1.5
		 *
		 * @param {number} old_zoom Previous zoom level.
		 * @param {number} new_zoom New zoom level.
		 */
		term_manager.updateLineZoom = function( old_zoom, new_zoom ) {
			
			$.each( loaded_terms, function( taxonomy, tax_data ) {

				$.each( tax_data.terms, function( term_id, term_data ) {

					if ( term_data.visible && term_data.line ) {

						if ( old_zoom <= term_data.max_line_zoom && new_zoom > term_data.max_line_zoom ) {

							GeoMashup.hideLine( term_data.line );

						} else if ( old_zoom > term_data.max_line_zoom && new_zoom <= term_data.max_line_zoom ) {

							GeoMashup.showLine( term_data.line );

						}
					}

				});

			});

		};

		/**
		 * Show or hide a tree of terms.
		 * @methodOf GeoMashup
		 * @since 1.5
		 *
		 * @param term_id
		 * @param hierarchy The term hierarchy ID tree.
		 * @param taxonomy
		 * @param visible
		 */
		term_manager.setHierarchyVisibility = function( term_id, hierarchy, taxonomy, visible ) {

			term_manager.setTermVisibility( term_id, taxonomy, visible );

			hierarchy = hierarchy || {};
			$.each( hierarchy, function( child_id, grandchildren ) {
				term_manager.setHierarchyVisibility( child_id, grandchildren, taxonomy, visible );
			} );
		};

		/**
		 * Get the current visibility of a term.
		 * @methodof GeoMashup
		 * @since 1.7
		 *
		 * @param term_id
		 * @param taxonomy
		 * @returns {*}
		 */
		term_manager.isTermVisible = function( term_id, taxonomy ) {

			if ( !loaded_terms[taxonomy] || !loaded_terms[taxonomy].terms[term_id] ) {
				return false;
			}

			return loaded_terms[taxonomy].terms[term_id].visible;
		};

		term_manager.isTermLineVisible = function( term_id, taxonomy ) {

			if ( !loaded_terms[taxonomy] || !loaded_terms[taxonomy].terms[term_id] ) {
				return false;
			}

			return GeoMashup.isLineVisible( loaded_terms[taxonomy].terms[term_id].line );
		};

		return term_manager;
	}()),

	/**
	 * Determine whether a category ID is an ancestor of another.
	 * 
	 * Works on the loadedMap action and after, when the category hierarchy has been
	 * determined.
	 *
	 * @methodOf GeoMashup
	 * @deprecated 1.5
	 * @see GeoMashup.term_manager.isTermAncestor()
	 *
	 * @param {String} ancestor_id The category ID of the potential ancestor
	 * @param {String} child_id The category ID of the potential child
	 */
	isCategoryAncestor : function(ancestor_id, child_id) {
		return this.term_manager.isTermAncestor( ancestor_id, child_id, 'category' );
	},

	/**
	 * Hide markers and line for a category.
	 * @methodOf GeoMashup
	 * @deprecated 1.5
	 * @see GeoMashup.term_manager.setTermVisibility()
	 * @param {String} category_id
	 */
	hideCategory : function(category_id) {
		GeoMashup.term_manager.setTermVisibility( category_id, 'category', false );
	},

	/**
	 * Show markers for a category. Also show line if consistent with configuration.
	 * @methodOf GeoMashup
	 * @deprecated 1.5
	 * @see GeoMashup.term_manager.setTermVisibility()
	 * @param {String} category_id
	 */
	showCategory : function(category_id) {
		GeoMashup.term_manager.setTermVisibility( category_id, 'category', true );
	},

	/**
	 * Hide a category and all its child categories.
	 * @methodOf GeoMashup
	 * @deprecated 1.5
	 * @see GeoMashup.term_manager.searchTermHierarchy()
	 * @see GeoMashup.term_manager.setHierarchyVisibility()
	 * @param {String} category_id The ID of the category to hide
	 */
	hideCategoryHierarchy : function(category_id) {
		var hierarchy = GeoMashup.term_manager.searchTermHierarchy( category_id, 'category' );
		GeoMashup.term_manager.setHierarchyVisibility( category_id, hierarchy, 'category', false );
	},

	/**
	 * Show a category and all its child categories.
	 * @methodof GeoMashup
	 * @deprecated 1.5
	 * @see GeoMashup.term_manager.searchTermHierarchy()
	 * @see GeoMashup.term_manager.setHierarchyVisibility()
	 * @param {String} category_id The ID of the category to show
	 */
	showCategoryHierarchy : function(category_id) {
		var hierarchy = GeoMashup.term_manager.searchTermHierarchy( category_id, 'category' );
		GeoMashup.term_manager.setHierarchyVisibility( category_id, hierarchy, 'category', true );
	},

	/**
	 * Select a tab of the tabbed category index control.
	 * @methodOf GeoMashup
	 * @deprecated 1.5
	 * @see GeoMashup.term_manager.tabbed_index.selectTab()
	 * @param {String} select_category_id The ID of the category tab to select
	 */
	categoryTabSelect : function(select_category_id) {
		this.term_manager.tabbed_index.selectTab( select_category_id, 'category' );
	},

	/**
	 * Get the DOM ID of the element containing a category index in the 
	 * tabbed category index control.
	 * @methodOf GeoMashup
	 * @deprecated 1.5
	 * @see GeoMashup.term_manager.tabbed_index.getTermIndexId()
	 * @param {String} category_id The category ID
	 * @return {String} DOM ID
	 */
	categoryIndexId : function(category_id) {
		return 'gm-cat-index-' + category_id;
	},

	createTermLine: function( taxonomy, term_id, term_data ) {
		//provider override
	}

} );

