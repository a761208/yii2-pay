<?php
namespace a76\pay;

use yii\base\Component;
use Yii;

/**
 * Collection is a storage for all pay clients.
 *
 * Example application configuration:
 *
 * ```php
 * 'components' => [
 *     'payCollection' => [
 *         'class' => 'a76\pay\Collection',
 *         'clients' => [
 *             'weixin' => [
 *                 'class' => 'a76\pay\clients\Weixin',
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
 * @author A761208 <a761208@gmail.com>
 */
class Collection extends Component
{
    /**
     * @var array list of pay client with their configuration in format: 'clientId' => [...]
     */
    private $_clients = [];

    /**
     * @param array $clients list of pay clients
     */
    public function setClients(array $clients)
    {
        $this->_clients = $clients;
    }

    /**
     * @param boolean 是否允许货到付款
     * @return ClientInterface[] list of pay clients.
     */
    public function getClients($canCOD = true)
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
     * @param string $id service id.
     * @return ClientInterface pay client instance.
     */
    public function getClient($id)
    {
        if ($id == 'cod') {
            return $this->createClient('cod', [
                'class'=>'a76\pay\clients\Cod'
            ]);
        }
        if (!array_key_exists($id, $this->_clients)) {
            throw new \Exception('Unknown pay client: ' . $id . '.');
        }
        if (!is_object($this->_clients[$id])) {
            $this->_clients[$id] = $this->createClient($id, $this->_clients[$id]);
        }
        return $this->_clients[$id];
    }

    /**
     * Checks if client exists in the hub.
     * @param string $id client id.
     * @return boolean whether client exist.
     */
    public function hasClient($id)
    {
        if ($id == 'cod') {
            return true;
        }
        return array_key_exists($id, $this->_clients);
    }

    /**
     * Creates pay client instance from its array configuration.
     * @param string $id pay client id.
     * @param array $config pay client instance configuration.
     * @return ClientInterface pay client instance.
     */
    protected function createClient($id, $config)
    {
        $config['id'] = $id;
        return Yii::createObject($config);
    }
}
