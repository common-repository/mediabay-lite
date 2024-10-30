<?php

class Mediabay_Plugin{

	public function __construct()
	{	
		$this->init_files();
	}

	private function init_files()
	{
		include_once ( MEDIABAY_PATH . 'inc/category.php');
		include_once ( MEDIABAY_PATH . 'inc/helper.php');
		include_once ( MEDIABAY_PATH . 'inc/sidebar.php');
	}

}

new Mediabay_Plugin();