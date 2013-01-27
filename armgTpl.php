<?php

/** 
 * This file is part of Sacaliens
 * Copyright (c) 2009 Patrick Paysant
 *
 * PHP Bookin is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * PHP Bookin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
/**
 * Template management
 * 
 * Example of use (foobar.php) :
 * <code>
 * <?php
 * include('armgTpl.php') ;
 * 
 * $tpl = new armgTpl("/path/to/tplDir/") ;
 * $tpl->addData(array("foo_field" => "bar_value")) ;
 * $tpl->run("foobar.tpl.php") ;
 * ?>
 * </code>
 * 
 * The datas will be available in global space for the template. A simple echo will display the data.
 * 
 * Example of template (foobar.tpl.php)
 * <code>
 * <html>
 * <h1>Title</1>
 * <p>Hello <?php echo $foo_field ; ?>, how are you ?</p>
 * </html>
 * </code>
 */
Class armgTpl {
	private $tplPath ;
	private $layoutPath ;
	private $datas ;

	/**
	 * Constructor
	 * 
	 * @param string $tplPath path to the template directory (where all the templates may be found)
	 */
	public function __construct($tplPath, $layoutPath="") {
		$this->setTplPath($tplPath) ; 
		$this->layoutPath = $layoutPath ;

		$this->datas = array() ;
	}

	/**
	 * Setter for tplPath variable
	 * 
	 * @param string $tplPath path to the template directory (where all the templates may be found)
	 */
	public function setTplPath($tplPath) {
		if (($tplPath != "/") and (substr($tplPath,-1) != "/")) {
			$tplPath = $tplPath . "/" ;
		}
		if (!is_dir($tplPath) or !is_readable($tplPath)) {
			die ('Fatal : tplPath "'.$tplPath.'" is not valid !') ;
		}
		$this->tplPath = $tplPath ;
	}

	/**
	 * Add data to template
	 * 
	 * @param array $data Associative array to add to template datas.
	 */
	public function addData($datas) {
		if (is_array($datas)) {
			foreach($datas as $key => $value) {
				$this->datas[$key] = $value ;
			}
		}
	}

	/**
	 * Display the template
	 * 
	 * @param string $tplFile Filename inside the tplPath to use.
	 */
	public function runTpl($tplFile) {
		if ($tplFile == "") {
			die ("Fatal : please choose a tplFile") ;
		}
		while ((substr($tplFile, 1) == ".") or (substr($tplFile, 1) == "/")) {
			$tplFile = ltrim($tplFile, "./") ;
		}
		if (!is_readable($this->tplPath.$tplFile)) {
			die ("Fatal : tplFile $tplFile is not readable") ;
		}
		if (count($this->datas) > 0) extract($this->datas) ;
		require($this->tplPath.$tplFile) ;
	}

}

?>
