
	if (!('widgets' in window)) {
		window['widgets'] = {};
	}
	_widgets = function(){
		this.selectors = {};

		this._observer = new window.MutationObserver(function(mutations){
			mutations.forEach(function(m,k){
				if (!m.addedNodes.length) {return false;}
				this.update();
			}.bind(this));
		}.bind(this));
		this._observer.observe(document.body, { childList:true, subtree:true, attributes:false});

		addEventListener('DOMContentLoaded',function(){this.update();}.bind(this));
		if (document.readyState === 'complete' || document.readyState === 'loaded' || document.readyState === 'interactive') {
			this.update();
		}
	};
	_widgets.prototype.update = function(){
		for (widget in this.selectors) {
			var _nodes = Array.prototype.slice.call(m.target.querySelectorAll(this.selectors[widget]));
			_nodes.forEach(function(v, k) {new widgets[widget](v);});
		}
	};
	_widgets.prototype.register = function(name,selector){
		this.selectors[name] = selector;
	};
	window._widgets = new _widgets();

