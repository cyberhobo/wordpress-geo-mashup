// Add a post 0.7.1 fix needed by the location editor

L.DomEvent.getMousePosition = function (e, container) {
	if (!container) {
		return new L.Point(e.clientX, e.clientY);
	}

	var rect = container.getBoundingClientRect();

	return new L.Point(
		e.clientX - rect.left - container.clientLeft,
		e.clientY - rect.top - container.clientTop);
};