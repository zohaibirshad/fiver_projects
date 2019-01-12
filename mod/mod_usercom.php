<?php

class mod_usercom extends module
{

function update($id)
{
	$date_created=request('date_created');
	$username=request('username');
	$useremail=request('useremail');
	$comment=request('comment');

	$sql="
		UPDATE
			wh_comment
		SET
			date_created='".$this->db->escape_string($date_created)."',
			username='".$this->db->escape_string($username)."',
			useremail='".$this->db->escape_string($useremail)."',
			comment='".$this->db->escape_string($comment)."'
		WHERE
			id=$id
		";

	$result=$this->db->execute($sql);
	if(!$result) die("Kan ikke opdatere kommentar: $id, ".$this->db->error());

	html_redirect('?mod=usercom&r='.rand(1,30000));
}

function accept($id)
{
	$result=$this->db->execute("UPDATE wh_comment SET active=1 WHERE id=$id");
	if(!$result) die("Kan ikke aktivere kommentar: $id, ".$this->db->error());
	html_redirect('?mod=usercom&r='.rand(1,30000));
}

function decline($id)
{
	$result=$this->db->execute("UPDATE wh_comment SET active=0 WHERE id=$id");
	if(!$result) die("Kan ikke deaktivere kommentar: $id, ".$this->db->error());
	html_redirect('?mod=usercom&r='.rand(1,30000));
}

function delete_confirm($id)
{
	$tpl=$this->page->read_template('usercom_remove');
	$tpl=str_replace('[COM[ID]]',$id,$tpl);
	$this->page->title='Slet kommentar';
	$this->page->content=utf8_encode($tpl);
}

function delete_comment($id)
{
	$result=$this->db->execute("DELETE FROM wh_comment WHERE id=$id");
	if(!$result) die("Kan ikke slette kommentar: $id, ".$this->db->error());
	html_redirect('?mod=usercom&r='.rand(1,30000));
}

function list_new_comments()
{
	$tpl=$this->page->read_template('usercom_list');
	$tpl_entry=$this->page->read_template('usercom_list_item');
	$clist='';

	$sql="
		SELECT
			c.*,
			t.title AS title
		FROM
			wh_comment c,
			wh_textdata t
		WHERE
			c.active=0
			AND
			c.textid=t.id
		ORDER BY
			c.date_created
		";
	$rs=$this->db->open($sql);


	while($rs->next()){
		$t=$tpl_entry;

		$t=str_replace('[COM[DATE]]',$rs->field('date_created'),$t);
		$t=str_replace('[COM[TITLE]]',$rs->field('title'),$t);
		$t=str_replace('[COM[TEXTID]]',$rs->field('textid'),$t);
		$t=str_replace('[COM[NAME]]',$rs->field('username'),$t);
		$t=str_replace('[COM[EMAIL]]',$rs->field('useremail'),$t);
		$t=str_replace('[COM[TEXT]]',$rs->field('comment'),$t);
		$t=str_replace('[COM[ID]]',$rs->field('id'),$t);

		$clist.=$t;
	}
	$rs->close();


	$tpl=str_replace('[PAGE[LIST]]',$clist,$tpl);

	$this->page->title='Brugerkommentarer';
	$this->page->content=utf8_encode($tpl);
}





function run($cmd='')
{
	if(!$this->user->present()) return;
	$this->usermode=false;

	$id=request('id');
	if($cmd=='do'){
		if(request('remove')!='') $cmd='remove';
		if(request('ok')!='')     $cmd='accept';
		if(request('edit')!='')   $cmd='update';
		$id=request('cid');
	}

	switch($cmd){
	case 'update':  $this->update($id);         break;
	case 'accept':  $this->accept($id);         break;
	case 'decline': $this->decline($id);        break;
	case 'remove':  $this->delete_confirm($id); break;
	case 'delete':  $this->delete_comment($id); break;
	default:        $this->list_new_comments(); break;
	}
}

}

?>