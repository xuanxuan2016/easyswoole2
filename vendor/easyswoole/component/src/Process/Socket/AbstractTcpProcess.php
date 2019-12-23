<?php


namespace EasySwoole\Component\Process\Socket;


use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\Component\Process\Exception;
use Swoole\Coroutine\Socket;
use Swoole\Coroutine;

/**
 * tcp进程抽象类
 */
abstract class AbstractTcpProcess extends AbstractProcess
{
    /**
     * 构造函数
     * @param \EasySwoole\Component\Process\Socket\TcpProcessConfig $config
     * @throws Exception
     */
    function __construct(TcpProcessConfig $config)
    {
        $config->setEnableCoroutine(true);
        if(empty($config->getListenPort())){
            throw new Exception("listen port empty at class ".static::class);
        }
        parent::__construct($config);
    }
    
    /**
     * 进程启动后执行
     * @param type $arg
     * @return type
     * @throws Exception
     */
    public function run($arg)
    {
        //创建socket套接字
        $socket = new Socket(AF_INET,SOCK_STREAM,0);
        //设置网络地址与端口可复用
        $socket->setOption(SOL_SOCKET,SO_REUSEPORT,true);
        $socket->setOption(SOL_SOCKET,SO_REUSEADDR,true);
        //socket绑定端口
        $ret = $socket->bind($this->getConfig()->getListenAddress(),$this->getConfig()->getListenPort());
        if(!$ret){
            throw new Exception(static::class." bind {$this->getConfig()->getListenAddress()} at {$this->getConfig()->getListenPort()} fail ");
        }
        //监听客户端连接
        $ret = $socket->listen(2048);
        if(!$ret){
            throw new Exception(static::class." listen {$this->getConfig()->getListenAddress()} at {$this->getConfig()->getListenPort()} fail ");
        }
        while (1){
            //等待客户端发起的连接，获取客户端连接的Socket对象
            $client = $socket->accept(-1);
            if(!$client){
                return;
            }
            if($this->getConfig()->isAsyncCallback()){
                //(默认)异步处理请求，使用协程
                Coroutine::create(function ()use($client){
                    try{
                        $this->onAccept($client);
                    }catch (\Throwable $throwable){
                        $this->onException($throwable,$client);
                    }
                });
            }else{
                //同步处理请求
                try{
                    $this->onAccept($client);
                }catch (\Throwable $throwable){
                    $this->onException($throwable,$client);
                }
            }
        }
    }
    
    /**
     * 接收到客户端请求
     */
    abstract function onAccept(Socket $socket);
}