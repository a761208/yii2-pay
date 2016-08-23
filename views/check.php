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
    <script>
        function checkPayResult()
        {
            $.getJSON("<?php echo $params['basePayUrl'];?>", <?php echo json_encode($params);?>, function(json) {
                if (json['result'] == 'success') { // 返回结果正常
                    if (json['pay_result'] == 'success') { // 支付成功
                        var is_cod = json['is_cod']; // 是否为货到付款
                        if (window.opener && !window.opener.closed) {
                            window.opener.pay_callback(true, is_cod);
                            window.opener.focus();
                            window.close();
                        } else {
                            window.location = url;
                        }
                    }
                    return true;
                }
                window.setTimeout(function() {checkPayResult();}, 1000);
            });
        }
        <?php $this->registerJs('checkPayResult();');?>
    </script>
</head>

<body>
<?php $this->beginBody() ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
