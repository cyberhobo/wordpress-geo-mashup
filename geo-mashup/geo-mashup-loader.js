var GeoMashupLoader = {
	addMapFrame : function (element, frame_url, height, width) {
		element.style.backgroundImage = 'none';
		var html = ['<iframe src="'];
		html.push(frame_url);
		html.push('" height="');
		html.push(height);
		html.push('" width="');
		html.push(width);
		html.push('" marginheight="0" marginwidth="0" frameborder="0" scrolling="no"></iframe>');
		element.innerHTML = html.join('');
	}
};

