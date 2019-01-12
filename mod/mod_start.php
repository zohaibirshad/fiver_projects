<?php

class mod_start extends module
{

function run($cmd='')
{

	$tpl=$this->page->read_template('about');
	//$tpl=$this->page->read_template('start');
	$this->page->content='Velkommen';
	$this->page->content=utf8_encode($tpl);
}

}

?>