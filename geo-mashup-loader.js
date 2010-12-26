/*global GeoMashupLoader */
var GeoMashupLoader;

/**
 * Geo Mashup Loader object.
 *
 * Currently implements click to load feature.
 */
GeoMashupLoader = {
	addMapFrame : function (element, frame_url, height, width, name) {
		var html = ['<iframe name="'];
		element.style.backgroundImage = 'none';
		html.push(name);
		html.push('" src="');
		html.push(frame_url);
		html.push('" height="');
		html.push(height);
		html.push('" width="');
		html.push(width);
		html.push('" marginheight="0" marginwidth="0" frameborder="0" scrolling="no"></iframe>');
		element.innerHTML = html.join('');
	}
};

