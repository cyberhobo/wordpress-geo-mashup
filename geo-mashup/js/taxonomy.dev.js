/**
 * @fileOverview 
 * Additions to GeoMashup for handling taxonomy terms.
 */

/*global GeoMashup, jQuery */

/*jslint browser: true, white: true, sloppy: true */

jQuery.extend( GeoMashup, {
	
	termManager : (function() {
		var $ = jQuery, 

			// Public interface
			termManager = {},

			// Loaded taxonomies as { "taxonomy1": { term_count: 0, terms: { 
			// "3" : {
			// 	icon : icon,
			// 	points : [point],
			// 	posts : [object_id],
			// 	color : color_rgb,
			// 	visible : true,
			// 	max_line_zoom : max_line_zoom
			// } } }
			taxonomies = {},
		
			// e.g. { "taxonomy1": { "3": { name: "Term 3 Name", parent_id: "1", color: "red", line_zoom: "7" } } }
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
		 * @param {String} taxonomy 
		 * @param {Document} context
		 * @return {Element} The legend element or null if not found.
		 */
		function getLegendElement( taxonomy, context ) {
			var element = null;

			if ( typeof context == 'undefined' ) {

				element = getLegendElement( taxonomy, document );

				if ( !element && GeoMashup.have_parent_access ) {
					element = getLegendElement( taxonomy, parent.document );
				}

			} else {

				if ( GeoMashup.opts.name ) {

					element = context.getElementById( GeoMashup.opts.name + '-' + taxonomy + '-legend' );

					if ( !element ) {
						element = context.getElementById( GeoMashup.opts.name + '-legend' );
					}

				}

				if ( !element ) {
					element = context.getElementById( 'gm-term-legend' );
				}

				if ( !element && 'category' == taxonomy ) {
					element = context.getElementById( 'gm-cat-legend' );
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

				for (child_id in properties[taxonomy]) {
					if (properties.hasOwnProperty( child_id ) && properties[child_id].parent_id && properties[child_id].parent_id === term_id) {
						children[child_id] = this.buildTermHierarchy(taxonomy, child_id);
						child_count += 1;
					}
				}

				return (child_count > 0) ? children : null;

			} else { // Top-level call

				// Build a tree for each taxonomy's top level (no parent) terms
				hierarchies[taxonomy] = {};

				for (top_id in properties[taxonomy]) {
					if ( properties.hasOwnProperty( top_id ) && !properties[top_id].parent_id) {
						hierarchies[taxonomy][top_id] = buildTermHierarchy(taxonomy, top_id);
					}
				}

			}
		}

		termManager.load = function() {

			term_properties = GeoMashup.opts.term_properties;

			if ( !term_properties ) {
				return;
			}

			$.each( term_properties, function( taxonomy ) {
				buildTermHierarchy( taxonomy );
			} );

		};

		termManager.extendTerm = function(point, taxonomy, term_id, object_id) {
			var 
				loaded_taxonomy,
				icon, 
				color_rgb, 
				color_name, 
				max_line_zoom;

			term_id = String( term_id );
			object_id = String( object_id );

			if ( !taxonomies.hasOwnProperty( taxonomy ) )
				taxonomies[taxonomy] = {terms: {}, term_count: 0};

			loaded_taxonomy = taxonomies[taxonomy];

			if ( !loaded_taxonomy.terms.hasOwnProperty( term_id ) ) {

				if ( term_properties[taxonomy][term_id].color ) {
					color_name = term_properties[taxonomy][term_id].color;
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

				/**
				 * A category icon is being assigned.
				 * @name GeoMashup#categoryIcon
				 * @event
				 * @param {GeoMashupOptions} properties Geo Mashup configuration data
				 * @param {GeoMashupIcon} icon
				 * @param {String} term_id
				 */
				GeoMashup.doAction( 'categoryIcon', GeoMashup.opts, icon, term_id );

				/**
				 * A category icon is being assigned by color.
				 * @name GeoMashup#colorIcon
				 * @event
				 * @param {GeoMashupOptions} properties Geo Mashup configuration data
				 * @param {GeoMashupIcon} icon
				 * @param {String} color_name
				 */
				GeoMashup.doAction( 'colorIcon', GeoMashup.opts, icon, color_name );

				max_line_zoom = -1;
				if ( term_properties[taxonomy][term_id].line_zoom ) {
					max_line_zoom = term_properties[taxonomy][term_id].line_zoom;
				}

				loaded_taxonomy.terms[term_id] = {
					icon : icon,
					points : [point],
					posts : [object_id],
					color : color_rgb,
					visible : true,
					max_line_zoom : max_line_zoom
				};

				loaded_taxonomy.term_count += 1;

			} else { // taxonomy term exists

				loaded_taxonomy.terms[term_id].points.push( point );
				loaded_taxonomy.terms[term_id].posts.push( object_id );

			}
		};

		/**
		 * Enable more objects to be loaded - probably needs work.
		 */
		termManager.reset = function() {

			$.each( taxonomies, function( taxonomy, tax_data ) {
				$.each( tax_data.terms, function( term_id, term_data ) {
					term_data.points.length = 0;
					if ( term_data.line ) {
						GeoMashup.hideLine( term_data.line );
					}
				} );
			} );

		};

		termManager.getTermData = function( taxonomy, term_id, property ) {
			return taxonomies[taxonomy].terms[term_id][property];
		};

		termManager.createTermWidgets = function() {
			var $legend, category_id, list_tag, row_tag, term_tag, definition_tag; 
			
			if ( GeoMashup.opts.legend_format && 'dl' === GeoMashup.opts.legend_format) { 
				list_tag = 'dl'; 
				row_tag = ''; 
				term_tag = 'dt'; 
				definition_tag = 'dd'; 
			} else { 
				list_tag = 'table'; 
				row_tag = 'tr'; 
				term_tag = 'td'; 
				definition_tag = 'td'; 
			} 
			
			$.each( GeoMashup.opts.include_taxonomies, function( i, taxonomy ) {
				var element = getLegendElement( taxonomy );

				if ( !element ) {
					return;
				}

				$legend = $( '<' + list_tag + ' class="gm-legend ' + taxonomy + '" />' );

				/**
				 * A taxonomy legend is being created
				 * @name GeoMashup#termLegendElement
				 * @event
				 * @param {jQuery} $legend Empty legend element with classes
				 * @param {String} taxonomy 
				 */
				GeoMashup.doAction( 'taxonomyLegendElement', $legend, taxonomy );

				$.each( taxonomies[taxonomy].terms, function ( term_id, term_data ) {
					var id, name, $entry, $key, $def, $label, $checkbox;

					GeoMashup.createTermLine( term_data );

					if ( !$legend.length ) {
						return true;
					}

					// Default is interactive
					if ( typeof GeoMashup.opts.interactive_legend === 'undefined' ) {
						GeoMashup.opts.interactive_legend = true;
					}

					if ( GeoMashup.opts.name && GeoMashup.opts.interactive_legend ) {

						id = 'gm-' + taxonomy + '-checkbox-' + term_id;
						name = term_properties[taxonomy][term_id].name;

						$checkbox = $( '<input type="checkbox" name="term_checkbox" />' )
							.attr( 'id', id )
							.attr( 'checked', 'checked' )
							.change( function() {
								GeoMashup.termManager.setTermVisibility( taxonomy, term_id, $( this ).is( ':checked' ) ); 
							});

						$label = $( '<label/>' )
							.attr( 'for', id )
							.text( name )
							.prepend( $checkbox );

					} else {

						$label = $( '<span/>' ).text( name ); 

					}

					$key = $( '<' + term_tag + '/>').append(
						$( '<img/>' )
							.attr( 'src', term_data.icon.image )
							.attr( 'alt', term_id )
					);

					$def = $( '<' + definition_tag + '/>' ).append( $label );

					/**
					 * A taxonomy legend entry is being created
					 * @name GeoMashup#termLegendEntry
					 * @event
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
		};

		termManager.setTermVisibility = function( taxonomy, term_id, visible ) {
			var term_data;

			if ( !taxonomies[taxonomy] || !taxonomies[taxonomy].terms[term_id] ) {
				return false;
			}

			term_data = taxonomies[taxonomy].terms[term_id];

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
			taxonomies[taxonomy].terms[term_id].visible = visible;

			$.each( taxonomies[taxonomy].terms[term_id].points, function( i, point ) {
				GeoMashup.updateMarkerVisibility( GeoMashup.locations[point].marker );
			});
			
			GeoMashup.recluster();
			GeoMashup.updateVisibleList();

			return true;
		};

		termManager.getTermName = function( taxonomy, term_id ) {
			return term_properties[taxonomy][term_id].name;
		};

		/**
		 * Determine whether a term ID is an ancestor of another.
		 * 
		 * Works on the loadedMap action and after, when the term hierarchy has been
		 * determined.
		 * 
		 * @param {String} ancestor_id The term ID of the potential ancestor
		 * @param {String} child_id The term ID of the potential child
		 */
		termManager.isTermAncestor = function(taxonomy, ancestor_id, child_id) {

			if ( !term_properties[taxonomy] ) {
				return false;
			}

			ancestor_id = ancestor_id.toString();
			child_id = child_id.toString();

			if ( term_properties[taxonomy][child_id].parent_id ) {
				if ( term_properties[taxonomy][child_id].parent_id === ancestor_id ) {
					return true;
				} else {
					return termManager.isTermAncestor( taxonomy, ancestor_id, term_properties[taxonomy][child_id].parent_id );
				}
			} else {
				return false;
			}
		};

		/**
		 * Show or hide category lines according to their max_line_zoom setting.
		 * 
		 * @since 1.5
		 */
		termManager.updateLineZoom = function( old_zoom, new_zoom ) {

			$.each( taxonomies, function( taxonomy, tax_data ) {

				$.each( tax_data.terms, function( term_id, term_data ) {

					if ( old_zoom <= term_data.max_line_zoom && new_zoom > term_data.max_line_zoom ) {

						GeoMashup.hideLine( term_data.line );

					} else if ( old_zoom > term_data.max_line_zoom && new_zoom <= term_data.max_line_zoom ) {

						GeoMashup.showLine( term_data.line );

					}

				});

			});

		};

		return termManager;
	}()),

	/**
	 * Determine whether a category ID is an ancestor of another.
	 * 
	 * Works on the loadedMap action and after, when the category hierarchy has been
	 * determined.
	 * 
	 * @deprecated use GeoMashup.termManager.isTermAncestor()
	 * 
	 * @param {String} ancestor_id The category ID of the potential ancestor
	 * @param {String} child_id The category ID of the potential child
	 */
	isCategoryAncestor : function(ancestor_id, child_id) {

		return this.termManager.isTermAncestor('category', ancestor_id, child_id);

	},

	hasLocatedChildren : function(category_id, hierarchy) {
		var child_id;

		if (this.categories[category_id]) {
			return true;
		}
		for (child_id in hierarchy) {
			if ( hierarchy.hasOwnProperty( child_id ) && this.hasLocatedChildren(child_id, hierarchy[child_id]) ) {
				return true;
			}
		}
		return false;
	},

	hasLocatedChildren : function(category_id, hierarchy) {

	},

	/**
	 * Hide markers and line for a category.
	 * @param {String} category_id
	 */
	hideCategory : function(category_id) {
		
		GeoMashup.termManager.setTermVisibility( 'category', category_id, false );

	},

	/**
	 * Show markers for a category. Also show line if consistent with configuration.
	 * @param {String} category_id
	 */
	showCategory : function(category_id) {
		
		GeoMashup.termManager.setTermVisibility( 'category', category_id, true );

	},

	searchCategoryHierarchy : function(search_id, hierarchy) {
		var child_search, category_id;

		if (!hierarchy) {
			hierarchy = this.category_hierarchy;
		}
		// Use a regular loop, so it can return a value for this function
		for( category_id in hierarchy ) {
			if ( hierarchy.hasOwnProperty( category_id ) && typeof hierarchy[category_id] !== 'function' ) {
				if (category_id === search_id) {
					return hierarchy[category_id];
				}else if (hierarchy[category_id]) {
					child_search = this.searchCategoryHierarchy(search_id, hierarchy[category_id]);
					if (child_search) {
						return child_search;
					}
				}
			}
		}
		return null;
	},

	/**
	 * Hide a category and all its child categories.
	 * @param {String} category_id The ID of the category to hide
	 */
	hideCategoryHierarchy : function(category_id) {
		var child_id;

		this.hideCategory(category_id);
		this.forEach( this.tab_hierarchy[category_id], function (child_id) {
			this.hideCategoryHierarchy(child_id);
		});
  },

	/**
	 * Show a category and all its child categories.
	 * @param {String} category_id The ID of the category to show
	 */
	showCategoryHierarchy : function(category_id) {
		var child_id;

		this.showCategory(category_id);
		this.forEach( this.tab_hierarchy[category_id], function (child_id) {
			this.showCategoryHierarchy(child_id);
		});
  },

	/**
	 * Select a tab of the tabbed category index control.
	 * @param {String} select_category_id The ID of the category tab to select
	 */
	categoryTabSelect : function(select_category_id) {
		var i, tab_div, tab_list_element, tab_element, id_match, category_id, index_div;

		if ( !this.have_parent_access ) {
			return false;
		}
		tab_div = parent.document.getElementById(this.opts.name + '-tab-index');
		if (!tab_div) {
			return false;
		}
		tab_list_element = tab_div.childNodes[0];
		for (i=0; i<tab_list_element.childNodes.length; i += 1) {
			tab_element = tab_list_element.childNodes[i];
			id_match = tab_element.childNodes[0].href.match(/\d+$/);
			category_id = id_match && id_match[0];
			if (category_id === select_category_id) {
				tab_element.className = 'gm-tab-active gm-tab-active-' + select_category_id;
			}else {
				tab_element.className = 'gm-tab-inactive gm-tab-inactive-' + category_id;
			}
		}
		this.forEach( this.tab_hierarchy, function (category_id) {
			index_div = parent.document.getElementById(this.categoryIndexId(category_id));
			if (index_div) {
				if (category_id === select_category_id) {
					index_div.className = 'gm-tabs-panel';
				} else {
					index_div.className = 'gm-tabs-panel gm-hidden';
					if (!this.opts.show_inactive_tab_markers) {
						this.hideCategoryHierarchy(category_id);
					}
				}
			}
		});
		if (!this.opts.show_inactive_tab_markers) {
			// Done last so none of the markers get re-hidden
			this.showCategoryHierarchy(select_category_id);
		}
	},

	/**
	 * Get the DOM ID of the element containing a category index in the 
	 * tabbed category index control.
	 * @param {String} category_id The category ID
	 */
	categoryIndexId : function(category_id) {
		return 'gm-cat-index-' + category_id;
	},

	categoryTabIndexHtml : function(hierarchy) {
		var html_array = [], category_id;

		html_array.push('<div id="');
		html_array.push(this.opts.name);
		html_array.push('-tab-index"><ul class="gm-tabs-nav">');
		for (category_id in hierarchy) {
			if ( hierarchy.hasOwnProperty( category_id ) && this.hasLocatedChildren(category_id, hierarchy[category_id]) ) {
				html_array = html_array.concat([
					'<li class="gm-tab-inactive gm-tab-inactive-',
					category_id,
					'"><a href="#',
					this.categoryIndexId(category_id),
					'" onclick="frames[\'',
					this.opts.name,
					'\'].GeoMashup.categoryTabSelect(\'',
					category_id,
					'\'); return false;">']);
				if (this.categories[category_id]) {
					html_array.push('<img src="');
					html_array.push(this.categories[category_id].icon.image);
					html_array.push('" />');
				}
				html_array.push('<span>');
				html_array.push(this.opts.category_opts[category_id].name);
				html_array.push('</span></a></li>');
			}
		} 
		html_array.push('</ul></div>');
		this.forEach( hierarchy, function (category_id) {
			html_array.push(this.categoryIndexHtml(category_id, hierarchy[category_id]));
		});
		return html_array.join('');
	},

	categoryIndexHtml : function(category_id, children, top_level) {
		var i, a_name, b_name, html_array = [], group_count, ul_open_tag, child_id;
		if ( typeof top_level === 'undefined' ) {
			top_level = true;
		}
		html_array.push('<div id="');
		html_array.push(this.categoryIndexId(category_id));
		html_array.push('" class="gm-tabs-panel');
		if ( top_level ) {
			html_array.push(' gm-hidden');
		}
		html_array.push('"><ul class="gm-index-posts">');
		if (this.categories[category_id]) {
			this.categories[category_id].posts.sort(function (a, b) {
				a_name = GeoMashup.objects[a].title;
				b_name = GeoMashup.objects[b].title;
				if (a_name === b_name) {
					return 0;
				} else {
					return a_name < b_name ? -1 : 1;
				}
			});
			for (i=0; i<this.categories[category_id].posts.length; i += 1) {
				html_array.push('<li>');
				html_array.push(this.objectLinkHtml(this.categories[category_id].posts[i]));
				html_array.push('</li>');
			}
		}
		html_array.push('</ul>');
		group_count = 0;
		ul_open_tag = '<ul class="gm-sub-cat-index">';
		html_array.push(ul_open_tag);
		this.forEach( children, function (child_id) {
			html_array.push('<li>');
			if (this.categories[child_id]) {
				html_array.push('<img src="');
				html_array.push(this.categories[child_id].icon.image);
				html_array.push('" />');
				html_array.push('<span class="gm-sub-cat-title">');
				html_array.push(this.opts.category_opts[child_id].name);
				html_array.push('</span>');
			}
			html_array.push(this.categoryIndexHtml(child_id, children[child_id], false));
			html_array.push('</li>');
			group_count += 1;
			if (this.opts.tab_index_group_size && group_count%this.opts.tab_index_group_size === 0) {
				html_array.push('</ul>');
				html_array.push(ul_open_tag);
			}
		});
		html_array.push('</ul></div>');
		return html_array.join('');
	},

	createTermLine: function( term_data ) {
		//provider override
	},

	createCategoryLine : function( category ) {
		return this.createTermLine( category );
	},

	createTermWidgets: function() {
	},

	initializeTabbedIndex : function() {
		var category_id,
			index_element;
		if ( !this.have_parent_access ) {
			return false;
		}
		index_element = parent.document.getElementById(this.opts.name + "-tabbed-index");
		if (!index_element) {
			index_element = parent.document.getElementById("gm-tabbed-index");
		}
		if (index_element) {
			if (this.opts.start_tab_category_id) {
				this.tab_hierarchy = this.searchCategoryHierarchy(this.opts.start_tab_category_id);
			} else {
				this.tab_hierarchy = this.category_hierarchy;
			}
			index_element.innerHTML = this.categoryTabIndexHtml(this.tab_hierarchy);
			if (!this.opts.disable_tab_auto_select) {
				// Select the first tab
				for (category_id in this.tab_hierarchy) {
					if (this.tab_hierarchy.hasOwnProperty(category_id) && typeof category_id !== 'function') {
						if (this.hasLocatedChildren(category_id, this.tab_hierarchy[category_id])) {
							this.categoryTabSelect(category_id);
							break;
						}
					}
				}
			}
		}
	}
} );

