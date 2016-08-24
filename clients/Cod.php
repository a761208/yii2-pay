<?php
namespace a76\pay\clients;

use a76\pay\BaseClient;
use Yii;

/**
 * 货到付款支付
 * @author 尖刀 <a761208@gmail.com>
 */
class Cod extends BaseClient
{
    /**
     * @inheritdoc
     */
    protected function defaultName()
    {
        return 'cod';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
    {
        return '货到付款';
    }
    
    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::initPay()
     */
    public function initPay($params) {
        Yii::$app->cache->set('pay_' . $params['id'], 'success'); // 货到付款直接设置支付成功
        /* @var $view \yii\web\View */
        $view = Yii::$app->getView();
        $viewFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->id . '.php';
        return $view->renderFile($viewFile, [
            'params'=>$params,
        ]);
    }
    
    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::getPayResult()
     */
    public function getPayResult() {
        return [
            'pay_result'=>Yii::$app->cache->get($this->getPayId()),
            'is_cod'=>true
        ];
    }
}
