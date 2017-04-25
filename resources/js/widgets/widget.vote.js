
	if(!window['widgets']){window['widgets'] = {};}
	widgets.vote = function(h,callback){
		if( h.getAttribute('data-widget') ){return false;}
		h.setAttribute('data-widget',true);
		var ths = this;

		this.input  = h;
		this.input.setAttribute('type','hidden');
		this.widget = document.createElement('DIV');
		this.widget.classList.add('widget-vote');

		this.input.parentNode.insertBefore(this.widget,this.input);
		this.widget.appendChild(this.input);

		this.progress = document.createElement('PROGRESS');
		this.range = document.createElement('INPUT');
		this.range.setAttribute('type','range');
		this.range.setAttribute('max',100);

		this.progress.setAttribute('max',this.range.max);
		this.widget.appendChild(this.progress);
		this.widget.appendChild(this.range);

		this.progress.value = this.range.value;
		this.input.value = this.range.value;

		this.range.addEventListener('input',function(e){
			ths.progress.value = this.value;
			ths.input.value = this.value;
		});
	};

	
	function widgets_vote_init(e){
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('input[type="widget-vote"]'));
		nodeList.forEach(function(v,k){new widgets.vote(v);});

		var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
		if( MutationObserver ){
			// define a new observer
			var obs = new MutationObserver(function(mutations, observer){
				if( mutations[0].addedNodes.length/* || mutations[0].removedNodes.length*/ ){
					var nodeList = Array.prototype.slice.call(document.querySelectorAll('input[type="widget-vote"]:not([data-widget])'));
					if( nodeList.length ){
						nodeList.forEach(function(v,k){new widgets.vote(v);});
					}
				}
			});
			// have the observer observe foo for changes in children
			obs.observe( document.body, { childList:true, subtree:true });
		}
	};
	
	addEventListener('DOMContentLoaded',widgets_vote_init);
	if (document.readyState === 'complete' || document.readyState === 'loaded' || document.readyState === 'interactive') {
		widgets_vote_init();
	}
