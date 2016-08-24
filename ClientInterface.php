<?php
namespace a76\pay;

/**
 * 支付客户端接口
 * @author 尖刀 <a761208@gmail.com>
 */
interface ClientInterface
{
    /**
     * @param string $id 客户端编号
     */
    public function setId($id);
    
    /**
     * @return string 客户端编号
     */
    public function getId();
    
    /**
     * @return string 客户端名称
     */
    public function getName();
    
    /**
     * @param string $name 客户端名称
     */
    public function setName($name);
    
    /**
     * @return string 客户端标题
     */
    public function getTitle();
    
    /**
     * @param string $title 客户端标题
     */
    public function setTitle($title);
    
    /**
     * 设置支付唯一编码：保存并判断支付状态
     * @param string $pay_id 支付编码
     */
    public function setPayId($pay_id);
    
    /**
     * 初始化支付：显示支付页面等
     * @param array 支付参数
     * @return string 页面内容
     */
    public function initPay($params);
    
    /**
     * 返回支付结果
     * @return array ['pay_result'=>'success|failure', 'is_cod'=>true|false]
     */
    public function getPayResult();
}
