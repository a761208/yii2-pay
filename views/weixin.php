<?php
use yii\helpers\Html;
use yii\web\YiiAsset;

/* @var $this \yii\web\View */
/* @var $params array 支付参数 */

YiiAsset::register($this);

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
</head>

<body>
<?php $this->beginBody() ?>
<div id="qr"><?php echo Html::img(['/site/qr', 'content'=>$prepay['code_url']]);?></div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
