<?php
namespace a76\pay;

use yii\base\Component;
use yii\base\NotSupportedException;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use Yii;

/**
 * BaseClient is a base pay Client class.
 *
 * @see ClientInterface
 *
 * @property string $id Service id.
 * @property string $name Service name.
 * @property string $title Service title.
 */
abstract class BaseClient extends Component implements ClientInterface
{
    /**
     * @var string pay service id.
     * cod weixin alipay
     */
    private $_id;
    /**
     * @var string pay service name.
     * This value may be used in database records, CSS files and so on.
     */
    private $_name;
    /**
     * @var string pay service title to display in views.
     * 货到付款 微信支付 支付宝支付
     */
    private $_title;
    /**
     * @var array view options in format: optionName => optionValue
     */
    private $_viewOptions;
    /**
     * @var array pay result.
     */
    private $_payResult;


    /**
     * @param string $id service id.
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string service id
     */
    public function getId()
    {
        if (empty($this->_id)) {
            $this->_id = $this->getName();
        }

        return $this->_id;
    }

    /**
     * @param string $name service name.
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string service name.
     */
    public function getName()
    {
        if ($this->_name === null) {
            $this->_name = $this->defaultName();
        }

        return $this->_name;
    }

    /**
     * @param string $title service title.
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @return string service title.
     */
    public function getTitle()
    {
        if ($this->_title === null) {
            $this->_title = $this->defaultTitle();
        }

        return $this->_title;
    }

    /**
     * @param array $viewOptions view options in format: optionName => optionValue
     */
    public function setViewOptions($viewOptions)
    {
        $this->_viewOptions = $viewOptions;
    }

    /**
     * @return array view options in format: optionName => optionValue
     */
    public function getViewOptions()
    {
        if ($this->_viewOptions === null) {
            $this->_viewOptions = $this->defaultViewOptions();
        }

        return $this->_viewOptions;
    }

    /**
     * @param array $payResult pay result.
     */
    public function setPayResult($payResult)
    {
        $this->_payResult = $payResult;
    }

    /**
     * @return array 支付结果
     */
    public function getPayResult()
    {
        if ($this->_payResult === null) {
            $this->_payResult = [];
        }
        return $this->_payResult;
    }

    /**
     * Generates service name.
     * @return string service name.
     */
    protected function defaultName()
    {
        return Inflector::camel2id(StringHelper::basename(get_class($this)));
    }

    /**
     * Generates service title.
     * @return string service title.
     */
    protected function defaultTitle()
    {
        return StringHelper::basename(get_class($this));
    }

    /**
     * Returns the default [[viewOptions]] value.
     * Particular client may override this method in order to provide specific default view options.
     * @return array list of default [[viewOptions]]
     */
    protected function defaultViewOptions()
    {
        return [];
    }
    
    /**
     * 初始化支付：显示支付页面
     * {@inheritDoc}
     * @see \a76\pay\ClientInterface::initPay()
     */
    public function initPay($params) {
        throw new NotSupportedException('Method "' . get_class($this) . '::' . __FUNCTION__ . '" not implemented.');
    }
    
    /**
     * 检查支付结果页面
     * @param array $params 支付参数
     * @return string
     */
    protected function renderCheck($params) {
        /* @var $view \yii\web\View */
        $view = Yii::$app->getView();
        $viewFile = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'check.php';
        return $view->renderFile($viewFile, [
            'params'=>$params
        ]);
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
