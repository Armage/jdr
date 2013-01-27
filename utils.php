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
			$cr_char = "";
		}
		else {
			$space_char = "&nbsp;";
			$cr_char = "<br />";
		}

		if (is_array($var)) {
			echo str_repeat($space_char.$space_char, $lvl) . $msg . $cr_char . "\n" ;
			foreach($var as $key => $val) {
				debug($val, "[$key]", $lvl+1) ;
			}
		}
		else {
			echo str_repeat($space_char.$space_char, $lvl) . $space_char . $msg . " :" . $var . ":" . $cr_char . "\n" ;

		}
	}
}

function getDateFromMysqlDatetime($mysqlDatetime) {
	list($date, $time) = explode(' ', $mysqlDatetime) ;
	return $date ;
}
