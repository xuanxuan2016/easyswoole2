<?php

class Service1 implements \Iterator {

    protected $intIndex = 0;
    protected $arrData = [];

    public function __construct() {
        $this->intIndex = 0;
        $this->arrData = [1, 2, 3, 4, 5];
    }

    /**
     * 返回当前元素
     */
    public function current() {
        return $this->arrData[$this->intIndex];
    }

    /**
     * 返回当前元素的键
     */
    public function key() {
        return $this->intIndex;
    }

    /**
     * 向前移动到下一个元素
     */
    public function next() {
        $this->intIndex++;
    }

    /**
     * 返回到迭代器的第一个元素
     */
    public function rewind() {
        $this->intIndex = 0;
    }

    /**
     * 检查当前位置是否有效
     */
    public function valid() {
        return $this->intIndex < count($this->arrData);
    }

}

//运行
$objService1 = new Service1();
foreach ($objService1 as $value) {
    echo $value;
}