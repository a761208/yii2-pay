<?php
namespace a76\pay\widgets;

use yii\base\InvalidConfigException;
use yii\base\Widget;
use Yii;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\helpers\Html;
use a76\pay\ClientInterface;

/**
 * PayChoice prints buttons for payment via various pay clients.
 * It opens a popup window for the client pay process.
 * By default this widget relies on presence of [[\a76\pay\Collection]] among application components
 * to get pay clients information.
 *
 * Example:
 *
 * ```php
 * <?php $payChoice = a76\pay\widgets\PayChoice::begin([
 *     'basePayUrl' => ['site/pay'],
 *     'canCOD' => false,
 * ]); ?>
 * <script>
 * function pay_init() {
 *     return {
 *         'name':'商品名称',
 *         'money':支付金额
 *     };
 * }
 * function pay_callback(pay_success, is_cod) {
 *     console.log("支付结果 boolean：" + pay_success);
 *     console.log("货到付款 boolean：" + is_cod);
 * }
 * </script>
 * <?php a76\pay\widgets\PayChoice::end(); ?>
 * ```
 * 
 *
 * This widget supports following keys for [[PayInterface::getViewOptions()]] result:
 *  - widget - configuration for the widget, which should be used to render a client link;
 *    such widget should be a subclass of [[PayChoiceItem]].
 *
 * @see \a76\pay\PayAction
 *
 * @property ClientInterface[] $clients Pay providers. This property is read-only.
 *
 * @author A761208 <a761208@gmail.com>
 */
class PayChoice extends Widget
{
    /**
     * @var string name of the pay client collection application component.
     * This component will be used to fetch services value if it is not set.
     */
     public $clientCollection = 'payClientCollection';
    /**
     * @var string name of the GET param , which should be used to passed pay client id to URL
     * defined by [[baseUrl]].
     */
    public $clientIdGetParamName = 'payclient';
    /**
     * @var array the HTML attributes that should be rendered in the div HTML tag representing the container element.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = [
        'class' => 'pay-clients'
    ];
    /**
     * @var array additional options to be passed to the underlying JS plugin.
     */
    public $clientOptions = [];
    /**
     * @var boolean indicates if popup window should be used instead of direct links.
     */
    public $popupMode = true;
    /**
     * @var boolean indicates if widget content, should be rendered automatically.
     * Note: this value automatically set to 'false' at the first call of [[createClientUrl()]]
     */
    public $autoRender = true;
    /**
     * @var boolean indicates if can cash on delivery.
     */
    public $canCOD = true;

    /**
     * @var array configuration for the external clients base pay URL.
     */
    private $_basePayUrl;
    /**
     * @var ClientInterface[] pay providers list.
     */
    private $_clients;

    /**
     * @param ClientInterface[] $clients pay providers
     */
    public function setClients(array $clients)
    {
        $this->_clients = $clients;
    }

    /**
     * @return ClientInterface[] pay providers
     */
    public function getClients()
    {
        if ($this->_clients === null) {
            $this->_clients = $this->defaultClients();
        }

        return $this->_clients;
    }

    /**
     * @param array $basePayUrl base pay URL configuration.
     */
    public function setBasePayUrl(array $basePayUrl)
    {
        $this->_basePayUrl = $basePayUrl;
    }

    /**
     * @return array base pay URL configuration.
     */
    public function getBasePayUrl()
    {
        if (!is_array($this->_basePayUrl)) {
            $this->_basePayUrl = $this->defaultBasePayUrl();
        }

        return $this->_basePayUrl;
    }

    /**
     * Returns default pay clients list.
     * @return ClientInterface[] pay clients list.
     */
    protected function defaultClients()
    {
        /* @var $collection \a76\pay\Collection */
        $collection = Yii::$app->get($this->clientCollection);
        return $collection->getClients($this->canCOD);
    }

    /**
     * Composes default base pay URL configuration.
     * @return array base pay URL configuration.
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
     * Outputs client pay link.
     * @param ClientInterface $client external pay client instance.
     * @param string $text link text, if not set - default value will be generated.
     * @param array $htmlOptions link HTML options.
     * @throws InvalidConfigException on wrong configuration.
     */
    public function clientLink($client, $text = null, array $htmlOptions = [])
    {
        $viewOptions = $client->getViewOptions();

        if (empty($viewOptions['widget'])) {
            if ($text === null) {
                $text = Html::tag('span', '', ['class' => 'pay-icon ' . $client->getName()]);
                $text .= Html::tag('span', $client->getTitle(), ['class' => 'pay-title']);
            }
            if (!array_key_exists('class', $htmlOptions)) {
                $htmlOptions['class'] = $client->getName();
            }
            Html::addCssClass($htmlOptions, ['widget' => 'pay-link']);

            if ($this->popupMode) {
                if (isset($viewOptions['popupWidth'])) {
                    $htmlOptions['data-popup-width'] = $viewOptions['popupWidth'];
                }
                if (isset($viewOptions['popupHeight'])) {
                    $htmlOptions['data-popup-height'] = $viewOptions['popupHeight'];
                }
            }
            echo Html::a($text, $this->createClientUrl($client), $htmlOptions);
        } else {
            $widgetConfig = $viewOptions['widget'];
            if (!isset($widgetConfig['class'])) {
                throw new InvalidConfigException('Widget config "class" parameter is missing');
            }
            /* @var $widgetClass Widget */
            $widgetClass = $widgetConfig['class'];
            if (!(is_subclass_of($widgetClass, PayChoiceItem::className()))) {
                throw new InvalidConfigException('Item widget class must be subclass of "' . PayChoiceItem::className() . '"');
            }
            unset($widgetConfig['class']);
            $widgetConfig['client'] = $client;
            $widgetConfig['payChoice'] = $this;
            echo $widgetClass::widget($widgetConfig);
        }
    }

    /**
     * Composes client pay URL.
     * @param ClientInterface $provider external pay client instance.
     * @return string pay URL.
     */
    public function createClientUrl($provider)
    {
        $this->autoRender = false;
        $url = $this->getBasePayUrl();
        $url[$this->clientIdGetParamName] = $provider->getId();

        return Url::to($url);
    }

    /**
     * Renders the main content, which includes all external services links.
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
     * Initializes the widget
     */
    public function init()
    {
        $view = Yii::$app->getView();
        if ($this->popupMode) {
            PayChoiceAsset::register($view);
            if (empty($this->clientOptions)) {
                $options = '';
            } else {
                $options = Json::htmlEncode($this->clientOptions);
            }
            $view->registerJs("\$('#" . $this->getId() . "').paychoice({$options});");
        } else {
            
        }
        $this->options['id'] = $this->getId();
        echo Html::beginTag('div', $this->options);
    }

    /**
     * Runs the widget.
     */
    public function run()
    {
        if ($this->autoRender) {
            $this->renderMainContent();
        }
        echo Html::endTag('div');
    }
}
