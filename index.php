<head>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<?php
ini_set('log_errors', false)
?>

<?php
session_start();

require 'class/cls_db.php';
require 'class/fnc_util.php';
require 'class/cls_module.php';
require 'class/cls_page.php';
require 'class/cls_user.php';

$db=new cms_dblib;
$db->connect('localhost','denmark1','root','');

$m=request('mod','start');
$m_name='mod_'.$m;
if(!file_exists('mod/'.$m_name.'.php')) die("Module: $m_name not found");
require "mod/".$m_name.".php";

$mod=new $m_name;
$mod->db=&$db;
$mod->page=new webpage;
$mod->user=new wh_user;

$mod->run(request('c'));

$mod->page->send($mod);
$db->disconnect();

?>
