
	if ( !window['widgets'] ) { window['widgets'] = {}; }
	widgets.clipboard = function ( elem ) {
		if ( elem.getAttribute('data-widget') || !elem.getAttribute('data-source') ) { return false; }
		elem.setAttribute('data-widget', true);

		var ths = this;
		this.button = elem;

		this.button.addEventListener('click', function ( e ) {
			var source = document.querySelector(this.getAttribute('data-source'));
			if ( !source ) { return false; }

			var tmp_txt = document.createElement('TEXTAREA');
			tmp_txt.style = 'position:absolute;left:-1000px;top:-1000px';
			document.body.appendChild(tmp_txt);
			if ( 'INPUT' == source.nodeName && 'text' == source.type ) { tmp_txt.innerHTML = source.value; }
			else { tmp_txt.innerHTML = source.innerHTML; }
			tmp_txt.select();
			var status = document.execCommand('copy');
			document.body.removeChild(tmp_txt);
		});
	};

	function widgets_clipboard_init ( e ) {
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('.widget-clipboard'));
		nodeList.forEach(function ( v, k ) { new widgets.clipboard(v); });
	};

	addEventListener('DOMContentLoaded', widgets_clipboard_init);
	if ( document.readyState === 'complete' || document.readyState === 'loaded' || document.readyState === 'interactive' ) { widgets_clipboard_init(); }
