<?php
namespace a76\pay;

use yii\base\Action;
use yii\base\NotSupportedException;
use yii\helpers\Url;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use Yii;

/**
 * PayAction performs pay via different pay clients.
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
 * Usually pay via external services is performed inside the popup window.
 * This action handles the redirection and closing of popup window correctly.
 *
 * @see Collection
 * @see \a76\pay\widgets\PayChoice
 */
class PayAction extends Action
{
    /**
     * @var string name of the pay client collection application component.
     * It should point to [[Collection]] instance.
     */
    public $clientCollection = 'payClientCollection';
    /**
     * @var string name of the GET param, which is used to passed pay client id to this action.
     */
    public $clientIdGetParamName = 'payclient';

    /**
     * Runs the action.
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
                return array_merge(['result'=>'success'], $client->getPayResult(Yii::$app->request->get()));
            }
            return $client->initPay(Yii::$app->request->get());
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @param \a76\pay\ClientInterface $client pay client instance.
     * @return Response response instance.
     * @throws \yii\base\NotSupportedException on invalid client.
     */
    protected function initPay($client, $params)
    {
        echo $client->initPay($params);
    }
}
