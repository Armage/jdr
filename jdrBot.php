<?php

include_once('iptreader.class.php');
include_once('dice_thrower.class.php');
include_once('armgBot.class.php');

class jdrBot extends armgBot {
	protected $dt, $iptParser;
	var $iptList = array() ;
	
	/**
	 * __construct
	 * @param array $list hash table like : $list = array(array('wild noises' => '/var/www/jdr/ipt/common/medfan/d12/wildernoise.ipt'),) ;
	 */
	public function __construct($list = array()) {
		if (!empty($list)) {
			$this->iptList = $list;
		}
	}
	
	public function setDiceThrower($dt) {
		$this->dt = $dt;
	}
	
	public function setIptParser($iptParser) {
		$this->iptParser = $iptParser;
	}
	
	protected function parseCommand($ircMsg, $botCommand){
		switch($botCommand['command']) {
			case 'dice' :
			case 'd' : {
				$this->command_dice($ircMsg, $botCommand['params']);
				break;
			}
			case 'iptlist' : {
				$this->command_iptlist($ircMsg);
				break;
			}
			case 'ipt' : {
				$this->command_ipt($ircMsg, $botCommand);
				break;
			}
			case 'help' :
			default : {
				$this->command_help($ircMsg);
			}
		}
	}
	
	protected function command_help($ircMsg) {
		$this->msg($ircMsg->target, "Available commands : !dice, !d, !help");
	}
	
	protected function command_dice($ircMsg, $diceCommand) {
		debug($diceCommand,"Parsing dice command");
		if (trim($diceCommand) == 'help') {
			$this->msg($ircMsg->target, "You may launch dices with commands like '!d 1d10', '!d 2d6+4', '2e6' (explosive dices), '6d6>4' (keep dice if result > 4).");
		}
		else {
			try {
				$result = $this->dt->parse($diceCommand) ;
				debug($result->str .' = ' . $result->val, "Result") ;
			
				//$this->msgChan($ircMsg->fromChan, $ircMsg->nick . ", ". $diceCommand . " : ".$result->str .' = '. $result->val);
				$this->msg($ircMsg->target, $ircMsg->nick . ", ". $diceCommand . " : ".$result->str .' = '. $result->val);
			}
			catch(Exception $e) {
				$this->msg($ircMsg->target, "Fatal error, command misinterpreted...");
			}
		}
		
	}
	
	protected function command_iptlist($ircMsg) {
		foreach ($this->iptList as $key => $list) {
			$this->msg($ircMsg->target, $key . " : " . $list['shortname']);
		}
	}
	
	protected function command_ipt($ircMsg, $botCommand) {
		$iptCommand = $this->iptList[$botCommand['params']]['filename'];
		debug($iptCommand, "Parsing ipt command");
		
		$result = explode("\n", $this->iptParse($iptCommand));
		
		foreach($result as $line) {
			$this->msg($ircMsg->target, $line);
		}
	}
	
	/**
	 * iptParser
	 * @param string $file complete path to the file
	 * @return string
	 */
	protected function iptParse($file = '') {
		if (empty($file)) return '' ;
	
		if (!file_exists($file)) return "file $file not found" ;
	
		$lines = file($file); // FIXME security (verify it exists in TOP_DIR...)
	
		// init
		$this->iptParser->reset();
		$this->iptParser->setLines($lines);
		$this->iptParser->parse();
	
		// run
		try {
			$result =  $this->iptParser->run();
			return $result ;
		}
		catch (Exception $e) {
			return $e->getMessage() ;
		}
	}
}
