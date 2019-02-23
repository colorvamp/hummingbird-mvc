	var $E = {
		parent: {
			find: function (elem,p) {
				/* p = {tagName:false,className:false} */
				if (p.tagName) {p.tagName = p.tagName.toUpperCase();}
				if (p.className) {p.className = new RegExp('( |^)'+p.className+'( |$)');}
				while (elem.parentNode && ((p.tagName && elem.tagName!=p.tagName) || (p.className && !elem.className.match(p.className)))) {elem = elem.parentNode;}
				if (!elem.parentNode) {return false;}
				return elem;
			},
			match: function (elem,m) {
				while (elem.parentNode && (!elem.matches || !elem.matches(m))) {elem = elem.parentNode;}
				return (elem.parentNode) ? elem : false;
			}
		},
		child: {
			number: function (elem) {
				/* Get numeric position of a child inside his parent */
				return Array.prototype.indexOf.call(elem.parentNode.childNodes,elem);
			}
		},
		style: {
			get: function(elem,style){
				var value = false;
				if ($is.string(style)) {
					/* If style is a string, we should return the current value for 
					 * this style */
					if (elem.currentStyle) {
						value = elem.currentStyle[style];
						return value.replace(/px$/,'');
					}
					if (window.getComputedStyle) {
						value = window.getComputedStyle(elem,null).getPropertyValue(style);
						return value.replace(/px$/,'');
					}
					return value;
				}
				return value;
			},
			apply: function(elem,style){
				/* If style is and object we should apply styles */
				for (var o in style) {
					if (o.indexOf('.') == 0) {
						elem.style[o.replace(/^./,'')] = style[o];
						continue;
					}
					elem[o] = style[o];
				}
				return elem;
			}
		}
	};

	var $is = {
		set:	  function (o,path) {
			var stone;
			path = path || '';
			if( path.indexOf('[') !== -1 ){throw new Error('Unsupported object path notation.');}
			path = path.split('.');
			do{
				if( o === undefined ){return false;}
				stone = path.shift();
				if( !o.hasOwnProperty(stone) ){return false;}
				o = o[stone];
			}while( path.length );
			return true;
		},
		empty:    function(o){
			if( !o || ($is.string(o) && o == '') || ($is.array(o) && !o.length) ){return true;}
			if( $is.object(o) ){for( var key in o ){if( hasOwnProperty.call(obj, key) ){return false;}}return true;}
			return false;
		},
		array:    function (o) {return (Array.isArray(o));},
		string:   function (o) {return (typeof o == 'string' || o instanceof String);},
		object:   function (o) {return (o.constructor.toString().indexOf('function Object()') == 0);},
		element:  function (o) {return ('nodeType' in o && o.nodeType === 1 && 'cloneNode' in o);},
		function: function (o) {return (o.constructor.toString().indexOf('function Function()') == 0);},
		formData: function (o) {return (o.constructor.toString().indexOf('function FormData()') == 0);}
	};

	var $json = {
		encode: function (obj) {if (JSON.stringify) {return JSON.stringify(obj);}},
		decode: function (str) {
			if ($is.empty(str)) {return {errorDescription:"La cadena estÃ¡ vacÃ­a, revise la API o el COMANDO"};}
			if (!$is.string(str)) {return {errorDescription:'JSON_ERROR'};}
			str = str.trim();
			if (str.match("<title>404 Not Found</title>")) {return {errorDescription:"La URL de la API es errÃ³nea: 404"};}
			if (!JSON || !JSON.parse) {return eval('('+str+')');}
			try{return JSON.parse(str.trim());}catch(err){return {errorDescription:str};}
		}
	};

	var $form = {
		parse: function(f,e) {
			/* f = formElement, e = encode */
			var ops = {};
			f.querySelectorAll('input,textarea,select')
			 .forEach( function (el) {
				o = ops;
				p = ops;
				if (el.name.indexOf('[') > -1) {
					path = el.name.split('[');
					do{
						if (o === undefined){ return false; }
						stone = path.shift();
						if (stone.substring(stone.length - 1) == ']') {stone = stone.substring(0,stone.length - 1);}
						if (!o.hasOwnProperty(stone)) {o[stone] = {};}
						p = o;
						o = o[stone];
					}while( path.length );
				}else{
					stone = el.name;
					if (!o.hasOwnProperty(stone)) {o[stone] = {};}
					p = o;
					o = o[stone];
				}
				if (el.type == 'checkbox') {p[stone] = !!el.checked;return;}
				if (el.type == 'radio' && !el.checked) {return;}
				p[stone] = (!e) ? el.value : encodeURIComponent(el.value);
				return;
			} );
			return ops;
		}
	};

	var $fx = {
		height: function(el,height) {
			$E.style.apply(el,{'.transition':'height .5s ease','.height':el.offsetHeight + 'px'});
			return new Promise(function(resolve, reject) {
				setTimeout(function(){
					var fn = function(e){
						$E.style.apply(el,{'.transition':'height 0 ease 0'});
						el.removeEventListener('transitionend',fn);
						resolve(el);
					};
					el.addEventListener('transitionend',fn);
					$E.style.apply(el,{'.height':height + 'px'});
				},10);
			});
		},
		enter: function(el) {
			$E.style.apply(el,{'.transition':'opacity .5s ease','.overflow':'hidden'});
			return new Promise(function(resolve, reject) {
				setTimeout(function(){
					var fn = function(e){
						$E.style.apply(el,{'.transition':'opacity 0 ease 0'});
						el.removeEventListener('transitionend',fn);
						resolve(el);
					};
					el.addEventListener('transitionend',fn);
					$E.style.apply(el,{'.opacity':1});
				},10);
			});
		},
		leave: function(el,params) {
			$E.style.apply(el,{'.transition':'opacity .5s ease','.overflow':'hidden'});
			return new Promise(function(resolve, reject) {
				setTimeout(function() {
					var fn = function(e){
						$E.style.apply(el,{'.transition':'opacity 0 ease 0'});
						el.removeEventListener('transitionend',fn);
						resolve(el);
					};
					el.addEventListener('transitionend',fn);
					$E.style.apply(el,{'.opacity':0});
				},10);
			});
		}
	};

	var $transition = {
		toState: function(parent_elem,target_state) {
			return new Promise(function(resolve, reject) {
				var state = false;
				if ((state = parent_elem.getAttribute('data-ready')) && state == 'false') {
					return reject(parent_elem);
				}

				/* p (parent) & s (state) */
				var target_elem = parent_elem.firstElementChild;
				while (target_elem.nextElementSibling && target_elem.getAttribute('data-state') != target_state) {
					target_elem = target_elem.nextElementSibling;
				}
				if (target_elem.getAttribute('data-state') != target_state) {
					/* Searched all childs and no matching data-state found */
					return reject('TARGET_STATE_NOT_FOUND');
				}

				var current_state = parent_elem.getAttribute('data-state');
				var current_elem  = false;
				if (current_state == target_state) {return resolve(parent_elem);}
				if (!current_state) {
					current_elem = parent_elem.firstElementChild;
					while (current_elem.nextElementSibling) {
						if ($E.style.get(current_elem,'display') == 'block'
						 && (current_state = current_elem.getAttribute('data-state'))) {
							break;
						}

						current_elem = current_elem.nextElementSibling;
					}
				} else {
					current_elem = parent_elem.firstElementChild;
					while (current_elem.nextElementSibling) {
						if ($E.style.get(current_elem,'display') == 'block'
						 && (current_state == current_elem.getAttribute('data-state'))) {
							break;
						}

						current_elem = current_elem.nextElementSibling;
					}
				}
				if (current_elem.getAttribute('data-state') != current_state) {
					return reject('CURRENT_STATE_NOT_FOUND');
				}

				parent_elem.setAttribute('data-state',target_state);
				var parent_style = {
					 'width':parent_elem.offsetWidth
					,'height':parent_elem.offsetHeight
					,'position':parent_elem.getBoundingClientRect()
					,'borderTopWidth':parseInt($E.style.get(parent_elem,'border-top-width'))
					,'borderBottomWidth':parseInt($E.style.get(parent_elem,'border-bottom-width'))
					,'borderLeftWidth':parseInt($E.style.get(parent_elem,'border-left-width'))
					,'paddingTop':parseInt($E.style.get(parent_elem,'padding-top'))
					,'paddingBottom':parseInt($E.style.get(parent_elem,'padding-bottom'))
					,'paddingLeft':parseInt($E.style.get(parent_elem,'padding-left'))
					,'paddingRight':parseInt($E.style.get(parent_elem,'padding-right'))
				};

				/* o = next, c = current */
				$E.style.apply(parent_elem,{'.overflow':'hidden','.boxSizing':'border-box','.width':parent_style.width + 'px','.height':parent_style.height + 'px'});
				$E.style.apply(target_elem,{'.position':'absolute','.display':'block','.visibility':'visible','.opacity':0,'.width':(parent_style.width - parent_style.paddingLeft - parent_style.paddingRight) + 'px'});
				$E.style.apply(current_elem,{'.position':'absolute','.width':current_elem.clientWidth + 'px'});
				var newHeight = target_elem.clientHeight + parent_style.paddingTop + parent_style.paddingBottom;

				var promises = [];
				promises.push($fx.height(parent_elem,newHeight));
				promises.push($fx.leave(current_elem));
				promises.push($fx.enter(target_elem));

				Promise.all(promises).then(function(){
					$E.style.apply(current_elem,{'.display':'none'});
					$E.style.apply(target_elem,{'.position':'static','.top':'auto','.left':'auto','.overflow':'visible'});
					$E.style.apply(parent_elem,{'.height':'auto','.overflow':'visible'});

					parent_elem.setAttribute('data-ready','true');
					resolve({'elem':target_elem,'parent':parent_elem});
				},reject);
			});
		}
	};

	function $uniqid(a = "",b = false) {
		var c = Date.now()/1000;
		var d = c.toString(16).split(".").join("");
		while (d.length < 14) {
			d += "0";
		}
		var e = "";
		if (b) {
			e = ".";
			var f = Math.round(Math.random()*100000000);
			e += f;
		}
		return a + d + e;
	}

	function $ajax(url,params) {
		if (!params) {params = {};}
		return new Promise(function (resolve, reject) {
			var method = 'GET';
			var rnd = Math.floor(Math.random() * 10000);
		
			var postdata = false;
			if( params && !params._data && !params._cache ){
				params = {'_data':params};
			}
			if( params._data ){
				method = 'POST';
				switch( true ){
					case ($is.object(params._data)):postdata = new FormData();for( var a in params._data ){postdata.append(a,params._data[a]);}break;
					case ($is.element(params._data) && params._data.tagName && params._data.tagName == 'FORM'):postdata = new postdata(params._data);break;
					default:postdata = params._data;
				}
			}
			if( !params._cache ){
				url += ( url.indexOf('?') > 0 ? '&' : '?' ) + 'rnd=' + rnd;
			}

			var xhr = new XMLHttpRequest();
			xhr.open(method,url,true);
			if( !params._binary ){
				xhr.onreadystatechange = function(){
					if( xhr.readyState == XMLHttpRequest.DONE ){
						return resolve(xhr.responseText);
					}
				};
				xhr.onload = function(){
					return resolve(xhr.responseText,xhr.getAllResponseHeaders());
				};
			}else{
				xhr.responseType = 'arraybuffer';
				xhr.onload = function(){
					return resolve(xhr.response,xhr.getAllResponseHeaders());
				};
			}
			//if(!$is.formData(postdata)){xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');//}
			xhr.send(postdata);
		});
	}

	function print_r(obj,i) {
		var s="";if(!i){i = "    ";}else{i += "    ";}
		if(obj.constructor == Array || obj.constructor == Object){
			for(var p in obj){
				if(!obj[p]){s += i+"["+p+"] => NULL\n";continue;};
				if(obj[p].constructor == Array || obj[p].constructor == Object){
					var t = (obj[p].constructor == Array) ? "Array" : "Object";
					s += i+"["+p+"] => "+t+"\n"+i+"(\n"+print_r(obj[p],i)+i+")\n";
				}else{s += i+"["+p+"] => "+obj[p]+"\n";}
			}
		}
		return s;
	}

	if (window.NodeList && !NodeList.prototype.forEach) {
		NodeList.prototype.forEach = function (callback, thisArg) {
			thisArg = thisArg || window;
			for (var i = 0; i < this.length; i++) {
				callback.call(thisArg, this[i], i, this);
			}
		};
	}
