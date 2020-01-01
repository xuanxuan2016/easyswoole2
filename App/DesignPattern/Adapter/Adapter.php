<?php

/**
 * 缓存适配器
 */
interface CacheAdapter {

    /**
     * 连接
     */
    public function connect();

    /**
     * 获取
     */
    public function get();

    /**
     * 关闭
     */
    public function close();
}

/**
 * redis缓存
 */
class CacheRedis implements CacheAdapter {

    public function connect() {
        
    }

    public function get() {
        echo "redis get \n";
    }

    public function close() {
        
    }

}

/**
 * memcache缓存
 */
class MemcacheRedis implements CacheAdapter {

    public function connect() {
        
    }

    public function get() {
        echo "memcache get \n";
    }

    public function close() {
        
    }

}

//运行
(new CacheRedis())->get();
(new MemcacheRedis())->get();
