<?php

include_once('iptreader.class.php') ;

// IPT (from Inspiration Pad Pro) reader / renderer
// Very partial implementation for the moment... (and it will certainly last so)

try {
    if (php_sapi_name() == 'cli') {
        $opts = getopt('f:t::h', array('file:','help')) ;
    }
    // FIXME, analyse GET variables, if any (web mode)
    
    // help
    if (array_key_exists('h', $opts) or array_key_exists('help', $opts)) {
        echo "SYNTAX : php iptreader.php (-f|--file)=<filename> -t=<# times> [-h|--help]\n" ;
        exit() ;
    }
    
    // file name ?
    $file = '' ;
    if (array_key_exists('f', $opts)) {
        $file = $opts['f'] ;
    }
    if (array_key_exists('file', $opts)) {
        $file = $opts['file'] ;
    }
    
    // number of times you want to run
    $nbTimes = 1 ;
    if (array_key_exists('t', $opts)) {
        $nbTimes = $opts['t'] ;
    }
    
    // ok, go
    $result = '' ;
    for ($i=1 ; $i <= $nbTimes ; $i++) {
    	if(!file_exists($file)) {
    		throw new Exception("file $file not found") ;
    	}
    	$lines = file($file);
    	$dt = new DiceThrower();
        $myIPTReader = new IPTParser();
        $myIPTReader->setLines($lines) ;
        $myIPTReader->setDiceThrower($dt);
        $myIPTReader->parse();
        
        $result .= $myIPTReader->run() ;
    }
    echo $result ;
    
}
catch(Exception $e) {
    // catchall exception
    echo "Exception : ". htmlspecialchars($e->getMessage()) ."\n" ;
}


