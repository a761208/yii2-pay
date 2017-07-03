<?php

namespace a76\pay\clients;

use a76\pay\BaseClient;
use Yii;
use yii\helpers\Url;

/**
 * 支付宝支付
 * @author 尖刀 <a761208@gmail.com>
 */
class Alipay extends BaseClient
{
    public $app_id; // 应用ID,您的APPID。
    public $merchant_private_key; // 商户私钥，您的原始格式RSA私钥
    public $notify_url = 'http://47.93.119.156:8888/site/pay-notify-alipay'; // 异步通知地址
    public $charset = 'UTF-8'; // 编码格式
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
     * @param $params array
     * [
     *     'order_no' 订单号
     *     'order_name' 订单名称
     *     'order_money' 订单金额
     * ]
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::initPay()
     */
    public function initPay($params)
    {
        if (isset($params['returned'])) {
            // ?total_amount=9.00
            // &timestamp=2016-08-11+19%3A36%3A01
            // &sign=ErCRRVmW%2FvXu1XO76k%2BUr4gYKC5%2FWgZGSo%2FR7nbL%2FPU7yFXtQJ2CjYPcqumxcYYB5x%2FzaRJXWBLN3jJXr01Icph8AZGEmwNuzvfezRoWny6%2Fm0iVQf7hfgn66z2yRfXtRSqtSTQWhjMa5YXE7MBMKFruIclYVTlfWDN30Cw7k%2Fk%3D
            // &trade_no=2016081121001004630200142207
            // &sign_type=RSA2
            // &charset=UTF-8
            // &seller_id=2088111111116894
            // &method=alipay.trade.wap.pay.return
            // &app_id=2016040501024706
            // &out_trade_no=70501111111S001111119
            // &version=1.0

            /* @var $view \yii\web\View */
            $view = Yii::$app->getView();
            $viewFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'alipay.php';
            return $view->renderFile($viewFile, [
                'client' => $this,
                'params' => $params,
            ]);
        }
        include_once Yii::getAlias('@vendor/a76/yii2-pay/clients/alipay/AopSdk.php');
        $aop = new \AopClient();
        $aop->appId = $this->app_id;
        $aop->rsaPrivateKey = $this->merchant_private_key;
        $aop->alipayrsaPublicKey = $this->alipay_public_key;
        $request = new \AlipayTradeWapPayRequest();
        $request->setBizContent(json_encode([
            'body' => $params['order_name'],
            'subject' => $params['order_name'],
            'out_trade_no' => $params['order_no'],
            'timeout_express' => '1d',
            'total_amount' => $params['order_money'],
            'product_code' => 'QUICK_WAP_WAY',
        ]));
        $request->setNotifyUrl($this->notify_url);
        $request->setReturnUrl(Url::current(['returned'=>1], true));
        $result = $aop->pageExecute($request);
        $this->setData('pay_result_' . $params['order_no'], 'waiting');
        return $result;
    }

    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::notifyPay()
     */
    public function notifyPay($raw)
    {
        parse_str($raw, $post);
        $sign = $post['sign'];
        $signType = $post['sign_type'];
        unset($post['sign']);
        unset($post['sign_type']);
        include_once Yii::getAlias('@vendor/a76/yii2-pay/clients/alipay/AopSdk.php');
        $aop = new \AopClient();
        $aop->appId = $this->app_id;
        $aop->rsaPrivateKey = $this->merchant_private_key;
        $aop->alipayrsaPublicKey = $this->alipay_public_key;
        $checkSign = $aop->verify($aop->getSignContent($post), $sign, Yii::getAlias($this->alipay_public_key), $signType);
        if (!$checkSign) {
            Yii::error('支付宝异步通知签名验证失败：' . $raw);
            return 'sign_error';
        }
        $out_trade_no = $post['out_trade_no'];
        $this->setPayId($out_trade_no);
        $this->setData('pay_result_' . $out_trade_no, 'success');
        $this->setData('pay_money_' . $out_trade_no, $post['total_amount']);
        $this->setData('pay_remark_' . $out_trade_no, $raw);

        return 'success';
    }

    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::getPayResult()
     */
    public function getPayResult()
    {
        return array_merge([
            'pay_id' => $this->getPayId(),
            'pay_result' => $this->getData('pay_result_' . $this->getPayId()),
            'pay_money' => $this->getData('pay_money_' . $this->getPayId()),
            'pay_remark' => $this->getData('pay_remark_' . $this->getPayId()),
        ], parent::getPayResult() !== false ? json_decode(parent::getPayResult(), true) : []);
    }
}
