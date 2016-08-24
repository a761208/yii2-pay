<?php
namespace a76\pay;

use yii\base\Component;
use Yii;

/**
 * 保存所有支付客户端
 *
 * 配置示例:
 *
 * ```php
 * 'components' => [
 *     'payCollection' => [
 *         'class' => 'a76\pay\Collection',
 *         'clients' => [
 *             'weixin' => [
 *                 'class' => 'a76\pay\clients\Weixin',
 *                 'app_id'     => 'xxxxxxxxxxxxxxxxxx', // 微信AppId
 *                 'app_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', // 微信AppSecret
 *                 'mch_id'     => 'xxxxxxxxxx', // 微信商户编号
 *                 'api_key'    => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', // 微信商户API密钥
 *                 'notify_url' => '/site/pay-notify.html', // 微信支付回调地址
 *                 'qr_url'     => '/site/qr.html?content={$content}', // 内容生成二维码地址，其中{$content}将被替换为实际二维码内容
 *             ],
 *             'alipay' => [
 *                 'class' => 'a76\pay\clients\Alipay',
 *                 'appid' => '',
 *                 'appsecret' => ''
 *             ],
 *         ],
 *     ],
 *     ...
 * ]
 * ```
 *
 * @author 尖刀 <a761208@gmail.com>
 */
class Collection extends Component
{
    /**
     * @var array 支付客户端列表：'weixin'=>[...], 'alipay'=>[...]
     */
    private $_clients = [];

    /**
     * @param array $clients 支付客户端列表
     */
    public function setClients(array $clients)
    {
        $this->_clients = $clients;
    }

    /**
     * @param boolean $canCOD = true 是否允许货到付款
     * @return ClientInterface[] 支付客户端列表
     */
    public function getClients($canCOD = false)
    {
        $clients = [];
        if ($canCOD) {
            $clients['cod'] = $this->createClient('cod', [
                'class'=>'a76\pay\clients\Cod'
            ]);
        }
        foreach ($this->_clients as $id => $client) {
            $clients[$id] = $this->getClient($id);
        }
        return $clients;
    }

    /**
     * @param string $id 客户端编号
     * @return ClientInterface 支付客户端列表
     */
    public function getClient($id)
    {
        if ($id == 'cod') {
            return $this->createClient('cod', [
                'class'=>'a76\pay\clients\Cod'
            ]);
        }
        if (!array_key_exists($id, $this->_clients)) {
            throw new \Exception('无法识别支付类型：' . $id . '.');
        }
        if (!is_object($this->_clients[$id])) {
            $this->_clients[$id] = $this->createClient($id, $this->_clients[$id]);
        }
        return $this->_clients[$id];
    }

    /**
     * 判断是否存在客户端
     * @param string $id 客户端编号
     * @return boolean
     */
    public function hasClient($id)
    {
        if ($id == 'cod') {
            return true;
        }
        return array_key_exists($id, $this->_clients);
    }

    /**
     * 根据设置生成支付客户端实例
     * @param string $id 客户端编号
     * @param array $config 设置
     * @return ClientInterface 支付客户端
     */
    protected function createClient($id, $config)
    {
        $config['id'] = $id;
        return Yii::createObject($config);
    }
}
