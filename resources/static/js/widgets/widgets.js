
	if (!('widgets' in window)) {
		window['widgets'] = {};
	}
	_widgets = function(){
		this.selectors = {};

		addEventListener('DOMContentLoaded',function(){
			this._observer = new window.MutationObserver(function(mutations){
				mutations.forEach(function(m,k){
					if (!m.addedNodes.length) {return false;}
					this.update(m.target);
				}.bind(this));
			}.bind(this));
			this._observer.observe(document.body, { childList:true, subtree:true, attributes:false});
			
			this.update(document.body);
		}.bind(this));
		if (document.readyState === 'complete' || document.readyState === 'loaded' || document.readyState === 'interactive') {
			this.update(document.body);
		}
	};
	_widgets.prototype.update = function(target){
		for (widget in this.selectors) {
			var _nodes = Array.prototype.slice.call(target.querySelectorAll(this.selectors[widget]));
			_nodes.forEach(function(v, k) {new widgets[widget](v);});
		}
	};
	_widgets.prototype.register = function(name,selector){
		this.selectors[name] = selector;
		/* First instance */
		if (document.body) {
			this.update(document.body);
		}
	};
	window._widgets = new _widgets();
