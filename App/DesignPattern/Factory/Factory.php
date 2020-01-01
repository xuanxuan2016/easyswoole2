<?php

class Service1 {

    public function run() {
        echo "service1 \n";
    }

}

class Service2 {

    public function run() {
        echo "service2 \n";
    }

}

/**
 * 工厂类
 */
class Factory {

    public static function getService1() {
        return new Service1();
    }

    public static function getService2() {
        return new Service2();
    }

}

//运行
Factory::getService1()->run();
Factory::getService2()->run();
