<?php

include_once('utils.php');
include_once('iptreader.class.php');
include_once('dice_thrower.class.php');
include_once('jdrBot.php');
include_once('bot.conf');

// define your variables
// $host = "irc.freenode.net";
// $port=6667;
// $nick="armgBot"; // change to something unique. this aint gonna try twice.
// $ident="a_bot";
// $chan="#truc";
// $realname = "armgBot";

$list = array(
	array(
		'shortname' => 'pulp', 
		'filename' => '/var/www/jdr/ipt/pulp/pulp.ipt'
	),
);

debug("initiating irc class and connecting...");

$dt = new DiceThrower();

$iptParser = new IPTParser();
$iptParser->setDiceThrower($dt);

$ircbot = new jdrBot($list);
$ircbot->setDiceThrower($dt);
$ircbot->setIptParser($iptParser);

//$ircbot = new armgBot();
$ircbot->connect($nick, $ident, $realname, $host, $port);

debug("joining channel..");
$ircbot->joinChan($chan);

debug("entering loop..");
$ircbot->loop();

debug("disconnected.");