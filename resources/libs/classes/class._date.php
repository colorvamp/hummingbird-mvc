<?php
	trait __date{
		public $_date_months_es = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
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
	}
	class _date{
		use __date;
	}
