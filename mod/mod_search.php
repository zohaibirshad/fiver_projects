<?php

class mod_search extends module
{

function search_form($msg='')
{
	$this->page->title='Find';
	$this->page->content=$this->page->read_template('search_form');
}

function search()
{
	// Get page selector
	$p=request('p');
	if($p=='') $p=1;

	// Get query
	$q=trim(request('q'));
	if(strlen($q)<4 && (strpos($q,'*')===false)) $q.='*';
	$qes=$this->db->escape_string($q);
	$que=urlencode($q);

	// Misc
	$page_rows=25;
	$start=($p-1)*$page_rows;
	$maxpagesel=9;
	$row_even=false;
	$count=0;
	$rows='';

	// Get templates
	$tpl=$this->page->read_template('search');
	$tpl_item='<tr><td style="padding:2px 3px 4px 3px;background-color:[ROW[BCOL]]"><a href="/?mod=entry&amp;id=[ROW[ID]]">[ROW[TITLE]]</a></td></tr>';
	$nav=$this->page->read_template('entry_nav');

	// Lookup entries
	$sql="
		SELECT
			*,
			MATCH (title,content) AGAINST ('$qes' IN BOOLEAN MODE) AS Score
		FROM
			wh_textdata
		WHERE
			MATCH (title,content) AGAINST ('$qes' IN BOOLEAN MODE)
		ORDER BY
			MATCH (title,content) AGAINST ('$qes' IN BOOLEAN MODE) DESC
		";

	$rs=$this->db->open($sql,($p-1)*$page_rows,$page_rows);
	if($rs->_full_count>0){
		while($rs->move_next()){
			$row_count=$rs->_full_count;

			$t=$tpl_item;
		
			$t=str_replace('[ROW[BCOL]]', $row_even?'#ffffff':'#e3e3ff',$t);
			$t=str_replace('[ROW[ID]]',   $rs->field('id'),$t);
			$t=str_replace('[ROW[TITLE]]',$rs->field('title'),$t);

			$rows.=$t;
			$count++;
			$row_even=$row_even?false:true;
		}
	}
	$rs->close();

	// Make navigation
	$maxpage=ceil($rs->_full_count/$page_rows);
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

	// Make selector numbers
	$psel='';
	for($i=$pstart;$i<=$pend;$i++){
		$n=$i;
		if($p==$i) $n='<b>'.$n.'</b>';
		$psel.='<td style="width:18px;text-align:center"><a href="/?mod=search&c=go&amp;q='.$que.'&amp;p='.$i.'">'.$n.'</a></td>';
	}

	// Make selector arrows
	if($p>1)
		$psel='<td style="width:18px;text-align:center"><a href="/?mod=search&c=go&amp;q='.$que.'&amp;p='.($p-1).'"><img src="/image/arrow_lf.gif" style="border:none" alt="Forrige" /></a></td>'.$psel;
	if($pstart>1)
		$psel='<td style="width:18px;text-align:center"><a href="/?mod=search&c=go&amp;q='.$que.'&amp;p=1"><img src="/image/arrow_lfx.gif" style="border:none" alt="Første" /></a></td>'.$psel;
	if($p<$maxpage)
		$psel.='<td style="width:18px;text-align:center"><a href="/?mod=search&c=go&amp;q='.$que.'&amp;p='.($p+1).'"><img src="/image/arrow_rg.gif" style="border:none" alt="Næste" /></a></td>';
	if($pend<$maxpage)
		$psel.='<td style="width:18px;text-align:center"><a href="/?mod=search&c=go&amp;q='.$que.'&amp;p='.$maxpage.'"><img src="/image/arrow_rgx.gif" style="border:none" alt="Sidste" /></a></td>';
	$psel='<table border="0" cellpadding="0" cellspacing="0" style="margin-right:0;margin-left:auto"><tr>'.$psel.'</tr></table>';

	// Apply selector template
	$nav=str_replace('[NAV[START]]',$start+1,$nav);
	$nav=str_replace('[NAV[END]]',$start+$count,$nav);
	$nav=str_replace('[NAV[PAGES]]',$rs->_full_count,$nav);
	$nav=str_replace('[NAV[PAGESEL]]',$psel,$nav);

	// Insert main page values

        header("Content-type: text/html; charset=iso-8859-1");

	$tpl=str_replace('[ENTRY[QUERY]]',$q,$tpl);
	$tpl=str_replace('[ENTRY[NAV]]',$nav,$tpl);
	$tpl=str_replace('[ENTRY[LIST]]',$rows,$tpl);

	// Apply page
	$this->page->title='Fandt '.$row_count.' poster';

        header("Content-type: text/html; charset=iso-8859-1");

	$this->page->content=utf8_encode($tpl);
}



function run($cmd='')
{
	if($cmd!='go') return $this->search_form();

	$this->search();
}

}

?>