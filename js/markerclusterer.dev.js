/**
 * @name MarkerClusterer
 * @version 1.0
 * @author Xiaoxi Wu
 * @copyright (c) 2009 Xiaoxi Wu
 * @fileoverview
 * This javascript library creates and manages per-zoom-level 
 * clusters for large amounts of markers (hundreds or thousands).
 * This library was inspired by the <a href="http://www.maptimize.com">
 * Maptimize</a> hosted clustering solution.
 * <br /><br/>
 * <b>How it works</b>:<br/>
 * The <code>MarkerClusterer</code> will group markers into clusters according to
 * their distance from a cluster's center. When a marker is added,
 * the marker cluster will find a position in all the clusters, and 
 * if it fails to find one, it will create a new cluster with the marker.
 * The number of markers in a cluster will be displayed
 * on the cluster marker. When the map viewport changes,
 * <code>MarkerClusterer</code> will destroy the clusters in the viewport 
 * and regroup them into new clusters.
 *
 */

/*
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


/**
 * @name MarkerClustererOptions
 * @class This class represents optional arguments to the {@link MarkerClusterer}
 * constructor.
 * @property {Number} [maxZoom] The max zoom level monitored by a
 * marker cluster. If not given, the marker cluster assumes the maximum map
 * zoom level. When maxZoom is reached or exceeded all markers will be shown
 * without cluster.
 * @property {Number} [gridSize=60] The grid size of a cluster in pixel. Each
 * cluster will be a square. If you want the algorithm to run faster, you can set
 * this value larger.
 * @property {Array of MarkerStyleOptions} [styles]
 * Custom styles for the cluster markers.
 * The array should be ordered according to increasing cluster size,
 * with the style for the smallest clusters first, and the style for the
 * largest clusters last.
 */

/**
 * @name MarkerStyleOptions
 * @class An array of these is passed into the {@link MarkerClustererOptions}
 * styles option.
 * @property {String} [url] Image url.
 * @property {Number} [height] Image height.
 * @property {Number} [height] Image width.
 * @property {Array of Number} [opt_anchor] Anchor for label text, like [24, 12]. 
 *    If not set, the text will align center and middle.
 * @property {String} [opt_textColor="black"] Text color.
 */

/**
 * Creates a new MarkerClusterer to cluster markers on the map.
 *
 * @constructor
 * @param {GMap2} map The map that the markers should be added to.
 * @param {Array of GMarker} opt_markers Initial set of markers to be clustered.
 * @param {MarkerClustererOptions} opt_opts A container for optional arguments.
 */
function MarkerClusterer(map, opt_markers, opt_opts) {
  // private members
  var clusters_ = [];
  var map_ = map;
  var maxZoom_ = null;
  var me_ = this;
  var gridSize_ = 60;
  var sizes = [53, 56, 66, 78, 90];
  var styles_ = [];
  var leftMarkers_ = [];
  var mcfn_ = null;

  var i = 0;
  for (i = 1; i <= 5; ++i) {
    styles_.push({
      'url': "http://gmaps-utility-library.googlecode.com/svn/trunk/markerclusterer/images/m" + i + ".png",
      'height': sizes[i - 1],
      'width': sizes[i - 1]
    });
  }

  if (typeof opt_opts === "object" && opt_opts !== null) {
    if (typeof opt_opts.gridSize === "number" && opt_opts.gridSize > 0) {
      gridSize_ = opt_opts.gridSize;
    }
    if (typeof opt_opts.maxZoom === "number") {
      maxZoom_ = opt_opts.maxZoom;
    }
    if (typeof opt_opts.styles === "object" && opt_opts.styles !== null && opt_opts.styles.length !== 0) {
      styles_ = opt_opts.styles;
    }
  }

  /**
   * When we add a marker, the marker may not in the viewport of map, then we don't deal with it, instead
   * we add the marker into a array called leftMarkers_. When we reset MarkerClusterer we should add the
   * leftMarkers_ into MarkerClusterer.
   */
  function addLeftMarkers_() {
    if (leftMarkers_.length === 0) {
      return;
    }
    var leftMarkers = [];
    for (i = 0; i < leftMarkers_.length; ++i) {
      me_.addMarker(leftMarkers_[i], true, null, null, true);
    }
    leftMarkers_ = leftMarkers;
  }

  /**
   * Get cluster marker images of this marker cluster. Mostly used by {@link Cluster}
   * @private
   * @return {Array of String}
   */
  this.getStyles_ = function () {
    return styles_;
  };

  /**
   * Remove all markers from MarkerClusterer.
   */
  this.clearMarkers = function () {
    for (var i = 0; i < clusters_.length; ++i) {
      if (typeof clusters_[i] !== "undefined" && clusters_[i] !== null) {
        clusters_[i].clearMarkers();
      }
    }
    clusters_ = [];
    leftMarkers_ = [];
    GEvent.removeListener(mcfn_);
  };

  /**
   * Check a marker, whether it is in current map viewport.
   * @private
   * @return {Boolean} if it is in current map viewport
   */
  function isMarkerInViewport_(marker) {
    return map_.getBounds().containsLatLng(marker.getLatLng());
  }

  /**
   * When reset MarkerClusterer, there will be some markers get out of its cluster.
   * These markers should be add to new clusters.
   * @param {Array of GMarker} markers Markers to add.
   */
  function reAddMarkers_(markers) {
    var len = markers.length;
    var clusters = [];
    for (var i = len - 1; i >= 0; --i) {
      me_.addMarker(markers[i].marker, true, markers[i].isAdded, clusters, true);
    }
    addLeftMarkers_();
  }

  /**
   * Add a marker.
   * @private
   * @param {GMarker} marker Marker you want to add
   * @param {Boolean} opt_isNodraw Whether redraw the cluster contained the marker
   * @param {Boolean} opt_isAdded Whether the marker is added to map. Never use it.
   * @param {Array of Cluster} opt_clusters Provide a list of clusters, the marker
   *     cluster will only check these cluster where the marker should join.
   */
  this.addMarker = function (marker, opt_isNodraw, opt_isAdded, opt_clusters, opt_isNoCheck) {
    if (opt_isNoCheck !== true) {
      if (!isMarkerInViewport_(marker)) {
        leftMarkers_.push(marker);
        return;
      }
    }

    var isAdded = opt_isAdded;
    var clusters = opt_clusters;
    var pos = map_.fromLatLngToDivPixel(marker.getLatLng());

    if (typeof isAdded !== "boolean") {
      isAdded = false;
    }
    if (typeof clusters !== "object" || clusters === null) {
      clusters = clusters_;
    }

    var length = clusters.length;
    var cluster = null;
    for (var i = length - 1; i >= 0; i--) {
      cluster = clusters[i];
      var center = cluster.getCenter();
      if (center === null) {
        continue;
      }
      center = map_.fromLatLngToDivPixel(center);

      // Found a cluster which contains the marker.
      if (pos.x >= center.x - gridSize_ && pos.x <= center.x + gridSize_ &&
          pos.y >= center.y - gridSize_ && pos.y <= center.y + gridSize_) {
        cluster.addMarker({
          'isAdded': isAdded,
          'marker': marker
        });
        if (!opt_isNodraw) {
          cluster.redraw_();
        }
        return;
      }
    }

    // No cluster contain the marker, create a new cluster.
    cluster = new Cluster(this, map);
    cluster.addMarker({
      'isAdded': isAdded,
      'marker': marker
    });
    if (!opt_isNodraw) {
      cluster.redraw_();
    }

    // Add this cluster both in clusters provided and clusters_
    clusters.push(cluster);
    if (clusters !== clusters_) {
      clusters_.push(cluster);
    }
  };

  /**
   * Remove a marker.
   *
   * @param {GMarker} marker The marker you want to remove.
   */

  this.removeMarker = function (marker) {
    for (var i = 0; i < clusters_.length; ++i) {
      if (clusters_[i].remove(marker)) {
        clusters_[i].redraw_();
        return;
      }
    }
  };

  /**
   * Redraw all clusters in viewport.
   */
  this.redraw_ = function () {
    var clusters = this.getClustersInViewport_();
    for (var i = 0; i < clusters.length; ++i) {
      clusters[i].redraw_(true);
    }
  };

  /**
   * Get all clusters in viewport.
   * @return {Array of Cluster}
   */
  this.getClustersInViewport_ = function () {
    var clusters = [];
    var curBounds = map_.getBounds();
    for (var i = 0; i < clusters_.length; i ++) {
      if (clusters_[i].isInBounds(curBounds)) {
        clusters.push(clusters_[i]);
      }
    }
    return clusters;
  };

  /**
   * Get max zoom level.
   * @private
   * @return {Number}
   */
  this.getMaxZoom_ = function () {
    return maxZoom_;
  };

  /**
   * Get map object.
   * @private
   * @return {GMap2}
   */
  this.getMap_ = function () {
    return map_;
  };

  /**
   * Get grid size
   * @private
   * @return {Number}
   */
  this.getGridSize_ = function () {
    return gridSize_;
  };

  /**
   * Get total number of markers.
   * @return {Number}
   */
  this.getTotalMarkers = function () {
    var result = 0;
    for (var i = 0; i < clusters_.length; ++i) {
      result += clusters_[i].getTotalMarkers();
    }
    return result;
  };

  /**
   * Get total number of clusters.
   * @return {int}
   */
  this.getTotalClusters = function () {
    return clusters_.length;
  };

  /**
   * Collect all markers of clusters in viewport and regroup them.
   */
  this.resetViewport = function () {
    var clusters = this.getClustersInViewport_();
    var tmpMarkers = [];
    var removed = 0;

    for (var i = 0; i < clusters.length; ++i) {
      var cluster = clusters[i];
      var oldZoom = cluster.getCurrentZoom();
      if (oldZoom === null) {
        continue;
      }
      var curZoom = map_.getZoom();
      if (curZoom !== oldZoom) {

        // If the cluster zoom level changed then destroy the cluster
        // and collect its markers.
        var mks = cluster.getMarkers();
        for (var j = 0; j < mks.length; ++j) {
          var newMarker = {
            'isAdded': false,
            'marker': mks[j].marker
          };
          tmpMarkers.push(newMarker);
        }
        cluster.clearMarkers();
        removed++;
        for (j = 0; j < clusters_.length; ++j) {
          if (cluster === clusters_[j]) {
            clusters_.splice(j, 1);
          }
        }
      }
    }

    // Add the markers collected into marker cluster to reset
    reAddMarkers_(tmpMarkers);
    this.redraw_();
  };


  /**
   * Add a set of markers.
   *
   * @param {Array of GMarker} markers The markers you want to add.
   */
  this.addMarkers = function (markers) {
    for (var i = 0; i < markers.length; ++i) {
      this.addMarker(markers[i], true);
    }
    this.redraw_();
  };

  // initialize
  if (typeof opt_markers === "object" && opt_markers !== null) {
    this.addMarkers(opt_markers);
  }

  // when map move end, regroup.
  mcfn_ = GEvent.addListener(map_, "moveend", function () {
    me_.resetViewport();
  });
}

/**
 * Create a cluster to collect markers.
 * A cluster includes some markers which are in a block of area.
 * If there are more than one markers in cluster, the cluster
 * will create a {@link ClusterMarker_} and show the total number
 * of markers in cluster.
 *
 * @constructor
 * @private
 * @param {MarkerClusterer} markerClusterer The marker cluster object
 */
function Cluster(markerClusterer) {
  var center_ = null;
  var markers_ = [];
  var markerClusterer_ = markerClusterer;
  var map_ = markerClusterer.getMap_();
  var clusterMarker_ = null;
  var zoom_ = map_.getZoom();

  /**
   * Get markers of this cluster.
   *
   * @return {Array of GMarker}
   */
  this.getMarkers = function () {
    return markers_;
  };

  /**
   * If this cluster intersects certain bounds.
   *
   * @param {GLatLngBounds} bounds A bounds to test
   * @return {Boolean} Is this cluster intersects the bounds
   */
  this.isInBounds = function (bounds) {
    if (center_ === null) {
      return false;
    }

    if (!bounds) {
      bounds = map_.getBounds();
    }
    var sw = map_.fromLatLngToDivPixel(bounds.getSouthWest());
    var ne = map_.fromLatLngToDivPixel(bounds.getNorthEast());

    var centerxy = map_.fromLatLngToDivPixel(center_);
    var inViewport = true;
    var gridSize = markerClusterer.getGridSize_();
    if (zoom_ !== map_.getZoom()) {
      var dl = map_.getZoom() - zoom_;
      gridSize = Math.pow(2, dl) * gridSize;
    }
    if (ne.x !== sw.x && (centerxy.x + gridSize < sw.x || centerxy.x - gridSize > ne.x)) {
      inViewport = false;
    }
    if (inViewport && (centerxy.y + gridSize < ne.y || centerxy.y - gridSize > sw.y)) {
      inViewport = false;
    }
    return inViewport;
  };

  /**
   * Get cluster center.
   *
   * @return {GLatLng}
   */
  this.getCenter = function () {
    return center_;
  };

  /**
   * Add a marker.
   *
   * @param {Object} marker An object of marker you want to add:
   *   {Boolean} isAdded If the marker is added on map.
   *   {GMarker} marker The marker you want to add.
   */
  this.addMarker = function (marker) {
    if (center_ === null) {
      /*var pos = marker['marker'].getLatLng();
       pos = map.fromLatLngToContainerPixel(pos);
       pos.x = parseInt(pos.x - pos.x % (GRIDWIDTH * 2) + GRIDWIDTH);
       pos.y = parseInt(pos.y - pos.y % (GRIDWIDTH * 2) + GRIDWIDTH);
       center = map.fromContainerPixelToLatLng(pos);*/
      center_ = marker.marker.getLatLng();
    }
    markers_.push(marker);
  };

  /**
   * Remove a marker from cluster.
   *
   * @param {GMarker} marker The marker you want to remove.
   * @return {Boolean} Whether find the marker to be removed.
   */
  this.removeMarker = function (marker) {
    for (var i = 0; i < markers_.length; ++i) {
      if (marker === markers_[i].marker) {
        if (markers_[i].isAdded) {
          map_.removeOverlay(markers_[i].marker);
        }
        markers_.splice(i, 1);
        return true;
      }
    }
    return false;
  };

  /**
   * Get current zoom level of this cluster.
   * Note: the cluster zoom level and map zoom level not always the same.
   *
   * @return {Number}
   */
  this.getCurrentZoom = function () {
    return zoom_;
  };

  /**
   * Redraw a cluster.
   * @private
   * @param {Boolean} isForce If redraw by force, no matter if the cluster is
   *     in viewport.
   */
  this.redraw_ = function (isForce) {
    if (!isForce && !this.isInBounds()) {
      return;
    }

    // Set cluster zoom level.
    zoom_ = map_.getZoom();
    var i = 0;
    var mz = markerClusterer.getMaxZoom_();
    if (mz === null) {
      mz = map_.getCurrentMapType().getMaximumResolution();
    }
    if (zoom_ >= mz || this.getTotalMarkers() === 1) {

      // If current zoom level is beyond the max zoom level or the cluster
      // have only one marker, the marker(s) in cluster will be showed on map.
      for (i = 0; i < markers_.length; ++i) {
        if (markers_[i].isAdded) {
          if (markers_[i].marker.isHidden()) {
            markers_[i].marker.show();
          }
        } else {
          map_.addOverlay(markers_[i].marker);
          markers_[i].isAdded = true;
        }
      }
      if (clusterMarker_ !== null) {
        clusterMarker_.hide();
      }
    } else {
      // Else add a cluster marker on map to show the number of markers in
      // this cluster.
      for (i = 0; i < markers_.length; ++i) {
        if (markers_[i].isAdded && (!markers_[i].marker.isHidden())) {
          markers_[i].marker.hide();
        }
      }
      if (clusterMarker_ === null) {
        clusterMarker_ = new ClusterMarker_(center_, this.getTotalMarkers(), markerClusterer_.getStyles_(), markerClusterer_.getGridSize_());
        map_.addOverlay(clusterMarker_);
      } else {
        if (clusterMarker_.isHidden()) {
          clusterMarker_.show();
        }
        clusterMarker_.redraw(true);
      }
    }
  };

  /**
   * Remove all the markers from this cluster.
   */
  this.clearMarkers = function () {
    if (clusterMarker_ !== null) {
      map_.removeOverlay(clusterMarker_);
    }
    for (var i = 0; i < markers_.length; ++i) {
      if (markers_[i].isAdded) {
        map_.removeOverlay(markers_[i].marker);
      }
    }
    markers_ = [];
  };

  /**
   * Get number of markers.
   * @return {Number}
   */
  this.getTotalMarkers = function () {
    return markers_.length;
  };
}

/**
 * ClusterMarker_ creates a marker that shows the number of markers that
 * a cluster contains.
 *
 * @constructor
 * @private
 * @param {GLatLng} latlng Marker's lat and lng.
 * @param {Number} count Number to show.
 * @param {Array of Object} styles The image list to be showed:
 *   {String} url Image url.
 *   {Number} height Image height.
 *   {Number} width Image width.
 *   {Array of Number} anchor Text anchor of image left and top.
 *   {String} textColor text color.
 * @param {Number} padding Padding of marker center.
 */
function ClusterMarker_(latlng, count, styles, padding) {
  var index = 0;
  var dv = count;
  while (dv !== 0) {
    dv = parseInt(dv / 10, 10);
    index ++;
  }

  if (styles.length < index) {
    index = styles.length;
  }
  this.url_ = styles[index - 1].url;
  this.height_ = styles[index - 1].height;
  this.width_ = styles[index - 1].width;
  this.textColor_ = styles[index - 1].opt_textColor;
  this.anchor_ = styles[index - 1].opt_anchor;
  this.latlng_ = latlng;
  this.index_ = index;
  this.styles_ = styles;
  this.text_ = count;
  this.padding_ = padding;
}

ClusterMarker_.prototype = new GOverlay();

/**
 * Initialize cluster marker.
 * @private
 */
ClusterMarker_.prototype.initialize = function (map) {
  this.map_ = map;
  var div = document.createElement("div");
  var latlng = this.latlng_;
  var pos = map.fromLatLngToDivPixel(latlng);
  pos.x -= parseInt(this.width_ / 2, 10);
  pos.y -= parseInt(this.height_ / 2, 10);
  var mstyle = "";
  if (document.all) {
    mstyle = 'filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src="' + this.url_ + '");';
  } else {
    mstyle = "background:url(" + this.url_ + ");";
  }
  if (typeof this.anchor_ === "object") {
    if (typeof this.anchor_[0] === "number" && this.anchor_[0] > 0 && this.anchor_[0] < this.height_) {
      mstyle += 'height:' + (this.height_ - this.anchor_[0]) + 'px;padding-top:' + this.anchor_[0] + 'px;';
    } else {
      mstyle += 'height:' + this.height_ + 'px;line-height:' + this.height_ + 'px;';
    }
    if (typeof this.anchor_[1] === "number" && this.anchor_[1] > 0 && this.anchor_[1] < this.width_) {
      mstyle += 'width:' + (this.width_ - this.anchor_[1]) + 'px;padding-left:' + this.anchor_[1] + 'px;';
    } else {
      mstyle += 'width:' + this.width_ + 'px;text-align:center;';
    }
  } else {
    mstyle += 'height:' + this.height_ + 'px;line-height:' + this.height_ + 'px;';
    mstyle += 'width:' + this.width_ + 'px;text-align:center;';
  }
  var txtColor = this.textColor_ ? this.textColor_ : 'black';

  div.style.cssText = mstyle + 'cursor:pointer;top:' + pos.y + "px;left:" +
      pos.x + "px;color:" + txtColor +  ";position:absolute;font-size:11px;" +
      'font-family:Arial,sans-serif;font-weight:bold';
  div.innerHTML = this.text_;
  map.getPane(G_MAP_MAP_PANE).appendChild(div);
  var padding = this.padding_;
  GEvent.addDomListener(div, "click", function () {
    var pos = map.fromLatLngToDivPixel(latlng);
    var sw = new GPoint(pos.x - padding, pos.y + padding);
    sw = map.fromDivPixelToLatLng(sw);
    var ne = new GPoint(pos.x + padding, pos.y - padding);
    ne = map.fromDivPixelToLatLng(ne);
    var zoom = map.getBoundsZoomLevel(new GLatLngBounds(sw, ne), map.getSize());
    map.setCenter(latlng, zoom);
  });
  this.div_ = div;
};

/**
 * Remove this overlay.
 * @private
 */
ClusterMarker_.prototype.remove = function () {
  this.div_.parentNode.removeChild(this.div_);
};

/**
 * Copy this overlay.
 * @private
 */
ClusterMarker_.prototype.copy = function () {
  return new ClusterMarker_(this.latlng_, this.index_, this.text_, this.styles_, this.padding_);
};

/**
 * Redraw this overlay.
 * @private
 */
ClusterMarker_.prototype.redraw = function (force) {
  if (!force) {
    return;
  }
  var pos = this.map_.fromLatLngToDivPixel(this.latlng_);
  pos.x -= parseInt(this.width_ / 2, 10);
  pos.y -= parseInt(this.height_ / 2, 10);
  this.div_.style.top =  pos.y + "px";
  this.div_.style.left = pos.x + "px";
};

/**
 * Hide this cluster marker.
 */
ClusterMarker_.prototype.hide = function () {
  this.div_.style.display = "none";
};

/**
 * Show this cluster marker.
 */
ClusterMarker_.prototype.show = function () {
  this.div_.style.display = "";
};

/**
 * Get whether the cluster marker is hidden.
 * @return {Boolean}
 */
ClusterMarker_.prototype.isHidden = function () {
  return this.div_.style.display === "none";
};
