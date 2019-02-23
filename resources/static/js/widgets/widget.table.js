
	if (!window['widgets']) {window['widgets'] = {};}
	widgets.table = function(elem){
		if (elem.getAttribute('data-widget')) {return false;}
		elem.setAttribute('data-widget',true);

		this._table = elem;
		if (this._table.parentNode.classList.contains('widget-table')) {
			/* If there is already a 'widget-table' parent node, we reuse it */
			this._container = this._table.parentNode;
		} else {
			this._container = document.createElement('DIV');
			this._container.classList.add('widget-table');
			this._table.parentNode.insertBefore(this._container,this._table.nextSibling);
			this._container.appendChild(this._table);
		}
		this._name = this._table.getAttribute('data-table-name');
		this._is_scroll_disabled = false;
		this._is_load_more = this._table.getAttribute('data-table-load-more');

		var opts = {passive:true,capture:false};
		this._table.addEventListener('mousedown',function(e){this.mousedown(e);}.bind(this),opts);
		this._table.addEventListener('touchstart',function(e){this.mousedown(e);}.bind(this),opts);

		/* If table has class 'radio' and have a input value
		 * we try to map this value over the inputs */
		if( this._table.classList.contains('radio')
		 && (this.tmp = this._table.getAttribute('data-input-value'))
		 && (this.input = this._table.querySelector('input[value="' + this.tmp + '"]')) ){
			this.input.setAttribute('checked','checked');
		}

		this._container.addEventListener('widget-item-add',function(e){
			if ($is.empty('detail.item',e)) {return false;}
			this.on_row_add(e.detail.item);
			e.stopPropagation();
		}.bind(this),false);
		if (this._is_load_more) {
			this._container._fn_scroll = function(){this.on_scroll();}.bind(this);
			window.addEventListener('scroll',this._container._fn_scroll,{passive: true});
		}

		/* INI-Load more */
		if ((this._btn_load_more = document.querySelector('.widget-table-load-more[data-table-name=' + this._name + ']'))) {
			this._btn_load_more.addEventListener('click',function(){
				if (this._btn_load_more.classList.contains('disabled')) {return false;}
				var _lis = this._table.querySelectorAll('li.hidden:not(.child)');
				if (!_lis.length) {
					/* There is no more to load */
					return false;
				}
				Array.prototype.slice.call(_lis,0,10).forEach(function(n){
					n.classList.remove('hidden');
				});
				this.update();
			}.bind(this));
		}
		/* END-Load more */

		var folded_buttons = this._table.querySelectorAll('.table-unfold');
		Array.prototype.slice.call(folded_buttons).forEach(function(n){
			n.addEventListener('click',function(){
				this.on_row_fold(n);
			}.bind(this));
		}.bind(this));

		/* INI-Filter */
		if (this._name) {
			if (filter = localStorage.getItem('data-table-filter-' + this._name)) {
				this.on_filter(filter);
			}

			var filters = document.querySelectorAll('[data-table-filter=' + this._name + ']');
			Array.prototype.slice.call(filters).forEach(function(v,k){
				if( filter ){v.value = filter;}

				v.addEventListener('keyup',function(e){
					this.on_filter(v.value);
				}.bind(this));
			}.bind(this));
		}
		/* END-Filter */

		this.update();

		/* Observer changes in 'parent' rows, if we hide a parent, we cannot
		 * leave the childs visible, so do it in an automater manner */
		this._observer = new MutationObserver(function(mutations){
			mutations.forEach(function(v,k){
				if (v.target.classList.contains('parent')
				 && !v.target.classList.contains('folded')) {
					var idx = v.target;
					var hide_or_show = idx.classList.contains('hidden');

					while (idx.nextElementSibling
					 && idx.nextElementSibling.classList.contains('child')) {
						if (hide_or_show) {
							idx.nextElementSibling.classList.add('hidden');
						} else {
							idx.nextElementSibling.classList.remove('hidden');
						}
						idx = idx.nextElementSibling;
					}
				}
			});
		});
		this._observer.observe(this._table, {attributes: true, subtree:true ,attributeOldValue: true ,attributeFilter: ['class']});
	};
	widgets.table.prototype.on_scroll = function(){
		if (this._is_scroll_disabled) {return false;}
		this._max_scroll = Math.max(
			 document.body.scrollHeight
			,document.body.offsetHeight
			,document.body.clientHeight
		);
		this._scroll_top = window.scrollY || window.scrollTop || document.querySelector('html').scrollTop;
		if (this._scroll_top + window.innerHeight > this._max_scroll - 20) {
			console.log('load more');
			this._is_scroll_disabled = true;

			var _lis = this._table.querySelectorAll('li.hidden');
			if (!_lis.length) {
				/* There is no more to load */
				window.removeEventListener('scroll',this._container._fn_scroll);
			}
			Array.prototype.slice.call(_lis,0,this._is_load_more).forEach(function(n){
				n.classList.remove('hidden');
			});
			this._is_scroll_disabled = false;
		}
	};
	widgets.table.prototype.on_row_add = function(item){
		/* item = {"id":uniqid,"child":true|false,"after":element,"columns":["col1","col2"]} */
		var _item = document.createElement('LI');
		if (item.id) {_item.setAttribute('id',item.id);}
		if (item.child) {_item.classList.add('child');}

		var _columns = false;
		if (!!(item.before)) {
			_columns = item.before.childElementCount;
		}

		var _count = 0;
		item.columns.forEach(function(k){
			var column = document.createElement('DIV');
			column.innerHTML = k;
			_item.appendChild(column);
			_count++;
		});
		if (_columns && _count < _columns) {
			/* If we have the number of columns of the parent,
			 * we should add a fixed number of empty columns to match */
			while (_count < _columns) {
				var column = document.createElement('DIV');
				_item.appendChild(column);
				_count++;
			}
		}


		switch (true) {
			case !!(item.after):
				//TODO
				break;
			case !!(item.before):
				this._table.insertBefore(_item,item.before);
				break;
			default:
				this._table.appendChild(_item);
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
					item.classList.remove('folded');
				});
			}else{
				var idx = item;
				while( idx.nextElementSibling
				 && idx.nextElementSibling.classList.contains('child') ){
					idx.nextElementSibling.classList.add('hidden');
					idx = idx.nextElementSibling;
				}
				item.classList.add('folded');
				item.classList.remove('unfolded');
			}
		}
	};
	widgets.table.prototype.on_filter = function(filter){
		this._filter = filter.replace(/([\[\]\.\$\/]{1})/ig,'\\$1');

		this._is_scroll_disabled = false;
		if (this._filter) {
			this._is_scroll_disabled = true;
			if (this._btn_load_more) {
				/* Deshabilitamos el botón de cargar más */
				this._btn_load_more.classList.add('disabled');
			}
		}

		localStorage.setItem('data-table-filter-' + this._name,this._filter.replace(/\\/g,''));

		var regex = new RegExp(this._filter,'i');
		Array.prototype.slice.call(this._table.children).forEach(function(v,k){
			if (v.classList.contains('child')) {return false;}

			if (!this._filter) {
				/* Restore before filter attributes if posible */
				if (this.tmp = v.getAttribute('data-before-filter')) {
					tmp = (this.tmp == 'hidden') ? v.classList.add('hidden') : v.classList.remove('hidden');
					v.removeAttribute('data-before-filter');
				}
				return true;
			}

			if (!v.getAttribute('data-before-filter')) {
				/* Save attributes for latter restore */
				v.setAttribute('data-before-filter',(v.classList.contains('hidden') ? 'hidden' : 'visible'));
			}

			var data_filter = v.getAttribute('data-filter');
			v.classList.remove('hidden');
			if (!data_filter) {return false;}
			if (!regex.test(data_filter)) {v.classList.add('hidden');}
		}.bind(this));

		this.update();
	};
	widgets.table.prototype.update = function(e){
		this._table.viewpW = this._container.offsetWidth;
		this._table.startW = this._table.offsetWidth;
		this._table.scrollMaxX = this._table.viewpW - this._table.startW;
		if (this._table.scrollMaxX > 0) {
			this._container.classList.remove('widget-table-scroll');
			this._table.scrollX = 0;
			this._table.style.transform = 'translateX(' + (this._table.scrollX) + 'px)';
		} else {
			//this._container.classList.add('widget-table-scroll');
		}

		var parents_hidden = this._table.querySelectorAll('li.parent.hidden');
		Array.prototype.slice.call(parents_hidden).forEach(function(v,k){
			var idx = v;
			while (idx.nextElementSibling
			 && idx.nextElementSibling.classList.contains('child')) {
				idx.nextElementSibling.classList.add('hidden');
				idx = idx.nextElementSibling;
			}
		});

		if (this._btn_load_more) {
			if (!parents_hidden.length || this._filter) {
				this._btn_load_more.classList.add('disabled');
			} else {
				this._btn_load_more.classList.remove('disabled');
			}
			if (this.tmp = this._btn_load_more.querySelector('.widget-table-hidden-count')) {
				this.tmp.innerHTML = parents_hidden.length;
			}
		}

		var event = new CustomEvent('widget-update',{'detail':{'container':this._container},'bubbles':true,'cancelable':true});
		this._table.dispatchEvent(event);
	};
	widgets.table.prototype.mousedown = function(e){
		if (('button' in e) && e.button !== 0) {return false;}

		/* INI z-index
		 * This is necessary because with transform:translate the z-index
		 * property is not taked into account. This means the dropdowns
		 * contained inside a table will render under the following table
		 * after the container */
		if (document.body.widgets_table
		 && document.body.widgets_table != this._table) {
			document.body.widgets_table.style.zIndex = 'auto';
		}
		document.body.widgets_table = this._table;
		this._table.style.zIndex = 10;
		/* END z-index */

		if (this._table.scrollMaxX > -1) {return false;}

		e.stopPropagation();
		this._table.startX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		this._table.startY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
		this._table.startScrollX = this._table.scrollX || 0;

		this._table.style.transition = '';
		this.update();
		if (this._table.scrollMaxX > 0) {return false;}

		if (!('handlerMouseMove' in this._table)) {
			this._table.handlerMouseMove = function(e){this.mousemove(e);}.bind(this);
			this._table.handlerMouseUp   = function(e){this.mouseup(e);}.bind(this);
		}

		addEventListener('mousemove',this._table.handlerMouseMove,false);
		addEventListener('mouseup',  this._table.handlerMouseUp,false);
		addEventListener('touchmove',this._table.handlerMouseMove,false);
		addEventListener('touchend', this._table.handlerMouseUp,false);
		addEventListener('touchstop',this._table.handlerMouseUp,false);
	};
	widgets.table.prototype.mousemove = function(e){
		//e.preventDefault();
		e.stopPropagation();

		this._table.x = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;

		this._table.scrollX = this._table.startScrollX - this._table.startX + this._table.x;
		if( this._table.scrollX > 0 ){this._table.scrollX = 0;}
		if( this._table.scrollX < this._table.scrollMaxX ){this._table.scrollX = this._table.scrollMaxX;}
		this._table.style.transform = 'translateX(' + (this._table.scrollX) + 'px)';
		//this._table.scrollLeft = this._table.scrollX;
	};
	widgets.table.prototype.mouseup = function(e){
		this._table.x  = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
		this._table.y  = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
		var absoluteX = Math.abs(this._table.startX - this._table.x);
		this._table.shouldPreventClick = true;
		if( absoluteX < 8 ){
			this._table.shouldPreventClick = false;
			if( e.type == 'touchend' ){
				var elem = document.elementFromPoint(this._table.x,this._table.y);
elem.click();
//console.log(elem);
			}
		}

		//e.preventDefault();
		//e.stopPropagation();
		removeEventListener('mousemove',this._table.handlerMouseMove,false);
		removeEventListener('mouseup',  this._table.handlerMouseUp,false);
		removeEventListener('touchmove',this._table.handlerMouseMove,false);
		removeEventListener('touchend', this._table.handlerMouseUp,false);
		removeEventListener('touchstop',this._table.handlerMouseUp,false);
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


