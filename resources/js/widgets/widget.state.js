
	if (!window['widgets']) {window['widgets'] = {};}
	widgets.state = function(elem){
		if (elem.getAttribute('data-widget')) {return false;}
		elem.setAttribute('data-widget',true);

		this._container = elem;

		_widgets.register('state','.widget-state:not([data-widget])');
	};
	widgets.state.prototype.update = function(){
		
	};
	widgets.state.prototype.toState = function(){

	};

	
	addEventListener('load',function(e){
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('.widget-state'));
		nodeList.forEach(function(v,k){new widgets.scroll(v);});

		var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
		if( MutationObserver ){
			// define a new observer
			var obs = new MutationObserver(function(mutations, observer){
				if( mutations[0].addedNodes.length/* || mutations[0].removedNodes.length*/ ){
					var nodeList = Array.prototype.slice.call(document.querySelectorAll('.widget-state:not([data-widget])'));
					if( nodeList.length ){
						nodeList.forEach(function(v,k){new widgets.state(v);});
					}
				}
			});
			// have the observer observe foo for changes in children
			obs.observe( document.body, { childList:true, subtree:true });
		}
	});


