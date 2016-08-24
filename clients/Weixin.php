<?php
namespace a76\pay\clients;

use a76\pay\BaseClient;
use Yii;

/**
 * 微信扫码支付
 * @author 尖刀 <a761208@gmail.com>
 */
class Weixin extends BaseClient
{
    public $app_id;
    public $app_secret;
    public $mch_id;
    public $api_key;
    public $notify_url;
    public $qr_url;
    
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
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::initPay()
     */
    public function initPay($params) {
        $prepay = $this->unifiedorder($params);
        Yii::$app->cache->set('pay_result_' . $params['id'], 'waiting');
        /* @var $view \yii\web\View */
        $view = Yii::$app->getView();
        $viewFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->id . '.php';
        return $view->renderFile($viewFile, [
            'client'=>$this,
            'params'=>$params,
            'prepay'=>$prepay,
        ]);
    }
    
    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::notifyPay()
     */
    public function notifyPay($raw) {
        $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xml = (array) $xml;
        if ($xml['return_code'] != 'SUCCESS') {
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return;
        }
        if ($xml['result_code'] != 'SUCCESS') {
            // 支付失败
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }
        $sign = $xml['sign'];
        unset($xml['sign']);
        if (Weixin::makeSign($xml, $this->api_key) != $sign) {
            echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
            return;
        }
        $this->setPayId($xml['out_trade_no']);
        Yii::$app->cache->set('pay_result_' . $xml['out_trade_no'], 'success');
        Yii::$app->cache->set('pay_remark_' . $xml['out_trade_no'], $raw);
    }
    
    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::getPayResult()
     */
    public function getPayResult() {
        return [
            'pay_result'=>Yii::$app->cache->get('pay_result_' . $this->getPayId()),
            'pay_remark'=>Yii::$app->cache->get('pay_remark_' . $this->getPayId()),
        ];
    }
    
    /**
     * 微信统一下单接口
     * @param array $params
     * @return array prepay_id：预支付会话标识，有效期2小时；code_url：二维码内容
     * @throws \Exception
     */
    private function unifiedorder($params) {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $post = [];
        $post['appid'] = $this->app_id;
        $post['mch_id'] = $this->mch_id;
        $post['device_info'] = 'WEB';
        $post['nonce_str'] = Yii::$app->security->generateRandomString(32);
        $post['body'] = $params['name'];
        $post['out_trade_no'] = $params['id'];
        $post['total_fee'] = round($params['money'] * 100);
        $post['spbill_create_ip'] = Yii::$app->request->userIP;
        $post['notify_url'] = Yii::$app->request->hostInfo . $this->notify_url;
        $post['trade_type'] = 'NATIVE';
        $post['sign'] = Weixin::makeSign($post, $this->api_key);
        $xml = '<xml>';
        foreach ($post as $k=>$v) {
            $xml .= '<' . $k . '>' . $v . '</' . $k . '>';
        }
        $xml .= '</xml>';
        Yii::error($xml);
        $res = $this->postXmlCurl($xml, $url);
        $xml = simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xml = (array) $xml;
        if ($xml['return_code'] != 'SUCCESS') {
            throw new \Exception($xml->return_msg);
        }
        if ($xml['result_code'] != 'SUCCESS') {
            throw new \Exception('code:' . $xml['err_code'] . ';msg:' . $xml['err_code_des']);
        }
        return [
            'prepay_id'=>$xml['prepay_id'],
            'code_url'=>$xml['code_url'],
        ];
    }
    
    /**
     * 生成签名
     * @return string
     */
    public static function makeSign($data, $api_key) {
        ksort($data);
        $stringA = '';
        foreach ($data as $k=>$v) {
            if (empty($v) && $v !== '0') {
                continue;
            }
            $stringA .= $k . '=' . $v . '&';
        }
        $stringA .= 'key=' . $api_key;
        $key = md5($stringA);
        $key = strtoupper($key);
        return $key;
    }
}
