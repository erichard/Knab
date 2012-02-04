<?php

namespace Knab;

class Knab {

    protected $sources = array();

    public function registerBank($backend, array $credentials){
        if (!$backend)
            throw new \Exception('I need a backend to work !');

        $backend = 'Knab\\Backend\\'.$backend;
        if (!class_exists($backend))
            throw new \Exception(sprintf('Unknown backend %s',$backend));

        $bank = new Bank();
        $bank->setBackend(new $backend($credentials));

        $id = spl_object_hash($bank);
        $this->sources[$id] = $bank;

        return $this;
    }

    public function getAvailableBackends(){

        $backends = array();
        $iterator = new \DirectoryIterator(__DIR__.'/Backend');
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() == 'php') {
                $pathinfo = pathinfo($file->getPathname());
                $className = 'Knab\\Backend\\'.$pathinfo['filename'];
                if (is_subclass_of($className, 'Knab\\Backend\\BackendAbstract')) {
                    $backends[] = array(
                        'name'  => $className::NAME,
                        'class' => $className
                    );
                }
            }
        }
        return $backends;
    }

    public function unregisterBank(Bank $bank){
        $id = spl_object_hash($bank);
        unset($this->sources[$id]);
        return $this;
    }

    public function getAccounts(){
        $accounts = array();
        foreach ($this->sources as $bank){
            if (!$bank->isLogged()){
                $bank->login();
            }
            $accounts = array_merge($accounts,$bank->getAccounts());
        }

        return $accounts;
    }

    public function getBank(){
        return $this->sources;
    }
}


