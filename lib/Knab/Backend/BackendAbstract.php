<?php

namespace Knab\Backend;

use Knab\Exception as Exception;
use Knab\Account;
use Knab\Bank;

abstract class BackendAbstract {

    protected $logged = false;

    public function __construct(array $credentials){
        $this->credential = $credentials;
    }

    public function isLogged(){
        return (Boolean) $this->logged;
    }

    abstract public function login();
    abstract public function getAccounts(Bank $bank);
    abstract public function getHistory(Account $account, $count = 15);
}
