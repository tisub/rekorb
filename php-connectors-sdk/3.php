<?php

function log_message($m)
{
	if( $m == null )
		com\anotherservice\util\Logger::warning(">> The operation did not return a message");
	else if( $m instanceof com\busit\IMessageList )
	{
		foreach( $m as $mm )
			log_message($mm);
	}
	else
	{
		com\anotherservice\util\Logger::info(">> Message text content : " . $m->content()->toText());
		$name = microtime(true) . '.html';
		file_put_contents('TEMP/' . $name, $m->content()->toHtml());
		com\anotherservice\util\Logger::info(">> Message html content : <a href=\"/TEMP/{$name}\">{$name}</a>");
		foreach( $m->files() as $name=>$content )
		{
			file_put_contents('TEMP/' . $name, $content);
			com\anotherservice\util\Logger::info(">> Message attachment : <a href=\"/TEMP/{$name}\">{$name}</a>");
		}
	}
}

if( !isset($_POST['configs']) ) $_POST['configs'] = array();
if( !isset($_POST['inputs']) ) $_POST['inputs'] = array();
if( !isset($_POST['outputs']) ) $_POST['outputs'] = array();
if( !isset($_POST['content']) ) $_POST['content'] = "";

$processor = new com\busit\local\Processor();
$processor->_1_construct(file_get_contents("CONNECTORS/".$_POST['connector'].'.php'));
$processor->_2_initialize(array('config'=>$_POST['configs'], 'inputs'=>$_POST['inputs'], 'outputs'=>$_POST['outputs']));

$message = new com\busit\local\LocalMessage("Someone", "Anyone");
$message->content(new com\busit\local\Content($_POST['content']));

if( isset($_POST['action']['cron']) )
	$processor->_3_push(array('cron'=>$message, 'interfaceId'=>$_POST['cron_output']));
else if( isset($_POST['action']['sample']) )
	$processor->_3_push(array('sample'=>$message, 'interfaceId'=>$_POST['sample_output']));
else if( isset($_POST['action']['send']) )
	$processor->_3_push(array('input'=>$message, 'interfaceId'=>$_POST['send_input']));

if( isset($_POST['action']['cron']) )
	log_message($processor->_4_pull(array('interfaceId'=>$_POST['cron_output'])));
else if( isset($_POST['action']['sample']) )
	log_message($processor->_4_pull(array('interfaceId'=>$_POST['sample_output'])));
else if( isset($_POST['action']['send']) )
	foreach( $_POST['outputs'] as $name=>$data )
		log_message($processor->_4_pull(array('interfaceId'=>$name)));

$content = "<pre class=\"success\">" . file_get_contents("TEMP/busit.log") . "</pre>";
file_put_contents("TEMP/busit.log", ""); // reset the log

return $content;

?>