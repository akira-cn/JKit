<?php defined('SYSPATH') or die('No direct script access.');

class JKit_Valid extends Kohana_Valid{
	/**
	 * 校验身份证
	 */
	public static function idnumber($str) {

		if(preg_match ( "/^[0-9]{15}$/D", $str )){ //15位
		    if (Valid::date('19'.substr($str,6,6))){
				return true;
			}else{
				return false;
			}
		}

		if (preg_match ( "/^[0-9]{17}[0-9xX]$/D", $str )) { //18位
			$wi = array (7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1 );
			$checkCode = array ('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2' );
			$sum = 0;
			for($i = 0; $i < 17; $i ++) {
				$ai = intval ( substr ( $str, $i, 1 ) );
				$sum += $ai * $wi [$i];
			}
			if ($checkCode [$sum % 11] == substr ( $str, - 1, 1 )){
			    if (Valid::date(substr($str,6,8))){
				    return true;
			    }else{
				    return false;
			    }
			}
			else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Tests if a string is a valid date string.
	 *
	 * @param   string   date to check
	 * @return  boolean
	 */
	public static function date($str)
	{
		return strlen($str) > 1 && (strtotime($str) !== FALSE);
	}

	public static function date_range($data, $from, $to){
		return Valid::range(strtotime($data), strtotime($from), strtotime($to));
	}
	
	public static function min_length($value, $length, $type='text'){
		if($type == 'bytetext'){
			return strlen($value) >= $length;
		}
		else if($type == 'text'){
			return UTF8::strlen($value) >= $length;
		}
		else if($type == 'richtext'){
			return HTML::strlen($value) >= $length;
		}
	}

	public static function max_length($value, $length, $type='text'){
		if($type == 'bytetext'){
			return strlen($value) <= $length;
		}
		else if($type == 'text'){
			return UTF8::strlen($value) <= $length;
		}
		else if($type == 'richtext'){
			return HTML::strlen($value) <= $length;
		}
	}

	public static function decimal($str, $places = 2, $digits = NULL)
	{
		if ($digits > 0)
		{
			// Specific number of digits
			$digits = '{1,'.( (int) $digits).'}';
		}
		else
		{
			// Any number of digits
			$digits = '+';
		}

		// Get the decimal point for the current locale
		list($decimal) = array_values(localeconv());

		return (bool) preg_match('/^[+-]?[0-9]'.$digits.preg_quote($decimal).($place==0?'?':'').'[0-9]{0,'.( (int) $places).'}$/D', $str);
	}
};