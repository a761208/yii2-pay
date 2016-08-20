<?php
namespace a76\pay\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use Yii;

/**
 * PayChoice prints buttons for payment via various pay clients.
 * It opens a popup window for the client pay process.
 * By default this widget relies on presence of [[\a76\pay\Collection]] among application components
 * to get pay clients information.
 *
 * Example:
 *
 * ```php
 * <?= a76\pay\widgets\PayChoice::widget(); ?>
 * ```
 *
 * You can customize the widget appearance by using [[begin()]] and [[end()]] syntax
 * along with using method [[clientLink()]] or [[createClientUrl()]].
 * For example:
 *
 * ```php
 * <?php
 * use a76\pay\widgets\PayChoice;
 * ?>
 * <?php $payChoice = PayChoice::begin();?>
 * <ul>
 * <?php foreach ($payChoice->getClients() as $client): ?>
 *     <li><?php $payChoice->clientLink($client) ?></li>
 * <?php endforeach; ?>
 * </ul>
 * <?php PayChoice::end(); ?>
 * ```
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
class PayChoice extends widget
{
    /**
     * Outputs client pay link.
     * @param ClientInterface $client external pay client instance.
     * @param string $text link text, if not set - default value will be generated.
     */
    public function clientLink($client, $text = null)
    {
        $viewOptions = $client->getViewOptions();
        if (empty($viewOptions['widget'])) {
            if ($text === null) {
                $text = Html::tag('span', $client->getTitle());
            }
            echo Html::a($text, $this->createClientUrl($client));
        } else {
            $widgetConfig = $viewOptions['widget'];
            if (!isset($widgetConfig['class'])) {
                throw new \Exception('Widget config "class" parameter is missing.');
            }
            /* @var $widgetClass Widget */
            $widgetClass = $widgetConfig['class'];
            if (!(is_subclass_of($widgetClass, PayChoiceItem::className()))) {
                throw new \Exception('Item widget class must be subclass of "' . PayChoiceItem::className() . '".');
            }
            unset($widgetConfig['class']);
            $widgetConfig['client'] = $client;
            $wigetConfig['payChoice'] = $this;
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
        return Url::to(['']);
    }

    /**
     * Renders the main content, which includes all external services links.
     */
    protected function renderMainContent()
    {
        echo Html::beginTag('ul');
        foreach ($this->getClients() as $externalService) {
            echo Html::beginTag('li');
            $this->createLink($externalService);
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
        // Register assets
        echo Html::beginTag('div');
    }

    /**
     * Runs the widget.
     */
    public function run()
    {
        $this->renderMainContent();
        echo Html::endTag('div');
    }
}
