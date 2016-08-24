<?php
namespace a76\pay\clients;

use a76\pay\BaseClient;
use Yii;

/**
 * 货到付款
 */
class Cod extends BaseClient
{
    /**
     * @inheritdoc
     */
     public function init()
     {
         parent::init();
     }

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
     * 初始化支付：货到付款直接返回支付检查页面
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::initPay()
     */
    public function initPay($params) {
        Yii::$app->cache->set('pay_' . $params['id'], 'success');
        /* @var $view \yii\web\View */
        $view = Yii::$app->getView();
        $viewFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->id . '.php';
        return $view->renderFile($viewFile, [
            'params'=>$params,
        ]);
    }
    
    /**
     * 返回支付结果
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::getPayResult()
     */
    public function getPayResult($params) {
        return [
            'pay_result'=>Yii::$app->cache->get('pay_' . $params['id']),
            'is_cod'=>true
        ];
    }
}
