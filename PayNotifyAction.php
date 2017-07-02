<?php
namespace a76\pay;

use yii\base\Action;
use yii\base\InvalidConfigException;
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
 *             'pay-notify-weixin' => [
 *                 'class' => 'a76\pay\PayNotifyAction',
 *                 'clientId' => 'weixin_scan',
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
     * @var string
     */
    public $clientId;
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
     * @return string
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function run()
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            // POST_RAW_DATA 没有内容
            return null;
        }
        /* @var $collection \a76\pay\Collection */
        $collection = Yii::$app->get($this->clientCollection);
        if (!$collection->hasClient($this->clientId)) {
            throw new NotFoundHttpException("无法识别支付类型：'{$this->clientId}'");
        }
        /* @var $client \a76\pay\ClientInterface */
        $client = $collection->getClient($this->clientId);
        $result = $client->notifyPay($raw); // 处理支付结果
        call_user_func($this->successCallback, $client); // 回调用户方法
        return $result;
    }
}
