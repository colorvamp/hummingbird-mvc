
	if( !window['widgets'] ){window['widgets'] = {};}
	widgets.scroll = function(elem){
		if( elem.getAttribute('data-widget') ){return false;}
		elem.setAttribute('data-widget',true);
		if( typeof window.orientation !== 'undefined' ){return false;}
		if( document.body.offsetWidth < 500 ){return false;}

		var ths = this;
		this.elem = elem;
		this.elem.scrollY = 0;
		this.elem.addEventListener('wheel',function(e){ths.wheel(e,elem);});
		this.elem.style.overflow  = 'hidden';
		this.elem.style.height    = 'auto';
		//this.elem.style.maxHeight = 'none';
		this.elem.parentNode.style.overflow = 'hidden';

		this.panel = document.createElement('DIV');
		this.panel.classList.add('widget-scroll-panel');
		this.panel.style.display = 'inline-block';
		/* Transbasamos todos los childs */
		while( this.elem.firstElementChild ){
			this.panel.appendChild(this.elem.firstElementChild);
		}

		this.elem.appendChild(this.panel);

		this.bar = document.createElement('SPAN');
		this.bar.classList.add('widget-scroll-bar');
		this.bar.startScrollY = 0;
		this.bar.startHeight  = this.elem.offsetHeight / 2;
		this.elem.appendChild(this.bar);

		this.bar.addEventListener('mousedown',function(e){
			this.bar_mousedown(e);
		}.bind(this));
		this.elem.addEventListener('widget-update',function(){
			this.update();
		}.bind(this));
	};
	widgets.scroll.prototype.update = function(){
		
	};
	widgets.scroll.prototype.bar_mousedown = function(e){
		if ( e.button === 1 ) {return false;}

		e.preventDefault();
		e.stopPropagation();

		this.panel.style.transition = '';
		this.panel.scrollLimit = this.elem.offsetHeight - this.panel.offsetHeight;
		if( this.elem.scrollLimit > 0 ){
			/* Si el hijo cabe dentro del padre (scrollLimit > 0) no debemos hacer nada */
			return false;
		}

		this.bar.startX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		this.bar.startY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
		this.bar.scrollLimit = this.elem.offsetHeight - this.bar.startHeight;

		if( !('mouseMoveHandler' in this.bar) ){
			this.bar.handlerMouseMove = function(e){this.bar_mousemove(e);}.bind(this);
			this.bar.handlerMouseUp   = function(e){this.bar_mouseup(e);}.bind(this);
		}

		addEventListener('mousemove',this.bar.handlerMouseMove,true);
		addEventListener('mouseup',  this.bar.handlerMouseUp,true);
		addEventListener('touchmove',this.bar.handlerMouseMove,true);
		addEventListener('touchend', this.bar.handlerMouseUp,true);
		addEventListener('touchstop',this.bar.handlerMouseUp,true);
	};
	widgets.scroll.prototype.bar_mousemove = function(e){
		e.preventDefault();
		e.stopPropagation();

		this.bar.y = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;

		this.bar.scrollY = this.bar.startScrollY - this.bar.startY + this.bar.y;
		if( this.bar.scrollY < 0 ){this.bar.scrollY = 0;}
		if( this.bar.scrollY > this.bar.scrollLimit ){this.bar.scrollY = this.bar.scrollLimit;}
		this.bar.style.transform  = 'translateY(' + (this.bar.scrollY) + 'px)';

		this.panel.perc = this.bar.scrollY / this.bar.scrollLimit;
		this.panel.scrollY = (this.panel.scrollLimit * this.panel.perc);
		this.panel.style.transform = 'translateY(' + this.panel.scrollY + 'px)';
	};
	widgets.scroll.prototype.bar_mouseup = function(e){
		e.preventDefault();
		e.stopPropagation();

		this.bar.startScrollY = this.bar.scrollY;

		removeEventListener('mousemove',this.bar.handlerMouseMove,true);
		removeEventListener('mouseup',  this.bar.handlerMouseUp,true);
		removeEventListener('touchmove',this.bar.handlerMouseMove,true);
		removeEventListener('touchend', this.bar.handlerMouseUp,true);
		removeEventListener('touchstop',this.bar.handlerMouseUp,true);
	};

	widgets.scroll.prototype.wheel = function(e,elem){
		this.panel.style.transition = 'transform 100ms ease-out';

		this.elem.viewPortH  = this.elem.offsetHeight;
		this.panel.scrollLimit = this.elem.viewPortH - this.panel.offsetHeight;
		if( this.panel.scrollLimit > 0 ){
			/* Si el hijo cabe dentro del padre (scrollLimit > 0) no debemos hacer nada */
			return false;
		}
		if( !('widget_bar' in this.panel) ){
			//this.bar.style.height = (elem.viewPortH / 2) + 'px';
		}

		var d = this.normalizeWheel(e);
		this.elem.scrollY -= (d.spinY * 60);
		if( this.elem.scrollY > 0 ){this.elem.scrollY = 0;}
		if( this.elem.scrollY < this.panel.scrollLimit  ){this.elem.scrollY = this.panel.scrollLimit;}
		this.panel.style.transform = 'translateY(' + this.elem.scrollY + 'px)';

		/* Calculate bar top */
		var perc = this.elem.scrollY / this.panel.scrollLimit;
		this.bar.startScrollY = (perc * (this.elem.viewPortH - this.bar.startHeight));
		this.bar.style.transform  = 'translateY(' + this.bar.startScrollY + 'px)';
	};
	widgets.scroll.prototype.normalizeWheel = function(/*object*/ event) /*object*/ {
		var PIXEL_STEP = 10;
		var LINE_HEIGHT = 40;
		var PAGE_HEIGHT = 800;

		var sX = 0, sY = 0,       // spinX, spinY
			pX = 0, pY = 0;       // pixelX, pixelY

		// Legacy
		if ('detail'      in event) { sY = event.detail; }
		if ('wheelDelta'  in event) { sY = -event.wheelDelta / 120; }
		if ('wheelDeltaY' in event) { sY = -event.wheelDeltaY / 120; }
		if ('wheelDeltaX' in event) { sX = -event.wheelDeltaX / 120; }

		// side scrolling on FF with DOMMouseScroll
		if ( 'axis' in event && event.axis === event.HORIZONTAL_AXIS ) {
			sX = sY;
			sY = 0;
		}

		pX = sX * PIXEL_STEP;
		pY = sY * PIXEL_STEP;

		if ('deltaY' in event) { pY = event.deltaY; }
		if ('deltaX' in event) { pX = event.deltaX; }

		if ((pX || pY) && event.deltaMode) {
			if (event.deltaMode == 1) {          // delta in LINE units
				pX *= LINE_HEIGHT;
				pY *= LINE_HEIGHT;
			} else {                             // delta in PAGE units
				pX *= PAGE_HEIGHT;
				pY *= PAGE_HEIGHT;
			}
		}

		// Fall-back if spin cannot be determined
		if (pX && !sX) { sX = (pX < 1) ? -1 : 1; }
		if (pY && !sY) { sY = (pY < 1) ? -1 : 1; }

		return { spinX  : sX,
			spinY  : sY,
			pixelX : pX,
			pixelY : pY };
	}
	
	addEventListener('load',function(e){
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('.widget-scroll'));
		nodeList.forEach(function(v,k){new widgets.scroll(v);});

		var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
		if( MutationObserver ){
			// define a new observer
			var obs = new MutationObserver(function(mutations, observer){
				if( mutations[0].addedNodes.length/* || mutations[0].removedNodes.length*/ ){
					var nodeList = Array.prototype.slice.call(document.querySelectorAll('.widget-scroll:not([data-widget])'));
					if( nodeList.length ){
						nodeList.forEach(function(v,k){new widgets.scroll(v);});
					}
				}
			});
			// have the observer observe foo for changes in children
			obs.observe( document.body, { childList:true, subtree:true });
		}
	});


