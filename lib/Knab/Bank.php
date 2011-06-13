<?php

namespace Knab;

class Bank {

    protected $backend;
    protected $accounts = array();

    public function __construct() {

    }

    public function setBackend(Backend\BackendAbstract $backend) {
        $this->backend = $backend;
    }

    public function getBackend(){
        return $this->backend;
    }

    public function getAccounts(){

        if (!$this->backend->isLogged())
            $this->backend->login();

        return $this->backend->getAccounts($this);
    }

    public function __toString(){
        return get_class($this->backend);
    }

}
