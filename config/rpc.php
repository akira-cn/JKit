<?php
/**
 配置示例
$_arrRpcConfig = array(
        'baidu_news' => array(
                'type' => RPC_TYPE_HTTP,
                'option' => array(
                        'ctimeout' => 200, //连接超时，单位毫秒
                        'rtimeout' => 1000, //读超时，单位毫秒
                        'wtimeout' => 1000, //写超时，单位毫秒
                        'balance' => 'Rpc_Balance_RoundRobin',
                ),
                'server' => array(
                        array('host' => 'news.baidu.com', 'port' => 80),
                        array('host' => '220.181.112.138', 'port' => 80),
                ),
        ),
        'reco_video' => array(
                'type' => RPC_TYPE_THRIFT,
                'option' => array(
                        'balance' => 'Rpc_Balance_RoundRobin',
                        'transport' => 'TBufferedTransport',
                ),
                'server' => array(
                        array('host' => '10.34.7.172', 'port' => 10021),
                        array('host' => '10.1.121.81', 'port' => 10021),
                ),
        ),
        'example_ttserver' => array(
                'type' => RPC_TYPE_TTSERVER,
                'option' => array(
                        'balance' => 'Rpc_Balance_RoundRobin',
                ),
                'server' => array(
                        array('host' => '10.34.7.172', 'port' => 10021),
                ),
        ),
);
*/
return array();