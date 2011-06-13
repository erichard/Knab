<?php

namespace Knab;

class Operation {

    protected $date;
    protected $label;
    protected $amount;

    /**
     * Setters
     */

    public function setLabel($label) {
        $this->label = $label;
        return $this;
    }

    public function setAmount($amount) {
        $this->amount = (float) $amount;
        return $this;
    }

    public function setDate(\DateTime $date) {
        $this->date = $date;
        return $this;
    }

    /**
     * Getters
     */

    public function getLabel() {
       return $this->label;
    }

    public function getAmount() {
       return $this->amount;
    }

    public function getDate($format = 'm-d-Y') {
       return $this->date->format($format);
    }




}
