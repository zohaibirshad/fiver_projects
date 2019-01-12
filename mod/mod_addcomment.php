<?php

class mod_addcomment extends module
{

function add_comment()
{
	// Get parameters
	$id     =request('id');
	$email  =request('email');
	$name   =request('name');
	$comment=request('comment');

	// Validate parameters
	if(!is_numeric($id))   return $this->error('Fejl: AC#1');
	if($id<1 || $id>30000) return $this->error('Fejl: AC#2');
	if($email=='')         return $this->display_form('E-Mail skal udfyldes',$id);
	if($name=='')          return $this->display_form('Navn skal udfyldes',$id);
	if($comment=='')       return $this->display_form('Kommentar skal udfyldes',$id);

	// Insert comment
	$sql="
		INSERT INTO
			wh_comment
		(date_created,active,textid,username,useremail,comment)
		VALUES(
			'".date('Y-m-d H:i:s')."',
			0,
			$id,
			'".$this->db->escape_string($name)."',
			'".$this->db->escape_string($email)."',
			'".$this->db->escape_string($comment)."'
		);
		";
	$result=$this->db->execute($sql);
	if(!$result) return $this->display_form('Kan ikke oprette kommentar: ('.$this->db->error().')',$id);

	// Return to entry page (!!)
	html_redirect('?mod=entry&id='.$id);
}

function display_form($msg='',$tid=0)
{
	// Get ID parameter
	if(!$tid) $tid=request('id');
	if(!is_numeric($tid))     return $this->error('Fejl: AC#3');
	if($tid<=0 || $tid>30000) return $this->error('Fejl: AC#4');

	// Lookup entry
	$rs=$this->db->open("SELECT title FROM wh_textdata WHERE id=$tid");
	if($rs->next())	$title=$rs->field('title');
	$rs->close();

	// Read template
	$tpl=$this->page->read_template('add_comment_form');
	$tpl=str_replace('[COM[MSG]]',   $msg,  $tpl);
	$tpl=str_replace('[COM[TEXTID]]',$tid,  $tpl);
	$tpl=str_replace('[COM[TITLE]]', $title,$tpl);

	// Apply page
	$this->page->title='Opret kommentar';
	$this->page->content=utf8_encode($tpl);
}

function run($cmd='')
{
	switch($cmd){
	case 'insert': $this->add_comment();  break;
	default:       $this->display_form(); break;
	}
}

}

?>