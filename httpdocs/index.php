<?php

use \Phalcon\Mvc\Micro,
    \Phalcon\DI\FactoryDefault;

$di = new FactoryDefault();

$di->set('config', function() {
    $config = new \Phalcon\Config\Adapter\Ini('/../app/config/config.ini');
    return $config;
});

$app = new Micro($di);

$app->get('/', function() use ($app) {
    $ip = $app->request->getServer('REMOTE_ADDR');
    $app->response->setContent($ip);
});

$app->get('/nic/update', function() use ($app) {
    $hostname = $app->request->getQuery('hostname');
    $ip = $app->request->getQuery('myip');
    error_log($hostname);
    error_log($ip);
});

$app->handle();
