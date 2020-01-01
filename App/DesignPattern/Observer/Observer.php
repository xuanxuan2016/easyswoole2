<?php

/**
 * 观察者接口
 */
interface Observer {

    /**
     * 更新
     */
    public function update();
}

/**
 * 事件生成抽象类
 */
abstract class Event {

    /**
     * 观察者集合
     */
    private $arrObserver = [];

    /**
     * 添加观察者
     */
    public function addObserver($objObserver) {
        $this->arrObserver[] = $objObserver;
    }

    /**
     * 通知观察者
     */
    public function notify() {
        foreach ($this->arrObserver as $objObserver) {
            $objObserver->update();
        }
    }

}

class Observer1 implements Observer {

    public function update() {
        echo "Observer1 \n";
    }

}

class Observer2 implements Observer {

    public function update() {
        echo "Observer2 \n";
    }

}

class UserEvent extends Event {
    
}

//运行
$objUserEvent = new UserEvent();
$objUserEvent->addObserver(new Observer1());
$objUserEvent->addObserver(new Observer2());
$objUserEvent->notify();
