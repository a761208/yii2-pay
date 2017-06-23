<?php
namespace a76\pay\widgets;

use a76\pay\ClientInterface;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * 显示支付链接，点击链接显示支付界面
 *
 * Example:
 *
 * ```php
 * <?php $payChoice = a76\pay\widgets\PayChoice::begin([
 *     'basePayUrl' => ['site/pay'], // 支付目录
 *     'canCOD' => false, // 是否允许货到付款
 *     'clientOptions' => ['popup' => false],
 * ]); ?>
 * <script>
 * // 支付信息，此方法会在用户点击支付链接时调用
 * function pay_init() {
 *     return {
 *         'id':'activity_money_123', // 唯一支付编号
 *         'name':'活动资金', // 商品名称
 *         'money':123.32 // 商品金额，两位小数
 *     };
 * }
 * // 支付回调，此方法会在用户支付成功后调用
 * // @param object json 支付结果
 * // json['pay_result'] = 'success|failure' 是否支付成功
 * // json['is_cod'] true|false 是否为货到付款
 * // json[...] PayNotifyAction callback 中设置的其它信息
 * function pay_callback(json) {
 *     console.log("支付结果：");
 *     console.log(result);
 * }
 * </script>
 * <?php a76\pay\widgets\PayChoice::end(); ?>
 * ```
 *
 * @see \a76\pay\PayAction
 * @author 尖刀 <a761208@gmail.com>
 */
class PayChoice extends Widget
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
     * 支付链接外层DIV的HTML属性
     * @see \yii\helpers\Html::renderTagAttributes()
     */
    public $options = [
        'class' => 'pay-clients'
    ];
    /**
     * @var array 传送给客户端脚本的参数
     * [
     *     'popup' => false, // 默认不弹出小窗口
     * ]
     */
    public $clientOptions = [];
    /**
     * @var boolean 是否允许货到付款支付方式
     */
    public $canCOD = false;
    /**
     * @var array 默认支付目录
     */
    private $_basePayUrl;
    /**
     * @var ClientInterface[] 支付客户端列表
     */
    private $_clients;
    /**
     * @param ClientInterface[] $clients 支付客户端列表
     */
    public function setClients(array $clients)
    {
        $this->_clients = $clients;
    }

    /**
     * @return ClientInterface[] 支付客户端列表
     */
    public function getClients()
    {
        if ($this->_clients === null) {
            $this->_clients = $this->defaultClients();
        }
        return $this->_clients;
    }

    /**
     * @param array $basePayUrl 设置支付目录
     */
    public function setBasePayUrl(array $basePayUrl)
    {
        $this->_basePayUrl = $basePayUrl;
    }

    /**
     * @return array 返回支付目录，需要用Url::to()进一步处理
     */
    public function getBasePayUrl()
    {
        if (!is_array($this->_basePayUrl)) {
            $this->_basePayUrl = $this->defaultBasePayUrl();
        }

        return $this->_basePayUrl;
    }

    /**
     * 返回默认支付客户端列表
     * @return ClientInterface[] 支付客户端列表
     */
    protected function defaultClients()
    {
        /* @var $collection \a76\pay\Collection */
        $collection = Yii::$app->get($this->clientCollection);
        return $collection->getClients($this->canCOD);
    }

    /**
     * @return array 默认支付目录，需要用Url::to()进一步处理
     */
    protected function defaultBasePayUrl()
    {
        $basePayUrl = [
            Yii::$app->controller->getRoute()
        ];
        $params = $_GET;
        unset($params[$this->clientIdGetParamName]);
        $basePayUrl = array_merge($basePayUrl, $params);
        return $basePayUrl;
    }

    /**
     * 输出支付链接
     * @param ClientInterface $client 支付客户端
     * @param string $text 链接文字，如果没有设置，将从支付客户端生成
     * @param array $htmlOptions 链接参数
     * @throws InvalidConfigException 错误的设置
     */
    public function clientLink($client, $text = null, array $htmlOptions = [])
    {
        if ($text === null) {
            $text = Html::tag('span', '', ['class' => 'pay-icon ' . $client->getName()]);
            $text .= Html::tag('span', $client->getTitle(), ['class' => 'pay-title']);
        }
        if (!array_key_exists('class', $htmlOptions)) {
            $htmlOptions['class'] = $client->getName();
        }
        Html::addCssClass($htmlOptions, ['widget' => 'pay-link']);
        echo Html::a($text, $this->createClientUrl($client), $htmlOptions);
    }

    /**
     * 生成支付链接URL
     * @param ClientInterface $provider 支付客户端
     * @return string 支付链接
     */
    public function createClientUrl($provider)
    {
        $url = $this->getBasePayUrl();
        $url[$this->clientIdGetParamName] = $provider->getId();
        return Url::to($url);
    }

    /**
     * 生成支付链接内容
     */
    protected function renderMainContent()
    {
        echo Html::beginTag('ul', ['class' => 'pay-clients clear']);
        foreach ($this->getClients() as $externalService) {
            echo Html::beginTag('li', ['class' => 'pay-client']);
            $this->clientLink($externalService);
            echo Html::endTag('li');
        }
        echo Html::endTag('ul');
    }

    /**
     * {@inheritDoc}
     * @see \yii\base\Object::init()
     */
    public function init()
    {
        $view = Yii::$app->getView();
        PayChoiceAsset::register($view);
        if (empty($this->clientOptions)) {
            $options = '';
        } else {
            $options = Json::htmlEncode($this->clientOptions);
        }
        $view->registerJs("\$('#" . $this->getId() . "').paychoice({$options});");
        $this->options['id'] = $this->getId();
        echo Html::beginTag('div', $this->options);
    }

    /**
     * {@inheritDoc}
     * @see \yii\base\Widget::run()
     */
    public function run()
    {
        $this->renderMainContent();
        echo Html::endTag('div');
    }
}
