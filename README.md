# 第三方支付组件
提供货到付款、微信支付流程

##目录文件说明
assets/                 资源文件
    paychoice.js        客户端脚本：负责弹出窗口、处理链接、
clients/                第三方支付客户端处理程序
    Cod.php             货到付款处理
    Weixin.php          微信扫码支付处理
views/                  支付页面
    cod.php             货到付款支付页面
    weixin.php          微信扫码支付页面
widgets/                小部件
    PayChoice.php       客户端显示支付链接小部件
    PayChoiceAsset.php  页面用到的资源定义
BaseClient.php          第三方支付客户端基类
ClientInterface.php     第三方支付客户端接口
Collection.php          第三方支付客户端定义类
PayAction.php           显示支付页面、查询支付状态
PayNotifyAction.php     第三方支付回调地址

##系统需求
系统使用Yii::$app->cache保存支付状态

##安装方式
最好使用composer安装本组件
执行
```
composer require a76/yii2-pay
```
或者在composer.json中`require`下面增加
```json
"a76/yii2-pay": "*"
```
