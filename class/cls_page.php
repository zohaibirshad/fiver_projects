<?php

class webpage
{
var $title='';
var $content='';

var $db;

function read_template($tname)
{
	return file_get_contents("tpl/$tname.htm");
}


function send(&$mod)
{
	$tpl=$this->read_template('master');
	
	$tpl=str_replace('[PAGE[CONTENT]]',$this->content,$tpl);
	$tpl=str_replace('[PAGE[TITLE]]',$this->title,$tpl);

	$tpl=str_replace('[TREF]','/?mod=entry&amp;id=',$tpl);

	$tpl=str_replace('[EXT]','.php',$tpl);

	$tpl=preg_replace("/\[OS\[(\S[^\]]+)\]\]/e","os_img(\"\\1\");",$tpl);
	$tpl=preg_replace("/\[KEY\[(\S[^\]]+)\]\]/e","spankey(\"\\1\");",$tpl);
	$tpl=preg_replace("/\[STR\[(\S[^\]]+)\]\]/e","local_chars(constant(\"STR_\\1\"));",$tpl);

	apply_section($tpl,'ADMIN',$mod->user->present()?0:1);
	apply_section($tpl,'USERMODE',$mod->usermode?0:1);

	echo $tpl;
}

};

?>
