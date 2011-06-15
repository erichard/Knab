<?php

namespace Knab\Backend;

use Knab\Exception as Exception;
use Knab\Account;
use Knab\Operation;
use Knab\Bank;

class LaBanquePostale extends BackendAbstract {

    const HOST = 'https://voscomptesenligne.labanquepostale.fr';

    protected $browser;

    public function getBrowser(){
        if ($this->browser == null) {
            $cookie_file = '/tmp/cookie.txt';
            $this->browser = new \Zend_Http_Client(self::HOST,array(
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

    public function getAccounts(Bank $bank) {
        if (!$this->logged)
            throw new Exception('Call login() first');

        $browser = $this->getBrowser();
        $response = $browser->setUri(self::HOST.'/voscomptes/canalXHTML/comptesCommun/synthese_assurancesEtComptes/afficheSynthese-synthese.ea')->request();

        $dom = new \DomDocument();
        @$dom->loadHTML($response->getBody());
        $xpath = new \DomXPath($dom);

        $accounts_xml = $xpath->query('//table[@id]/tbody/tr');

        $accounts = array();


        if ($accounts_xml->length > 0){
            foreach ($accounts_xml as $a) {

                $td1 = $xpath->query('td[@class="bdrT"]/span/a',$a)->item(0);
                $td2 = $xpath->query('td[@class="bdrL num"]',$a);

                $balance = preg_replace("/[\\xA0\\xC2]/",'',$td2->item(1)->nodeValue);
                $balance = str_replace(',','.',$balance);
                $account = new Account();
                $account
                    ->setBank($bank)
                    ->setId($td2->item(0)->nodeValue)
                    ->setLabel($td1->nodeValue)
                    ->setLink(str_replace('../..',self::HOST.'/voscomptes/canalXHTML',$td1->getAttribute('href')))
                    ->setBalance($balance);

                $accounts[] = $account;
            }
        }

        return $accounts;
    }

    public function login(){
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

            foreach (str_split($this->credential['password']) as $char){
                $hash_password .= $numbers[$char];
            }

            $browser
                ->setUri(self::HOST.'/wsost/OstBrokerWeb/auth')
                ->setParameterPost(array(
                    'username'    => $this->credential['username'],
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

    public function getHistory(Account $account, $count = 15){

        if (!$this->logged)
            throw new Exception('Call login() first');

        $browser = $this->getBrowser();

        $link = preg_replace('/typeRecherche=(\d){0,2}/','typeRecherche=10',$account->getLink());
        $response = $browser->setUri($link)->request()->getBody();

        $dom = new \DomDocument();
        @$dom->loadHTML($response);
        $xpath = new \DomXPath($dom);
        $lignes = $xpath->query("//table[@id='mouvements']/tbody/tr");

        $operations = array();


        foreach($lignes as $idx => $ligne){
            if ($count > 0 && $idx >= $count) break;

            $operation = new Operation();
            $operation->setAccount($account);

            $date = $xpath->query('./td',$ligne)->item(0)->textContent;
            $date_time = \DateTime::createFromFormat('d/m/Y',$date,new \DateTimeZone('Europe/Paris'));
            $date_time->setTime(0,0,0);
            $operation->setDate($date_time);

            $tp = $xpath->query('./td',$ligne)->item(1);
            $label = strip_tags($dom->saveXML($tp));
            $label = preg_replace('/\s+/',' ',$label);
            $label = trim($label);
            $operation->setLabel($label);

            $tp = $xpath->query("./td[@class='num bdrL']",$ligne);
            $amount = null;
            $amount = preg_replace("/[\\xA0\\xC2]/",'',$amount);
            $amount = str_replace(',','.',$amount);

            if ($tp->item(0)->textContent{0} == '-') {
                $amount = $tp->item(0)->textContent;
            } else {
                $amount = $tp->item(1)->textContent;
            }

            $amount = preg_replace("/[\\xA0\\xC2]/",'',$amount);
            $amount = str_replace(',','.',$amount);

            $operation->setAmount($amount);


            $operations[] = $operation;
        }

        return $operations;
    }
}
