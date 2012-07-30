<?php

namespace Knab\Backend;

use Knab\Account;
use Knab\Operation;
use Knab\Bank;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Backend for "La Banque Postale"
 */
class LaBanquePostale implements BackendInterface
{
    const NAME = 'La Banque Postale';

    /**
     * @var array
     */
    private $credentials;

    /**
     * @var boolean
     */
    private $logged;

    /**
     * The HTTP client
     * @var Goutte\Client
     */
    protected $browser;

    /**
     * {@inheritDoc}
     */
    public function login()
    {
        // Exit if already logged in
        if ($this->logged) {
            return true;
        }

        // Check an existing session
        $browser = $this->getBrowser();
        $crawler = $browser->request('GET', $this->getUri('init-synthese'));

        if ($crawler->filter('table#comptes')->count() > 0) {
            $this->logged = true;

            return true;
        }

        // valid md5 hashes of the keypad
        $hash = array(
            'a02574d7bf67677d2a86b7bfc5e864fe',
            'eb85e1cc45dd6bdb3cab65c002d7ac8a',
            '596e6fbd54d5b111fe5df8a4948e80a4',
            '9cdc989a4310554e7f5484d0d27a86ce',
            '0183943de6c0e331f3b9fc49c704ac6d',
            '291b9987225193ab1347301b241e2187',
            '163279f1a46082408613d12394e4042a',
            'b0a9c740c4cada01eb691b4acda4daea',
            '3c4307ee92a1f3b571a3c542eafcb330',
            'dbccecfa2206bfdb4ca891476404cc68'
        );

        // Grab the login form to initialize a session
        $browser->request('GET', $this->getUri('auth-form'));

        // Compute the hashes of the key
        $md5Map = array();
        for ($i = 0; $i < 10; $i++) {
            $url = sprintf($this->getUri('img'), $i);
            $browser->request('GET', $url);
            $md5Map[$i] = md5($browser->getResponse()->getContent());
        }
        $md5Map = array_unique($md5Map);

        // We need 10 keys
        if (count($md5Map) != 10) {
            throw new \RuntimeException("Unable to compute password ");
        }

        // Order the hash array according to actual key position
        $numbers = array();
        foreach ($hash as $idx => $img) {
            $numbers[$idx] = array_search($img, $md5Map);
        }

        // Build the password
        $hashPassword = "";
        $credentials = $this->getCredentials();
        foreach (str_split($credentials['password']) as $char) {
            $hashPassword .= $numbers[$char];
        }

        // POST the login form
        $crawler = $browser->request('POST', $this->getUri('post-auth'), array(
            'username'    => $credentials['username'],
            'password'    => $hashPassword,
            'urlbackend'  => $this->getUri('url-backend'),
            'origin'      => 'particuliers',
            'cv'          => 'true',
            'cvvs'        => ''
        ));

        // Too many failure
        if ($crawler->filter('div.textFCK')->count() > 0) {
            $this->logged = false;

            return $this->logged;
        }

        // Handle the login proccess manually
        $browser->request('GET', $this->getUri('init-login'));
        $browser->request('GET', $this->getUri('check-password'));
        $crawler = $browser->request('GET', $this->getUri('init-synthese'));

        // If we see the account table we have won :)
        if ($crawler->filter('table#comptes')->count() > 0) {
            $this->logged = true;
        } else {
            $this->logged = false;
        }

        return $this->logged;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccounts(Bank $bank)
    {
        // We need to be logged for this
        if (!$this->logged) {
            throw new \RuntimeException('Call login() first');
        }

        // Get the accounts list
        $browser = $this->getBrowser();
        $crawler = $browser->request('GET', $this->getUri('show-synthese'));

        // Loop on each of them
        $accounts = array();
        $xmlAccounts = $crawler->filter('table#comptesEpargne tbody tr, table#comptes tbody tr');
        foreach ($xmlAccounts as $account) {

            // Reset the crawler for further re-use
            $crawler->clear();
            $crawler->add($account);

            // Crawl the account informations
            $cell1 = $crawler->filter('td.bdrT');
            $cell2 = $crawler->filter('td.bdrL.num')->first();
            $cell3 = $crawler->filter('td.bdrL.num')->eq(1);

            // Build a new account
            $account = new Account();
            $account->setBank($bank);
            $account->setId($cell2->text());
            $account->setLabel($cell1->text());
            $account->setLink(str_replace('../..', $this->getUri('prefix'), $cell1->filter('a')->attr('href')));
            $account->setBalance($this->parseAmount($cell3->text()));

            $accounts[] = $account;
        }

        return $accounts;
    }

    /**
     * {@inheritDoc}
     */
    public function getHistory(Account $account, $limit = 15)
    {
        // We need to be logged for this
        if (!$this->logged) {
            throw new \RuntimeException('Call login() first');
        }

        $browser = $this->getBrowser();

        // Tweak the account link to retrieve the maximum operations
        $link = preg_replace('/typeRecherche=(\d){0,2}/', 'typeRecherche=10', $account->getLink());
        $crawler = $browser->request('GET', $link);

        // Prepare operations array
        $operations = array();

        // Loop on each row
        $xmlOperations = $crawler->filter('#mouvements tbody tr');
        foreach ($xmlOperations as $idx => $operation) {

            // Break loop if limit is reached
            if ($limit > 0 && $idx >= $limit) {
                break;
            }

            // Clean and restart the crawler
            $crawler->clear();
            $crawler->add($operation);

            // Build an operation
            $operation = new Operation();
            $operation->setAccount($account);

            // operation date
            $date = $crawler->filter('td')->first()->text();
            $dateTime = \DateTime::createFromFormat('d/m/Y', $date, new \DateTimeZone('Europe/Paris'));
            $dateTime->setTime(0, 0, 0);
            $operation->setDate($dateTime);

            // operation title
            $label = $crawler->filter('td')->eq(1)->each(function ($node, $i){
                $innerHTML = '';
                foreach ($node->childNodes as $child) {
                    $innerHTML .= $child->ownerDocument->saveXml($child);
                }
                return $innerHTML;
            });
            $label = strip_tags($label[0], '<br>');
            $label = preg_replace("#<br ?/>#", "\n", $label);
            $label = trim($label);

            $operation->setLabel($label);

            // operation amount
            $amounts = $crawler->filter('td.num.bdrL');
            $debit   = $this->parseAmount($amounts->first()->text());
            $credit  = $this->parseAmount($amounts->eq(1)->text());
            $amount = $credit === 0 ? $debit: $credit;
            $operation->setAmount($amount);

            $operations[] = $operation;
        }

        return $operations;
    }


    /**
     * Used internally to create/get the HTTP client
     *
     * @return Goutte\Client
     */
    private function getBrowser()
    {
        if (!$this->browser instanceof Client) {
            $browser = new Client();
            $browser->setServerParameter('HTTP_USER_AGENT', 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)');

            $this->browser = $browser;
        }

        return $this->browser;
    }

    /**
     * Parse an monetary text. Knab use integer for all amount variable to
     * avoid floating number.
     *
     * @param string $amount An amount
     *
     * @return integer
     */
    private function parseAmount($amount)
    {
        return (int) preg_replace("/[\\xA0\\xC2,]/", '', $amount);
    }

    /**
     * Contains all urls used in this backend
     *
     * @param string $key A key
     *
     * @return string A URI
     */
    private function getUri($key)
    {
        $uris = array(
            'prefix'         => '/voscomptes/canalXHTML',
            'show-synthese'  => '/voscomptes/canalXHTML/comptesCommun/synthese_assurancesEtComptes/afficheSynthese-synthese.ea',
            'init-synthese'  => '/voscomptes/canalXHTML/comptesCommun/synthese_assurancesEtComptes/init-synthese.ea',
            'check-password' => '/voscomptes/canalXHTML/securite/authentification/verifierMotDePasse-identif.ea',
            'init-login'     => '/voscomptes/canalXHTML/securite/authentification/initialiser-identif.ea',
            'url-backend'    => '/voscomptes/canalXHTML/identif.ea?origin=particuliers',
            'post-auth'      => '/wsost/OstBrokerWeb/auth',
            'img'            => '/wsost/OstBrokerWeb/loginform?imgid=%d&0.25122230781963073',
            'auth-form'      => '/wsost/OstBrokerWeb/loginform?TAM_OP=login&ERROR_CODE=0x00000000&URL=%2Fvoscomptes%2FcanalXHTML%2Fidentif.ea%3Forigin%3Dparticuliers'
        );

        if (!array_key_exists($key, $uris)) {
            throw new \RuntimeException(sprintf(
                'The %s key is not registred in the URI map of "%s" backend',
                $key,
                get_class($this)
            ));
        }

        $host = 'https://voscomptesenligne.labanquepostale.fr';

        return $host . $uris[$key];
    }

    /**
     * {@inheritDoc}
     */
    public function setCredentials(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * {@inheritDoc}
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * {@inheritDoc}
     */
    public function isLogged()
    {
        return $this->logged;
    }
}
