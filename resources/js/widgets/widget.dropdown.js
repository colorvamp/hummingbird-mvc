
	if( !window.$E ){var $E = {
		classHas: function(elem,className){var p = new RegExp('(^| )'+className+'( |$)');return (elem.className && elem.className.match(p));},
		classAdd: function(elem,className){if($E.classHas(elem,className)){return true;}elem.className += ' '+className;},
		classRemove: function(elem,className){var c = elem.className;var p = new RegExp('(^| )'+className+'( |$)');c = c.replace(p,' ').replace(/  /g,' ');elem.className = c;},
		classParentHas: function(elem,className,limit){
			limit = typeof limit !== 'undefined' ? limit : 1;
			if($E.classHas(elem,className)){return elem;}
			if(!elem.parentNode){return false;}
			do{if($E.classHas(elem.parentNode,className)){return elem.parentNode;}elem = elem.parentNode;}while(elem.parentNode && limit--);return false;
		},
		class: {
			exists: function(elem,className){var p = new RegExp('(^| )'+className+'( |$)');return (elem.className && elem.className.match(p));},
			add: function(elem,className){if($E.classHas(elem,className)){return true;}elem.className += ' '+className;},
			remove: function(elem,className){var c = elem.className;var p = new RegExp('(^| )'+className+'( |$)');c = c.replace(p,' ').replace(/  /g,' ');elem.className = c;}
		},
		parent: {
			find: function(elem,p){/* p = {tagName:false,className:false} */if(p.tagName){p.tagName = p.tagName.toUpperCase();}if(p.className){p.className = new RegExp('( |^)'+p.className+'( |$)');}while(elem.parentNode && ((p.tagName && elem.tagName!=p.tagName) || (p.className && !elem.className.match(p.className)))){elem = elem.parentNode;}if(!elem.parentNode){return false;}return elem;},
			match: function(elem,m){
				while( elem.parentNode && (!elem.matches || !elem.matches(m)) ){elem = elem.parentNode;}
				return ( elem.parentNode ) ? elem : false;
			}
		}
	}}

	if( !window['widgets'] ){window['widgets'] = {};}
	widgets.dropdown = function(elem){
		this.elem = elem;
		/* Evitamos que un mismo elemento se pueda instanciar 2 veces como dropdown */
		if( elem.getAttribute('data-dropdown') ){return false;}
		elem.setAttribute('data-dropdown',true);

		var ths = this;
		this.elem.addEventListener('click',function(e){ths.onClick.call(ths,e);});
		this.elem.addEventListener('touchstart',function(e){ths.onClick.call(ths,e);});
		this.elem.addEventListener('close',function(e){ths.close.call(ths,e);});
		/* Evitamos que se cierre al hacer click en el contenido */
		this.dropdown_menu = elem.querySelector('.dropdown-menu');
		if( this.dropdown_menu ){
			this.dropdown_menu.addEventListener('click',function(e){e.stopPropagation();});
			this.dropdown_menu.addEventListener('touchstart',function(e){e.stopPropagation();});
		}

		/* INI-botones de cerrar que incorpore el dropdown */
		var buttons = this.elem.querySelectorAll('.btn.close,.btn.btn-close');
		if(buttons.length){Array.prototype.slice.call(buttons).forEach(function(btn){
			btn.addEventListener('click',function(e){e.stopPropagation();ths.close.call(ths,e);});
			btn.addEventListener('touchstart',function(e){e.stopPropagation();ths.close.call(ths,e);});
		});}
		/* END-botones de cerrar que incorpore el dropdown */

		/* INI-selectBox */
		if( 0 && this.isSelectBox()){
			var dataParent = this.elem.getAttribute('data-value');
			this.setValue(dataParent,true);
			Array.prototype.slice.call(this.dropdown_menu.childNodes).forEach(function(it){
				if(!it.classList.contains('item')){return;}
				if(!dataParent){var val = it.getAttribute('data-value');ths.setValue.call(ths,val,true);dataParent = val;}
				it.addEventListener('click',function(e){e.stopPropagation();ths.setValue.call(ths,this.getAttribute('data-value'));ths.close.call(ths);});
			});
		}
		/* END-selectBox */

		/* INI-ajax*/
		if( 0 && this.isAjax()){
			var buttons = this.elem.querySelectorAll('.btn.ok,.btn.btn-ok');
			if(buttons.length){Array.prototype.slice.call(buttons).forEach(function(btn){
				btn.addEventListener('click',function(e){
					if(btn.tagName.toUpperCase() == 'BUTTON'){e.preventDefault();}/* Porque puede ser un button y nos envía el formulario */
					e.stopPropagation();
					ths.ajax.call(ths,e);
				});
			});}
		}
		/* END-ajax*/
	}
	widgets.dropdown.prototype.isDisabled = function(){return this.elem.classList.contains('disabled');};
	widgets.dropdown.prototype.isOpen = function(){return this.elem.classList.contains('active');};
	widgets.dropdown.prototype.isSelectBox = function(){return this.elem.classList.contains('select');};
	widgets.dropdown.prototype.isAjax = function(){return this.elem.classList.contains('ajax');};
	widgets.dropdown.prototype.isRemoveBox = function(){return this.elem.classList.contains('remove');};
	widgets.dropdown.prototype.mustSubmit = function(){return this.elem.classList.contains('autosubmit');};
	widgets.dropdown.prototype.setValue = function(dataToSet,initial){
		var parent = this.elem.getElementsByClassName('dropdown-menu');if(!parent.length){return;}parent = parent[0];
		Array.prototype.slice.call(parent.childNodes).forEach(function(it){
			if(!$E.class.exists(it,'item')){return;}
			var data = it.getAttribute('data-value');
			if(data != dataToSet){/* Buscamos el element coincidente, saltamos si este no fuera */return;}
			var ddw = $E.parent.find(it,{'className':'dropdown-toggle'});if(!ddw){return false;}
			var ipt = ddw.getElementsByClassName('input');if(ipt){
				Array.prototype.slice.call(ipt).forEach(function(y){y.value = data;});
			}
			var val = ddw.getElementsByClassName('value');if(val){
				Array.prototype.slice.call(val).forEach(function(y){y.innerHTML = it.innerHTML;});
			}
		});

		if(!initial && this.mustSubmit()){
			/* Widget calendar tiene su propio handler */
			if(this.elem.classList.contains('widget-calendar')){return false;}
			var form = $E.parent.find(this.elem,{'tagName':'form'});
			return form.submit();
		}
	};
	widgets.dropdown.prototype.onClick = function(e){
		var item = false;
		if(this.isDisabled()){return false;}
		e.preventDefault();
		e.stopPropagation();
		if(this.isOpen()){
			//VAR_dropdownToggled = false;
			if(this.isSelectBox() &&  (item = $E.parent.find(e.target,{'className':'item'})) ){
				var dataToSet = item.getAttribute('data-value');
				this.setValue(dataToSet);
			}
			return this.close();
		}
		return this.open();
	}
	widgets.dropdown.prototype.ajax = function(e){
		var ths  = this;
		var form = $E.parent.find(e.target,{'tagName':'form'});if(!form){return false;}
		var ddm  = this.elem.querySelector('.dropdown-menu');if(!ddm){return false;}
		var info = this.elem.querySelector('div[data-state="info"]');

		ddm.classList.remove('state-main','state-working','state-end');
		ddm.classList.add('state-working');

		var params = $toUrl($parseForm(form));
		ajaxPetition(window.location.href,params,function(ajax){
			var r = $json.decode(ajax.responseText);
			if( 'errorDescription' in r ){alert(print_r(r));return;}
			ddm.classList.remove('state-main','state-working','state-end');

			if( 'html' in r && info ){
				info.innerHTML = r.html;
				ddm.classList.add('state-info');
			}
		});

		return false;
	};
	widgets.dropdown.prototype.open = function(){
		//FIXME: evento before open
		var btn = $E.parent.find(this.elem,{'className':'dropdown-toggle'});
		var cbOnBeforeOpen = btn.getAttribute('data-dropdown-onBeforeOpen');

		/* INI-Soporte para recaptcha */
		if( window.grecaptcha && (elems = this.elem.querySelectorAll('.g-recaptcha')) ){
			var key  = false;
			var elem = false;
			Array.prototype.slice.call(elems).forEach(function(elem){
				if( elem.firstChild ){return;}
				key = elem.getAttribute('data-sitekey');
				if(key){grecaptcha.render(elem,{'sitekey':key,'theme':'light'});}
			});
		}
		/* END-Soporte para recaptcha */
		/* INI-Search for updatable elements */
		if( (this.tmp = this.elem.querySelectorAll('.widget-update')) ){
			/* Give some time to calculate bounding correctly */
			setTimeout(function(){
				Array.prototype.slice.call(this.tmp).forEach(function(elem){
					var event = new CustomEvent('widget-should-update',{'detail':{},'bubbles':true,'cancelable':true});
					elem.dispatchEvent(event);
				});
			}.bind(this),2);
		}
		/* END-Search for updatable elements */

		btn.classList.toggle('active');
		if( cbOnBeforeOpen && this.isOpen() ){$execByString(cbOnBeforeOpen,[false,btn]);}

		if( !window.VAR ){window.VAR = {};}
		window.VAR.dropdown = this.elem;
		var body_width = (document.body.offsetWidth);
		var pos = this.dropdown_menu.getBoundingClientRect();
		if( pos.width > (body_width - 20) ){
			this.dropdown_menu.style.width = (body_width - 20) + 'px';
			this.dropdown_menu.style.minWidth = (body_width - 20) + 'px';
			pos  = this.dropdown_menu.getBoundingClientRect();
		}
		var rpos = body_width - (pos.left + pos.width);
		/* If the infoBox is out the page, fix it to the right border */
		if( rpos < 10 ){this.dropdown_menu.style.left = this.dropdown_menu.offsetLeft+rpos-10+'px';}
		

		this.dropdown_menu.classList.remove('state-end');
		//FIXME: evento open
	}
	widgets.dropdown.prototype.close = function(){
		var btn = $E.parent.find(this.elem,{'className':'dropdown-toggle'});
		if(btn){btn.classList.remove('active');}
	};
	/* END-dropdown */

	addEventListener('DOMContentLoaded',function(){
		drodropdown_init();
	});
	if (document.readyState === 'complete' || document.readyState === 'loaded' || document.readyState === 'interactive') {
		drodropdown_init();
	}

	function drodropdown_init(){
		/* INI-dropdown */
		if( !window.VAR ){window.VAR = {};}
		window.VAR.dropdown = false;
		var dropdownToggles = document.querySelectorAll('.dropdown-toggle');
		Array.prototype.slice.call(dropdownToggles).forEach(function(v){var d = new widgets.dropdown(v);});
		var body = document.body;
		body.addEventListener('click',function(event){
			if( window.VAR.dropdown ){
				var event = new CustomEvent('close',{'detail':{},'bubbles':true,'cancelable':true});
				window.VAR.dropdown.dispatchEvent(event);
			}
		});
		/* END-dropdown */
	}

