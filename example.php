<?php

error_reporting(E_ALL ^ E_STRICT);

ini_set('display_errors',1);
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

foreach($accounts as $account){

    printf("### % 15s ###\n",$account->getId());
    //echo $account->getLink();

    $history = $backend->getHistory($account);

    print_r($history);
}
