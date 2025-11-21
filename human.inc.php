<?php
############################################################################################
#
# HumanSize (size); human readable size (1024) = (1kb)
# HumanAgo (timestanp,level,beginning unit); Human Readable "ago" (time(),8,"second")
#
# Partly extended with subfunctions
############################################################################################

define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);

function CompoundInterest($amount = 1, $rate = 1, $time = 1) {
    // Convert the rate from percentage to a decimal
    $rateDecimal = $rate / 100;

    // Calculate the compound interest
    return $amount * pow((1 + $rateDecimal), $time);
}

function HumanPhone($number,$country=false){
	// this function does "just" make a phone number more readable by making sure its Countrycode and/or 0 is in front .. cut off after 4 digits and continue the rest
	// i know this is not following any rules .. but requested by darcy
	
	$number = preg_replace("/[^0-9]/","",$number); // make sure the number is only "numbers"
	
	if ((isset($number{0}))&&($number{0} != "0")) { 
		$number = "0".$number; // add a zero if not already there
	}
	
	if ($country) {
		// we are in a different country so use the users country code instead of a zero
		
	}
	else {
		return substr($number, 0, 5)." ".substr($number, 5);
		
	}
	
}

if (!function_exists(HumanNumber)) {
	function HumanNumber($n) {
		return HumanShort($n);
	}
}
function HumanShort( $n ) {
	if ($n < 1000) {
    	// Anything less than 1k
		$n_format = $n;
	} else if ($n < 1000000) {
    	// Anything less than a million
		$n_format = number_format($n / 1000, 1) . 'K';
	} else if ($n < 1000000000) {
    	// Anything less than a billion
		$n_format = number_format($n / 1000000, 1) . 'M';
	} else {
    	// At least a billion
		$n_format = number_format($n / 1000000000, 1) . 'B';
	}
	return $n_format;
}

function HumanMoney( $amount, $currency ) {
// this function not working yet .. beeing developed in fxfish.php
		switch ($currency):
		case "EUR":
			setlocale(LC_MONETARY, 'nl_NL');
//			$amount = money_format('%(#1n', $amount);	
//			$amount = str_replace('Eu','&euro;',$amount);
		break;

		case "USD":
		default:
			setlocale(LC_MONETARY, 'en_US.UTF-8');
			$amount = money_format('%.2n', $amount);
	endswitch;
	return $amount;
}

function HumanTimeDiff( $from, $to = '' ) {
	if (empty( $to )) { $to = time(); }

	$diff = (int) abs( $to - $from );
	
	if ( $diff <= HOUR_IN_SECONDS ) {
        $mins = @round( $diff / MINUTE_IN_SECONDS );
        if ( $mins <= 1 ) {
        	$mins = 1;
        }
        $since = sprintf('%s min', $mins );
    }
	elseif ( ( $diff <= DAY_IN_SECONDS ) && ( $diff > HOUR_IN_SECONDS ) ) {
		$hours = @round( $diff / HOUR_IN_SECONDS );
		if ( $hours <= 1 ) {
			$hours = 1;
		}
		$since = sprintf('%s hour', $hours );
	}
	elseif ( $diff >= DAY_IN_SECONDS ) {
		$days = @round( $diff / DAY_IN_SECONDS );
		if ( $days <= 1 ) {
			$days = 1;
		}
		$since = sprintf('%s day', $days );
	}
	return $since;
}


function HumanSize ($size, $retstring = null) {
        // adapted from code at http://aidanlister.com/repos/v/function.size_readable.php
        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        if ($retstring === null) { $retstring = '%01.2f %s'; }
        $lastsizestring = end($sizes);
        foreach ($sizes as $sizestring) {
                if ($size < 1024) { break; }
                if ($sizestring != $lastsizestring) { $size /= 1024; }
        }
        if ($sizestring == $sizes[0]) { $retstring = '%01d %s'; } // Bytes aren't normally fractional
        return sprintf($retstring, $size, $sizestring);
}

function HumanNumber ($size, $retstring = null, $fullnames = false) {
        // adapted from code at http://aidanlister.com/repos/v/function.size_readable.php
        if ($fullnames) {
        	$sizes = array('', 'Thousand', 'Million', 'Billion', 'Trillion');
        }
        else {
	        $sizes = array('', 'K', 'M', 'B', 'T');
        }
        if ($retstring === null) { $retstring = '%01.2f %s'; }
        $lastsizestring = end($sizes);
        foreach ($sizes as $sizestring) {
                if ($size < 1024) { break; }
                if ($sizestring != $lastsizestring) { $size /= 1000; }
        }
        if ($sizestring == $sizes[0]) { $retstring = '%01d %s'; } // Bytes aren't normally fractional
        return sprintf($retstring, $size, $sizestring);
}

# max_detail_levels - how deep to go down? If max_detail_levels is set to 2, text will output something like "3 days 4 hours" instead of "3 days 4 hours 10 minutes 55 seconds"
# precision_level - this is what rspenc29 was trying to accomplish. If you want to only report a minimum value of say 1 hour, then you should set this to "hour"

function HumanAgo($timestamp,$max_detail_levels=2, $precision_level='second', $word = "yes", $shortperiods = false, $maxage = false){
    // $word needs to be True/false setting instead of a variable 
    // $maxage = 2592000;
	$detailed=true;
	$now = time();

    	#If the difference is positive "ago" - negative "away"
    	($timestamp >= $now) ? $action = 'away' : $action = 'ago';
//    	($timestamp >= $now) ? $action = 'ago' : $action = 'ago';

    # Set the periods of time
	$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
    if ($shortperiods === true) {
    	$periods = array("sec", "min", "hr", "day", "wk", "mth", "yr", "decade");
    }
    $lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);

    $diff = ($action == 'away' ? $timestamp - $now : $now - $timestamp);
  
    $prec_key = array_search($precision_level,$periods);
  
    # round diff to the precision_level
    $diff = round(($diff/$lengths[$prec_key]))*$lengths[$prec_key];
  
    # if the diff is very small, display for ex "just seconds ago"
    if ((isset($maxage))&&($maxage>0)&&($diff > $maxage)) {
	    // too old entry
		return "some time ".$action;
	}
	if ($diff <= 10) {
        $periodago = max(0,$prec_key-1);
        $agotxt = $periods[$periodago].'s';

        if ($word!="yes") {
    		return $agotxt;
        }
        else {
    		return "just $agotxt $action";
        }
    }
  
    # Go from decades backwards to seconds
    $time = "";
    for ($i = (sizeof($lengths) - 1); $i>0; $i--) {
        if($diff > $lengths[$i-1] && ($max_detail_levels > 0)) {        # if the difference is greater than the length we are checking... continue
            $val = floor($diff / $lengths[$i-1]);    # 65 / 60 = 1.  That means one minute.  130 / 60 = 2. Two minutes.. etc
            $time .= $val ." ". $periods[$i-1].($val > 1 ? 's ' : ' ');  # The value, then the name associated, then add 's' if plural
            $diff -= ($val * $lengths[$i-1]);    # subtract the values we just used from the overall diff so we can find the rest of the information
            if(!$detailed) { $i = 0; }    # if detailed is turn off (default) only show the first set found, else show all information
            $max_detail_levels--;
        }
    }
 
    # Basic error checking.
    if($time == "") {
        return "Error-- Unable to calculate time.";
    } else {
        if ($word!="yes") {
        	return $time;
        }
        else {
           	return $time.$action;
        }
    }
} ?>
