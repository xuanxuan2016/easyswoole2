<?php

/**
 * 单例复用
 */
trait Singleton {

    private static $objInstance;

    /**
     * 获取对象实例
     */
    public static function getInstance(...$arrParam) {
        if (!isset(self::$objInstance)) {
            self::$objInstance = new static(...$arrParam);
        }
        return self::$objInstance;
    }

}

/**
 * 单例
 */
class Service1 {

    use Singleton;

    protected $intId;

    /**
     * 私有化构造函数
     */
    private function __construct(...$arrParam) {
        $this->intId = $arrParam[0];
    }

    public function run() {
        echo "{$this->intId} \n";
        $this->intId++;
    }

}

//运行
Service1::getInstance(1)->run();
Service1::getInstance(1)->run();
