<?php

use Phalcon\DI\FactoryDefault,
	Phalcon\Mvc\Micro,
	Phalcon\Http\Response;

$di = new FactoryDefault();

$di->set('config', function () {
	$config = new \Phalcon\Config\Adapter\Ini('/../app/config/config.ini');
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

	$response->setContent('good');
	return $response;
});

$app->handle();
