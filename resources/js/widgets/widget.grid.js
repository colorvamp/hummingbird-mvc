
	if (!window['widgets']) {window['widgets'] = {};}
	widgets.grid = function(h){
		if (h.getAttribute('data-widget')) {return false;}
		h.setAttribute('data-widget',true);

		this._grid = h;
		this.update();

		window.addEventListener('resize',function(){
			this.update();
		}.bind(this),{
			passive: true
		});
	};
	widgets.grid.prototype.update = function(){
		if (this._grid.offsetWidth >= 800) {
			this._grid.classList.remove('grid-is-tablet');
		}
		if (this._grid.offsetWidth < 800) {
			this._grid.classList.add('grid-is-tablet');
		}
		if (this._grid.offsetWidth >= 500) {
			this._grid.classList.remove('grid-is-phone');
		}
		if (this._grid.offsetWidth < 500) {
			this._grid.classList.add('grid-is-phone');
		}
	};

	addEventListener('load', function( e ) {
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('.grid'));
		nodeList.forEach(function(v, k) {
			new widgets.grid(v);
		});
	});

