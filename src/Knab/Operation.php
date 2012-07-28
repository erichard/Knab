<?php

namespace Knab;

use Knab\Account;

/**
 * The lower level of Knab. Operation is a unique transaction in a account.
 */
class Operation
{

    protected $account;
    protected $date;
    protected $label;
    protected $amount;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * Set Account
     *
     * @param Account $account
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;
    }

    /**
     * Set Label
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Set Amount
     *
     * @param integer $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Set Date
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Get account
     *
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
       return $this->label;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
       return $this->amount;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
       return $this->date;
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            '% 10s - %s - %5.2f',
            $this->getDate(),
            $this->getLabel(),
            $this->getAmount()
        );
    }

}
