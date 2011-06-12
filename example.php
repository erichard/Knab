<?php

set_include_path(implode(
    PATH_SEPARATOR, array(
        __DIR__.'/lib',
        get_include_path()
    )
));
require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance();

require_once 'Knab/ClassLoader.php';
Knab\ClassLoader::register();

header('Content-Type:text/plain;');
$backend = new Knab\Backend\LaBanquePostale(array(
    'username' => '0320981175',
    'password' => '084860'
));

$cookie_file = '/tmp/cookie.txt';

$bank = new Knab\Bank($backend);
$accounts = $bank->getAccounts();


var_dump($accounts);
