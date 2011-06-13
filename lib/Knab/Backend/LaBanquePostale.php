<?php

namespace Knab\Backend;

use Knab\Account;

class LaBanquePostale extends BackendAbstract {

    const HOST = 'https://voscomptesenligne.labanquepostale.fr';

    protected $browser;

    public function __construct(array $options){
        parent::__construct($options);

        if (array_key_exists('browser.class',$options)){
            $browser_opts = $options['browser.options'] ?: array();
            $this->setBrowser(new $options['browser.class']($browser_opts));
        }

    }

    public function getBrowser(){
        if ($this->browser == null) {
            $cookie_file = '/tmp/cookie.txt';
            $this->browser = new \Zend_Http_Client('https://voscomptesenligne.labanquepostale.fr',array(
                'useragent' => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)",
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

    public function getAccounts() {
        if (!$this->logged) {
            if (!$this->_login())
                throw new \Exception('Unable to login on '.$this->username);
        }

        echo "Logged !\n";

        $browser = $this->getBrowser();
        $response = $browser->setUri(self::HOST.'/voscomptes/canalXHTML/comptesCommun/synthese_assurancesEtComptes/afficheSynthese-synthese.ea')->request();

        $dom = new \DomDocument();
        @$dom->loadHTML($response->getBody());
        $xpath = new \DomXPath($dom);

        $accounts_xml = $xpath->query('//table[@id]/tbody/tr');

        $accounts = array();


        if ($accounts_xml->length > 0){
            foreach ($accounts_xml as $a) {
                $account = new Account();
                $td1 = $xpath->query('td[@class="bdrT"]/span/a',$a)->item(0);
                $td2 = $xpath->query('td[@class="bdrL num"]',$a);

                $balance = preg_replace("/[\\xA0\\xC2]/",'',$td2->item(1)->nodeValue);
                $balance = str_replace(',','.',$balance);
                $account
                    ->setLabel($td1->nodeValue)
                    ->setLink($td1->getAttribute('href'))
                    ->setId($td2->item(0)->nodeValue)
                    ->setBalance($balance);

                $accounts[] = $account;
            }
        }

        return $accounts;
    }

    public function getAccount($account_id) {

    }

    public function getOperations($account) {

    }

    protected function _login(){
        if ($this->logged) return true;

        $browser = $this->getBrowser();

        $response = $browser
                ->setUri(self::HOST.'/voscomptes/canalXHTML/comptesCommun/synthese_assurancesEtComptes/init-synthese.ea')
                ->request()
                ->getBody();

        $dom = new \DomDocument();
        @$dom->loadHTML($response);
        $xpath = new \DomXPath($dom);

        if ($xpath->query('//table[@id="comptes"]')->length == 1){
            echo 'Fast login ';
            $this->logged = true;
            return true;
        }

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

        $auth_form = self::HOST."/wsost/OstBrokerWeb/loginform?TAM_OP=login&ERROR_CODE=0x00000000&URL=%2Fvoscomptes%2FcanalXHTML%2Fidentif.ea%3Forigin%3Dparticuliers";



        $browser->setUri($auth_form);
        $response = $browser->request();

        $map = array();
        for ($i = 0; $i < 10 ; $i++) {
            $url = sprintf(self::HOST.'/wsost/OstBrokerWeb/loginform?imgid=%d&0.25122230781963073',$i);
            $browser->setUri($url);
            $img = $browser->request()->getBody();
            $map[$i] = md5($img);
        }
        $map = array_unique($map);

        if (count($map) != 10) {
            throw new Exception("Unable to compute password ");
        } else {
            $numbers = array();

            foreach ($hash as $idx => $img){
            $numbers[$idx] = array_search($img,$map);
            }

            $hash_password = "";

            foreach (str_split($this->password) as $char){
                $hash_password .= $numbers[$char];
            }

            $browser
                ->setUri(self::HOST.'/wsost/OstBrokerWeb/auth')
                ->setParameterPost(array(
                    'username'    => $this->username,
                    'password'    => $hash_password,
                    'urlbackend'  => "/voscomptes/canalXHTML/identif.ea?origin=particuliers",
                    'origin'      => 'particuliers',
                    'cv'          => 'true',
                    'cvvs'        => ''
                ))
                ->request('POST');

            $browser
                ->setUri(self::HOST.'/voscomptes/canalXHTML/securite/authentification/initialiser-identif.ea')
                ->request();

            $browser
                ->setUri(self::HOST.'/voscomptes/canalXHTML/securite/authentification/verifierMotDePasse-identif.ea')
                ->request();

            $response = $browser
                ->setUri(self::HOST.'/voscomptes/canalXHTML/comptesCommun/synthese_assurancesEtComptes/init-synthese.ea')
                ->request()
                ->getBody();

            $dom = new \DomDocument();
            @$dom->loadHTML($response);
            $xpath = new \DomXPath($dom);

            if ($xpath->query('//table[@id="comptes"]')->length == 1){
                $this->logged = true;
            } else {
                $this->logged = false;
            }
            return $this->logged;
        }
    }
}