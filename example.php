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

$cookie_file = '/tmp/cookie.txt';

$knab = new Knab\Knab();

/*
$knab->registerBank(
    'LaBanquePostale',
    array(
        'username' => '0320981175',
        'password' => '084860'
    )
);
*/


$knab->registerBank(
    'CreditAgricole',
    array(
        'username' => '65082400001',
        'password' => '253070',
        'site'     => 'm.ca-atlantique-vendee.fr'
    )
);



/*
foreach ($knab->getBank() as $bank) {
    $bank->getAccounts();
}
*/

header('Content-Type:text/plain');
foreach($knab->getAccounts() as $account){

//    echo '<h1>'.$account->getLabel().'<span>'.$account->getBalance().'</span></h1>';

    $history = $account->getHistory();
/*
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
    * */
}
