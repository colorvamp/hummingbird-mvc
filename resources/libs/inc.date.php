<?php
	function date_humanReadable($timestamp = 0){
		/* Borrowed from http://www.patricktalmadge.com/2011/06/30/php-date-in-human-readable-format/ */
		$diff = time() - $timestamp;
		$periods = array('second','minute','hour','day','week','month','years');
		$lengths = array('60','60','24','7','4.35','12');

    // Past or present
    if ($diff >= 0) 
    {
        $ending = 'ago';
    }
    else
    {
        $diff = -$diff;
        $ending = 'to go';
    }

		/* Figure out difference by looping while less than array length
		* and difference is larger than lengths. */
		$len = count($lengths);
		for($j = 0; $j < $len && $diff >= $lengths[$j]; $j++){
			$diff /= $lengths[$j];
		}

		/* Round up */
		$diff = round($diff);

		/* Make plural if needed */
		if($diff > 1){$periods[$j] .= 's';}

		/* Default format */
		$text = $diff.' '.$periods[$j].' '.$ending;

		/* over 24 hours */
		if($j < 3){return $text;}

		// future date over a day formate with year
		if($ending == 'to go'){
			$text = ($j == 3 && $diff == 1) ? 'Tomorrow at '.date('g:i a',$timestamp) : date('F j, Y \a\\t g:i a',$timestamp);
			return $text;
		}

		switch(true){
			case ($j == 3 && $diff == 1): /* Yesterday */
				return 'Yesterday at '.date('g:i a',$timestamp);
			case ($j == 3): /* Less than a week display -- Monday at 5:28pm */
				return date('l \a\\t g:i a',$timestamp);
			case ($j < 6 && !($j == 5 && $diff == 12)): /* Less than a year display -- June 25 at 5:23am */
				return date('F j \a\\t g:i a',$timestamp);
			default: /* if over a year or the same month one year ago -- June 30, 2010 at 5:34pm */
				return date('F j, Y \a\\t g:i a',$timestamp);
		}

		return true;
	}

