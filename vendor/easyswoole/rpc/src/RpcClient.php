<?php


namespace EasySwoole\Rpc;


use EasySwoole\Rpc\NodeManager\NodeManagerInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class RpcClient
{
    protected $nodeManager;
    protected $callList = [];

    public function __construct(NodeManagerInterface $manager)
    {
        $this->nodeManager = $manager;
    }
    
    /**
     * 获取服务器状态
     * @param \EasySwoole\Rpc\ServerNode $serverNode
     * @param float $timeout
     * @return \EasySwoole\Rpc\Response
     */
    public function serverStatus(ServerNode $serverNode, float $timeout = 3.0): Response
    {
        $command = new Command();
        $command->setCommand(Command::SERVICE_STATUS);
        $client = new TcpClient($serverNode, $timeout);
        $client->sendCommand($command);
        return $client->recv();
    }
    
    /**
     * 添加请求方法
     * @param string $service
     * @param string $action
     * @param type $arg
     * @param type $serviceVersion
     * @return \EasySwoole\Rpc\ServiceCall
     */
    public function addCall(string $service, string $action, $arg = null, $serviceVersion = null): ServiceCall
    {
        $item = new ServiceCall([
            'serviceName' => $service,
            'action' => $action,
            'arg' => $arg,
            'serviceVersion' => $serviceVersion
        ]);
        $this->callList[] = $item;
        return $item;
    }
    
    /**
     * 执行所有请求
     * @param float $timeout
     */
    function exec(float $timeout = 3.0)
    {
        $list = [];
        /** @var ServiceCall $item */
        foreach ($this->callList as $item) {
            /*
             * 如果指定了节点
             */
            $serviceNode = null;
            if ($item->getServiceNode()) {
                //已存在服务节点
                $serviceNode = $item->getServiceNode();
            } else {
                //从节点管理器获取节点
                $serviceNode = $this->nodeManager->getServiceNode($item->getServiceName(), $item->getServiceVersion());
            }
            if (!$serviceNode) {
                $response = new Response([
                    'status' => Response::STATUS_NODES_EMPTY
                ]);
                $this->callback($response, $item);
            } else {
                //通过TcpClient发送命令
                $item->setServiceNode($serviceNode);
                $command = new Command();
                $command->setCommand(Command::SERVICE_CALL);
                $command->setRequest(new Request($item->toArray()));
                $client = new TcpClient($serviceNode);
                $client->sendCommand($command);
                $list[] = [
                    'client' => $client,
                    'call' => $item
                ];
            }
        }
        //协程获取服务端返回
        $channel = new Channel(128);
        foreach ($list as $item) {
            Coroutine::create(function () use ($item, $channel, $timeout) {
                //将请求放入通道
                $channel->push([
                    'response' => $item['client']->recv($timeout),
                    'call' => $item['call']
                ]);
            });
        }

        $left = $timeout;
        $leftHandler = count($list);
        //从通道获取请求结果
        while ($left > 0 && $leftHandler > 0) {
            $start = round(microtime(true), 3);
            $ret = $channel->pop($left);
            $left = $left - (round(microtime(true), 3) - $start);
            $leftHandler--;
            if (is_array($ret)) {
                $this->callback($ret['response'], $ret['call']);
            }
        }
    }
    
    /**
     * 执行服务调用后的回调方法
     * @param \EasySwoole\Rpc\Response $response
     * @param \EasySwoole\Rpc\ServiceCall $serviceCall
     */
    private function callback(Response $response, ServiceCall $serviceCall)
    {
        if ($response->getStatus() == $response::STATUS_OK) {
            $call = $serviceCall->getOnSuccess();
        } else {
            $call = $serviceCall->getOnFail();
        }
        if (is_callable($call)) {
            call_user_func($call, $response, $serviceCall);
        }
    }
}