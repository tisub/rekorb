<?php

$files = scandir("CONNECTORS");
if( $files === false )
	throw new Exception("Failed to list content of the CONNECTORS directory");

$tmp = scandir("TEMP");
if( $tmp === false )
	throw new Exception("Failed to list content of the TEMP directory");
foreach( $tmp as $t )
	if( $t != 'busit.log' && $t != '.' && $t != '..' )
		unlink('TEMP/'.$t);

$candidates = array();
foreach( $files as $f )
{
	$info = pathinfo("CONNECTORS/".$f);
	if( !isset($candidates[$info['filename']]) )
		$candidates[$info['filename']] = 0;
	if( $info['extension'] == 'php' )
		$candidates[$info['filename']] |= 1;
	if( $info['extension'] == 'json' )
		$candidates[$info['filename']] |= 2;
}

$content = "<h1>Please choose the connector</h1>
	<form action=\"/2\" method=\"post\">
		<select name=\"connector\">";

foreach( $candidates as $name=>$value )
	if( $value == 3 )
		$content .= "<option value=\"{$name}\">{$name}</option>";

$content .= "
		</select>
		<input type=\"submit\" value=\"Select\" />
	</form>";

return $content;

?>