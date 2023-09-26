<?php
use Symfony\Component\HttpFoundation\Request;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup
// for more information
//umask(0000);

$allowedIps = [
    '127.0.0.1'
  , 'fe80::1'
  , '::1'
  , '192.168.56.1' # local PC
  , '192.168.56.101' # local PC
  , '192.168.56.20' # local XServer
  , '192.168.56.30' # local Cube
  , '192.168.56.40' # local Dev
  , '124.110.136.202'
  , '10.0.2.2'
  , '10.0.40.1'
  , 'fd42:b880:eea9:72a1::1' // 10.0.40.1
  , '10.0.40.100'
];


// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
if (isset($_SERVER['HTTP_CLIENT_IP'])
//  || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
  || !(in_array(@$_SERVER['REMOTE_ADDR'], $allowedIps) || php_sapi_name() === 'cli-server')
) {
  header('HTTP/1.0 403 Forbidden');
  exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';
// Debug::enable();

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('test', true); // debug ON
// $kernel = new AppKernel('test', false); // debug OFF

$kernel->loadClassCache();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
