
	if(!window['widgets']){window['widgets'] = {};}
	widgets.calendar = function(h,callback){
		this.vars = {holder:"",month:0};

		this.input  = h;
		this.input.style.display = 'none';
		this._container = document.createElement('DIV');
		this._container.classList.add('widget-calendar');
		this.input.parentNode.insertBefore(this._container,this.input);
		this.render(0);

		/* Modifiers */
		this.vars.maxDate = this.input.getAttribute('data-max');
		this.vars.minDate = this.input.getAttribute('data-min');
		this.vars.multi   = this.input.getAttribute('data-multi');

		if(callback){this.dayDetails = callback;}
	};
	widgets.calendar.prototype.getMonthsNames = function(){
		return ["","January","February","March","April","May","June","July","August","September","October","November","December"];
		return ["","Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
	};
	widgets.calendar.prototype.getDaysNames = function(){
		return ["","Mon","Tue","Wed","Thu","Fri","Sat","Sun"];
		return ["","Lun","Mar","Mie","Jue","Vie","Sab","Dom"];
	};
	widgets.calendar.prototype.getMonthDays = function(m,a){var f = (((a % 4 == 0) && (a % 100 != 0)) || (a % 400 == 0)) ? 29 : 28;var months = [31,f,31,30,31,30,31,31,30,31,30,31];return months[m-1];};
	widgets.calendar.prototype.getDateStandar = function(y,m,d){
		if( y instanceof Date ){
			d = y.getDate();
			m = y.getMonth() + 1;
			y = y.getFullYear();
		}
		return y + '-' + ( m < 10 ? '0' : '' ) + m + '-' + ( d < 10 ? '0' : '' ) + d;
	};
	widgets.calendar.prototype.getRange = function(date_ini,date_end){
		if( date_end < date_ini ){
			/* Lower dates first */
			var tmp  = date_end;
			date_end = date_ini;
			date_ini = tmp;
		}

		var dates = [],
		dateCurrent = new Date(date_ini),
		dateTarget  = new Date(date_end),
		addDays = function(days) {
			var date = new Date(this.valueOf());
			date.setDate(date.getDate() + days);
			return date;
		};

		while( dateCurrent <= dateTarget ) {
			dates.push(this.getDateStandar(dateCurrent));
			dateCurrent = addDays.call(dateCurrent,1);
		}
		return dates;
	};
	widgets.calendar.prototype.select = function(date_ini,date_end){
		var square_ini = this._container.querySelector('td[data-date="' + date_ini + '"]');
		if( !square_ini ){return false;}
		if( !date_end ){date_end = date_ini;}
		if( this.vars.multi ){this.vars.date_ini = date_ini;}

		if( date_end == date_ini ){
			if( this.vars.maxDate && date_ini > this.vars.maxDate ){return false;}
			if( this.vars.minDate && date_ini < this.vars.minDate ){return false;}
			this.unselect();
			this.input.value = date_ini + '|' + date_end;
			if( date_ini == date_end ){this.input.value = date_ini;}
			square_ini.classList.add('selected');
		}else{
			var _range = this.getRange(date_ini,date_end);
			this.unselect();
			_range.forEach(function(date){
				var square = this._container.querySelector('td[data-date="' + date + '"]');
				//if( this.vars.maxDate && date_ini > this.vars.maxDate ){return false;}
				//if( this.vars.minDate && date_ini < this.vars.minDate ){return false;}
				square.classList.add('selected');
			}.bind(this))
			this.input.value = date_ini + '|' + date_end;
		}
	};
	widgets.calendar.prototype.unselect = function(){
		this.input.value = '';
		var elems = this._container.querySelectorAll('.selected');
		Array.prototype.slice.call(elems).forEach(function(v,k){
			v.classList.remove('selected');
		});
	};
	widgets.calendar.prototype.render = function(monthOffset){
		this.vars.month = monthOffset;
		var ths = this;
		var now = new Date();
		var year  = now.getFullYear();
		var month = now.getMonth()+monthOffset+1;
		while(month > 12){month-=12;year++;}
		while(month < 1){month+=12;year--;}

		this._container.innerHTML = '';
		var head = $C("DIV",{className:"widget-calendar-head"},this._container);
		var d = $C("DIV",{className:"right pointer",innerHTML:"&lt;"},head);
		d.addEventListener('click',function(){ths.render(ths.vars.month-12);});
		$C("DIV",{className:"year",innerHTML:year},head);
		var d = $C("DIV",{className:"right pointer",innerHTML:"&gt;"},head);
		d.addEventListener('click',function(){ths.render(ths.vars.month+12);});

		var d = $C("DIV",{className:"left pointer",innerHTML:"&lt;"},head);
		d.addEventListener('click',function(){ths.render(ths.vars.month-1);});
		$C("DIV",{className:"month",innerHTML:this.getMonthsNames()[month]},head);
		var d = $C("DIV",{className:"left pointer",innerHTML:"&gt;"},head);
		d.addEventListener('click',function(){ths.render(ths.vars.month+1);});

		var table = $C("TABLE",{cellPadding:0,cellSpacing:0},this._container);
		var tbody = $C("TBODY",{},table);
		var tr    = document.createElement('TR');
		tr.classList.add('widget-calendar-day-names');
		tbody.appendChild(tr);
		this.getDaysNames().forEach(function(elem){
			var td = document.createElement('TD');
			td.innerHTML = '<div>' + elem + '</div>';
			tr.appendChild(td);
		});

		var firstDay  = new Date(year,month-1,1).getDay();
		if( firstDay == 0 ){firstDay = 7;}
		var monthDays = this.getMonthDays(month,year);
		var numTrs    = Math.ceil((monthDays+firstDay)/7);

		var m = month-1;
		var totalDays = new Date(year,0,1).getDay();
		if( totalDays == 0 ){totalDays = 7;}
		while(m>0){
			totalDays += this.getMonthDays(m,year);
			m--;
		}
		var initWeek = Math.ceil(totalDays/7);

		var row = 0;
		while(numTrs--){
			row++;

			var tr = $C("TR",{className:"week"},tbody);
			$C("TD",{className:"week-number",innerHTML:initWeek},tr);
			initWeek++;

			for( var col = 1; col <= 7; col++ ){
				var day  = (row * 7) + col - firstDay - 6;
				this.render_day(year,month,day,monthDays,tr);
			}
		}
		return;
	};
	widgets.calendar.prototype.render_day = function(year,month,day,monthDays,tr){
		var date = this.getDateStandar(year,month,day);
		var cls  = 'normal day';
		if( this.vars.maxDate && date > this.vars.maxDate ){cls += ' disabled';}
		if( this.vars.minDate && date < this.vars.minDate ){cls += ' disabled';}
		if( date == this.input.value ){cls += ' selected';}

		/* Si son los dias restantes del mes anterior */
		if( day < 1 ){var td = $C("TD",{className:"empty day",innerHTML:""},tr);return false;}
		/* Si son los dias del mes siguiente */
		if( day > monthDays ){var td = $C("TD",{className:"empty day",innerHTML:""},tr);return false;}

		var td = $C('TD',{className:cls,innerHTML:day},tr);
		td.setAttribute('data-date',date);
		td.setAttribute('data-day',day);
		td.setAttribute('data-month',month);
		td.setAttribute('data-year',year);
		td.addEventListener('click',function(e){
			this.select(date,this.vars.date_ini);
		}.bind(this));
	};

	addEventListener('load',function(e){
		var nodeList = Array.prototype.slice.call(document.querySelectorAll('input[type="calendar"]'));
		nodeList.forEach(function(v,k){
			new widgets.calendar(v);
		});
	});
