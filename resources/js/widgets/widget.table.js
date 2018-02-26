
	if( !window['widgets'] ){window['widgets'] = {};}
	widgets.table = function(elem){
		if( elem.getAttribute('data-widget') ){return false;}
		elem.setAttribute('data-widget',true);

		var ths    = this;
		this.table = elem;
		this._container = document.createElement('DIV');
		this._container.classList.add('widget-table');
		this.table.parentNode.insertBefore(this._container,this.table.nextSibling);

		this._container.appendChild(this.table);

		var opts = {passive:true,capture:false};
		this.table.addEventListener('mousedown',function(e){this.mousedown(e);}.bind(this),opts);
		this.table.addEventListener('touchstart',function(e){this.mousedown(e);}.bind(this),opts);

		var name = false;
		if( (name = this.table.getAttribute('data-table-name')) ){
			if( filter = localStorage.getItem('data-table-filter-' + name) ){
				this.filter(filter);
			}


			var filters = document.querySelectorAll('*[data-table-filter=' + name + ']');
			Array.prototype.slice.call(filters).forEach(function(v,k){
				if( filter ){v.value = filter;}

				v.addEventListener('keyup',function(e){
					ths.filter(v.value);
				});
			});
		}

		/* If table has class 'radio' and have a input value
		 * we try to map this value over the inputs */
		if( this.table.classList.contains('radio')
		 && (this.tmp = this.table.getAttribute('data-input-value'))
		 && (this.input = this.table.querySelector('input[value="' + this.tmp + '"]')) ){
			this.input.setAttribute('checked','checked');
		}

		this._container.addEventListener('widget-add-item',function(e){
			if( $is.empty('detail.item',e) ){return false;}
			this.on_row_add(e.detail.item);
		}.bind(this),false);
		this._container.addEventListener('widget-item-add',function(e){
			if( $is.empty('detail.item',e) ){return false;}
			this.on_row_add(e.detail.item);
		}.bind(this),false);

		var folded_buttons = this.table.querySelectorAll('.table-unfold');
		Array.prototype.slice.call(folded_buttons).forEach(function(n){
			n.addEventListener('click',function(){
				this.on_row_fold(n);
			}.bind(this));
		}.bind(this));
	};
	widgets.table.prototype.on_row_add = function(item){
		/* item = {"id":uniqid,"child":true|false,"after":element,"columns":["col1","col2"]} */
		var _item = document.createElement('LI');
		if( item.id ){_item.setAttribute('id',item.id);}
		if( item.child ){_item.classList.add('child');}

		item.columns.forEach(function(k){
			var column = document.createElement('DIV');
			column.innerHTML = k;
			_item.appendChild(column);
		});
		switch( true ){
			case !!(item.after):
				//TODO
				break;
			case !!(item.before):
				this.table.insertBefore(_item,item.before);
				break;
			default:
				this.table.appendChild(_item);
		}
		this.update();
	};
	widgets.table.prototype.on_row_fold = function(item){
		if( (item = $E.parent.match(item,'.parent')) ){
			if(  item.classList.contains('working') ){return false;}
			if( !item.classList.contains('unfolded') ){
				//FIXME: necesitamos bloqueo -> working

				var query = [];
				var shouldQuery = false;
				if( !shouldQuery && !item.nextElementSibling ){shouldQuery = true;}
				if( !shouldQuery && (item.nextElementSibling && !item.nextElementSibling.classList.contains('child')) ){shouldQuery = true;}

				/* Maybe we have to ask for the rows via ajax */
				if( shouldQuery
				 && (this.tmp = item.getAttribute('data-table-query')) ){
					var post = {'subcommand':this.tmp};
					for( a in item.dataset ){
						if( /^query/.test(a) ){
							/* Add extra */
							post[a.substring(5).toLowerCase()] = item.dataset[a];
						}
					}
					query.push($ajax(window.location.href,post).then(function(data){
						var rows = $json.decode(data);
						rows.forEach(function(row){
							if( item.nextElementSibling ){
								/* If parent has a sibling we tell the code to 
								 * insert new rows before the sibling element, if
								 * there is no sibling elements then its ok to 
								 * append them at the end */
								row.before = item.nextElementSibling;
							}
							this.on_row_add(row);
						}.bind(this));
					}.bind(this),function(){

					}));
				}

				Promise.all(query).then(function(){
					var idx = item;
					while( idx.nextElementSibling
					 && idx.nextElementSibling.classList.contains('child') ){
						idx.nextElementSibling.classList.remove('hidden');
						idx = idx.nextElementSibling;
					}
					item.classList.add('unfolded');
				});
			}else{
				var idx = item;
				while( idx.nextElementSibling
				 && idx.nextElementSibling.classList.contains('child') ){
					idx.nextElementSibling.classList.add('hidden');
					idx = idx.nextElementSibling;
				}
				item.classList.remove('unfolded');
			}
		}
	};
	widgets.table.prototype.filter = function(filter){
		filter = filter.replace(/\./ig,'\\.');

		var name = this.table.getAttribute('data-table-name');
		localStorage.setItem('data-table-filter-' + name,filter.replace(/\\/g,''));

		var regex = new RegExp(filter,'i');
		Array.prototype.slice.call(this.table.children).forEach(function(v,k){
			v.classList.remove('hidden');
			var data_filter = v.getAttribute('data-filter');
			if( !data_filter ){return false;}
			if( !regex.test(data_filter) ){v.classList.add('hidden');}
		});
	};
	widgets.table.prototype.update = function(e){
		this.table.viewpW = this._container.offsetWidth;
		this.table.startW = this.table.offsetWidth;
		this.table.scrollMaxX = this.table.viewpW - this.table.startW;
		if( this.table.scrollMaxX > 0 ){
			this._container.classList.remove('widget-table-scroll');
			this.table.scrollX = 0;
			this.table.style.transform = 'translateX(' + (this.table.scrollX) + 'px)';
		}else{
			//this._container.classList.add('widget-table-scroll');
		}

		var event = new CustomEvent('widget-update',{'detail':{'container':this._container},'bubbles':true,'cancelable':true});
		this.table.dispatchEvent(event);
	};
	widgets.table.prototype.mousedown = function(e){
		if( e.button === 1 ){return false;}
		if( this.table.scrollMaxX > -1 ){return false;}
		e.stopPropagation();
		var ths = this;
		this.table.startX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		this.table.startY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
		this.table.startScrollX = this.table.scrollX || 0;

		this.table.style.transition = '';
		this.update();
		if( this.table.scrollMaxX > 0 ){return false;}

		if( !('handlerMouseMove' in this.table) ){
			this.table.handlerMouseMove = function(e){this.mousemove(e);}.bind(this);
			this.table.handlerMouseUp   = function(e){this.mouseup(e);}.bind(this);
		}

		addEventListener('mousemove',this.table.handlerMouseMove,false);
		addEventListener('mouseup',  this.table.handlerMouseUp,false);
		addEventListener('touchmove',this.table.handlerMouseMove,false);
		addEventListener('touchend', this.table.handlerMouseUp,false);
		addEventListener('touchstop',this.table.handlerMouseUp,false);
	};
	widgets.table.prototype.mousemove = function(e){
		//e.preventDefault();
		e.stopPropagation();

		this.table.x = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;

		this.table.scrollX = this.table.startScrollX - this.table.startX + this.table.x;
		if( this.table.scrollX > 0 ){this.table.scrollX = 0;}
		if( this.table.scrollX < this.table.scrollMaxX ){this.table.scrollX = this.table.scrollMaxX;}
		this.table.style.transform = 'translateX(' + (this.table.scrollX) + 'px)';
		//this.table.scrollLeft = this.table.scrollX;
	};
	widgets.table.prototype.mouseup = function(e){
		this.table.x  = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		this.table.y  = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
		var absoluteX = Math.abs(this.table.startX - this.table.x);
		this.table.shouldPreventClick = true;
		if( absoluteX < 8 ){
			this.table.shouldPreventClick = false;
			if( e.type == 'touchend' ){
				var elem = document.elementFromPoint(this.table.x,this.table.y);
elem.click();
console.log(elem);
			}
		}

		//e.preventDefault();
		//e.stopPropagation();
		removeEventListener('mousemove',this.table.handlerMouseMove,false);
		removeEventListener('mouseup',  this.table.handlerMouseUp,false);
		removeEventListener('touchmove',this.table.handlerMouseMove,false);
		removeEventListener('touchend', this.table.handlerMouseUp,false);
		removeEventListener('touchstop',this.table.handlerMouseUp,false);
	};

	function widget_table_init(){
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('ul.table'));
		nodeList.forEach(function(v,k){new widgets.table(v);});

		var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
		if( MutationObserver ){
			// define a new observer
			var obs = new MutationObserver(function(mutations, observer){
				if( mutations[0].addedNodes.length/* || mutations[0].removedNodes.length*/ ){
					var nodeList = Array.prototype.slice.call(document.querySelectorAll('ul.table:not([data-widget])'));
					if( nodeList.length ){
						nodeList.forEach(function(v,k){new widgets.table(v);});
					}
				}
			});
			// have the observer observe foo for changes in children
			obs.observe( document.body, { childList:true, subtree:true });
		}
	}
	
	addEventListener('DOMContentLoaded',function(e){widget_table_init();});
	if (document.readyState === 'complete' || document.readyState === 'loaded' || document.readyState === 'interactive') {
		widget_table_init();
	}

