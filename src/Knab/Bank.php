<?php

namespace Knab;

/**
 * A Bank is holding one or more Account
 */
class Bank
{
    /**
     * The backend used for that bank
     *
     * @var Backend\BackendInterface
     */
    protected $backend;

    /**
     * A collection of accounts
     *
     * @var array
     */
    protected $accounts = array();

    /**
     * Set Backend
     *
     * @param Backend\BackendInterface $backend A backend
     */
    public function setBackend(Backend\BackendInterface $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Get Backend
     *
     * @return Backend\BackendInterface
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Get Accounts
     *
     * @return array
     */
    public function getAccounts()
    {
        return $this->backend->getAccounts($this);
    }

    /**
     * String representation of this Bank
     *
     * @return string
     */
    public function __toString()
    {
        return get_class($this->backend);
    }

}
