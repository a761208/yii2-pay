<?php

namespace a76\pay\clients;

use a76\pay\BaseClient;
use Yii;

/**
 * 支付宝支付
 * @author 尖刀 <a761208@gmail.com>
 */
class Alipay extends BaseClient
{
    public $app_id; // 应用ID,您的APPID。
    public $merchant_private_key; // 商户私钥，您的原始格式RSA私钥
    public $notify_url; // 异步通知地址
    public $return_url; // 同步跳转
    public $charset = 'UTF-8'; // 编码格式
    public $gatewayUrl = 'https://openapi.alipay.com/gateway.do'; // 支付宝网关
    public $alipay_public_key; // 支付宝公钥

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
        return 'alipay';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
    {
        return '支付宝支付';
    }

    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::initPay()
     */
    public function initPay($params)
    {
        include_once Yii::getAlias('@vendor/a76/yii2-pay/clients/alipay/AopSdk.php');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->app_id;
        $aop->rsaPrivateKey = $this->merchant_private_key;
        $aop->alipayrsaPublicKey = $this->alipay_public_key;
        $aop->apiVersion = '1.0';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';
        $aop->signType = 'RSA';
        $request = new \AlipayTradeWapPayRequest();
        $request->setBizContent(json_encode([
            'body' => $params['name'],
            'subject' => $params['name'],
            'out_trade_no' => $params['id'],
            'timeout_express' => '1d',
            'total_amount' => $params['money'],
            'product_code' => 'QUICK_WAP_WAY',
        ]));
        $result = $aop->pageExecute($request);
        $this->setData('pay_result_' . $params['id'], 'waiting');
        return $result;
    }

    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::notifyPay()
     */
    public function notifyPay($raw)
    {
        $out_trade_no = '';
        $this->setPayId($out_trade_no);
        $this->setData('pay_result_' . $out_trade_no, 'success');
        $this->setData('pay_money_' . $out_trade_no, 123);
        $this->setData('pay_remark_' . $out_trade_no, $raw);
    }

    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::getPayResult()
     */
    public function getPayResult()
    {
        return array_merge([
            'pay_result' => $this->getData('pay_result_' . $this->getPayId()),
            'pay_money' => $this->getData('pay_money_' . $this->getPayId()),
            'pay_remark' => $this->getData('pay_remark_' . $this->getPayId()),
        ], parent::getPayResult() !== false ? json_decode(parent::getPayResult(), true) : []);
    }
}
