<?php
	trait __date{
		public $_date_months_es = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
		public $timezone = '';
		function date($country = '',$string = ''){
			if( strlen($country) != 2 ){return false;}
			$identifiers = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY,strtoupper($country));
			if( !$identifiers ){return false;}
			$identifier  = reset($identifiers);
			$d = new DateTime('now',new DateTimeZone($identifier));
			return $d->format($string);
		}
		function _date_monthToString($m = 'mm'){
			if( $m < 1 || $m > 12 ){return false;}
			return $this->_date_months_es[(int)$m-1];
		}
		function _date_es_format_friendly($d = 'dd-mm-yyyy'){
			return '<span class="day">'.substr($d,8,2).'</span> de <span class="month">'.$this->_date_monthToString(substr($d,5,2)).'</span> de <span class="year">'.substr($d,0,4).'</span>';
		}
		function _date_days_between($to = '',$from = ''){
			if( empty($from) ){$from = date('Y-m-d');}
			if( $to < $from ){$tmp = $to;$to = $from;$from = $tmp;unset($tmp);}
			$dates = [];
			while( $from < $to ){
				$from = date('Y-m-d',strtotime('+1 day',strtotime($from)));
				$dates[] = $from;
			}
			return $dates;
		}
		function _date_hours(){
			$hours = range(0,23);
			foreach( $hours as &$hour ){
				$hour = str_pad($hour,2,0,STR_PAD_LEFT);
			}
			unset($hour);
			return $hours;
		}
		function _date_parse($date = ''){
			if( preg_match('!^([0-9]{4})\-([0-9]{2})\-([0-9]{2})$!',$date,$m) ){
				return ['year'=>$m[1],'month'=>$m[2],'day'=>$m[3]];
			}
			return false;
		}
	}
	class _date{
		use __date;
	}
