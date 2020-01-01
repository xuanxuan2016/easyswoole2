<?php

/**
 * 注册器
 */
class Register {

    private static $arrObj = [];

    /**
     * 获取对象
     */
    public static function get($strKey) {
        return isset(self::$arrObj[$strKey]) ? self::$arrObj[$strKey] : null;
    }

    /**
     * 保存对象
     */
    public static function set($strKey, $obj) {
        self::$arrObj[$strKey] = $obj;
    }

}

class Service1 {

    public function run() {
        echo "service1 \n";
    }

}

//运行
Register::set('service1', new Service1());

Register::get('service1')->run();
Register::get('service1')->run();
