<?php

namespace Knab;

class Account {

    protected $bank;
    protected $id;
    protected $label;
    protected $balance;
    protected $link;

    public function __construct(){}

    public function getHistory($count = 15){
        return $this->bank->getBackend()->getHistory($this,$count);
    }

    /**
     * Setters
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function setLabel($label) {
        $this->label = $label;
        return $this;
    }

    public function setBalance($balance) {
        $this->balance = (float) $balance;
        return $this;
    }

    public function setLink($link) {
        $this->link = $link;
        return $this;
    }

    public function setBank(Bank $bank) {
        $this->bank = $bank;
        return $this;
    }

    /**
     * Getters
     */
    public function getId() {
       return $this->id;
    }

    public function getLabel() {
       return $this->label;
    }

    public function getBalance() {
       return $this->balance;
    }

    public function getLink() {
       return $this->link;
    }

    public function getBank() {
       return $this->bank;
    }




}
