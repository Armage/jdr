<?php

include_once('./armgDB.php') ;
include_once('./armgTpl.php') ;
define('DEBUG', true) ;

function ajax_debug($var, $text="") {
      if (DEBUG) {
		$file = "/tmp/log.txt" ;
		$h = fopen($file, 'a') ;
		if (is_array($var)) {
		  fputs($h, date('d/m/Y H:i:s').' array '.$text."\n") ;
		  foreach($var as $key => $val) {
		    fputs($h, '    ['.$key.'] => :'.$val.":\n") ;
		  }
		}
		else {
		  fputs($h, date('d/m/Y H:i:s').' '.$text. ' :'.$var.":\n") ;
		}
		fclose($h) ;
      }
}

function debug($var, $msg="", $lvl=0) {
	if (DEBUG) {
		if (php_sapi_name() == 'cli') {
			$space_char = " ";
			$cr_char = "\n";
		}
		else {
			$space_char = "&nbsp;";
			$cr_char = "<br />";
		}

		$tabul = str_repeat($space_char . $space_char, $lvl) ; ;
  	
		if (is_array($var)) {
			echo $tabul."$msg (array)".$space_char.$cr_char ;
			foreach($var as $key => $val) {
				debug($val, "[$key]", $lvl+1) ;
			}
		}
		elseif(is_object($var)) {
			$array = array() ;
			$array = (array)$var ;
			echo $tabul ."$msg (object ". get_class($var) .") ".$space_char.$cr_char ;
			debug($array, "", $lvl+1) ;
		}
		elseif(is_bool($var)) {
			$boolean2string = ($var)?"TRUE":"FALSE" ;
			echo $tabul .$msg ." (boolean):". $boolean2string .":".$space_char.$cr_char ; 
		}
		else {
			echo $tabul ."$msg (". gettype($var) ."):$var:".$space_char.$cr_char ;
		}
	}
  
}

function getDateFromMysqlDatetime($mysqlDatetime) {
	list($date, $time) = explode(' ', $mysqlDatetime) ;
	return $date ;
}
