<?php
namespace a76\pay\clients;

use a76\pay\BaseClient;
use a76\pay\PayAction;

/**
 * 货到付款
 */
class Cod extends BaseClient
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
        return 'cod';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
    {
        return '货到付款';
    }
    
    /**
     * 初始化支付：货到付款直接返回支付检查页面
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::initPay()
     */
    public function initPay($params) {
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
            'is_cod'=>true,
        ];
    }
}
