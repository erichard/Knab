<?php

require_once __DIR__.'/lib/Knab/ClassLoader.php';
Knab\ClassLoader::register();

require_once 'config.php';

$knab = new Knab\Knab();

$knab->registerBank(
    'LaBanquePostale',
    $bank_lbp
);

$knab->registerBank(
    'CreditAgricole',
    $bank_cragr
);

//header('Content-Type:text/plain');
foreach($knab->getAccounts() as $account){

    printf('<h1>%s - %s : % -5.2f</h1>',
        str_replace( 'Knab\\Backend\\','',get_class($account->getBank()->getBackend()) ),
        $account->getLabel(),
        $account->getBalance()
    );

    $history = $account->getHistory();

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
