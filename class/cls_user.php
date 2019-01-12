<?php

class wh_user
{
var $id=0;
var $username='';
var $fullname='';

function wh_user()
{
	$this->id       = ''.@$_SESSION['id'];
	$this->username = ''.@$_SESSION['username'];
	$this->fullname = ''.@$_SESSION['fullname'];

	if($this->id=='') $this->id=0;
}

function present()
{
	return true;
	//return $this->id=='0'?false:true;
}

function login($user,$pass)
{
	global $db;

	$sql="
		SELECT
			*
		FROM
			wh_user
		WHERE
			username='$user'
			AND
			password='$pass';
		";
	$db->open($sql);
	if(!$db->move_next()) return false;
	$this->id=$db->field('id');
	$this->username=$db->field('username');
	$this->fullname=$db->field('fullname');
	$db->close();

	return true;
}

}

?>
