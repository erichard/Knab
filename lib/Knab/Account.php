<?php

namespace Knab;

class Account {

    protected $id;
    protected $label;
    protected $balance;
    protected $link;

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

}
