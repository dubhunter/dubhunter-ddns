<?php

require '../vendor/autoload.php';

use Phalcon\DI\FactoryDefault,
	Phalcon\Mvc\Micro,
	Phalcon\Http\Response,
	Aws\Route53\Route53Client;

$di = new FactoryDefault();

$di->set('config', function () {
	$config = new \Phalcon\Config\Adapter\Ini('../app/config/config.ini');
	return $config;
});

$app = new Micro($di);

$app->notFound(function () {
	$response = new Response();
	$response->setStatusCode(404, 'Not Found')->sendHeaders();
	return $response;
});

$app->get('/', function () use ($app) {
	$ip = $app->request->getServer('REMOTE_ADDR');
	$response = new Response();
	$response->setContentType('text/plain')->setContent($ip);
	return $response;
});

$app->get('/nic/update', function () use ($app) {
	$response = new Response();
	$response->setContentType('text/plain');

	$hostname = $app->request->getQuery('hostname');
	$ip = $app->request->getQuery('myip');
	error_log($hostname);
	error_log($ip);

	$r53 = Route53Client::factory(array(
		'key' => $app->getDI()->get('config')->aws->key,
		'secret' => $app->getDI()->get('config')->aws->secret,
	));

	error_log(print_r($r53->listResourceRecordSets(array(
		'HostedZoneId' => 'ZGTCRIQVM9TGC',
		'StartRecordName' => $hostname,
	)), true));

	$response->setContent('good');
	return $response;
});

$app->handle();
