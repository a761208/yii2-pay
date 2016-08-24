<?php
namespace a76\pay;

interface ClientInterface
{
    /**
     * @param string $id service id.
     */
    public function setId($id);
    
    /**
     * @return string service id
     */
    public function getId();
    
    /**
     * @return string service name.
     */
    public function getName();
    
    /**
     * @param string $name service name.
     */
    public function setName($name);
    
    /**
     * @return string service title.
     */
    public function getTitle();
    
    /**
     * @param string $title service title.
     */
    public function setTitle($title);
    
    /**
     * 初始化支付：显示支付页面等
     * @param array 支付参数
     * @return string 页面内容
     */
    public function initPay($params);
    
    /**
     * @param array $params
     * @return array 支付结果
     */
    public function getPayResult($params);
}
