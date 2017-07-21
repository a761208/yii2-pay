<?php
use a76\pay\widgets\PayChoiceAsset;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $client \a76\pay\clients\WeixinScan */
/* @var $params array 支付参数 */
/* @var $prepay array 微信预支付结果 */
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
<div id="qr" style="text-align: center;"><?php echo Html::img(str_replace('{$content}', $prepay['code_url'], $client->qr_url));?></div>
<div style="text-align: center;"><button class="btn btn-default" type="button" onclick="closeSelf()">返回</button></div>
<?php $this->endBody() ?>
<script>
    function closeSelf() {
        if (parent) {
            parent.document.getElementById('yii_pay_choice').remove()
        } else {
            window.close();
        }
    }
</script>
</body>
</html>
<?php $this->endPage() ?>
