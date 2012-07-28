<?php

require_once __DIR__.'/../vendor/autoload.php';

if (!is_file(__DIR__.'/config.php')) {
    die ('No config file found !'.PHP_EOL);
}

require_once __DIR__.'/config.php';

$bankLbp = new Knab\Backend\LaBanquePostale();
$bankLbp->setCredentials($bankLbpCredentials);

$manager = new Knab\BankManager();
$manager->registerNewBank($bankLbp);

foreach ($manager->getAccounts() as $account) {
    printf(
        '<h1>%s - %s : % -5.2f</h1>' . PHP_EOL,
        str_replace('Knab\\Backend\\', '', get_class($account->getBank()->getBackend())),
        $account->getLabel(),
        ($account->getBalance()/100)
    );

    $history = $account->getHistory(0);

    echo '<table>'.PHP_EOL;
    foreach ($history as $operation) {
        echo '<tr>'.PHP_EOL;
        echo '<td>';
        echo $operation->getDate()->format('d/m/Y');
        echo '</td>'.PHP_EOL;
        echo '<td>'.PHP_EOL;
        echo nl2br($operation->getLabel());
        echo PHP_EOL.'</td>'.PHP_EOL;

        echo '<td align="right">';
        printf('% -4.2f', $operation->getAmount()/100);
        echo '</td>'.PHP_EOL;

        echo '</tr>'.PHP_EOL;
    }
    echo '</table>'.PHP_EOL;

}
