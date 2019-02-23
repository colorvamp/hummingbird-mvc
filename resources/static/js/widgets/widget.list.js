
	if( !window['widgets'] ){window['widgets'] = {};}
	widgets.list = function(h,callback){
		if( h.getAttribute('data-list') ){return false;}
		h.setAttribute('data-list',true);
		var ths = this;

		this.panel = h;
		this.panel.classList.add('widget-list');
		this.height = this.panel.getAttribute('data-height');

		if( this.height && this.height < this.panel.offsetHeight ){
			this.panel.style.height = this.height + 'px';
			this.panel.style.overflow = 'hidden';
		}

		/* Decide what kind of list is */
		var hasRadio    = !!this.panel.querySelector('input[type="radio"]');
		var hasCheckbox = !!this.panel.querySelector('input[type="checkbox"]');
		var listType    = ( hasRadio && !hasCheckbox ) ? 'radio' : 'checkbox';

		var input = false;
		var value = h.getAttribute('data-value');
		var items = Array.prototype.slice.call(this.panel.querySelectorAll('.item'));
		items.forEach(function(item,k){
			input = item.querySelector('input[type="radio"],input[type="checkbox"]');
			if( !value && input.checked ){
				item.classList.add('checked');
			}else if( value && value == input.value ){
				input.checked = 'checked';
				item.classList.add('checked');
			}

			item.addEventListener('click',function(e){
				var input = this.querySelector('input');
				if( listType == 'radio' ){
					input.checked = 'checked';
					items.forEach(function(item,k){
						item.classList.remove('checked');
					});
					this.classList.add('checked');
					return false;
				}

				if( listType == 'checkbox' ){
					if( this.classList.contains('checked') ){
						input.checked = '';
						this.classList.remove('checked');
					}else{
						input.checked = 'checked';
						this.classList.add('checked');
					}
				}
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

		if( !('handlerMouseMove' in this.panel) ){
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
