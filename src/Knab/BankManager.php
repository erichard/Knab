<?php

namespace Knab;

use Knab\Backend\BackendInterface;

/**
 * Manager your bank with the BankManager
 */
class BankManager
{
    /**
     * Collection of banks
     * @var array
     */
    protected $sources = array();

    /**
     * Register a new bank in the manager
     *
     * @param Backendinterface $backend
     */
    public function registerNewBank(BackendInterface $backend)
    {
        $bank = new Bank();
        $bank->setBackend($backend);

        $id = spl_object_hash($bank);
        $this->sources[$id] = $bank;
    }

    /**
     * Register an existing bank in the manager
     *
     * @param Bank $bank
     */
    public function registerBank(Bank $bank)
    {
        if (!$bank->getBackend() instanceof BackendInterface) {
            throw new \RuntimeException('Error while registering a bank without valid backend');
        }

        $id = spl_object_hash($bank);
        $this->sources[$id] = $bank;
    }

    /**
     * Unregister a bank from the manager
     *
     * @param Bank $bank
     */
    public function unregisterBank(Bank $bank)
    {
        $id = spl_object_hash($bank);
        unset($this->sources[$id]);
    }

    /**
     * Proxy method for fetching all accounts of all registered banks.
     *
     * @return array
     */
    public function getAccounts()
    {
        $accounts = array();
        foreach ($this->sources as $bank) {
            if (!$bank->getBackend()->isLogged()) {
                $bank->getBackend()->login();
            }
            $accounts = array_merge($accounts, $bank->getAccounts());
        }

        return $accounts;
    }

    /**
     * Get the registered bank
     *
     * @return array
     */
    public function getBanks()
    {
        return $this->sources;
    }
}
