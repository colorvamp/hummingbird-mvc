
	if( !window['widgets'] ){window['widgets'] = {};}
	widgets.tabs = function(elem){
		if( elem.getAttribute('data-widget') ){return false;}
		elem.setAttribute('data-widget',true);

		var ths     = this;
		this.panel  = elem;
		this._container = document.createElement('DIV');
		this._container.classList.add('widget-tabs');
		this._container.classList.add('widget-update');
		this.panel.parentNode.insertBefore(this._container,this.panel.nextSibling);
		this.panel.style.maxWidth = 'none';
		this.panel.scrollX = 0;

		this.content = document.createElement('DIV');
		this.content.classList.add('tabs-content');

		this.tabs = document.createElement('DIV');
		this.tabs.classList.add('tabs-holder');
		this.tabs.style.overflow = 'hidden';

		this._scroll_next = document.createElement('DIV');
		this._scroll_next.classList.add('next');
		this._scroll_next.style.display = 'none';
		this._scroll_prev = document.createElement('DIV');
		this._scroll_prev.classList.add('prev');
		this._scroll_prev.style.display = 'none';

		this.tabs.appendChild(this.panel);
		this._container.appendChild(this._scroll_next);
		this._container.appendChild(this._scroll_prev);
		this._container.appendChild(this.tabs);
		this._container.appendChild(this.content);

		var viewPort = this._container.offsetWidth;
		var tabs = elem.querySelectorAll('li.tab');
		var tabDefault = elem.querySelectorAll('li.tab.active');
		if( !tabDefault.length && tabs.length ){tabs[0].classList.add('active');}

		this._container.addEventListener('widget-should-update',function(e){
			this.update();
		}.bind(this),false);

		this.update();
		var hasContent = false;
		Array.prototype.slice.call(tabs).forEach(function(tab,k){
			var label = tab.querySelector('.label');
			var left  = label ? label.offsetLeft : tab.offsetLeft;
			var width = label ? label.offsetWidth : tab.offsetWidth;
			var isChecked = tab.classList.contains('active');

			/* Si la tab está escondida por el scroll */
			if( isChecked && left + width > viewPort ){
				//FIXME: no
				//ths.panel.scrollLeft = (left+width)-viewPort;
			}

			var content = tab.querySelector('.content');
			if( content ){
				if( isChecked ){content.classList.add('active');}
				tab._content = content;
				hasContent = true;
				ths.content.appendChild(content);
				tab.addEventListener('click',function(e){
					if (tab.classList.contains('disabled')) {return false;}
					x = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
					var change = Math.abs(ths.panel.startX - x);
					if( change > 20 ){return false;}

					Array.prototype.slice.call(ths.content.children).forEach(function(v,k){
						v.classList.remove('active');
					});
					Array.prototype.slice.call(this.parentNode.children).forEach(function(v,k){
						v.classList.remove('active');
					});

					this.classList.add('active');
					content.classList.add('active');

					if( (this.tmp = content.querySelectorAll('.widget-update')) ){
						/* Give some time to calculate bounding correctly */
						setTimeout(function(){
							Array.prototype.slice.call(this.tmp).forEach(function(elem){
								var event = new CustomEvent('widget-should-update',{'detail':{},'bubbles':true,'cancelable':true});
								elem.dispatchEvent(event);
							});
						}.bind(this),2);
					}
				});
			}

			tab.addEventListener('mousedown',function(e){
				//e.preventDefault();
				//e.stopPropagation();
			});
		});

		this._scroll_next.addEventListener('click',function(e){
			this.panel.scrollX += 40;
			if( this.panel.scrollX > 0 ){this.panel.scrollX = 0;}
			if( this.panel.scrollX < this.panel.scrollMaxX ){this.panel.scrollX = this.panel.scrollMaxX;}
			this.panel.style.transition = 'transform 100ms ease-out';
			this.panel.style.transform = 'translateX(' + (this.panel.scrollX) + 'px)';
		}.bind(this));
		this._scroll_prev.addEventListener('click',function(e){
			this.panel.scrollX -= 40;
			if( this.panel.scrollX > 0 ){this.panel.scrollX = 0;}
			if( this.panel.scrollX < this.panel.scrollMaxX ){this.panel.scrollX = this.panel.scrollMaxX;}
			this.panel.style.transition = 'transform 100ms ease-out';
			this.panel.style.transform = 'translateX(' + (this.panel.scrollX) + 'px)';
		}.bind(this));

		var opts = {passive:true,capture:true};
		this.panel.addEventListener('mousedown',function(e){this.mousedown(e);}.bind(this),opts);
		this.panel.addEventListener('touchstart',function(e){this.mousedown(e);}.bind(this),opts);
		this.panel.addEventListener('dragstart',function(e){
			e.preventDefault();
			e.stopPropagation();
			return false;
		});
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

		var state_buttons = this._container.querySelectorAll('.state-change');
		Array.prototype.slice.call(state_buttons).forEach(function(btn){
			if (btn.getAttribute('data-transition')) {return false;}
			btn.setAttribute('data-transition',true);
			btn.addEventListener('click',function(e){
				var state = btn.getAttribute('data-state-to');
				if (!state) {return false;}
				this.on_state_change(btn,state);
			}.bind(this));
		}.bind(this));
	};
	widgets.tabs.prototype.on_state_change = function(content,state){
		if( (parent = $E.parent.match(content,'.content')) ){
			$transition.toState(parent,state);
//alert(state);
		}
	};
	widgets.tabs.prototype.update = function(e){
		this.panel.viewpW = this.tabs.offsetWidth;
		this.panel.startW = this.panel.offsetWidth;
		this.panel.scrollMaxX = this.panel.viewpW - this.panel.startW;
		if( this.panel.scrollMaxX >= 0 ){
			this._container.classList.remove('widget-tabs-scroll');
			this.panel.scrollX = 0;
			this.panel.style.transform = 'translateX(' + (this.panel.scrollX) + 'px)';
			this._scroll_next.style.display = 'none';
			this._scroll_prev.style.display = 'none';
		}else{
			this._container.classList.add('widget-tabs-scroll');
			this._scroll_next.style.display = 'block';
			this._scroll_prev.style.display = 'block';
		}
	};
	widgets.tabs.prototype.mousedown = function(e){
		if( e.button === 1 ){return false;}
		e.stopPropagation();
		var ths = this;
		this.panel.startX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		this.panel.startY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
		this.panel.startScrollX = this.panel.scrollX || 0;

		this.panel.style.transition = '';
		this.update();
		if( this.panel.scrollMaxX > 0 ){return false;}

		if( !('handlerMouseMove' in this.panel) ){
			this.panel.handlerMouseMove = function(e){this.mousemove(e);}.bind(this);
			this.panel.handlerMouseUp   = function(e){this.mouseup(e);}.bind(this);
		}

		addEventListener('mousemove',this.panel.handlerMouseMove,true);
		addEventListener('mouseup',  this.panel.handlerMouseUp,true);
		addEventListener('touchmove',this.panel.handlerMouseMove,true);
		addEventListener('touchend', this.panel.handlerMouseUp,true);
		addEventListener('touchstop',this.panel.handlerMouseUp,true);
	};
	widgets.tabs.prototype.mousemove = function(e){
		//e.preventDefault();
		e.stopPropagation();

		this.panel.x = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;

		this.panel.scrollX = this.panel.startScrollX - this.panel.startX + this.panel.x;
		if( this.panel.scrollX > 0 ){this.panel.scrollX = 0;}
		if( this.panel.scrollX < this.panel.scrollMaxX ){this.panel.scrollX = this.panel.scrollMaxX;}
		this.panel.style.transform = 'translateX(' + (this.panel.scrollX) + 'px)';
		//this.panel.scrollLeft = this.panel.scrollX;
	};
	widgets.tabs.prototype.mouseup = function(e){
		this.panel.x  = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		this.panel.y  = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
		var absoluteX = Math.abs(this.panel.startX - this.panel.x);
		this.panel.shouldPreventClick = true;
		if( absoluteX < 8 ){
			this.panel.shouldPreventClick = false;
			if( e.type == 'touchend' ){
				var elem = document.elementFromPoint(this.panel.x,this.panel.y);
elem.click();
console.log(elem);
			}
		}

		//e.preventDefault();
		//e.stopPropagation();
		removeEventListener('mousemove',this.panel.handlerMouseMove,true);
		removeEventListener('mouseup',  this.panel.handlerMouseUp,true);
		removeEventListener('touchmove',this.panel.handlerMouseMove,true);
		removeEventListener('touchend', this.panel.handlerMouseUp,true);
		removeEventListener('touchstop',this.panel.handlerMouseUp,true);
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
	function widgets_tabs_update(e){
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('ul.widget-tabs'));
		nodeList.forEach(function(v,k){
			var event = new CustomEvent('widget-should-update',{'detail':{},'bubbles':true,'cancelable':true});
			v.dispatchEvent(event);
		});
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('ul.tabs'));
		nodeList.forEach(function(v,k){
			var event = new CustomEvent('widget-should-update',{'detail':{},'bubbles':true,'cancelable':true});
			v.dispatchEvent(event);
		});
	};

	addEventListener('DOMContentLoaded',widgets_tabs_init);
	addEventListener('resize',widgets_tabs_update);
	addEventListener('load',widgets_tabs_update);
	if (document.readyState === 'complete' || document.readyState === 'loaded' || document.readyState === 'interactive') {
		widgets_tabs_init();
	}


