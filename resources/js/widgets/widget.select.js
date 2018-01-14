
	if ( !window['widgets'] ) { window['widgets'] = {}; }
	widgets.select = function( h ) {
		if ( h.getAttribute('data-widget') ) { return false; }
		h.setAttribute('data-widget',true);

		if( (value = h.getAttribute('value')) ){
			h.value = value;
		}
	};

	addEventListener('load', function( e ) {
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('select'));
		nodeList.forEach(function(v, k) {
			new widgets.select(v);
		});
	});
