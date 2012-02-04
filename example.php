<?php

require_once __DIR__.'/lib/Knab/ClassLoader.php';
Knab\ClassLoader::register();

if (!is_file(__DIR__.'/config.php')) {
    die ('No config file found !'.PHP_EOL);
}

require_once __DIR__.'/config.php';

$knab = new Knab\Knab();

$knab->registerBank(
    'LaBanquePostale',
    $bank_lbp
);

//header('Content-Type:text/plain');
foreach($knab->getAccounts() as $account){

    printf('<h1>%s - %s : % -5.2f</h1>'.PHP_EOL,
        str_replace( 'Knab\\Backend\\','',get_class($account->getBank()->getBackend()) ),
        $account->getLabel(),
        $account->getBalance()
    );

    $history = $account->getHistory();

    echo '<table>'.PHP_EOL;
    foreach($history as $operation){
        echo '<tr>'.PHP_EOL;
        echo '<td>';
        echo $operation->getDate('d/m/Y');
        echo '</td>'.PHP_EOL;
        echo '<td>'.PHP_EOL;
        echo nl2br($operation->getLabel());
        echo PHP_EOL.'</td>'.PHP_EOL;

        echo '<td align="right">';
        printf ('% -4.2f',$operation->getAmount());
        echo '</td>'.PHP_EOL;

        echo '</tr>'.PHP_EOL;
    }
    echo '</table>'.PHP_EOL;

}
