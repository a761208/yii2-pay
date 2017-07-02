<?php
use a76\pay\widgets\PayChoiceAsset;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $client \a76\pay\clients\Alipay */
/* @var $params array 支付参数 */
/* @author 尖刀 <a761208@gmail.com> */

PayChoiceAsset::register($this);

$params['action'] = 'check_pay_result';
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
    <script>
    <?php $this->registerJs('checkPayResult("' . Url::current(['action'=>'check_pay_result']) . '", ' . json_encode($params) . ');');?>
    </script>
</head>

<body>
<?php $this->beginBody() ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
