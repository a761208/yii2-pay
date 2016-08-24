<?php
namespace a76\pay\widgets;

use yii\web\AssetBundle;

/**
 * 支付组件使用到的资源
 * @author 尖刀 <a761208@gmail.com>
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
