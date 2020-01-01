<?php

/**
 * 原型
 */
class Prototype {

    private static $arrObj = [];

    public static function getService1() {
        if (!isset(self::$arrObj['service1'])) {
            self::$arrObj['service1'] = new Service1();
        }
        //克隆对象
        return clone self::$arrObj['service1'];
    }

}

class Service1 {

    private $intTime1;
    private $intTime2;

    /**
     * 构造函数
     * 1.执行很多初始化操作
     */
    public function __construct() {
        $this->intTime1 = time();
    }

    public function change() {
        $this->intTime2 = microtime();
        return $this;
    }

    public function run() {
        echo "time1:{$this->intTime1} time2:{$this->intTime2} \n";
    }

}

//运行
Prototype::getService1()->change()->run();
Prototype::getService1()->change()->run();
