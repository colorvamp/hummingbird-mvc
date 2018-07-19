
	if (!('widgets' in window)) {
		window['widgets'] = {};
	}
	_widgets = function(){
		this.selectors = {};

		this._observer = new window.MutationObserver(function(mutations){
			mutations.forEach(function(m,k){
				if (!m.addedNodes.length) {return false;}
				for (widget in this.selectors) {
					var _nodes = Array.prototype.slice.call(m.target.querySelectorAll(this.selectors[widget]));
					_nodes.forEach(function(v, k) {new widgets[widget](v);});
				}
			}.bind(this));
		}.bind(this));
		this._observer.observe(document.body, { childList:true, subtree:true, attributes:false});
	};
	_widgets.prototype.register = function(name,selector){
		this.selectors[name] = selector;
	};
	window._widgets = new _widgets();

