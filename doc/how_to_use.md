How To Use Knab
===============

Initializing
------------

```php
require_once __DIR__.'/lib/Knab/ClassLoader.php';
Knab\ClassLoader::register();
$knab = new Knab\Knab();
```

Register bank connection
------------------------

```php
$knab->registerBank(
    'LaBanquePostale',
    array(
        'username' => '123098123', // bank id
        'password' => '123456'     // Your very strong password
    )
);

$knab->registerBank(
    'CreditAgricole',
    array(
        'site'     => 'm.ca-atlantique-vendee.fr', // A site to connect to
        'username' => '090983',                    // bank id
        'password' => '123456'                     // Your very strong password,

    )
);
```

Fetch all accounts from a registered bank
--------------------------------------------

```php
foreach($knab->getAccounts() as $account){
    printf('%s : % -5.2f',
        $account->getLabel(),
        $account->getBalance()
    );
}
```

Fetch the 30 last operations
---------------------------------

```php
$history = $account->getHistory(30);
foreach($history as $operation){
    printf('%s : %s % -5.2f',
        $account->getDate('m/d/Y'),
        $account->getLabel(),
        $account->getAmount()
    );
}
```