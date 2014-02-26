<?php

include_once('iptreader.class.php');
include_once('dice_thrower.class.php');
include_once('armgBot.class.php');
include_once('duel.class.php');

class duelBot extends armgBot {
	protected $dt;
	
	/**
	 * __construct
	 */
	public function __construct() {
		$this->dt = null;
	}
	
	public function setDiceThrower($dt) {
		$this->dt = $dt;
	}
	
	protected function parseCommand($ircMsg, $botCommand){
		switch($botCommand['command']) {
			case 'dice' :
			case 'd' : {
				$this->command_dice($ircMsg, $botCommand['params']);
				break;
			}
			case 'help' :
			default : {
				$this->command_help($ircMsg);
			}
		}
	}
	
	protected function command_help($ircMsg) {
		$this->msg($ircMsg->target, "Commands : !dice, !d");
	}
	
	protected function command_dice($ircMsg, $diceCommand) {
		debug($diceCommand,"Parsing dice command");
		
		$result = $this->dt->parse($diceCommand) ;
		debug($result->str .' = ' . $result->val, "Result") ;
		debug($this->dt->dices);
		$this->msg($ircMsg->target, $ircMsg->nick . ", ". $diceCommand . " : ".$result->str);
	}
	
}
