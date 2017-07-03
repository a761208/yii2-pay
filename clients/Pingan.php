<?php

namespace a76\pay\clients;

use a76\pay\BaseClient;
use Yii;
use yii\helpers\Url;

/**
 * 平安银行支付
 * @author 尖刀 <a761208@gmail.com>
 */
class Pingan extends BaseClient
{
    public $masterId; // 商户号
    public $merchantCertFile; // 私钥文件
    public $tTrustPayCertFile; // 公钥文件
    public $ssl_password; // SSL密码

    /**
     * @inheritdoc
     */
    protected function defaultName()
    {
        return 'pingan';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
    {
        return '平安银行支付';
    }

    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::initPay()
     */
    public function initPay($params)
    {
        if (isset($params['action'])) {
            switch ($params['action']) {
                case 'bind_card': // 绑定银行卡
                    return $this->bindCard($params);
                case 'bind_result': // 绑定银行卡返回页
                    return '<script>window.location.href="' . $params['return_url'] . '";</script>';
            }
        }
        if (isset($params['returned'])) {
            $orig = Yii::$app->request->post('orig');
            $sign = Yii::$app->request->post('sign');

            $orig = $this->_base64_url_decode($orig);
            $sign = $this->_base64_url_decode($sign);

            //$result = $this->validate($orig, $sign, Yii::getAlias($this->tTrustPayCertFile));

            $orig = mb_convert_encoding($orig, 'UTF-8', 'GBK');
            return '结果：' . \yii\helpers\Html::encode($orig);
        }

        $this->setData('pay_result_' . $params['id'], 'waiting');

        $gateway_url = 'https://ebank.sdb.com.cn/khpayment/UnionAPI_Open.do';
        $return_url = Url::current(['returned' => 1], true);
        $notify_url = Url::to(['/site/pay-notify'], true);

        $data = array(
            'masterId' => $this->masterId,
            'customerId' => '123',
            'orderId' => $params['id'],
            'dateTime' => date('YmdHis'),
        );

        $data = $this->_getData($data);

        list($orig, $sign) = $this->_getOrigAndSing(Yii::getAlias($this->merchantCertFile), $data);

        $parameter = array(
            'orig' => $orig,
            'sign' => $sign,
            'returnurl' => $return_url,
            'NOTIFYURL' => $notify_url,
        );

        return $this->showHtml($gateway_url, $parameter);
    }

    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::notifyPay()
     */
    public function notifyPay($raw)
    {
        Yii::error('平安银行回调：' . $raw);
        echo '支付结果回调';
    }

    /**
     * {@inheritDoc}
     * @see \a76\pay\BaseClient::getPayResult()
     */
    public function getPayResult()
    {
        return array_merge([
            'pay_result' => $this->getData('pay_result_' . $this->getPayId()),
            'pay_money' => $this->getData('pay_money_' . $this->getPayId()),
            'pay_remark' => $this->getData('pay_remark_' . $this->getPayId()),
        ], parent::getPayResult() !== false ? json_decode(parent::getPayResult(), true) : []);
    }

    /**
     * 绑定银行卡
     * @param $params array
     * @return string
     */
    private function bindCard($params)
    {
        $notify_url = Url::to(['/site/pay-notify-pingan'], true);
        $return_url = $params['return_url'];
        $gateway = 'https://ebank.sdb.com.cn/khpayment/UnionAPI_Open.do';

        // 组装订单数据
        $data = array(
            'masterId' => $this->masterId,
            'customerId'=> $params['customerId'],
            'orderId' =>  $this->masterId . date('Ymd') . rand(10000000, 99999999),
            'dateTime' => date('YmdHis'),
        );

        $data = $this->_getData($data);

        // 获取orig和sign
        list($orig, $sign) = $this->_getOrigAndSing(Yii::getAlias($this->merchantCertFile), $data);

        $parameter = array(
            'orig' => $orig,
            'sign' => $sign,
            'returnurl' =>Url::to(['/site/pay', 'payclient'=>'pingan', 'action'=>'bind_result', 'return_url'=>$return_url], true),
            'NOTIFYURL' =>$notify_url,
        );

        return $this->showHtml($gateway, $parameter);
    }

    /**
     * 获取绑定银行卡列表
     * @param $customer_id string 用户编号
     * @return false|array
     */
    public function getBindCardList($customer_id)
    {
        $gateway_url = 'https://ebank.sdb.com.cn/khpayment/UnionAPI_Opened.do';
        $data = [
            'masterId' => $this->masterId,
            'customerId' => $customer_id,
        ];

        $xml_data = $this->array_to_xml($data);
        $merchantCertFile = Yii::getAlias($this->merchantCertFile);

        // 获取签名后的orig和sign
        $orig = $this->getOrig($xml_data);
        $sign = $this->getSign($merchantCertFile, $xml_data);

        // 通过curl请求接口
        $parms = 'orig=' . $orig . '&sign=' . $sign;
        $rsponse = $this->curl($gateway_url, $parms);

        $rsponse = preg_split('/\r|\n/', $rsponse, -1, PREG_SPLIT_NO_EMPTY);
        $rsponseSign = substr($rsponse[0], 5);
        $rsponseOrig = substr($rsponse[1], 5);

        // 解码
        $formSign = $this->_base64_url_decode($rsponseSign);
        $formOrig = $this->_base64_url_decode($rsponseOrig);
        // 验证签名是否正确
        $result = $this->validate($formOrig, $formSign, Yii::getAlias($this->tTrustPayCertFile));
        if (!$result) {
            return false;
        }

        $formOrig = iconv("GBK", "UTF-8", $formOrig);
        $orig_data = $this->xml_to_array($formOrig);

        return $orig_data['body'];
    }

    /**
     * 进行数据提交
     * @param string $gateway
     * @param array $parameter
     * @return string
     */
    private function showHtml($gateway, $parameter) {
        $html = '<html><head><meta charset="UTF-8" /></head><body>';
        $html .= '<form method="post" name="P_FORM" action="' . $gateway . '">';
        foreach ($parameter as $key => $val) {
            $html .= "<input type='hidden' name='$key' value='$val' />";
        }
        $html .= '</form><script type="text/javascript">document.P_FORM.submit();</script>';
        $html .= '</body></html>';
        return $html;
    }

    public function array_to_xml($data)
    {
        $xml_data = '<kColl id="input" append="false">';
        foreach ($data as $key => $value) {
            $xml_data .= '<field id="' . $key . '" value="' . $value . '"/>';
        }
        $xml_data .= '</kColl>';

        return $xml_data;
    }

    public function xml_to_array($orig_xml)
    {
        $xml = simplexml_load_string($orig_xml);
        $arr = json_decode(json_encode($xml), TRUE);

        $res = array();

        foreach ($arr ['field'] as $key => $row) {
            $res [$row ['@attributes'] ['id']] = $row ['@attributes'] ['value'];
            //array_push($res, $row['@attributes']['id']);
        }

        if (array_key_exists('iColl', $arr)) {
            $resBody = array();
            if (array_key_exists('kColl', $arr ['iColl'])) {
                // 如果多个对象，是在一个数组
                if (!array_key_exists('field', $arr ['iColl'] ['kColl'])) {
                    foreach ($arr ['iColl'] ['kColl'] as $key => $row) {
                        $coll = array();
                        foreach ($row ['field'] as $_key => $_row) {
                            $coll [$_row ['@attributes'] ['id']] = $_row ['@attributes'] ['value'];
                        }
                        array_push($resBody, $coll);
                    }
                } //如果单个对象，仅返回对象
                else {
                    $coll = array();
                    foreach ($arr ['iColl'] ['kColl'] ['field'] as $_key => $_row) {
                        $coll [$_row ['@attributes'] ['id']] = $_row ['@attributes'] ['value'];
                    }
                    array_push($resBody, $coll);
                }
                $res ['body'] = $resBody;
            } else { // 没有循环体，只有unionInfo，false
                foreach ($arr ['iColl'] as $key => $row) {
                    $res [$row ['id']] = $row ['append'];
                    // array_push($res, $row['@attributes']['id']);
                }
            }
        }
        return $res;
    }

    /**
     * 将数据组装为xml格式的数据
     * @param array $data
     * @return string
     *
     * $data = '<kColl id="input" append="false"><field id="masterId" value="2000311146"/><field id="orderId" value="20003111462015060473550416"/><field id="currency" value="RMB"/><field id="amount" value="0.01"/><field id="objectName" value="??"/><field id="paydate" value="20150604143506"/><field id="remark" value="??"/><field id="validtime" value="0"/></kColl>';
     */
    private function _getData($data)
    {
        $xml_data = '<kColl id="input" append="false">';
        foreach ($data as $key => $value) {
            $xml_data .= '<field id="' . $key . '" value="' . $value . '"/>';
        }
        $xml_data .= '</kColl>';

        return $xml_data;
    }

    /**
     * 获取签名过后的原始数据orig和签名数据sign
     * @param string $merchantCertFile
     * @param string $data
     * @return array
     */
    private function _getOrigAndSing($merchantCertFile, $data)
    {
        $orig = $this->_base64_url_encode($data);
        $sign = $this->getSign($merchantCertFile, $data);

        return array($orig, $sign);
    }

    public function validate($orig, $sign, $tTrustPayCertFile)
    {
        $tSign = $this->hex2bin(trim($sign));
        $pem = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode(file_get_contents($tTrustPayCertFile)), 64, "\n") . "-----END CERTIFICATE-----\n";
        try {
            $iTrustpayCertificate = openssl_x509_read($pem);
            $key = openssl_pkey_get_public($iTrustpayCertificate);
        } catch (\Exception $e) {
            $key = openssl_pkey_get_public($pem);
        }
        $r = openssl_verify(trim($orig), $tSign, $key, OPENSSL_ALGO_MD5);
        openssl_free_key($key);
        return $r;
    }

    private function _validate_5_3($orig, $sign, $tTrustPayCertFile)
    {
        $tSign = $this->hex2bin(trim($sign));
        $pem = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode(file_get_contents($tTrustPayCertFile)), 64, "\n") . "-----END CERTIFICATE-----\n";
        $key = openssl_pkey_get_public($pem);
        return openssl_verify(trim($orig), $tSign, $key, OPENSSL_ALGO_MD5);
    }

    function hex2bin($hexdata)
    {
        $bindata = '';
        $length = strlen($hexdata);
        for ($i = 0; $i < $length; $i += 2) {
            $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
        }
        return $bindata;
    }

    /**
     * to base64 and url
     *
     * @param string $data
     * @return string
     */
    private function _base64_url_encode($data)
    {
        $data_base64 = base64_encode($data); // base64
        $data_gbk = iconv("UTF-8", "GBK", $data_base64); // utf-8 to gbk
        $data_url = urlencode($data_gbk); // url
        return $data_url;
    }

    public function _base64_url_decode($data)
    {
        $data_url = urldecode($data); // url
        $data_base64 = base64_decode($data_url); // base64
        return $data_base64;
    }

    public function getOrig($data)
    {
        $orig = $this->_base64_url_encode($data);
        return $orig;
    }

    /**
     * get sign
     *
     * @param string $merchantCertFile
     * @param string $data
     * @return string
     */
    public function getSign($merchantCertFile, $data)
    {
        $sign = $this->_getSign($merchantCertFile, $data);
        $sign = $this->_base64_url_encode($sign);
        return $sign;
    }

    /**
     * sign by open_ssl
     *
     * @param string $merchantCertFile
     * @param string $data
     * @return string
     */
    private function _getSign($merchantCertFile, $data)
    {
        $tCertificate = array();
        $pkey = '';
        if (openssl_pkcs12_read(file_get_contents($merchantCertFile), $tCertificate, $this->ssl_password)) {
            $pkey = openssl_pkey_get_private($tCertificate ['pkey']);
        }

        $signature = '';

        if (!openssl_sign($data, $signature, $pkey, OPENSSL_ALGO_MD5)) {
            exit ("Have a error!Please check!");
        }
        $sign = bin2hex($signature);
        return $sign;
    }

    function curl($url, $parms)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parms);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
