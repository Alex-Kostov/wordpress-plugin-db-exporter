<?php

/**
 
 * @package dx-exporter
 
 */
 
/*
 
Plugin Name: db-exporter
 
Plugin URI: 
 
Description: 

Version: 1.0.0
 
Author: 
 
Author URI: 
 
License: 
 
Text Domain:
 
*/

//@TODO
//  Save file in the correct local directory

//  Generate Name
//  Save in proper Directory
//  register CRON
//  Connect to google API - https://developers.google.com/drive/api/v3/quickstart/php
//  Upload the data.
//  Delete local copy
//  Optimize the code and check for future errors
//  Fix coding standards
//  Fill plugin details
//  Sanitize


//ENTER THE RELEVANT INFO BELOW
$mysqlUserName      = "root";
$mysqlPassword      = "password";
$mysqlHostName      = "localhost";
$DbName             = "wordpress_aaxmedia";

$tables = [];
$site_name = strtolower( get_bloginfo() );
$backup_name = $site_name . '-db-export-' . date('mdYhis', time()) . '.sql';


// Get All the tables.
global $wpdb;
$mytables=$wpdb->get_results("SHOW TABLES");
foreach ($mytables as $mytable)
{
    foreach ($mytable as $t)
    {
		array_push($tables, $t);
    }
}

Export_Database($mysqlHostName,$mysqlUserName,$mysqlPassword,$DbName,  $tables, $backup_name );

function Export_Database($host,$user,$pass,$name,  $tables=false, $backup_name=false )
{
	$mysqli = new mysqli($host,$user,$pass,$name);
	$mysqli->select_db($name);
	$mysqli->query("SET NAMES 'utf8'");

	$queryTables    = $mysqli->query('SHOW TABLES');
	while($row = $queryTables->fetch_row())
	{
		$target_tables[] = $row[0];
	}
	if($tables !== false)
	{
		$target_tables = array_intersect( $target_tables, $tables);
	}
	foreach($target_tables as $table)
	{
		$result         =   $mysqli->query('SELECT * FROM '.$table);
		$fields_amount  =   $result->field_count;
		$rows_num=$mysqli->affected_rows;
		$res            =   $mysqli->query('SHOW CREATE TABLE '.$table);
		$TableMLine     =   $res->fetch_row();
		$content        = (!isset($content) ?  '' : $content) . "\n\n".$TableMLine[1].";\n\n";

		for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0)
		{
			while($row = $result->fetch_row())
			{ //when started (and every after 100 command cycle):
				if ($st_counter%100 == 0 || $st_counter == 0 )
				{
						$content .= "\nINSERT INTO ".$table." VALUES";
				}
				$content .= "\n(";
				for($j=0; $j<$fields_amount; $j++)
				{
					$row[$j] = str_replace("\n","\\n", addslashes($row[$j]) );
					if (isset($row[$j]))
					{
						$content .= '"'.$row[$j].'"' ;
					}
					else
					{
						$content .= '""';
					}
					if ($j<($fields_amount-1))
					{
							$content.= ',';
					}
				}
				$content .=")";
				//every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
				if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num)
				{
					$content .= ";";
				}
				else
				{
					$content .= ",";
				}
				$st_counter=$st_counter+1;
			}
		} $content .="\n\n\n";
	}
	//$backup_name = $backup_name ? $backup_name : $name."___(".date('H-i-s')."_".date('d-m-Y').")__rand".rand(1,11111111).".sql";
	// $backup_name = $backup_name ? $backup_name : $name.".sql";
	// header('Content-Type: application/octet-stream');
	// header("Content-Transfer-Encoding: Binary");
	// header("Content-disposition: attachment; filename=\"".$backup_name."\"");
	// echo $content;

	$dir = WP_PLUGIN_DIR . '/db-exporter';


	$fp = fopen($dir . '/' . $backup_name, 'w');
	fwrite($fp, $content);
	fclose($fp);
	// die();
}