<?php

class ircBot {
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
	
	public function joinChan($channel) { 
		$this->sendMsg("JOIN :".$channel);
	}
	
	public function loop() {
		while (!feof($this->socket)) {
			$this->line = fgets($this->socket, 1024);
			debug(rtrim($this->line));
			if ($this->line == '') {
				continue;
			}
			
			$this->ircMsg->parseInput($this->line);
			
			if($this->is_ping($this->ircMsg->command)) {
				$this->pong();
			}

			if($this->is_privmsg($this->ircMsg->command)) {
				debug("PRIVMSG...");
				if (!$this->isForMe($this->ircMsg)) {
					continue;
				}
				
				// is this a command?
				// ici un mecanisme qui gere les commandes envoyées au bot... suivi (normalement) d'un message sur irc (channel ou user)
				// implémenté ailleurs que dans cet objet
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
	
	protected function isForMe($ircMsg) {
		if ($ircMsg->inQuery) {
			return true;
		}
		else {
			$str = explode(':', $ircMsg->msg);
		
			$pattern = "/^".$this->nick."/";
			if (preg_match($pattern,$str[1]) == 0) {
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * simple politesse
	 **/
	protected function isGreeting($msg) {
		static $count=1;
		
		$greeting = array(
			'hello',
			'ola',
			'bonjour',
			'salut',
		);
		//if (in_array(strtolower(trim($this->ircMsg->msg)), $greeting)) {
		$pattern = "/(" . join ('|', $greeting) . ")/";
		if (preg_match($pattern,strtolower($msg)) != 0) {
			$count++;
			debug($count);
			if ($count % 2 !== 0) {
				$count = 1;
				return false;
			}
			return true;
		}
		return false;
	}
	
	protected function answerGreeting() {
		$this->msg($this->ircMsg->target, $this->ircMsg->nick . ", bonjour, bonjour !");
	}
	
	protected function command_help($ircMsg) {
		$this->msg($ircMsg->target, "Hello, I'm a bot. This is standard help message for this bot.");
	}

}

class IRCMsg {
	public $input;
	public $prefix;
	public $command;
	public $param;
	
	public $inQuery = false;
	public $nick = '';
	public $client = '';
	public $domain = '';
	public $fromChan = '';
	public $msg = '';
	
	protected $SEP = " ";
	protected $EOL = "\r\n";

	/**
	 * Extract informations from irc input and store in object properties
	 * @param string $input
	 */
	public function __construct($input='') {
		if (!empty($input)) {
			$this->input = $input;
			$this->parseInput($this->input);
		}
	}
	
	/**
	 * Split an irc msg in prefix, command and params.
	 * Parse params for somme commands
	 * @param string $input
	 */
	public function parseInput($input) {
		$this->init();
		
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
	 * Extract nick, client and domain from irc msg prefix
	 * @param string $prefix
	 */
	protected function parsePrefix($prefix) {
		$this->nick = '';
		if (strstr($prefix, '!')) {
			list($this->nick, $tmp) = explode('!', $prefix);
			list($this->client, $this->domain) = explode('@', $tmp);
		}
		return $this->nick;
	}
	
	protected function parsePrivMsgCommand($command, $param) {
		if ($command == 'PRIVMSG') {
			$parts = explode($this->SEP, $param);
			if ($parts[0][0] == '#') {
				$this->fromChan = $parts[0];
				$this->inQuery = false;
				$this->target = $this->fromChan;
				$this->msg = join ($this->SEP, array_splice($parts, 1));
			}
			else {
				$this->fromChan = '';
				$this->inQuery = true;
				$this->target = $this->nick;
				$this->msg = join ($this->SEP, $parts);
			}
		}
	}
	
	protected function init() {
		$this->input = '';
		$this->prefix = '';
		$this->command = '';
		$this->param = '';
	
		$this->inQuery = false;
		$this->nick = '';
		$this->client = '';
		$this->domain = '';
		$this->fromChan = '';
		$this->msg = '';
	}
}

class armgBotCommandManager {
	protected $commands;
	
	function __construct() {
		$this->commands = array();
	}
	
	function parseCommand($msg) {
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
	
	/**
	 * @param array $commandCallback must be same as format as call_user_func first param array($object, $function)
	 **/
	function addCommand($commandName, $commandCallback) {
		$this->commands[$commandName] = $commandCallback;
	}
	
	function runCommand(IRCMsg $ircMsg, $command, $parameters) {
		//debug($this->commands);
		if (in_array($command, $this->commands)) {
			call_user_func($this->commands[$command], $ircMsg, $parameters);
		}
	}
}
