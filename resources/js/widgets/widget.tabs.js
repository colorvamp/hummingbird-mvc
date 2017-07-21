
	if( !window['widgets'] ){window['widgets'] = {};}
	widgets.tabs = function(elem){
		if( elem.getAttribute('data-widget') ){return false;}
		elem.setAttribute('data-widget',true);

		var ths     = this;
		this.panel  = elem;
		this.panel.style.overflow = 'hidden';
		this.widget = document.createElement('DIV');
		this.widget.classList.add('widget-tabs');
		this.panel.parentNode.insertBefore(this.widget,this.panel.nextSibling);

		this.content = document.createElement('DIV');
		this.content.classList.add('tabs-content');
		this.tabs = document.createElement('DIV');
		this.tabs.classList.add('tabs-holder');
		var scrollNext = document.createElement('DIV');
		scrollNext.classList.add('next');
		var scrollPrev = document.createElement('DIV');
		scrollPrev.classList.add('prev');
		this.tabs.appendChild(scrollNext);
		this.tabs.appendChild(this.panel);
		this.tabs.appendChild(scrollPrev);

		this.widget.appendChild(this.tabs);
		this.widget.appendChild(this.content);

		var viewPort = this.widget.offsetWidth;
		var tabs = elem.querySelectorAll('li.tab');
		var tabDefault = elem.querySelectorAll('li.tab.active');
		if( !tabDefault.length && tabs ){tabs[0].classList.add('active');}

		var hasContent = false;
		Array.prototype.slice.call(tabs).forEach(function(tab,k){
			var label = tab.querySelector('.label');
			var left  = label ? label.offsetLeft : tab.offsetLeft;
			var width = label ? label.offsetWidth : tab.offsetWidth;
			var isChecked = tab.classList.contains('active');

			/* Si la tab está escondida por el scroll */
			if( isChecked && left+width > viewPort ){
				ths.panel.scrollLeft = (left+width)-viewPort;
			}

			var content = tab.querySelector('.content');
			if( content ){
				if( isChecked ){content.classList.add('active');}
				hasContent = true;
				ths.content.appendChild(content);
				tab.addEventListener('click',function(e){
					x = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
					var change = Math.abs(ths.panel.startX-x);
					if( change > 20 ){return false;}

					Array.prototype.slice.call(ths.content.children).forEach(function(v,k){
						v.classList.remove('active');
					});
					Array.prototype.slice.call(this.parentNode.children).forEach(function(v,k){
						v.classList.remove('active');
					});

					this.classList.add('active');
					content.classList.add('active');
				});
			}

			tab.addEventListener('mousedown',function(e){
				//e.preventDefault();
				//e.stopPropagation();
			});
		});

		scrollNext.addEventListener('click',function(e){
			ths.panel.scrollLeft -= 20;
		});
		scrollPrev.addEventListener('click',function(e){
			ths.panel.scrollLeft += 20;
		});

		this.panel.addEventListener('mousedown',function(e){ths.mousedown(e);},true);
		this.panel.addEventListener('touchstart',function(e){ths.mousedown(e);},true);
		this.panel.addEventListener('click',function(e){
			if( hasContent ){
				e.preventDefault();
				e.stopPropagation();
			}
			if( ths.panel.shouldPreventClick ){
				ths.panel.shouldPreventClick = false;
				e.preventDefault();
				e.stopPropagation();
			}
		});
	};
	widgets.tabs.prototype.mousedown = function(e){
		if ( e.button === 1 ) {return false;}

		e.preventDefault();
		e.stopPropagation();
		var ths = this;
		this.panel.startX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		this.panel.startY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
		this.panel.startScrollX = this.panel.scrollLeft;

		//elem.startScrollX = elem.parentNode.scrollLeft;
		//elem.viewpW = holder.offsetWidth;
		//elem.startW = elem.getAttribute('width');

		if( !('mouseMoveHandler' in this.panel) ){
			this.panel.handlerMouseMove = function(e){ths.mousemove(e);}
			this.panel.handlerMouseUp   = function(e){ths.mouseup(e);}
		}

		addEventListener('mousemove',this.panel.handlerMouseMove,false);
		addEventListener('mouseup',  this.panel.handlerMouseUp,false);
		addEventListener('touchmove',this.panel.handlerMouseMove,false);
		addEventListener('touchend', this.panel.handlerMouseUp,false);
		addEventListener('touchstop',this.panel.handlerMouseUp,false);
	};
	widgets.tabs.prototype.mousemove = function(e){
		//e.preventDefault();
		e.stopPropagation();

		x = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		//elem.scrollMax = elem.startW-elem.viewpW;

		this.panel.scrollX = this.panel.startScrollX + this.panel.startX - x;
		if( this.panel.scrollX < 0 ){this.panel.scrollX = 0;}
		//if( elem.scrollX > elem.scrollMax ){elem.scrollX = elem.scrollMax;}
		this.panel.scrollLeft = this.panel.scrollX;
	};
	widgets.tabs.prototype.mouseup = function(e){
		var currentX  = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		var absoluteX = Math.abs(this.panel.startX - currentX);
		if( absoluteX > 10 ){
			this.panel.shouldPreventClick = true;
		}

		e.preventDefault();
		e.stopPropagation();
		removeEventListener('mousemove',this.panel.handlerMouseMove,false);
		removeEventListener('mouseup',  this.panel.handlerMouseUp,false);
		removeEventListener('touchmove',this.panel.handlerMouseMove,false);
		removeEventListener('touchend', this.panel.handlerMouseUp,false);
		removeEventListener('touchstop',this.panel.handlerMouseUp,false);
	};

	function widgets_tabs_init(e){
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('ul.widget-tabs'));
		nodeList.forEach(function(v,k){new widgets.tabs(v);});
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('ul.tabs'));
		nodeList.forEach(function(v,k){new widgets.tabs(v);});

		var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
		if( MutationObserver ){
			// define a new observer
			var obs = new MutationObserver(function(mutations, observer){
				if( mutations[0].addedNodes.length/* || mutations[0].removedNodes.length*/ ){
					var nodeList = Array.prototype.slice.call(document.querySelectorAll('ul.widget-tabs:not([data-widget])'));
					if( nodeList.length ){
						nodeList.forEach(function(v,k){new widgets.tabs(v);});
					}
				}
			});
			// have the observer observe foo for changes in children
			obs.observe( document.body, { childList:true, subtree:true });
		}
	};
	
	addEventListener('DOMContentLoaded',widgets_tabs_init);
	if (document.readyState === 'complete' || document.readyState === 'loaded' || document.readyState === 'interactive') {
		widgets_tabs_init();
	}

