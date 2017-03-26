
	if(!window['widgets']){window['widgets'] = {};}
	widgets.list = function(h,callback){
		if( h.getAttribute('data-list') ){return false;}
		h.setAttribute('data-list',true);
		var ths = this;

		this.panel = h;
		this.panel.classList.add('widget-list');
		this.height = this.panel.getAttribute('data-height');

		if( this.height && this.height < this.panel.offsetHeight ){
			this.panel.style.height = this.height+'px';
			this.panel.style.overflow = 'hidden';
		}

		var items = Array.prototype.slice.call(this.panel.querySelectorAll('.item'));
		items.forEach(function(item,k){
			if( (input = item.querySelector('input[type="radio"]')) && input.checked ){
				item.classList.add('checked');
			}

			item.addEventListener('click',function(e){
				var input = this.querySelector('input');
				input.checked = 'checked';

				items.forEach(function(item,k){
					item.classList.remove('checked');
				});
				this.classList.add('checked');
			});
		});

		this.panel.addEventListener('mousedown',function(e){ths.mousedown(e);});
		this.panel.addEventListener('click',function(e){
			e.preventDefault();
			e.stopPropagation();
		});
	};
	widgets.list.prototype.mousedown = function(e){
		e.preventDefault();
		e.stopPropagation();
		var ths = this;
		this.panel.startX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		this.panel.startY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
		this.panel.startScrollY = this.panel.scrollTop;

		//elem.startScrollX = elem.parentNode.scrollLeft;
		//elem.viewpW = holder.offsetWidth;
		//elem.startW = elem.getAttribute('width');

		if( !('mouseMoveHandler' in this.panel) ){
			this.panel.handlerMouseMove = function(e){ths.mousemove(e);}
			this.panel.handlerMouseUp   = function(e){ths.mouseup(e);}
		}

		removeEventListener('mouseup',  this.panel.handlerMouseUp,true);
		removeEventListener('touchmove',this.panel.handlerMouseMove,true);
		removeEventListener('touchend', this.panel.handlerMouseUp,true);
		removeEventListener('touchstop',this.panel.handlerMouseUp,true);
	};

	function widgets_list_init(e){
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('.widget-list'));
		nodeList.forEach(function(v,k){new widgets.list(v);});

		var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
		if( MutationObserver ){
			// define a new observer
			var obs = new MutationObserver(function(mutations, observer){
				if( mutations[0].addedNodes.length/* || mutations[0].removedNodes.length*/ ){
					var nodeList = Array.prototype.slice.call(document.querySelectorAll('.widget-list:not([data-widget])'));
					if( nodeList.length ){
						nodeList.forEach(function(v,k){new widgets.list(v);});
					}
				}
			});
			// have the observer observe foo for changes in children
			obs.observe( document.body, { childList:true, subtree:true });
		}
	};
	
	addEventListener('DOMContentLoaded',widgets_list_init);
	if (document.readyState === 'complete' || document.readyState === 'loaded' || document.readyState === 'interactive') {
		widgets_list_init();
	}

