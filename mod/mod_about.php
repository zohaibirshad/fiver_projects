
<?php

class mod_about extends module
{

function run($cmd='')
{
	$tpl=$this->page->read_template('about');
	$this->page->content='Om WinHelp';
	$this->page->content=utf8_encode($tpl);
}

}

?>