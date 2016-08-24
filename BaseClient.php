<?php
namespace a76\pay;

use yii\base\Component;
use yii\base\NotSupportedException;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * 支付客户端基类
 *
 * @see ClientInterface
 *
 * @property string $id 客户端编号
 * @property string $name 客户端名称
 * @property string $title 客户端标题
 * @property string $payId 支付唯一编码
 * 
 * @author 尖刀 <a761208@gmail.com>
 */
abstract class BaseClient extends Component implements ClientInterface
{
    /**
     * @var string 支付客户端编号
     * cod weixin alipay
     */
    private $_id;
    /**
     * @var string 支付客户端名称，用来标识CSS等
     */
    private $_name;
    /**
     * @var string 支付客户端标题，显示到页面
     * 货到付款 微信支付 支付宝支付
     */
    private $_title;
    /**
     * @var string 支付唯一编码
     */
    private $_pay_id;

    /**
     * @param string $id 客户端编号
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string 客户端编号
     */
    public function getId()
    {
        if (empty($this->_id)) {
            $this->_id = $this->getName();
        }
        return $this->_id;
    }

    /**
     * @param string $name 客户端名称
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string 客户端名称
     */
    public function getName()
    {
        if ($this->_name === null) {
            $this->_name = $this->defaultName();
        }
        return $this->_name;
    }

    /**
     * @param string $title 客户端标题
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @return string 客户端标题
     */
    public function getTitle()
    {
        if ($this->_title === null) {
            $this->_title = $this->defaultTitle();
        }
        return $this->_title;
    }

    /**
     * 生成默认的客户端名称
     * @return string 客户端名称
     */
    protected function defaultName()
    {
        return Inflector::camel2id(StringHelper::basename(get_class($this)));
    }

    /**
     * 生成默认的客户端标题
     * @return string 客户端标题
     */
    protected function defaultTitle()
    {
        return StringHelper::basename(get_class($this));
    }
    
    /**
     * {@inheritDoc}
     * @see \a76\pay\ClientInterface::setPayId()
     */
    public function setPayId($pay_id) {
        $this->_pay_id = $pay_id;
    }
    
    /**
     * 返回支付编号
     * @return string
     */
    protected function getPayId() {
        return $this->_pay_id;
    }
    
    /**
     * {@inheritDoc}
     * @see \a76\pay\ClientInterface::initPay()
     */
    public function initPay($params) {
        throw new NotSupportedException('Method "' . get_class($this) . '::' . __FUNCTION__ . '" not implemented.');
    }
    
    /**
     * {@inheritDoc}
     * @see \a76\pay\ClientInterface::notifyPay()
     */
    public function notifyPay($raw) {
        throw new NotSupportedException('Method "' . get_class($this) . '::' . __FUNCTION__ . '" not implemented.');
    }
    
    /**
     * {@inheritDoc}
     * @see \a76\pay\ClientInterface::getPayResult()
     */
    public function getPayResult() {
        throw new NotSupportedException('Method "' . get_class($this) . '::' . __FUNCTION__ . '" not implemented.');
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     */
    protected function postXmlCurl($xml, $url, $useCert = false, $second = 30) {
        $ch = curl_init();
        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        // 如果有配置代理这里就设置代理
        if (false) {
            curl_setopt($ch,CURLOPT_PROXY, '0.0.0.0');
            curl_setopt($ch,CURLOPT_PROXYPORT, 0);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验
        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, '/path/to/your/cert/file');
            curl_setopt($ch,CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, '/path/to/your/key/file');
        }
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        // 运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new \Exception("curl出错，错误码:$error");
        }
    }
}
