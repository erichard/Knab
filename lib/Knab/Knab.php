<?php

namespace Knab;

class Knab {

    protected $sources = array();

    public function registerBank($backend, array $credentials){
        if (!$backend)
            throw new \Exception('I need a backend to work !');

        $backend = 'Knab\\Backend\\'.$backend;
        if (!class_exists($backend))
            throw new \Exception(sprintf('Unknown backend %s',$backend));

        $bank = new Bank();
        $bank->setBackend(new $backend($credentials));

        $id = spl_object_hash($bank);
        $this->sources[$id] = $bank;

        return $this;
    }

    public function unregisterBank(Bank $bank){
        $id = spl_object_hash($bank);
        unset($this->sources[$id]);
        return $this;
    }

    public function getAccounts(){
        $accounts = array();
        foreach ($this->sources as $bank){
            $accounts = array_merge($accounts,$bank->getAccounts());
        }

        return $accounts;
    }

    public function getBank(){
        return $this->sources;
    }
}


