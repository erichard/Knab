<?php

namespace Knab;

class Bank {

    protected $backend;

    public function __construct(Backend\BackendAbstract $backend) {
        $this->setBackend($backend);
    }

    public function setBackend(Backend\BackendAbstract $backend) {
        $this->backend = $backend;
    }

    public function getAccounts(){
        return $this->backend->getAccounts();
    }
}
