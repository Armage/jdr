<?php

/**
 * Duel game rules
 * use with sqlite dsn like : $duel = new Duel('sqlite:duel.game.sqlite3');
 * 
 */

class Duel {
	protected $db;
	protected $dbfile;

	public function __construct($dbfile='') {
		$this->db = null;
		$this->dbfile = $dbfile;
		$this->initSQLite();
	}
	
	/**
	 * Démarre un duel entre deux joueurs.
	 * Un joueur ne peut participer à deux duels en même temps.
	 */
	public function initGame($player1='', $player2='') {
		
	}
	
	protected function initSQLite() {
		if (empty($this->dbfile)) {
			throw Exception('Unable to load db file.');
		}
		
		$this->db = new PDO($this->dbfile);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$this->db->exec("CREATE TABLE IF NOT EXISTS ".$this->db_name." (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				url TEXT,
				create_time INTEGER)");
	}

}
