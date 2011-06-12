<?php

namespace Knab\Backend;

abstract class BackendAbstract {

    protected $username;
    protected $password;
    protected $logged;

    public function __construct(array $options){
        if (!array_key_exists('username',$options)) {
            throw new Exception('We need a username to login !');
        }

        if (!array_key_exists('password',$options)) {
            throw new Exception('We need a password to login !');
        }

        $this->setUsername($options['username']);
        $this->setPassword($options['password']);
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    abstract public function getAccounts();
    abstract public function getAccount($account_id);

    abstract public function getOperations($account);
}
