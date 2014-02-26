<?php

// Hacked and slashed from http://code.google.com/p/phpdiceroller/

/**
 * use 
 * $dt = new DiceThrower ;
 * $result = $dt->parse('12d6+4e4*3') ;
 * echo $result->str .' = '. $result->val ;
 */

class DiceThrower {
	public $ts = "";
	public $text="";
    public $current = 0 ;
    public $explosive = false;
    
    public $dices = array();
	
	public function __construct() {
        $this->initialize() ;
	}
    
    private function initialize() {
        $this->ts = "" ;
        $this->text = "" ;
        $this->current = 0 ;
        
        $this->dices = array();
    }
	
	function Parse($text='') {
        $this->initialize() ;
        if ($text != '') {
            $this->ts = $text ;
        }
		$ov = $this->E($this->ts);        
		return $ov; 
	}
	
	// And here comes all the rules that are implemented for the following grammar: (with @ == empty)
	//	E 	= T Eopt
	//	Eopt 	= '+' T Eopt | '-' T Eopt | '>' N | '<' N | @
	//	T 	= F Topt
	//	Topt	= '*' F Topt | '/' F Topt | @
	// F    = G Fopt
	// Fopt = '>' N | '<' N | @
	// G    = N Gopt
	// Gopt = 'd' N | 'e' N | @   // e is for 'explosive' dice
	//	N	= D Nopt
	//	Nopt	= D Nopt | @
	//	D is a digit
	function E($ts) {
		$ov1 = $this->T($ts);
		$ov2 = $this->Eopt($ts, $ov1);		
        return $ov2;
	}
	
	function Eopt($ts, OutValue $ov) {
		if (($this->current <= strlen($ts) - 1) and ($ts[$this->current] == '+')) {
			$this->current++ ;
			$ov1 = $this->T($ts) ;
			$ov_ = new OutValue($ov->val + $ov1->val, "( ".$ov->str." + ".$ov1->str." )") ;
			$ov2 = $this->Eopt($ts, $ov_) ;			
            return $ov2;
		} 
		elseif (($this->current <= strlen($ts) - 1) and ($ts[$this->current] == '-')) {
			$this->current++ ;
			$ov1 = $this->T($ts);
			$ov_ = new OutValue($ov->val - $ov1->val, "( ".$ov->str." - ".$ov1->str." )");
			$ov2 = $this->Eopt($ts, $ov_);			
            return $ov2;
		}
		return $ov;
	}
	
	function T($ts) {
		$ov1 = $this->F($ts);
		$ov2 = $this->Topt($ts, $ov1);
		return $ov2;
	}
	
	
	function Topt($ts, OutValue $ov) {
		if (($this->current <= strlen($ts) - 1) and ($ts[$this->current] == '*')) {
			$this->current++;
			$ov1 = $this->F($ts);
			$ov_ = new OutValue($ov->val * $ov1->val, "( ".$ov->str." * ".$ov1->str." )");
			//$this->debug("val: ".$ov_->val." str: ".$ov_->str);
			$ov2 = $this->Topt($ts, $ov_);
			return $ov2;
		} elseif (($this->current <= strlen($ts) - 1) and ($ts[$this->current] == '/')) {
			$this->current++;
			$ov1 = $this->F($ts);
			$ov_ = new OutValue($ov->val / $ov1->val, "( ".$ov->str." / ".$ov1->str." )");
			$ov2 = $this->Topt($ts, $ov_);
			return $ov2;			
		}
		return $ov;
	}
	
	function F($ts) {
		$ov1 = $this->G($ts);
		$ov2 = $this->Fopt($ts, $ov1);
		return $ov2;
	}
	
	function Fopt($ts, OutValue $ov) {
		if (($this->current <= strlen($ts) - 1) and ($ts[$this->current] == '>')) {
			$this->current++ ;
			$ov1 = $this->N($ts);

			$successNb = 0;
			foreach($this->dices as $diceVal) {
				if ($diceVal > $ov1->val) {
					$successNb++;
				}
			}
			$this->dices = array();
			$ov2 = new OutValue($successNb, $ov->str);
			return $ov2;
		}
		elseif (($this->current <= strlen($ts) - 1) and ($ts[$this->current] == '<')) {
			$this->current++ ;
			$ov1 = $this->N($ts);

			$successNb = 0;
			foreach($this->dices as $diceVal) {
				if ($diceVal < $ov1->val) {
					$successNb++;
				}
			}
			$this->dices = array();
			$ov2 = new OutValue($successNb, $ov->str);
			return $ov2;
		}
		return $ov;
	}
	
	function G($ts) {
		$ov1 = $this->N($ts);
		$ov2 = $this->Gopt($ts);
		if ( $ov2->val == "") {
			//This is not a dice
			return $ov1;
		} else {
			//This is a dice
			$str = "[ ";
			$val = 0;
			for ($i=0;$i<$ov1->val;$i++){
				$tmp = rand(1,$ov2->val);
				$val += $tmp;
				$str .= $tmp;
				array_push($this->dices, $tmp);
				if ($this->explosive) {
					while ($tmp == $ov2->val) {
						$tmp = rand(1,$ov2->val);
						$val += $tmp;
						$str .= '/'.$tmp;
						array_push($this->dices, $tmp);
					}
				}
				if ( $i< $ov1->val-1) {
					$str .= " , ";
				}
			}
			$str .= " ]";
	
			return new OutValue($val, $str);
		}
	}
	
	function Gopt($ts){
		$this->explosive = false;
		if (($this->current <= strlen($ts) - 1) and ($ts[$this->current] == 'd')) {
			$this->current++ ;
			return $this->N($ts);
		}
		if (($this->current <= strlen($ts) - 1) and ($ts[$this->current] == 'e')) {
			$this->current++ ;
			$this->explosive = true;
			return $this->N($ts);
		}
		return new OutValue("","");
	}
	
	function N($ts){
		$ov = $this->D($ts);
		$val = $ov->val;
		$str = $ov->str;
		
		$ov = $this->Nopt($ts);
		$val .= $ov->val;
		$str .= $ov->str;
		
		return new OutValue( $val*1, $str);
	}
	
	function Nopt($ts) {
        if ($this->current <= strlen($ts) - 1) {
            $cur = $ts[$this->current] ;
        }
        else {
            $cur = "" ;
        }
        if ($cur === "0"
			or $cur === "1"
			or $cur === "2"
			or $cur === "3"
			or $cur === "4"
			or $cur === "5"
			or $cur === "6"
			or $cur === "7"
			or $cur === "8"
			or $cur === "9"
			) {
				$ov = new OutValue($cur, $cur);
				$val = $ov->val;
				$str = $ov->str;

				$this->current++ ;
				
				$ov = $this->Nopt($ts);
				$val .= $ov->val;
				$str .= $ov->str;
				
				return new OutValue( $val, $str);
		}
        else {
            return new OutValue("","");
        }
	}
	
	function D($ts) {
		$cur = $ts[$this->current] ;

		if ($cur === "0"
			or $cur === "1"
			or $cur === "2"
			or $cur === "3"
			or $cur === "4"
			or $cur === "5"
			or $cur === "6"
			or $cur === "7"
			or $cur === "8"
			or $cur === "9"
			) {
			$this->current++ ;
			return new OutValue($cur, $cur);
		} else {
			throw new Exception("Input is incorrect.");
		}
	}
}

class OutValue {
	public $val;
	public $str;
	
	public function __construct($val, $str){
		$this->val = $val; 
        $this->str = $str;
	}
}

?>
