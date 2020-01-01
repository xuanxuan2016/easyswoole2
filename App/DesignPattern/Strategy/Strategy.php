<?php

/**
 * 用户策略
 */
interface UserStrategy {

    /**
     * 广告推荐
     */
    public function showAd();
}

/**
 * 男士策略
 */
class MaleStrategy implements UserStrategy {

    public function showAd() {
        echo "男士广告 \n";
    }

}

/**
 * 女士策略
 */
class FemaleStrategy implements UserStrategy {

    public function showAd() {
        echo "女士广告 \n";
    }

}

/**
 * 商品页面
 */
class Page {

    private $objStrategy;

    public function setStrategy($objStrategy) {
        $this->objStrategy = $objStrategy;
    }

    public function show() {
        $this->objStrategy->showAd();
    }

}

//运行
$objPage = new Page();

$objPage->setStrategy(new MaleStrategy());
$objPage->show();

$objPage->setStrategy(new FemaleStrategy());
$objPage->show();
