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

$backend = new Knab\Backend\LaBanquePostale(array(
    'username' => '0320981175',
    'password' => '084860'
));

$cookie_file = '/tmp/cookie.txt';

$bank = new Knab\Bank($backend);
$accounts = $bank->getAccounts();

foreach($accounts as $account){

    echo '<h1>'.$account->getLabel().'<span>'.$account->getBalance().'</span></h1>';

    $history = $backend->getHistory($account);

    echo '<table>';
    foreach($history as $operation){
        echo '<tr>';
        echo '<td>';
        echo $operation->getDate('d/m/Y');
        echo '</td>';
        echo '<td>';
        echo $operation->getLabel();
        echo '</td>';

        echo '<td align="right">';
        printf ('% -4.2f',$operation->getAmount());
        echo '</td>';

        echo '<td>';

        echo '</tr>';
    }
    echo '</table>';
}
