<?php

include_once('utils.php') ;
include_once('dice_thrower.class.php') ;

/***
 * FIXME: do a factory...
 * 
 * Example of use : 
 * $lines = file('foobar.ipt');
 * $dt = new diceThrower;
 * $iptParser = new IPTParser();
 * $iptParser->setLines($lines);
 * $iptParser->setDiceThrower = $dt;
 * $iptParser->parse();
 * 
 * $iptParser->run(); // run main table
 * $iptParser->run('table'); // run table
 *
 */
class IPTParser {
	protected $lines ; // lines to interpret
	protected $tables ; // array of IPTTable classes, key is table name

	private $diceThrower ;
	private $currentTableName ;
	private $mainTableName ; // first table found !

	/*
	 * @param array $lines ipt lines to interpret ( something like the result of "file('truc.ipt')" )
	*/
	function __construct() {
		//$this->setLines($lines) ;
		$this->tables = array() ;
		$this->currentTableName = '' ;
	}

	function parse() {
		$lines = $this->lines ;

		foreach($lines as $key => $line) {
			$line = trim($line) ;

			// empty line
			if (strlen($line) <= 0) {
				$this->currentTableName = '' ;
				continue ;
			}

			// comment line
			if ($line[0] == ";" or $line[0] == "#") {
				//if (preg_match("/^;/", $line) > 0) {
				continue ;
			}

			// use line
			if (strpos($line, 'use:') === 0) {
				// not yet implemented ;)
				continue ;
			}

			// tablename line
			if (strpos($line, 'table:') === 0) { // FIXME regexp ?
				list(, $tablename) = explode(':', $line) ;
				$this->addTable($tablename) ;
				continue ;
			}
			
			// shuffle line
			if (strpos($line, 'shuffle:') === 0) { // FIXME regexp ?
				if ($this->currentTableName == '') {
					throw new Exception ("line $key not in a table (maybe a misplaced shuffle line ?)") ;
				}
				list(, $tablename) = explode(':', $line) ;
				$this->addShuffle(trim($tablename), $this->currentTableName) ;
				unset($lines[$key]) ;
			}
			
			// roll line
			if (strpos($line, 'roll:') === 0) { // FIXME regexp ?
				// not yet implemented ;)
				continue ;
			}

			// command line
			else {
				if ($this->currentTableName == '') {
					throw new Exception ("line $key not in a table (maybe a misplaced empty line ?)") ;
				}
				$this->addLine($line, $this->currentTableName) ;
			}
		}

		// run the main table (first found) ;
		//$command = $this->tables[$this->mainTableName]->run($this)."\n" ;
		//return $command ;
	}
	
	function run($table='') {
		if (empty($table)) {
			$table = $this->mainTableName;
		}
		if (!in_array($table, array_keys($this->tables))) {
			throw new Exception ("Table $table not in my lines...") ;
			return;
		}
		
		$result = $this->tables[$table]->run($this)."\n" ;
		return $result ;
	}

	public function setLines($lines) {
		if(empty($lines) or !is_array($lines)) {
			throw new Exception("IptParser needs an array of ipt lines as input...") ;
		}
		$this->lines = $lines ;
	}
	
	public function reset() {
		$this->lines = array();
		$this->tables = array();
	}

	public function setDiceThrower($dt) {
		// FIXME which control ?
		$this->diceThrower = $dt ;
	}

	private function addTable($tableName) {
		$tableName = trim($tableName) ;
		if (array_key_exists($tableName, $this->tables)) {
			throw new Exception("table $tableName set more than once") ;
		}
		if (count($this->tables) <= 0) {
			$this->mainTableName = $tableName ;
		}
		$this->tables[$tableName] = new IPTTable() ;
		$this->currentTableName = $tableName ;
	}

	private function addLine($line, $tableName) {
		if (!array_key_exists($tableName, $this->tables)) {
			throw new Exception ("line can't be inserted in $tableName (table $tableName not found)") ;
		}
		$this->tables[$tableName]->addLine($line) ;
	}
	
	private function addShuffle($tableName, $currentTableName) {
		$this->tables[$currentTableName]->addShuffle($tableName) ;
	}
	
	function shuffle($table) {
		if (!array_key_exists($table, $this->tables)) {
			throw new Exception ("Can't shuffle $table (table $table not found)") ;
		}
		$this->tables[$table]->shuffle();
	}

	function getTable($tableName) {
		if (!array_key_exists($tableName, $this->tables)) {
			throw new Exception ("table $tableName not found") ;
		}
		return $this->tables [$tableName] ;
	}

	function throwDices($expr) {
		return $this->diceThrower->parse($expr) ;
	}
}

class IPTTable {
    private $lines ; // array of lines. line is array('x1'=>, 'x2'=>, 'command'=>)
    private $hiddenLineKeys; // array of unselectable line keys (used for deck pick in [!n table] command)
    private $shuffleTables; // array of table names that must be shuffle when $this is run
    private $memoryLine ; // use to store a line when ending with a "&", waiting to complete the command part
    
    function __construct() {
        $this->lines = array() ;
        $this->hiddenLineKeys = array();
        $this->shuffleTables = array();
        $this->memoryLine = '' ;
    }
    
    function addLine($line='') {
        $iptLine = new IPTLine($line) ;
        
        if ($this->memoryLine === '') {
            // weight or range ?
            if($iptLine->getWeight() !== '') {
                // absolu
                // FIXME, ranges must be contiguous from line to line
                if (strpos($iptLine->getWeight(), '-') !== false) {
                    list($x1, $x2) = explode('-', $iptLine->getWeight()) ;
                    $x1 = intval($x1) ;
                    $x2 = intval($x2) ;
                }
                // relatif
                else {
                    $relativeWeight = intval($iptLine->getWeight()) ;
                    if (count($this->lines) > 0) {
                        $precLine = $this->lines[count($this->lines)-1] ;
                        $x1 = $precLine['x2'] + 1 ;
                        $x2 = $precLine['x2'] + $relativeWeight ;
                    }
                    else {
                        $x1 = 1 ;
                        $x2 = $relativeWeight ;
                    }
                }
            }
            else { // should be impossible (IPTLine returns always a weight)
                $x1 = 0 ;
                $x2 = 0 ;
            }
        }
        
        if (preg_match("/&$/", $line) > 0) {
            if ($this->memoryLine !== '') {
                $this->memoryLine['command'] .= rtrim($iptLine->getCommand(), '&') ;
            }
            else {
                $this->memoryLine = array(
                    'x1' => $x1,
                    'x2' => $x2,
                    'command' => rtrim($iptLine->getCommand(), '&'),
                ) ;
            }
        }
        else {
            if ($this->memoryLine !== '') {
                $this->memoryLine['command'] .= $iptLine->getCommand() ;
                array_push($this->lines, $this->memoryLine) ;
                $this->memoryLine = '' ;
            }
            else {
                array_push(
                    $this->lines,
                    array(
                        'x1' => $x1,
                        'x2' => $x2,
                        'command' => $iptLine->getCommand(),
                    )
                ) ;
            }
            
        }
        unset($iptLine) ;
    }
    
    function addShuffle($tablename) {
    	array_push($this->shuffleTables, $tablename);
    }
    
    function shuffle() {
    	$this->hiddenLineKeys = array();
    }
    
    function run($iptReader, $keep = true) {
        $command = '' ;
      
        // shuffle
        if (!empty($this->shuffleTables)) {
        	foreach($this->shuffleTables as $table) {
        		$iptReader->shuffle($table);
        	}
        }
        
        // randomly choose a line, respecting the line's weight
        if (count($this->lines) > 0) {
            $firstLine = $this->lines[0] ;
            $lastLine = $this->lines[count($this->lines) - 1] ;
            
			$found = false;
			while (!$found) {
				$rnd = mt_rand($firstLine['x1'], $lastLine['x2']) ;
				foreach($this->lines as $key => $line) {
					if (in_array($key, $this->hiddenLineKeys)) {
						continue;
					}
					if ($rnd < $line['x1']) {
						continue ;
					}
					if ($rnd > $line['x2']) {
						continue ;
					}
					$found = true;
					break ;
				}
			}
            
            $command = $this->lines[$key]['command'] ;
            if (!$keep) {
            	array_push($this->hiddenLineKeys, $key);
            }
        }
        
        // inline call command
        $command = $this->interpretInlineCallCommand($command) ;
        
        // subtable call command
        $command = $this->interpretCallCommand($command, $iptReader) ;

        // is there some dice commands ?
        $command = $this->interpretDiceCommand($command, $iptReader) ;
        
        // cleaning
        $command = str_replace("\\n", "\n", $command) ;
        
        return $command ;
    }
    
    // inline call [|item1|item2|item3...]
    private function interpretInlineCallCommand($command) {
        if ($nbMatch = preg_match_all("/\[(\|[^\]]*)\]/", $command, $matches, PREG_SET_ORDER) > 0) {
            if (count($matches) > 0) {
                foreach($matches as $match) {
                    $callCommand = ltrim($match[1], '|') ;
                    $items = array() ;
                    $items = explode('|', $callCommand) ;
                    $item = $items[mt_rand(0, count($items) - 1)] ;
                    
                    $command = preg_replace("/\[\|([^\]]*)\]/", $item, $command, 1) ;
                }
            }
        }
        return $command ;
    }
    
    // table call [@n tablename >> filter] or [@{dice_expr} tablename >> filter]
    private function interpretCallCommand($command, $iptReader, $fromDice = false) {
        if ($nbMatch = preg_match_all("/\[([@|!][^\]]*)\]/", $command, $matches, PREG_SET_ORDER) > 0) {
            if (count($matches) > 0) {
                foreach($matches as $match) {
                    // match[1] => call command without '[' nor ']'
                    
                    // if there is a nested dice command
                    if (!$fromDice) {
                        $callCommand = "[". $this->interpretDiceCommand($match[1], $iptReader) ."]" ;
                    }
                    
                    // run the call command
                    if ($nbMatch = preg_match_all("/\[([@|!])(\d* ){0,1}([^\] ]*)( >> ([^\] ]*)( [^\]]*){0,1}){0,1}\]$/", $callCommand, $matches, PREG_SET_ORDER) > 0) {
                        if (count($matches) > 0) {
                            foreach($matches as $match) {
                                // $match[1] => @ ou !
                            	// $match[2] => n
                                // $match[3] => tablename
                                // $match[4] =>  >> filter param
                                // $match[5] => filter
                                // $match[6] =>  param
                                $keep = true;
                                if ($match[1] == '!') {
                                	$keep = false;
                                }
                            	
                                $n = 1 ;
                                if ($match[2] !== '') {
                                    $n = intval($match[2]) ;
                                }
                                
                                $tableName = $match[3] ;
                                $tableName = trim($tableName) ;
                                $iptTable = $iptReader->getTable($tableName) ;
                                $result = '' ;
                                
                                $results = array() ;
                                for ($i = 1 ; $i <= $n ; $i++) {
                                    array_push($results, $iptTable->run($iptReader, $keep)) ;
                                }
                                
                                // applies filter
                                if (count($match) > 5) {
                                    if (count($match) > 6) {
                                        // filter with parameters
                                        $result = $this->callFilter($results, $match[5], $match[6]) ;
                                    }
                                    else {
                                        // filter without parameter
                                        $result = $this->callFilter($results, $match[5]) ;
                                    }
                                    
                                    if (is_array($result)) {
                                        $result = join('', $result) ;
                                    }
                                }
                                else {
                                    $result = join('', $results) ;
                                }
                                
                                $result = rtrim($result, " ") ;
                                $command = preg_replace("/\[[@|!]([^ ]* ){0,1}". $tableName. "( >> ([^\] ]*)( [^\]]*){0,1}){0,1}\]/", $result, $command, 1) ;
                            }
                        }
                    }
                }
            }
		}
        return $command ;
    }
    
    // dice call {xdy+n} +,-,*,/ are ok, no "(" nor ")" for now
    private function interpretDiceCommand($command, $iptReader) {
        while ($nbMatch = preg_match_all("/([^\{]*)\{([^\}]*)}(.*)/", $command, $matches, PREG_SET_ORDER) > 0) {
            if (count($matches) > 0) {
                foreach($matches as $match) {
                    $diceExpr = $match[2] ;
                    $diceExpr = $this->interpretCallCommand($diceExpr, $iptReader, true) ;                    
                    $diceExpr = $iptReader->throwDices($diceExpr)->val ;
                    $command = preg_replace("/[^\}]*\{([^\}]*)}(.*)/", $match[1] . $diceExpr . $match[3], $command, 1) ;
                } 
            }
        }
        return $command ;
    }
    
    // filters
    private function callFilter(&$items, $filter, $params=', ') {
    	// TODO (enchainement des filtres)
    	// si $params est du type truc >> filter2 params2,
    	// alors faire la liste des filres, puis les appliquer un par un, dans un ordre logique... (sort avant implode avant bold par exemple) 
    	$str = '';
$params = "params1 >> filter2 params2 >> filter3 params3" ;
debug($params, 'params');

		$filters[1] = $filter;

		preg_match_all("/^([^>]*)( >> .*)?$/", $params, $matches, PREG_SET_ORDER) ;
		if (count($matches) > 0) {
			$matches = $matches[0];
			// $matches[1] => params;
			// $matches[2] => le reste
			$params1 = $matches[1];
		}

    	if ($nbMatch = preg_match_all("/(>> ([^ >]*) {0,1}([^> ]*)?)/", $params, $matches, PREG_SET_ORDER) > 0) {
debug($items, 'items');
debug($filter, 'filter');
debug($params, 'params2');
debug($matches, 'matches');
    		if (count($matches) > 0) {
    			foreach($matches as $key => $match) {
    			// $match[2] => filter
    			// $match[3] => param
    			$filters[$key+2] = $match[3];
    			}
    		}
    			
    		/*	// applies filter
    			if (count($matches) > 3) {
    				if (count($matches) > 4) {
    					// filter with parameters
    					$str .= $this->callFilter($items, $matches[3], $matches[4]) ;
    				}
    				else {
    					// filter without parameter
    					$str .= $this->callFilter($items, $matches[3]) ;
    				}
    			}
    		}*/
    	}
    	
        $filter = trim($filter) ;
        switch($filter) {
            case 'implode' : {
                return join($params, $items) . $str ;
            }
            case 'left' : {
                $params = 1 ;
                if ($params !== '') {
                    $params = intval($params) ;
                }
                return substr($items, 0, $params) . $str ;                
            }
            case 'sort' : {
                sort($items) ;
                return join('' ,$items) . $str;
            }
        }
    }
}

class IPTLine {
    private $weight ;
    private $command ;
    
    function __construct($line) {
        $this->weight = 1 ;
        $command = '' ;
    
        // FIXME check line validity

        $this->analyseLine($line) ;
    }
    
    function analyseLine($line) {
        // range, weight ?
        //if (strpos($line, ':') !== false) {
        if (preg_match("/[^\\\]:/", $line) > 0) {
            $parts = explode(':', $line) ;
            $weight = array_shift($parts) ;
            $command = join(':', $parts) ;
            $this->weight = trim($weight) ;
            if ($this->weight <= 0) {
                $this->weight = 1 ;
            }
            $this->command = trim($command) ;
        }
        else {
            $this->command = trim($line) ;
        }
        
        // special chars
        $this->command = str_replace('\:', ':', $this->command) ;
        $this->command = str_replace('\_', ' ', $this->command);
    }
    
    function getWeight() {
        return $this->weight ;
    }
    
    function getCommand() {
        return $this->command ;
    }
    
}

// class IPTReader {
// 	protected $file ; // file to read
// 	protected $tables ; // array of IPTTable classes, key is table name

// 	private $diceThrower ;
// 	private $currentTableName ;
// 	private $mainTableName ; // first table found !

// 	/*
// 	 * var string $file filepath of the .ipt file
// 	*/
// 	function __construct($file) {
// 		$this->setFile($file) ;
// 		$this->tables = array() ;
// 		$this->setDiceThrower() ;
// 		$this->currentTableName = '' ;
// 	}

// 	function run() {
// 		$lines = $this->getLines() ;

// 		foreach($lines as $key => $line) {
// 			$line = trim($line) ;

// 			// empty line
// 			if (strlen($line) <= 0) {
// 				$this->currentTableName = '' ;
// 				continue ;
// 			}

// 			// comment line
// 			if ($line[0] == ";" or $line[0] == "#") {
// 				//if (preg_match("/^;/", $line) > 0) {
// 				continue ;
// 			}

// 			// use line
// 			if (strpos($line, 'use:') === 0) {
// 				// not yet implemented ;)
// 				continue ;
// 			}

// 			// tablename line
// 			if (strpos($line, 'table:') === 0) { // FIXME regexp ?
// 				list(, $tablename) = explode(':', $line) ;
// 				$this->addTable($tablename) ;
// 			}

// 			// command line
// 			else {
// 				if ($this->currentTableName == '') {
// 					throw new Exception ("line $key not in a table (maybe a misplaced empty line ?)") ;
// 				}
// 				$this->addLine($line, $this->currentTableName) ;
// 			}
// 		}

// 		// run the main table (first found) ;
// 		$command = $this->tables[$this->mainTableName]->run($this)."\n" ;
// 		return $command ;
// 	}

// 	private function setFile($file) {
// 		if(!file_exists($file)) {
// 			throw new Exception("file $file not found") ;
// 		}
// 		$this->file = $file ;
// 	}

// 	private function getLines() {
// 		return file($this->file, FILE_IGNORE_NEW_LINES) ;
// 	}

// 	private function setDiceThrower() {
// 		$this->diceThrower = new DiceThrower ;
// 	}

// 	private function addTable($tableName) {
// 		$tableName = trim($tableName) ;
// 		if (array_key_exists($tableName, $this->tables)) {
// 			throw new Exception("table $tableName set more than once") ;
// 		}
// 		if (count($this->tables) <= 0) {
// 			$this->mainTableName = $tableName ;
// 		}
// 		$this->tables[$tableName] = new IPTTable() ;
// 		$this->currentTableName = $tableName ;
// 	}

// 	private function addLine($line, $tableName) {
// 		if (!array_key_exists($tableName, $this->tables)) {
// 			throw new Exception ("line can't be inserted in $tableName (table $tableName not found)") ;
// 		}
// 		$this->tables[$tableName]->addLine($line) ;
// 	}

// 	function getTable($tableName) {
// 		if (!array_key_exists($tableName, $this->tables)) {
// 			throw new Exception ("table $tableName not found") ;
// 		}
// 		return $this->tables [$tableName] ;
// 	}

// 	function throwDices($expr) {
// 		return $this->diceThrower->parse($expr) ;
// 	}
// }
