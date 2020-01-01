<?php

/**
 * 装饰器接口
 */
interface Decorator {

    /**
     * 主逻辑执行前
     */
    public function before();

    /**
     * 主逻辑执行后
     */
    public function after();
}

class Decorator1 implements Decorator {

    public function before() {
        echo "Decorator1 before \n";
    }

    public function after() {
        echo "Decorator1 after \n";
    }

}

class Decorator2 implements Decorator {

    public function before() {
        echo "Decorator2 before \n";
    }

    public function after() {
        echo "Decorator2 after \n";
    }

}

class Service1 {

    private $arrDecorator = [];

    public function addDecorator($objDecorator) {
        $this->arrDecorator[] = $objDecorator;
    }

    protected function before() {
        foreach ($this->arrDecorator as $objDecorator) {
            $objDecorator->before();
        }
    }

    protected function after() {
        foreach (array_reverse($this->arrDecorator) as $objDecorator) {
            $objDecorator->after();
        }
    }

    public function run() {
        $this->before();
        echo "Service1 run \n";
        $this->after();
    }

}

//运行
$objService1 = new Service1();
$objService1->addDecorator(new Decorator1());
$objService1->addDecorator(new Decorator2());
$objService1->run();
