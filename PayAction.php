<?php
namespace a76\pay;

use yii\base\Action;
use yii\base\Exception;
use yii\base\InvalidConfigException;
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
 *                 'successCallback' => [$this, 'successCallback'],
 *             ],
 *         ]
 *     }
 *
 *     public function successCallback($client)
 *     {
 *         // TODO:获取支付结果
 *         // $attributes = $client->getUserAttributes();
 *         // user login or signup comes here
 *     }
 * }
 * ```
 *
 * Usually pay via external services is performed inside the popup window.
 * This action handles the redirection and closing of popup window correctly.
 *
 * @see Collection
 * @see \a76\pay\widgets\PayChoice
 *
 * @property string $cancelUrl Cancel URL.
 * @property string $successUrl Successful URL.
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
     * @var callable PHP callback, which should be triggered in case of successful pay.
     * This callback should accept [[ClientInterface]] instance as an argument.
     * For example:
     *
     * ```php
     * public function onPaySuccess($client)
     * {
     *     // TODO:获取支付结果
     *     // $attributes = $client->getUserAttributes();
     *     // user login or signup comes here
     * }
     * ```
     *
     * If this callback returns [[Response]] instance, it will be used as action response,
     * otherwise redirection to [[successUrl]] will be performed.
     *
     */
    public $successCallback;
    /**
     * @var string name or alias of the view file, which should be rendered in order to perform redirection.
     * If not set default one will be used.
     */
    public $redirectView;

    /**
     * @var string the redirect url after successful pay.
     */
    private $_successUrl = '';
    /**
     * @var string the redirect url after unsuccessful pay (e.g. user canceled).
     */
    private $_cancelUrl = '';


    /**
     * @param string $url successful URL.
     */
    public function setSuccessUrl($url)
    {
        $this->_successUrl = $url;
    }

    /**
     * @return string successful URL.
     */
    public function getSuccessUrl()
    {
        if (empty($this->_successUrl)) {
            $this->_successUrl = $this->defaultSuccessUrl();
        }

        return $this->_successUrl;
    }

    /**
     * @param string $url cancel URL.
     */
    public function setCancelUrl($url)
    {
        $this->_cancelUrl = $url;
    }

    /**
     * @return string cancel URL.
     */
    public function getCancelUrl()
    {
        if (empty($this->_cancelUrl)) {
            $this->_cancelUrl = $this->defaultCancelUrl();
        }

        return $this->_cancelUrl;
    }

    /**
     * Creates default [[successUrl]] value.
     * @return string success URL value.
     */
    protected function defaultSuccessUrl()
    {
        return Yii::$app->getUser()->getReturnUrl();
    }

    /**
     * Creates default [[cancelUrl]] value.
     * @return string cancel URL value.
     */
    protected function defaultCancelUrl()
    {
        return Url::to(Yii::$app->getUser()->loginUrl);
    }

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
                // 检查支付结果
                // 如果支付成功
                return $client->getPayResult();
            }
            return $this->initPay($client, Yii::$app->request->get());
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @param mixed $client pay client instance.
     * @return Response response instance.
     * @throws \yii\base\NotSupportedException on invalid client.
     */
    protected function initPay($client, $params)
    {
        echo $client->initPay($params);
    }

    /**
     * This method is invoked in case of successful authentication via auth client.
     * @param ClientInterface $client auth client instance.
     * @throws InvalidConfigException on invalid success callback.
     * @return Response response instance.
     */
    protected function paySuccess($client)
    {
        if (!is_callable($this->successCallback)) {
            throw new InvalidConfigException('"' . get_class($this) . '::successCallback" should be a valid callback.');
        }
        $response = call_user_func($this->successCallback, $client);
        if ($response instanceof Response) {
            return $response;
        }
        return $this->redirectSuccess();
    }

    /**
     * Redirect to the given URL or simply close the popup window.
     * @param mixed $url URL to redirect, could be a string or array config to generate a valid URL.
     * @param boolean $enforceRedirect indicates if redirect should be performed even in case of popup window.
     * @return \yii\web\Response response instance.
     */
    public function redirect($url, $enforceRedirect = true)
    {
        $viewFile = $this->redirectView;
        if ($viewFile === null) {
            $viewFile = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'redirect.php';
        } else {
            $viewFile = Yii::getAlias($viewFile);
        }
        $viewData = [
            'url' => $url,
            'enforceRedirect' => $enforceRedirect,
        ];
        $response = Yii::$app->getResponse();
        $response->content = Yii::$app->getView()->renderFile($viewFile, $viewData);
        return $response;
    }

    /**
     * Redirect to the URL. If URL is null, [[successUrl]] will be used.
     * @param string $url URL to redirect.
     * @return \yii\web\Response response instance.
     */
    public function redirectSuccess($url = null)
    {
        if ($url === null) {
            $url = $this->getSuccessUrl();
        }
        return $this->redirect($url);
    }

    /**
     * Redirect to the [[cancelUrl]] or simply close the popup window.
     * @param string $url URL to redirect.
     * @return \yii\web\Response response instance.
     */
    public function redirectCancel($url = null)
    {
        if ($url === null) {
            $url = $this->getCancelUrl();
        }
        return $this->redirect($url, false);
    }
}
