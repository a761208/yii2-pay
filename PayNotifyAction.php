<?php
namespace a76\pay;

use a76\pay\clients\Weixin;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use Yii;

/**
 * 支付回调地址，接收第三方支付回调信息，通知用户支付结果
 *
 * Usage:
 *
 * ```php
 * class SiteController extends Controller
 * {
 *     public function actions()
 *     {
 *         return [
 *             'pay-notify' => [
 *                 'class' => 'a76\pay\PayNotifyAction',
 *                 'successCallback' => [$this, 'successCallback'],
 *             ],
 *         ]
 *     }
 *
 *     public function successCallback($client)
 *     {
 *         // $client->getPayResult() // 支付结果
 *     }
 * }
 * ```
 *
 * @property string $cancelUrl Cancel URL.
 * @property string $successUrl Successful URL.
 * 
 * @author 尖刀 <a761208@gmail.com>
 */
class PayNotifyAction extends Action
{
    /**
     * @var string Config中设置的组件名称
     */
    public $clientCollection = 'payClientCollection';
    /**
     * @var callable PHP回调方法，支付成功后调用，此方法接受\a76\pay\ClientInterface的实例参数
     * 此方法输出内容或返回值将被丢弃
     * For example:
     *
     * ```php
     * public function successCallback($client)
     * {
     *     // $client->getPayResult() // 支付结果
     * }
     * ```
     */
    public $successCallback;

    /**
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function run()
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            // POST_RAW_DATA 没有内容
            return;
        }
        Yii::error($raw);
        // TODO：支付回调通知：此处需要判断回调来源：如微信/支付宝/...
        $clientId = 'weixin';
        /* @var $collection \yii\authclient\Collection */
        $collection = Yii::$app->get($this->clientCollection);
        if (!$collection->hasClient($clientId)) {
            throw new NotFoundHttpException("Unknown auth client '{$clientId}'");
        }
        /* @var $client \a76\pay\ClientInterface */
        $client = $collection->getClient($clientId);
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
        if (Weixin::makeSign($xml, $client->api_key) != $sign) {
            echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
            return;
        }
        // 通知成功
        if (!is_callable($this->successCallback)) {
            throw new InvalidConfigException('"' . get_class($this) . '::successCallback" should be a valid callback.');
        }
        Yii::$app->cache->set('pay_' . $xml['out_trade_no'], 'success');
        $client->setPayId('pay_' . $xml['out_trade_no']);
        call_user_func($this->successCallback, $client);
        ob_clean();
        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        return;
    }
}
