<?php

define('DB_FIELD_DEFAULT','0');
define('DB_FIELD_DATE',   '1');


class db_cursor
{
var $_db;
var $_rs;
var $_is_open=false;
var $_move_count=0;
var $_row_status=0;
var $_row;
var $_row_num=-1;
var $_full_count=0;







function insert_id()
{
	return mysql_insert_id($this->_rs);
}



//
//
//
function insert_sql()
{
	$names='';
	$values='';
	$table;
	$i=0;
	while($i<mysql_num_fields($this->_rs)){
		$meta=mysql_fetch_field($this->_rs,$i);
		$table=$meta->table;
		if($names!='') $names.=',';
		$names.="`".$meta->name."`";
		$t=$this->_row[$i];
		if($meta->numeric!=1) $t="'".mysql_escape_string($t)."'";
		if($values!='') $values.=',';
		$values.=$t;
		$i++;
	}
	return "INSERT INTO $table ($names) VALUES($values);\n";
}

//
// Read next record
//
function _read()
{
	$this->_row=mysql_fetch_array($this->_rs);
	$this->_move_count++;
	$this->_row_num++;

	return ($this->_row===false)?false:true;
}

//
// Get current row number
//
function row_num()
{
	return $this->_row_num-1;
}

//
// Move to first record and read it
//
function first()
{
	//::if(!$this->_is_open) return false;

	if(!mysql_data_seek($this->_rs,0))
		return false;
	
	return $this->_read();
}

//
// Move to last record and read it
//
function last()
{
	//::if(!$this->_is_open) return false;

	if(!$this->rows())
		return false;

	if(!mysql_data_seek($this->_rs,$this->rows()-1))
		return false;
	
	return $this->_read();
}

//
// Move to previous record and read it
//
function previous()
{
	//::if(!$this->_is_open) return false;

	if(!mysql_data_seek($this->_rs,$this->_row_num-2))
		return false;

	$this->_row_num=$this->_row_num-2;
	return $this->_read();
}

//
// Move to next record and read it
//
function next()
{
	//::if(!$this->_is_open) return false;

	if($this->_move_count>0){
		return $this->_read();
	}

	$this->_move_count++;
	$this->_row_num++;
	return $this->_row_status;
}

//
// Get number of records
//
function rows()
{
	//::if(!$this->_is_open) return -1;

	return mysql_num_rows($this->_rs);
}

//
// Get number of records
//
function all_rows()
{
	//::if(!$this->_is_open) return -1;

	return $this->_full_count;
}

//
// Get column data for current record
//
function field($name,$type=0)
{
	//::if(!$this->_is_open) return '';

	// Get data
	$data=$this->_row[$name];

	// Prepare date field
	if($type==DB_FIELD_DATE){
		$data=str_replace(' ','',$data);
		$data=str_replace('-','',$data);
		$data=str_replace(':','',$data);
		$data=str_replace('/','',$data);
	}

	// Return data
	return $data;
}

//
// Close this recordset
//
function close()
{
	//::if(!$this->_is_open) return false;

	// Free recordset
	mysql_free_result($this->_rs);

	// Clear open flag and return success
	$this->_is_open=false;
	return true;
}

}










class db_block_cursor
	extends db_cursor
{
var $_row_start=0;
var $_row_end=0;

var $_full_count=0;

var $_page_size=0;
var $_page_count=0;
var $_page_number=0;

var $_pos_start=0;
var $_pos_end=0;
var $_pos=0;



function row_count($full=false)
{
	return $full?$this->_full_count:$this->rows();
}

function move_next()
{
	$result=$this->next();
	if($result) $this->_pos++;
	return $result;
}

function prepare($page_size,$page_number)
{
	//
	// Page
	//
	$this->page_size=$page_size;
	$this->_page_count=ciel($this->_full_count/$this->page_size);
	if($page_number>$this->page_count)
		return false;
	$this->_page_number=$page_number;

	//
	// Pos
	//
	$this->_pos_start=($this->_page_size*($this->_page_number-1));
	$this->_pos_end=$this->_pos_start+$this->_page_size;
	if($this->_pos_end>$this->_full_count)
		$this->_pos_end=$this->_full_count;
	$this->pos=$this->rows()>0?1:0;

	//
	// Return success
	//
	return true;
}




function pos($full=false)
{
	return $full?($this->_pos_start+$this->_pos):$this->_pos;
}

function next_page()
{
	return $this->_page_number-1;
}

function previous_page()
{
	return (($this->_page_number+1)>$this->_page_count)?(-1):($this->_page_number+1);
}


}




class cms_dblib
{
var $_is_connected=false;
var $_cn;


//
// Make database specific changes to SQL statement
//
function _prepare_sql($sql,$row_start,$row_count)
{
	// Remove extra whitespace
	$sql=str_replace("\t",' ',$sql);
	$sql=str_replace('  ',' ',$sql);
	$sql=trim($sql);

	// Apply TOP directive
	if(strtoupper(substr($sql,0,11))=='SELECT TOP '){
		$sql=trim(substr($sql,11));
		$p=strpos($sql,' ');
		$top=substr($sql,0,$p);
		$sql=trim(substr($sql,$p));
		$sql='SELECT '.$sql.' LIMIT 0,'.$top;
	}

	// Apply DAY directive
	$sql=str_replace('DAY(','DAYOFMONTH(',$sql);

	// Apply LIMIT directive
	if($row_start!=0 || $row_count!=0){
		if(strtoupper(substr($sql,0,6))=='SELECT')
			$sql=substr($sql,0,7)."SQL_CALC_FOUND_ROWS ".substr($sql,7);
		$sql.=' LIMIT '.$row_start.','.$row_count;
	}

	//echo htmlentities($sql).'<p>';

	// Return the converted string
	return $sql;
}

//
// Prepare string for safe SQL use
//
function escape_string($txt)
{
	return mysql_escape_string($txt);
}

//
// Connect to server and database
//
function connect($host,$database,$user,$password,$options='')
{
	//::if($this->_is_connected) return;

	// Connect to host
	$this->_cn=mysql_connect($host,$user,$password);
	if($this->_cn===false){
		$this->_str_error="Error connecting to database: ".$host;
		return false;
	}

	// Connect to database
	if(!mysql_select_db($database)){
		$this->_str_error="Error selecting to database: ".$database;
		return false;
	}

	// Set connected flag and return success
	$this->_is_connected=true;
	return true;
}

//
// Disconnect from database and server
//
function disconnect()
{
	//::if(!$this->_is_connected) return false;

	// Close connection
	mysql_close($this->_cn);

	// Clear connected flag and return success
	$this->_is_connected=false;
	return true;
}

//
// Open cursor
//
function open($sql,$row_start=0,$row_count=0)
{
	//::if(!$this->_is_connected) return null;

	// Prepare SQL sentencs
	$sql=$this->_prepare_sql($sql,$row_start,$row_count);

	// Create new cursor object
	if($row_start!=0||$row_count!=0)
		$cursor=&new db_block_cursor;
	else
		$cursor=&new db_cursor;
	$cursor->_db=&$this;

	// Lookup records
	$cursor->_rs=mysql_query($sql,$this->_cn);
	if($cursor->_rs===false){
		$this->_str_error='Unable to query database';
		return null;
	}

	// Get full row sount
	if($row_start!=0||$row_count!=0){
		$rs=mysql_query("SELECT FOUND_ROWS() AS RowCount",$this->_cn);
		if(!($rs===false)){
			$row=mysql_fetch_array($rs);
			$cursor->_full_count=$row['RowCount'];
			mysql_free_result($rs);
			//echo $sql;
		}
	}

	// Prepare cursor
	$cursor->_is_open=true;
	$cursor->_move_count=0;

	// Fetch first record
	if(mysql_num_rows($cursor->_rs)>0){
		$cursor->_row=mysql_fetch_array($cursor->_rs);
		$cursor->_row_status=($cursor->_row===false)?false:true;
		$cursor->_row_num=1;
	}else{
		$cursor->_row_status=0;
		$cursor->_row_num=0;
	}

	// Return cursor
	return $cursor;
}


function open_block($sql,$page_size,$page_number)
{
	if($page_number<1||$page_size<1) return null;

	$row_start=($page_number-1)*$page_size;

	$rs=$this->open($sql,$row_start,$page_size);

	if(!$rs->_is_open) return null;

	$rs->prepare($page_size,$page_number);
	
	return $rs;
}


//
// Close cursor
//
function close(&$cursor)
{
	$cursor->close();
	$cursor=null;
}

//
// Execute SQL statement
//
function execute($sql)
{
	//::if(!$this->_is_connected) return false;

	// Execute statement
	$result=mysql_query($sql,$this->_cn)==false?false:true;
	return $result;


}

//
// Begin new transaction
//
function begin()
{
	return $this->execute('START TRANSACTION');
}

//
// Rollback changes in current transaction
//
function rollback()
{
	return $this->execute('ROLLBACK');
}

//
// Commit changes in current transaction
//
function commit()
{
	return $this->execute('COMMIT');
}


function error()
{
	return mysql_error($this->_cn);
}


}

?>