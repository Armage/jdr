<?php

include_once('utils.php');
include_once('iptreader.class.php');
include_once('dice_thrower.class.php');
//include_once('duelBot.php');
include_once('jdrBot.php');
include_once('bot.conf');

// define your variables
// $host = "irc.freenode.net";
// $port=6667;
// $nick="armgBot"; // change to something unique. this aint gonna try twice.
// $ident="a_bot";
// $chan="#truc";
// $realname = "armgBot";

$opts = getopt('h::s::p::n::c::', array('help::','server::','port::','channel::','nick::')) ;

// help
if (array_key_exists('h', $opts) or array_key_exists('help', $opts)) {
	echo "SYNTAX : php runBot.php [(-s|--server)=<server>] [(-p|--port)=<port>] [(-c|--channel)=<#channel>] [(-n|--nick)=<nick>] [-h|--help]\n" ;
	exit() ;
}
// server
if (array_key_exists('s', $opts)) {
	$host = $opts['s'];
}
if (array_key_exists('server', $opts)) {
	$host = $opts['server'];
}
// port
if (array_key_exists('p', $opts)) {
	$port = $opts['p'];
}
if (array_key_exists('port', $opts)) {
	$port = $opts['port'];
}
// channel
if (array_key_exists('c', $opts)) {
	$chan = $opts['c'];
}
if (array_key_exists('channel', $opts)) {
	$chan = $opts['channel'];
}
// nickname
if (array_key_exists('n', $opts)) {
	$nick = $ident = $realname = $opts['n'];
}
if (array_key_exists('nick', $opts)) {
	$nick = $ident = $realname = $opts['nick'];
}

debug("initiating irc class and connecting...");

$dt = new DiceThrower();

$ircbot = new jdrBot();
$ircbot->setDiceThrower($dt);

$ircbot->connect($nick, $ident, $realname, $host, $port);

debug("joining channel..");
$ircbot->joinChan($chan);

debug("entering loop..");
$ircbot->loop();

debug("disconnected.");
