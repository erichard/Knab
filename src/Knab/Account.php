<?php

namespace Knab;

/**
 * An Account that hold many operations
 */
class Account
{
    /**
     * An account identifier
     * @var string
     */
    protected $id;

    /**
     * The bank holding this account
     * @var Knab\Bank
     */
    protected $bank;

    /**
     * Text that describe this account
     * @var string
     */
    protected $label;

    /**
     * The current balance.
     *
     * As PHP is not very good at performing floating number operation,
     * we only use integer
     *
     * @var integer
     */
    protected $balance;

    /**
     * Http link the the account
     *
     * @var string
     */
    protected $link;

    /**
     * Fetch this account operations history.
     *
     * @param integer $limit
     *
     * @return array
     */
    public function getHistory($limit = 15)
    {
        return $this
            ->bank
            ->getBackend()
            ->getHistory($this, $limit)
        ;
    }

    /**
     * Set Id
     *
     * @param integer $id
     *
     * @return Account
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set label
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Set label
     *
     * @param integer $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * Set label
     *
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Set label
     *
     * @param Bank $bank
     */
    public function setBank(Bank $bank)
    {
        $this->bank = $bank;
    }

    /**
     * Get Id
     *
     * @return integer
     */
    public function getId()
    {
       return $this->id;
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getLabel()
    {
       return $this->label;
    }

    /**
     * Get Id
     *
     * @return integer
     */
    public function getBalance()
    {
       return $this->balance;
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getLink()
    {
       return $this->link;
    }

    /**
     * Get Id
     *
     * @return Bank
     */
    public function getBank()
    {
       return $this->bank;
    }

}
