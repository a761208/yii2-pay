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
 *                 'class' => 'a76\pay\clients\Weixin'
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
    public $_clients = [];

    /**
     * @param array $clients list of pay clients
     */
    public function setClients(array $clients)
    {
        $this->_clients = $clients;
    }

    /**
     * @return ClientInterface[] list of pay clients.
     */
    public function getClients()
    {
        $clients = [];
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
        return array_key_exists($id, $this->_clients);
    }

    /**
     * Creates auth client instance from its array configuration.
     * @param string $id auth client id.
     * @param array $config auth client instance configuration.
     * @return ClientInterface auth client instance.
     */
    protected function createClient($id, $config)
    {
        $config['id'] = $id;
        return Yii::createObject($config);
    }
}
