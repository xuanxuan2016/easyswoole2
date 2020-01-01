<?php

class ServiceProxy {

    public function run() {
        //复杂逻辑
        echo "ServiceProxy\n";
    }

}

class Service1 {

    private $objProxy;

    public function __construct() {
        $this->objProxy = new ServiceProxy();
    }

    public function run() {
        $this->objProxy->run();
    }

}

//运行
$objService1 = new Service1();
$objService1->run();
