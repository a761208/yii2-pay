<?php
namespace a76\pay\widgets;

use yii\web\AssetBundle;

/**
 * PayChoiceAsset is an asset bundle for [[PayChoice]] widget.
 */
class PayChoiceAsset extends AssetBundle
{
    public $sourcePath = '@vendor/a76/yii2-pay/assets';
    public $js = [
        'paychoice.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
