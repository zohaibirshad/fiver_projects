<?php

//
//
//
function request($item,$default='')
{
	$v=''.@$_REQUEST[$item];
	if($v=='') $v=$default;
	return $v;
}

//
//
//
function request_dbfield($item)
{
	global $db;
	return $db->escape_string(trim(request($item)));
}

//
//
//
function apply_section_base(&$text,$name,$type)
{
	//
	// Type=0 means delete tagsm but keep text
	//
	if($type==0){
		$text=str_replace('[TAG['.$name.']]','',$text);
		$text=str_replace('[TAG[/'.$name.']]','',$text);

	//
	// Type!=0 means delete tags and text 
	//
	}else{
		$stop=false;
		$count=0;
		$pos=strpos($text,'[TAG['.$name.']]');
		do{
			$end='[TAG[/'.$name.']]';
			if($pos===false){
				$stop=true;
			}else{
				$text=substr($text,0,$pos).
					substr($text,strpos($text,$end)+strlen($end));
			}
			$count++;
			$pos=strpos($text,'[TAG['.$name.']]');
		}while(!($pos===false) && $count<4 && $stop==false);

	}
	return $text;
}

function apply_section(&$text,$name,$type)
{
	$text=apply_section_base($text,$name,$type);
	$text=apply_section_base($text,'!'.$name,$type==0?1:0);
	return $text;
}

function email_ob($m)
{
	$m=str_replace('.',' DOT ',$m);
	$m=str_replace('@',' AT ',$m);

	return $m;
}

function spankey($k)
{
	return '<span class="key">'.$k.'</span>';
}

function os_img($os)
{
	return '<p><img src="image/os_'.$os.'.jpg" style="width:122px;height:25px" alt="Windows '.strtoupper($os).'" /></p>';
}

//----------------------------------------------------------------------
// Redirect user browser using HTML
//
function html_redirect($url)
{
	echo "<html><head><title></title>";
	echo '<META HTTP-EQUIV="Refresh" CONTENT="0;URL=http://'.$_SERVER['HTTP_HOST']."/".$url.'")>';
	echo "</head><body></body></html>\n";
	exit;
}


?>
