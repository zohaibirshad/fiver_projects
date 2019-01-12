<?php



class mod_list extends module

{



function display($type=0)

{

	$p=request('p',1);

	if($p<0) $p=1;



	$cnt=25;



	$start=($p-1)*$cnt;

	$maxpagesel=15;





	$tpl=$this->page->read_template('list');


	$tpl_item='<tr><td style="padding:2px 3px 4px 3px;background-color:[ROW[BCOL]]"><a href="/?mod=entry&amp;id=[ROW[ID]]">[ROW[TITLE]]</a></td></tr>';



	$row_even=false;

	$count=0;

	$rows='';



	$sql="

		SELECT

			*

		FROM

			wh_textdata

		WHERE

			active='1'

		ORDER BY

			".($type==0?'title':'id DESC')."

		";



	$rs=$this->db->open($sql,$start,$cnt);



	while($rs->next()){



		$t=$tpl_item;



		$t=str_replace('[ROW[BCOL]]', $row_even?'#ffffff':'#e3e3ff',$t);

		$t=str_replace('[ROW[ID]]',   $rs->field('id'),$t);

		$t=str_replace('[ROW[TITLE]]',$rs->field('title'),$t);



		$rows.=$t;

		$count++;

		$row_even=$row_even?false:true;



	}

	$rs->close();



	// Make navigation

	$nav=$this->page->read_template('entry_nav');

	$maxpage=ceil($rs->_full_count/$cnt);

	if($maxpage>$maxpagesel){

		$h=(int)($maxpagesel/2);

		$pstart=$p-$h;

		$pend=$p+$h;

		if($pstart<1){

			$pstart=1;

			$pend=$pstart+($maxpagesel-1);

		}

		if(($pend+$h)>$maxpage){

			$pend=$maxpage;

			$pstart=$maxpage-$maxpagesel;

		}

	}else{

		$pstart=1;

		$pend=$maxpage;

	}

	$psel='';

	for($i=$pstart;$i<=$pend;$i++){

		$n=$i;

		if($p==$i) $n='<b>'.$n.'</b>';

		$psel.='<td style="width:18px;text-align:center"><a href="/?mod=list&amp;p='.$i.'">'.$n.'</a></td>';

	}



	if($p>1)

		$psel='<td style="width:18px;text-align:center"><a href="/?mod=list&amp;p='.($p-1).'"><img src="/image/arrow_lf.gif" style="border:none" alt="Forrige" /></a></td>'.$psel;

	if($pstart>1)

		$psel='<td style="width:18px;text-align:center"><a href="/?mod=list&amp;p=1"><img src="/image/arrow_lfx.gif" style="border:none" alt="Første" /></a></td>'.$psel;

	if($p<$maxpage)

		$psel.='<td style="width:18px;text-align:center"><a href="/?mod=list&amp;p='.($p+1).'"><img src="/image/arrow_rg.gif" style="border:none" alt="Næste" /></a></td>';

	if($pend<$maxpage)

		$psel.='<td style="width:18px;text-align:center"><a href="/?mod=list&amp;p='.$maxpage.'"><img src="/image/arrow_rgx.gif" style="border:none" alt="Sidste" /></a></td>';








	$psel='<table border="0" cellpadding="0" cellspacing="0" style="margin-right:0;margin-left:auto"><tr>'.$psel.'</tr></table>';



	//

	$nav=str_replace('[NAV[START]]',$start+1,$nav);

	$nav=str_replace('[NAV[END]]',$start+$count,$nav);

	$nav=str_replace('[NAV[PAGES]]',$rs->_full_count,$nav);

	$nav=str_replace('[NAV[PAGESEL]]',$psel,$nav);




	$tpl=str_replace('[ENTRY[NAV]]',$nav,$tpl);



	$tpl=str_replace('[ENTRY[LIST]]',$rows,$tpl);

	$this->page->title=($type==0?'Alfabetisk liste':'Seneste tips');

	$this->page->content=utf8_encode($tpl);






}






function run($cmd='')

{

	switch($cmd){

	case 'latest': $this->display(1); break;

	default:       $this->display(0); break;

	}



}



}



?>
