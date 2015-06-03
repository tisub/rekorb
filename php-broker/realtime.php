#!/usr/bin/env php
<?php

error_reporting(0);

require_once('lib/SiteException.inc');
require_once('lib/api.inc');

$conf = parse_ini_file('/dns/com/bus-it/etc/settings/bin.ini', true);

$GLOBALS['CONFIG']['HOST']              = $conf['Main']['RABBIT_HOST'];
$GLOBALS['CONFIG']['VHOST']             = $conf['Main']['RABBIT_VHOST'];
$GLOBALS['CONFIG']['USER']              = $conf['Main']['RABBIT_USERNAME'];
$GLOBALS['CONFIG']['PASS']              = $conf['Main']['RABBIT_PASSWORD'];
$GLOBALS['CONFIG']['PORT']              = $conf['Main']['RABBIT_PORT'];
$GLOBALS['CONFIG']['API_HOST']          = 'https://' . $conf['Main']['API_HOST'];
$GLOBALS['CONFIG']['API_USERNAME']      = $conf['Main']['API_USERNAME'];
$GLOBALS['CONFIG']['API_PASSWORD']      = $conf['Main']['API_TOKEN'];

$handler = new \AMQPConnection(array('host'=>$GLOBALS['CONFIG']['HOST'], 'vhost'=>$GLOBALS['CONFIG']['VHOST'], 'login'=>$GLOBALS['CONFIG']['USER'], 'password'=>$GLOBALS['CONFIG']['PASS'], 'port'=>$GLOBALS['CONFIG']['PORT']));
$handler->connect();

$channel = new \AMQPChannel($handler);
$exchange = new \AMQPExchange($channel);

$exchange->setName('busit');
$exchange->setType(AMQP_EX_TYPE_TOPIC);
$exchange->setFlags(AMQP_DURABLE);
$exchange->declare();

$queue = new \AMQPQueue($channel);
$queue->setFlags(AMQP_DURABLE);
$queue->setName('trace');
$queue->declare();
$queue->bind('busit', 'transit.*');

$handle = fopen('/var/log/busit/trace.log', 'a');

$memcache = new Memcache();
$memcache->connect('bi-001.vlan-101', 11211);

function getCache($to, $user)
{
        global $memcache;

        $cache = array("user_name"=>null, "connector_name"=>null, "instance_connector"=>null, "instance_name"=>null);

        $data_to = null;
        if( $memcache )
                $data_to = $memcache->get('_tracer_to_'.$to);

        if( !$data_to )
        {
                $data_to = api::send('busit/instance/select', array('id'=>$to, 'lang'=>'EN', 'extended'=>1), $GLOBALS['CONFIG']['API_USERNAME'].':'.$GLOBALS['CONFIG']['API_PASSWORD']);
                if( !isset($data_to[0]) )
                        return null;

                $data_to = array("connector_name"=>$data_to[0]['connector']['connector_name'], "instance_connector"=>$data_to[0]['instance_connector'], "instance_name"=>$data_to[0]['instance_name']);

                if( $memcache )
                        $memcache->set('_tracer_to_'.$to, $data_to, 0, 360000); // 100 hours validity
        }
        $cache["connector_name"] = $data_to["connector_name"];
        $cache["instance_connector"] = $data_to["instance_connector"];
        $cache["instance_name"] = $data_to["instance_name"];

        $data_user = null;
        if( $memcache )
                $data_user = $memcache->get('_tracer_user_'.$user);

        if( !$data_user )
        {
                $data_user = api::send('system/user/select', array('id'=>$user), $GLOBALS['CONFIG']['API_USERNAME'].':'.$GLOBALS['CONFIG']['API_PASSWORD']);
                if( !isset($data_user[0]) )
                        return null;
                $data_user = $data_user[0]["user_name"];

                if( $memcache )
                        $memcache->set('_tracer_user_'.$user, $data_user, 0, 360000); // 100 hours validity
        }
        $cache["user_name"] = $data_user;

        return $cache;
}

while( true )
{
        try
        {
        while( ($content = $queue->get()) === false )
                usleep(250000);
        $message = $content->getBody();
        $key = $content->getRoutingKey();
        $tag = $content->getDeliveryTag();

        $message = json_decode($message, true);
        $date = date('[d/M/Y:H:i:s +0200]', $message['time']/1000);

        $cache = getCache($message['to'], $message['user']);
        if( $cache['user_name'] && $cache['instance_connector'] )
        {
                $line = "{$date} {$message['id']} {$cache['user_name']} \"{$message['sender']}\" \"{$cache['connector_name']}\" {$cache['instance_connector']} {$cache['instance_name']} {$message['tax']} {$message['size']}\n";
                fwrite($handle, $line);
                echo $line;
        }

        $queue->ack($tag);
        }
        catch(Exception $e)
        {
        //      echo $e;
        }
}

if( $memcache )
        $memcache->close();

fclose($handle);

?>