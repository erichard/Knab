<?php

namespace Knab\Backend;

use Knab\Account;
use Knab\Bank;

/**
 * Provide a interface for building backends.
 */
interface BackendInterface
{

    /**
     * Set the credentials
     *
     * @param array $credentials
     */
    public function setCredentials(array $credentials);

    /**
     * Get credentials
     *
     * @return array
     */
    public function getCredentials();

    /**
     * Return the login status.
     *
     * @return boolean
     */
    public function isLogged();

    /**
     * Login method
     *
     * @return boolean true if login success, false otherwise
     * @throws \RuntimeException
     */
    public function login();

    /**
     * Fetch All account for a given Bank
     *
     * @param Bank $bank
     *
     * @throws \RuntimeException
     * @return array
     */
    public function getAccounts(Bank $bank);

    /**
     * Fetch operation history for an account
     *
     * @param Account $account The account
     * @param integer $limit   number of operation to fetch. Set 0 for maximum history (depending on your bank).
     *
     * @throws \RuntimeException
     * @return array
     */
    public function getHistory(Account $account, $limit = 15);
}
