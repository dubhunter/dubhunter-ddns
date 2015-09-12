<?php

require '../vendor/autoload.php';

use Phalcon\DI\FactoryDefault,
	Phalcon\Mvc\Micro,
	Phalcon\Http\Response,
	Aws\Route53\Route53Client;

$di = new FactoryDefault();

$di->set('config', function () {
	$config = new \Phalcon\Config\Adapter\Ini('../conf/config.ini');
	return $config;
});

$app = new Micro($di);

$app->notFound(function () {
	$response = new Response();
	$response->setStatusCode(404, 'Not Found')->sendHeaders();
	return $response;
});

$app->get('/', function () use ($app) {
	$ip = $app->request->hasServer('HTTP_X_FORWARDED_FOR') ? $app->request->getServer('HTTP_X_FORWARDED_FOR') : $app->request->getServer('REMOTE_ADDR');
	$response = new Response();
	$response->setContentType('text/plain')->setContent($ip);
	return $response;
});

$app->get('/nic/update', function () use ($app) {
	$response = new Response();
	$response->setContentType('text/plain');

	$hostname = $app->request->getQuery('hostname');
	$ip = $app->request->getQuery('myip');

	$r53 = Route53Client::factory(array(
		'key' => $app->getDI()->get('config')->aws->key,
		'secret' => $app->getDI()->get('config')->aws->secret,
	));

	try {
		$recordResponse = $r53->listResourceRecordSets(array(
			'HostedZoneId' => $app->getDI()->get('config')->aws->zone,
			'StartRecordName' => $hostname,
			'MaxItems' => '1',
		));

		$recordSets = $recordResponse->get('ResourceRecordSets');

		if (count($recordSets) == 0) {
			$response->setContent('nohost');
		} elseif ($recordSets[0]['ResourceRecords'][0]['Value'] == $ip) {
			$response->setContent('nochg');
		} else {
			$r53->changeResourceRecordSets(array(
				'HostedZoneId' => $app->getDI()->get('config')->aws->zone,
				'ChangeBatch' => array(
					'Changes' => array(
						array(
							'Action' => 'UPSERT',
							'ResourceRecordSet' => array(
								'Name' => $hostname,
								'Type' => 'A',
								'TTL' => 60,
								'ResourceRecords' => array(
									array(
										'Value' => $ip,
									),
								),
							),
						),
					),
				),
			));

			$response->setContent('good');
		}
	} catch (Exception $e) {
		error_log($e->getMessage());
		$response->setContent('911');
	}

	return $response;
});

$app->handle();
