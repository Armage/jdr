<?php

include_once('utils.php');
include_once('armgBot.class.php');

class ArmgBotTest extends PHPUnit_Framework_TestCase
{

	public function testParsingInputWithNick()
	{
		$input = ":nickname!~ident@my_ip PRIVMSG #a_chan :coucou";
		
		$irc_msg = new IRCMsg($input);
		$this->assertEquals($irc_msg->prefix, "nickname!~ident@my_ip");
		$this->assertEquals($irc_msg->nick, "nickname");
		$this->assertEquals($irc_msg->client, "~ident");
		$this->assertEquals($irc_msg->domain, "my_ip");
		$this->assertEquals($irc_msg->command, "PRIVMSG");
		$this->assertEquals($irc_msg->param, "#a_chan :coucou");
		$this->assertEquals($irc_msg->fromChan, "#a_chan");
		$this->assertEquals($irc_msg->msg, ":coucou");
		$this->assertEquals($irc_msg->target, "#a_chan");
	}
	
	public function testParsingQueryWithNick()
	{
		$input = ":nickname!~ident@my_ip PRIVMSG :coucou";
		
		$irc_msg = new IRCMsg($input);
		$this->assertEquals($irc_msg->prefix, "nickname!~ident@my_ip");
		$this->assertEquals($irc_msg->nick, "nickname");
		$this->assertEquals($irc_msg->client, "~ident");
		$this->assertEquals($irc_msg->domain, "my_ip");
		$this->assertEquals($irc_msg->command, "PRIVMSG");
		$this->assertEquals($irc_msg->param, ":coucou");
		$this->assertEquals($irc_msg->fromChan, "");
		$this->assertEquals($irc_msg->msg, ":coucou");
		$this->assertEquals($irc_msg->target, "nickname");
	}

}
