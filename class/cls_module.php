<?php

class module
{
var $usermode=true;
var $db;
var $page;
var $user;

function check_user()
{
	$this->user=trim(''.@$_SESSION['wh_user']);
	return $this->user==''?false:true;
}

function login_form()
{
	$tpl=$this->page->read_template('login');
	$this->page->title='Adgangskontrol';
	$this->page->content=$tpl;
	return false;
}

function login_user()
{
	$user=request('user');
	$pass=request('pass');
	if($user=='') return $this->login_form();

	$rs=$this->db->open("SELECT * FROM wh_username='$user' AND passworr='$pass'");
	if(is_null($rs)) return $this->login_form();
	if(!$rs->next()) return $this->login_form();
	$_SESSION['wh_user']=$user;
	$rs->close();
	return $this->check_user();
}

function force_user()
{
	if($this->check_user()) return true;
	if($this->login_user()) return true;
	return false;
}

function html_redirect($url)
{
	$tpl=$this->page->read_template('redirect');
	$tpl=str_replace('[REDIRECT[URL]]',$url,$tpl);
	$tpl=str_replace('[EXT]','.php',$tpl);
	echo $tpl;
	exit(0);
}


function error($msg)
{
	$this->page->title='Fejl';
	$this->page->content=$msg;
}


}

?>