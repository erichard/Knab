<?php

require_once __DIR__.'/lib/Knab/ClassLoader.php';
Knab\ClassLoader::register();

require_once 'config.php';

$knab = new Knab\Knab();

/*
$knab->registerBank(
    'LaBanquePostale',
    $bank_lbp
);
*/

$knab->registerBank(
    'CreditAgricole',
    $bank_cragr
);

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
