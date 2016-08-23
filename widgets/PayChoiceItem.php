<?php
namespace a76\pay\widgets;

use yii\base\Widget;

/**
 * PayChoiceItem is a base class for creating widgets, which can be used to render link
 * for pay client at [[PayChoice]].
 */
class PayChoiceItem extends Widget
{
    /**
     * @var \a76\pay\ClientInterface pay client instance.
     */
    public $client;
    /**
     * @var PayChoice parent PayChoice widget
     */
    public $payChoice;
}