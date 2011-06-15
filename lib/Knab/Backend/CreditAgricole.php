<?php

namespace Knab\Backend;

use Knab\Exception as Exception;
use Knab\Account;
use Knab\Bank;
use Knab\Operation;

class CreditAgricole extends BackendAbstract {

    protected $browser;
    protected $site;
    protected $scheme = 'https';
    protected $urls = array(
        'm.ca-alpesprovence.fr'     =>'Alpes Provence',
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
        'm.ca-toulouse31.fr'        => 'Toulouse 31', # m.ca-toulousain.fr redirects here
        'm.ca-tourainepoitou.fr'    => 'Tourraine Poitou'
    );

    public function __construct(array $credentials) {

        parent::__construct($credentials);

        if (!isset($this->urls[$credentials['site']]))
            throw new Exception('Invalid URL '.$credentials['site']);

        $this->site = $credentials['site'];
        $this->browser = $this->getBrowser();
    }

    public function getBrowser(){
        if ($this->browser == null) {
            $cookie_file = '/tmp/cookie.txt';
            $this->browser = new \Zend_Http_Client($this->scheme.'://'.$this->site,array(
                'useragent' => "wget",
                'adapter'   => 'Zend_Http_Client_Adapter_Curl',
                'curloptions' => array(
                    CURLOPT_COOKIESESSION => false,
                    CURLOPT_COOKIEJAR => $cookie_file,
                    CURLOPT_COOKIEFILE => $cookie_file
                ))
            );
        }
        return $this->browser;
    }

    public function getAccounts(Bank $bank) {
        if (!$this->logged)
            throw new Exception('Call login() first');

        $accounts = array();

        $browser = $this->getBrowser();
        $response = $browser->request()->getBody();

        $dom = new \DomDocument();
        @$dom->loadHTML($response);
        $xpath = new \DomXPath($dom);

        foreach ($xpath->query("//div[@class='dv']") as $div){

            $xml_account = $xpath->query("a",$div);

            if ($xml_account->length > 0) {
                $id = preg_replace('/(.*)<br *\/>(\d*)<div.*/','$2',$dom->saveXml($div));

                $balance = str_replace(',','.',$xpath->query("div",$div)->item(0)->textContent);
                $balance = preg_replace("/[\\xA0\\xC2 €]/",'',$balance);

                $account = new Account();
                $account
                    ->setBank($bank)
                    ->setId($id)
                    ->setLabel($xml_account->item(0)->textContent)
                    ->setLink($xml_account->item(0)->getAttribute('href'))
                    ->setBalance($balance);


                $accounts[$account->getId()] = $account;
            }
        }
        return $accounts;
    }

    public function login(){
        if ($this->isLogged()) return true;

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
            throw new Exception('Login error');
        }
    }

    public function getHistory(Account $account, $count = 15){
        if (!$this->logged)
            throw new Exception('Call login() first');

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
        foreach ($lignes as $idx => $ligne){

            $tds = $xpath->query('td',$ligne);

            $date = trim($tds->item(0)->textContent);
            $date_time = \DateTime::createFromFormat('d/m',$date,new \DateTimeZone('Europe/Paris'));

            if (!$date_time || $cpt >= $count) continue;


            $operation = new Operation();
            $operation->setAccount($account);
            $operation->setDate($date_time->setTime(0,0,0));
            $operation->setLabel($tds->item(1)->textContent);

            $amount = $this->_cleanBalance($tds->item(2)->textContent);
            $operation->setAmount($amount);

            $cpt++;
            $operations[] = $operation;
        }

        return $operations;
    }

    protected function _cleanBalance($balance){
        $balance = str_replace(',','.',$balance);
        $balance = preg_replace("/[\\xA0\\xC2 €]/",'',$balance);
        return (Float) $balance;
    }
}
