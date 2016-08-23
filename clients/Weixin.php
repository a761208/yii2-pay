<?php
namespace a76\pay\clients;

use a76\pay\BaseClient;

/**
 * 微信扫码
 */
class Weixin extends BaseClient
{
    /**
     * @inheritdoc
     */
     public function init()
     {
         parent::init();
     }

    /**
     * @inheritdoc
     */
    protected function defaultName()
    {
        return 'weixin';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
    {
        return '微信扫码支付';
    }
    
    /**
     * 初始化支付：货到付款直接返回支付检查页面
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::initPay()
     */
    public function initPay($params) {
        // TODO:初始化支付
        return $this->renderCheck($params);
    }
    
    /**
     * 返回支付结果
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::getPayResult()
     */
    public function getPayResult() {
        return [
            'result'=>'success',
            'pay_result'=>'success',
        ];
    }
}
