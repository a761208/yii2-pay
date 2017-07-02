<?php
namespace a76\pay;

use Yii;
use yii\base\Action;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * 支付页面显示及支付结果查询
 *
 * Usage:
 *
 * ```php
 * class SiteController extends Controller
 * {
 *     public function actions()
 *     {
 *         return [
 *             'pay' => [
 *                 'class' => 'a76\pay\PayAction',
 *             ],
 *         ]
 *     }
 * }
 * ```
 *
 * 此Action负责显示支付界面并查询支付状态
 * 支付成功后通知调用者的回调方法
 *
 * @see \a76\pay\Collection
 * @see \a76\pay\widgets\PayChoice
 * 
 * @author 尖刀 <a761208@gmail.com>
 */
class PayAction extends Action
{
    /**
     * @var string Config中设置的组件名称
     */
    public $clientCollection = 'payClientCollection';
    /**
     * @var string 判断支付类型的GET字段
     */
    public $clientIdGetParamName = 'payclient';

    /**
     * @throws NotFoundHttpException
     * @return string
     */
    public function run()
    {
        if (!empty($_GET[$this->clientIdGetParamName])) {
            $clientId = $_GET[$this->clientIdGetParamName];
            /* @var $collection \a76\pay\Collection */
            $collection = Yii::$app->get($this->clientCollection);
            if (!$collection->hasClient($clientId)) {
                throw new NotFoundHttpException("Unknown pay client '{$clientId}'");
            }
            $client = $collection->getClient($clientId);
            if (Yii::$app->request->get('action') == 'check_pay_result') {
                Yii::$app->response->format = Response::FORMAT_JSON;
                $client->setPayId(Yii::$app->request->get('order_no'));
                return array_merge(['result'=>'success'], $client->getPayResult());
            }
            return $client->initPay(Yii::$app->request->get());
        } else {
            throw new NotFoundHttpException();
        }
    }
}
