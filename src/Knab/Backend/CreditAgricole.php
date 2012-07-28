<?php

namespace Knab\Backend;

use Knab\Account;
use Knab\Bank;
use Knab\Operation;

/**
 * I don't know if this backend work or not. I need to rewrite it with
 * the Goutte HTTP Client instead of Zend_Http_Client. I no longer provide
 * the Zend librairies in Knab so it MAY work if you register a zend autoloader.
 *
 * In fact, the Credit Agricol have opened an API that I may use in the future.
 * Tell me if you are interested.
 *
 * @deprecated Provided for memory
 */
class CreditAgricole implements BackendInterface
{

    const NAME = 'Crédit Agricole';

    protected $browser;
    protected $site;
    protected $scheme = 'https';
    protected $urls = array(
        'm.ca-alpesprovence.fr'     => 'Alpes Provence',
        'm.ca-anjou-maine.fr'       => 'Anjou Maine',
        'm.ca-atlantique-vendee.fr' => 'Atlantique Vendée',
        'm.ca-aquitaine.fr'         => 'Aquitaine',
        'm.ca-briepicardie.fr'      => 'Brie Picardie',
        'm.ca-centrefrance.fr'      => 'Centre France',
        'm.ca-centreloire.fr'       => 'Centre Loire',
        'm.ca-centreouest.fr'       => 'Centre Ouest',
        'm.ca-cb.fr'                => 'Champagne Bourgogne',
        'm.ca-charente-perigord.fr' => 'Charente Périgord',
        'm.ca-cmds.fr'              => 'Charente-Maritime Deux-Sèvres',
        'm.ca-corse.fr'             => 'Corse',
        'm.ca-cotesdarmor.fr'       => 'Côtes d\'Armor',
        'm.ca-des-savoie.fr'        => 'Des Savoie',
        'm.ca-finistere.fr'         => 'Finistere',
        'm.ca-paris.fr'             => 'Ile-de-France',
        'm.ca-illeetvilaine.fr'     => 'Ille-et-Vilaine',
        'm.ca-languedoc.fr'         => 'Languedoc',
        'm.ca-loirehauteloire.fr'   => 'Loire Haute Loire',
        'm.ca-lorraine.fr'          => 'Lorraine',
        'm.ca-martinique.fr'        => 'Martinique Guyane',
        'm.ca-morbihan.fr'          => 'Morbihan',
        'm.ca-norddefrance.fr'      => 'Nord de France',
        'm.ca-nord-est.fr'          => 'Nord Est',
        'm.ca-nmp.fr'               => 'Nord Midi-Pyrénées',
        'm.ca-normandie.fr'         => 'Normandie',
        'm.ca-normandie-seine.fr'   => 'Normandie Seine',
        'm.ca-pca.fr'               => 'Provence Côte d\'Azur',
        'm.lefil.com'               => 'Pyrénées Gascogne',
        'm.ca-reunion.fr'           => 'Réunion',
        'm.ca-sudrhonealpes.fr'     => 'Sud Rhône Alpes',
        'm.ca-sudmed.fr'            => 'Sud Méditerranée',
        'm.ca-toulouse31.fr'        => 'Toulouse 31',
        'm.ca-tourainepoitou.fr'    => 'Tourraine Poitou'
    );

    /**
     * {@inheritDoc}
     */
    public function __construct(array $credentials)
    {

        parent::__construct($credentials);

        if (!isset($this->urls[$credentials['site']])) {
            throw new \RuntimeException('Invalid URL '.$credentials['site']);
        }

        $this->site = $credentials['site'];
        $this->browser = $this->getBrowser();
    }

    /**
     * Used internally to create/get the HTTP client
     *
     * @return \Zend_Http_Client
     */
    public function getBrowser()
    {
        if ($this->browser == null) {
            $cookieFile = '/tmp/cookie.txt';
            $this->browser = new \Zend_Http_Client($this->scheme.'://'.$this->site, array(
                'useragent' => "wget",
                'adapter'   => 'Zend_Http_Client_Adapter_Curl',
                'curloptions' => array(
                    CURLOPT_COOKIESESSION => false,
                    CURLOPT_COOKIEJAR => $cookieFile,
                    CURLOPT_COOKIEFILE => $cookieFile
                ))
            );
        }

        return $this->browser;
    }

    /**
     * {@inheritDoc}
     */
    public function login()
    {
        if ($this->isLogged()) {
            return true;
        }

        $browser = $this->getBrowser();
        $response = $browser->request()->getBody();

        $dom = new \DomDocument();
        @$dom->loadHTML($response);
        $xpath = new \DomXPath($dom);

        if ($xpath->query('//input[@name = "code"]')->length == 0
            && $xpath->query('//input[@name = "userPassword"]')->length == 0
            && $xpath->query('//img[@alt = "Attention"]')->length == 0) {

            $this->logged = true;

            return true;
        }

        $form = $xpath->query("//form")->item(0);
        $response = $browser
            ->setUri($this->scheme.'://'.$this->site.$form->getAttribute('action'))
            ->setParameterPost(array(
                'numero'        => $this->credential['username'],
                'code'          => $this->credential['password'],
                'userLogin'     => $this->credential['username'],
                'userPassword'  => $this->credential['password']
            ))
            ->request("POST");

        $dom = new \DomDocument();
        @$dom->loadHTML($response);
        $xpath = new \DomXPath($dom);

        if ($xpath->query('//input[@name = "code"]')->length == 0
            && $xpath->query('//input[@name = "userPassword"]')->length == 0
            && $xpath->query('//img[@alt = "Attention"]')->length == 0
            ) {
            $this->logged = true;

            return true;
        } else {
            throw new \RuntimeException('Login error');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAccounts(Bank $bank)
    {
        if (!$this->logged) {
            throw new \RuntimeException('Call login() first');
        }

        $accounts = array();

        $browser = $this->getBrowser();
        $response = $browser->request()->getBody();

        $dom = new \DomDocument();
        @$dom->loadHTML($response);
        $xpath = new \DomXPath($dom);

        foreach ($xpath->query("//div[@class='dv']") as $div) {

            $xmlAccount = $xpath->query("a", $div);

            if ($xmlAccount->length > 0) {
                $id = preg_replace('/(.*)<br *\/>(\d*)<div.*/', '$2', $dom->saveXml($div));

                $balance = str_replace(',', '.', $xpath->query("div", $div)->item(0)->textContent);
                $balance = preg_replace("/[\\xA0\\xC2 €]/", '', $balance);

                $account = new Account();
                $account
                    ->setBank($bank)
                    ->setId($id)
                    ->setLabel($xmlAccount->item(0)->textContent)
                    ->setLink($xmlAccount->item(0)->getAttribute('href'))
                    ->setBalance($balance);

                $accounts[$account->getId()] = $account;
            }
        }

        return $accounts;
    }

    /**
     * {@inheritDoc}
     */
    public function getHistory(Account $account, $count = 15)
    {
        if (!$this->logged) {
            throw new \RuntimeException('Call login() first');
        }

        $browser = $this->getBrowser();
        $browser
            ->setUri($this->scheme.'://'.$this->site.$account->getLink())
            ->request();
        $browser
            ->setUri($this->scheme.'://'.$this->site.'/accounting/showMoreAccountOperations')
            ->request();
        $browser
            ->setUri($this->scheme.'://'.$this->site.'/accounting/showMoreAccountOperations')
            ->request();
        $browser
            ->setUri($this->scheme.'://'.$this->site.'/accounting/showMoreAccountOperations')
            ->request();
        $response = $browser
            ->setUri($this->scheme.'://'.$this->site.'/accounting/showMoreAccountOperations')
            ->request()
            ->getBody();

        $dom = new \DomDocument();
        @$dom->loadHTML($response);
        $xpath = new \DomXPath($dom);
        $lignes = $xpath->query("//table[@class='tb']/tr");

        $operations = array();
        $cpt = 0;
        foreach ($lignes as $idx => $ligne) {

            $tds = $xpath->query('td', $ligne);

            $date = trim($tds->item(0)->textContent);
            $dateTime = \DateTime::createFromFormat('d/m', $date, new \DateTimeZone('Europe/Paris'));

            if (!$dateTime || $cpt >= $count) {
                continue;
            }

            $operation = new Operation();
            $operation->setAccount($account);
            $operation->setDate($dateTime->setTime(0, 0, 0));
            $operation->setLabel($tds->item(1)->textContent);

            $amount = $this->_cleanBalance($tds->item(2)->textContent);
            $operation->setAmount($amount);

            $cpt++;
            $operations[] = $operation;
        }

        return $operations;
    }

    protected function _cleanBalance($balance)
    {
        $balance = str_replace(',', '.', $balance);
        $balance = preg_replace("/[\\xA0\\xC2 €]/", '', $balance);

        return (Float) $balance;
    }
}
