<?php


class armgBot {
	protected $socket, $line, $ircMsg;
	public $nick, $ident, $realname, $host, $port;
	
	public function connect($nick, $ident, $realname, $host, $port) {
		$this->nick 		= $nick;
		$this->ident 		= $ident;
		$this->realname 	= $realname;
		$this->host 		= $host;
		$this->port 		= $port;
	
		$this->ircMsg = new IRCMsg();
		
		$this->socket = fsockopen($host, $port, $erno, $errstr, 30);
		if(!$this->socket) die("Could not connect\r\n");
	
		$this->sendMsg("USER ".$ident." ".$host." bla :".$realname);
		$this->sendMsg("NICK ".$nick);
		
		$this->flush();
	}
	
	public function loop() {
		while (!feof($this->socket)) {
			$this->line = fgets($this->socket, 1024);
			$this->ircMsg->parseInput($this->line);
			
			if($this->is_ping($this->ircMsg->command)) {
				$this->pong();
			}

			if($this->is_privmsg($this->ircMsg->command)) {
				debug("PRIVMSG...");
				if (!$this->isForMe($this->ircMsg->param)) {
					continue;
				}
				
				// is this a command?
				if($botCommand = $this->getCommand($this->ircMsg->msg)) {
					debug($botCommand['command'], "processing command");
					$this->parseCommand($this->ircMsg, $botCommand);
				}
				else {
					$this->command_chat_help($this->ircMsg);
				}
			}

			$this->line = "";
			$this->flush();
			$this->wait(); // time to next cycle
		}
	
	}
	
	protected function wait()	{
		usleep(100000);
	}
	
	function flush() {
		@ob_flush; 
		@flush();
	}
	
	protected function sendMsg($str) {
		if (empty($str)) {
			return false;
		}
		fwrite($this->socket, $str."\r\n");
	}
	
	protected function pong() {
		$this->sendMsg("PONG :".$this->host);
	}
	
	public function joinChan($channel) { 
		$this->sendMsg("JOIN :".$channel);
	}
	
	function msg($target, $msg) {
		$this->sendMsg("PRIVMSG $target :$msg");
	}
	
	function msgChan($channel, $msg) {
		$this->msg($channel, $msg);
	}
	
	function msgUser($user, $msg) {
		$this->msg($user, $msg);
	}
	
	protected function is_ping($command) {
		if ($command == 'PING') return true;
	}
	
	protected function is_privmsg($command) {
		if ($command == 'PRIVMSG') return true;
	}
	
	protected function isForMe($input) {
		$str = explode(':', $input);
		
		$pattern = "/^".$this->nick."/";
		if (preg_match($pattern,$str[1]) == 0) {
			return false;
		}
		return true;
	}
	
	/**
	 * Get and parse bot command, if exists
	 * @param string $msg
	 * @return array Keys : 'command' and 'params' or FALSE if no command
	 */
	protected function getCommand($msg) {
		if(!strstr($msg,"!")) {
			return false;
		}
		
		$botCommand = array();
		
		$str = explode("!", $msg);
		$parts = explode(" ", $str[1]);
		$botCommand['command'] = trim($parts[0]);
		$botCommand['params'] = trim(join (" ", array_splice($parts, 1)));
		
		return $botCommand;
	}
	
	protected function parseCommand($ircMsg, $botCommand){
		switch($botCommand['command']) {
			case 'help' :
			default : {
				$this->command_help($ircMsg);
			}
		}
	}
	
	protected function command_chat_help($ircMsg) {
		static $msgChatIndex = 0;
		$msgChat = array(
			"I'm not a chat bot ! Please ask for command with \"!\". Like \"!help\" :)",
			"I've already said that I'm *not* a chat bot... please give command prefixed by \"!\". Type  \"!help\" for more.",
			"Oo !!! Is there anything you do not understand ??? Ask !help.",
			"Okayyy, back to the beginning...",
		);
		
// 		$this->msgChan($ircMsg->fromChan, $msgChat[$msgChatIndex]);
// 		$msgChatIndex++;
// 		if ($msgChatIndex > 3) {
// 			$msgChatIndex = 0;
// 		}
		
		$this->msgChan($ircMsg->fromChan,$msgChat[0]);
	}
	
	protected function command_help($ircMsg) {
		$this->msgChan($ircMsg->fromChan, "Hello, I'm a bot. This is standard help message for this bot.");
	}
	
// 	function setNick($nick)						{
// 		$this->out("NICK ".$nick."\r\n"); $this->nick = $nick;
// 	}
// 	function joinChan($channel) 			{
// 		$this->out("JOIN :".$channel."\r\n");
// 	}
// 	function quitChan($channel) 			{
// 		$this->out("PART :".$channel."\r\n");
// 	}
	
// 	function listChans() 							{
// 		$this->out("LIST\r\n");
// 	}
// 	function getTopic($channel)				{
// 		$this->out("TOPIC ".$channel."\r\n");
// 	}
	
}

/**
 * Utility class : IRCMsg
 *
 */
class IRCMsg {
	public $input;
	public $prefix;
	public $command;
	public $param;
	
	public $nick = '';
	public $fromChan = '';
	public $msg = '';
	
	protected $SEP = " ";
	protected $EOL = "\r\n";

	/**
	 * Extract informations from irc input and store in object properties
	 * @param string $input
	 */
	public function __construct($input='') {
		if (empty($input)) {
			return false;
		}
		$this->input = $input;
		
		$this->parseInput($this->input);
	}
	
	/**
	 * Split an irc msg in prefix, command and params.
	 * Parse params for somme commands
	 * @param string $input
	 */
	function parseInput($input) {
debug($input, 'Input');
		
		$parts = explode($this->SEP, $input);
		
		if ($parts[0][0] == ':') { // there is a prefix
			$this->prefix = trim($parts[0], ':');
			$this->command = $parts[1];
			$this->param = join ($this->SEP, array_splice($parts, 2));
		}
		else {
			$this->prefix = '';
			$this->command = trim($parts[0]);
			$this->param = trim(join($this->SEP, array_splice($parts, 1)));
		}
		
		$this->parsePrefix($this->prefix);
		
		if ($this->command == 'PRIVMSG') {
			$this->parsePrivMsgCommand($this->command, $this->param);
		}
		
		return array($this->prefix, $this->command, $this->param);
	}
	
	/**
	 * Extract nick from irc msg prefix
	 * @param string $prefix
	 */
	function parsePrefix($prefix) {
		$this->nick = '';
		if (strstr($prefix, '!')) {
			list($this->nick, $tmp) = explode('!', $prefix);
		}
		return $this->nick;
	}
	
	function parsePrivMsgCommand($command, $param) {
		if ($command == 'PRIVMSG') {
			$parts = explode($this->SEP, $param);
			$this->fromChan = $parts[0];
			$this->msg = join ($this->SEP, array_splice($parts, 1));
		}
	}
}