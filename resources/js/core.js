
	var $is = {
		set:	  function(o,path){
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
		array:    function(o){return (Array.isArray(o) || $type(o.length) === 'number');},
		string:   function(o){return (typeof o == 'string' || o instanceof String);},
		object:   function(o){return (o.constructor.toString().indexOf('function Object()') == 0);},
		element:  function(o){return ('nodeType' in o && o.nodeType === 1 && 'cloneNode' in o);},
		function: function(o){return (o.constructor.toString().indexOf('function Function()') == 0);},
		formData: function(o){return (o.constructor.toString().indexOf('function FormData()') == 0);}
	};
	var $json = {
		encode: function(obj){if(JSON.stringify){return JSON.stringify(obj);}},
		decode: function(str){
			if($is.empty(str)){return {errorDescription:"La cadena estÃ¡ vacÃ­a, revise la API o el COMANDO"};}
			if(!$is.string(str)){return {errorDescription:'JSON_ERROR'};}
			str = str.trim();
			if(str.match("<title>404 Not Found</title>")){return {errorDescription:"La URL de la API es errÃ³nea: 404"};}
			if(!JSON || !JSON.parse){return eval('('+str+')');}
			try{return JSON.parse(str.trim());}catch(err){return {errorDescription:str};}
		}
	};

	function print_r(obj,i){
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
