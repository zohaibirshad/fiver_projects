<?php

class mod_entry extends module {

function from_db($text)
{
	$text=str_replace('[TREF]','[TXT]',$text);
	$text=str_replace('[KEY[','[K[',$text);
	$text=str_replace('[OS[','[IMG[',$text);
	return $text;
}

function to_db($text)
{
	$text=str_replace('[TXT]','[TREF]',$text);
	$text=str_replace('[K[','[KEY[',$text);
	$text=str_replace('[IMG[','[OS[',$text);
	return $text;
}

function display_email($email)
{
	$email=str_replace('@','&nbsp;AT&nbsp;',$email);
	$email=str_replace('.','&nbsp;DOT&nbsp;',$email);

	return $email;
}




function delete($id)
{
	$this->db->execute("DELETE FROM wh_textdata WHERE id=$id");
	$this->html_redirect('/?mod=list');
}




function insert()
{
	$title=request('title');
	$text=request('text');
	$text=$this->to_db($text);

	if($title=='') $title='TITEL';
	if($text=='') $text='<p>TEKST</p>';

	$title=$this->db->escape_string($title);
	$text=$this->db->escape_string($text);

	$this->db->execute("INSERT INTO wh_textdata (title,content) VALUES('$title','$text')");

	$rs=$this->db->open("SELECT MAX(id) AS mid FROM wh_textdata");
	$id=$rs->field('mid');
	$rs->close();

	$this->html_redirect('/?mod=entry&id='.$id.'&c=edit&x='.rand(1000,9999));
}


function newtext()
{
	$tpl=$this->page->read_template('entry_new');




	$this->page->title='Opret';
	$this->page->content=utf8_encode($tpl);
}




function rem_os($id)
{
	$os=request('os');
	if(!is_numeric($os)) return;
	$this->db->execute("DELETE FROM wh_text_os WHERE text_id=$id AND os_id=$os");
	$this->html_redirect('/?mod=entry&id='.$id.'&c=edit&x='.rand(1000,9999));
}

function add_os($id)
{
	$os=request('os');
	if(!is_numeric($os)) return;
	$this->db->execute("INSERT INTO wh_text_os VALUES($id,$os)");
	$this->html_redirect('/?mod=entry&id='.$id.'&c=edit&x='.rand(1000,9999));
}

function edit($id)
{
	$tpl=$this->page->read_template('entry_edit');

	$rs=$this->db->open("SELECT * FROM wh_textdata WHERE id=$id");
	if(!$rs->next()) return;
	$title=$rs->field('title');
	$text=$rs->field('content');
	$text=$this->from_db($text);
	$tpl=str_replace('[ENTRY[ID]]',$rs->field('id'),$tpl);
	$tpl=str_replace('[ENTRY[TITLE]]',$title,$tpl);
	$tpl=str_replace('[ENTRY[TEXT]]',$text,$tpl);
	$rs->close();

	$os='';
	$oslist=array();
	$rs=$this->db->open("SELECT o.* FROM wh_text_os t,wh_os o WHERE t.text_id=$id AND t.os_id=o.id ORDER BY o.seq");
	while($rs->next()){
		$oslist[]=$rs->field('id');
		$os.='<option value="'.$rs->field('id').'">'.$rs->field('name').'</option>';
	}
	$rs->close();
	$tpl=str_replace('[ENTRY[C-OS]]',$os,$tpl);

	$os='';
	$rs=$this->db->open("SELECT * FROM wh_os ORDER BY seq");
	while($rs->next()){
		if(!in_array($rs->field('id'),$oslist))
			$os.='<option value="'.$rs->field('id').'">'.$rs->field('name').'</option>';
	}
	$rs->close();
	$tpl=str_replace('[ENTRY[P-OS]]',$os,$tpl);

	$this->page->title='Rediger "'.$title.'"';
	$this->page->content=utf8_encode($tpl);
}

function update($id)
{
	$title=request('title');
	$text=request('text');

	$text=$this->to_db($text);

	$title=$this->db->escape_string($title);
	$text=$this->db->escape_string($text);

	$this->db->execute("UPDATE wh_textdata SET title='$title',content='$text' WHERE id=$id");

	$this->html_redirect('/?mod=entry&id='.$id.'&c=edit&x='.rand(1000,9999));
}

function display($id)
{
	// Read entry template
	$tpl=$this->page->read_template('entry');

	// Lookup entry
	$sql="
		SELECT
			*
		FROM
			wh_textdata
		WHERE
			id=$id
		";
	$rs=$this->db->open($sql);
	if(!$rs->next()) return;

	// Get and normalize date/time
	$d=$rs->field('date_created');
	if($d=='0000-00-00 00:00:00') $d='<font color=white>For længe siden</font>';

	// Get and normalize text content
	$content=$rs->field('content');
	if($content{0}!='<') $content="<p>$content</p>";
	$content=str_ireplace('<br>','<br />',$content);

	// Insert entry data
	$tpl=str_replace('[ENTRY[ID]]',   $rs->field('id'),$tpl);
	$tpl=str_replace('[ENTRY[DATE]]', $d,$tpl);
	$tpl=str_replace('[ENTRY[TITLE]]',$rs->field('title'),$tpl);
	$tpl=str_replace('[ENTRY[TEXT]]', $content,$tpl);
	$this->page->title=utf8_encode($rs->field('title'));
	$rs->close();

	// Get OS list for entry
	$os='';
	$sql="
		SELECT
			o.*
		FROM
			wh_os o,
			wh_text_os t
		WHERE
			t.text_id=$id
			AND
			t.os_id=o.id
		ORDER BY
			o.seq,
			o.name
		";
	$rs=$this->db->open($sql);
	if($rs->next()){
		do{
			if($os!='') $os.=', ';
			$os.=$rs->field('shortname');
		}while($rs->next());
	}
	$rs->close();
	if($os=='') $os='Generelt';
	$tpl=str_replace('[ENTRY[OSLIST]]', $os,$tpl);

	// Get Comments for entry
	$clist='';
	$sql="
		SELECT
			*
		FROM
			wh_comment
		WHERE
			textid=$id
			AND
			active=1
		ORDER BY
			date_created DESC
		";
	$rs=$this->db->open($sql);
//die($sql);
	while($rs->next()){

		//$t='<tr><td style="border-top:1px solid #999999">[COMMENT[NAME]]</td></tr><tr><td>[COMMENT[EMAIL]]</td></tr><tr><td style="background-color:#ffffff">[COMMENT[TEXT]]</td></tr>';
		$t=$this->page->read_template('entry_comment_item');
		$t=str_replace('[COMMENT[ID]]',   $rs->field('id'),$t);
		$t=str_replace('[COMMENT[NAME]]', $rs->field('username'),$t);
		$t=str_replace('[COMMENT[EMAIL]]',$this->display_email($rs->field('useremail')),$t);
		$t=str_replace('[COMMENT[TEXT]]', $rs->field('comment'),$t);
		$clist.=$t;

	}
	$rs->close();
	$tpl=str_replace('[ENTRY[COMMENTS]]',$clist,$tpl);
	apply_section($tpl,'HASCOMMENT',$clist==''?1:0);

	// Apply page content
	$this->page->content=utf8_encode($tpl);
}



function run($cmd)
{
	$id=request('id');
	switch($cmd){
	case 'delete':
		$this->delete($id);
		break;

	case 'new':
		$this->newtext();
		break;

	case 'insert':
		$this->insert();
		break;

	case 'edit':
		$this->edit($id);
		break;

	case 'update':
		$this->update($id);
		break;

	case 'addos':
		$this->add_os($id);
		break;

	case 'remos':
		$this->rem_os($id);
		break;

	default:
		$this->display($id);
		break;
	}

}

}

?>