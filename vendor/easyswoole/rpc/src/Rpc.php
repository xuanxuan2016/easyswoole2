<?php


namespace EasySwoole\Rpc;


use EasySwoole\Component\Process\Socket\TcpProcessConfig;
use EasySwoole\Component\Singleton;
use EasySwoole\Component\TableManager;
use EasySwoole\Rpc\Exception\Exception;
use Swoole\Table;

class Rpc
{
    protected $config;
    protected $list = [];

    use Singleton;

    function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * 注册服务
     * @param AbstractService $service
     * @return $this
     */
    public function add(AbstractService $service)
    {
        if (!isset($this->list[$service->serviceName()])) {
            $this->list[$service->serviceName()] = $service;
            //创建服务统计表
            TableManager::getInstance()->add($service->serviceName(), [
                'success' => ['type' => Table::TYPE_INT, 'size' => 8],
                'fail' => ['type' => Table::TYPE_INT, 'size' => 8]
            ], 64);
            //初始化每个接口的统计信息
            $list = $service->actionList();
            foreach ($list as $action) {
                TableManager::getInstance()->get($service->serviceName())->set($action, [
                    'success' => 0,
                    'fail' => 0,
                ]);
            }
        }
        return $this;
    }
    
    /**
     * 添加work与tick进程到swoole
     */
    public function attachToServer(\swoole_server $server)
    {
        $list = $this->generateProcess();
        foreach ($list['worker'] as $p) {
            $server->addProcess($p->getProcess());
        }
        foreach ($list['tickWorker'] as $p) {
            $server->addProcess($p->getProcess());
        }
    }
    
    /**
     * 创建work与tick进程
     */
    public function generateProcess(): array
    {
        $this->check();
        $ret = [];
        for ($i = 1; $i <= $this->getConfig()->getWorkerNum(); $i++) {
            $config = new TcpProcessConfig();
            $config->setProcessName("Rpc.Worker.{$i}");
            $config->setListenAddress($this->getConfig()->getListenAddress());
            $config->setListenPort($this->getConfig()->getListenPort());
            $config->setArg(['config' => $this->getConfig(), 'serviceList' => $this->list]);
            //创建服务进程
            $ret['worker'][] = new WorkerProcess($config);
        }
        //创建定时器进程
        $ret['tickWorker'][] = new TickProcess("Rpc.TickWorker", ['config' => $this->getConfig(), 'serviceList' => $this->list], false, 2, true);
        return $ret;
    }

    /**
     * 检查rpc配置信息是否正确
     */
    private function check()
    {
        if (empty($this->config->getServerIp())) {
            throw new Exception("serve ip is require");
        }
        if (empty($this->config->getNodeManager())) {
            throw new Exception("serve NodeManager require");
        }
    }
    
    /**
     * 获取rpc客户端
     */
    function client(): RpcClient
    {
        return new RpcClient($this->getConfig()->getNodeManager());
    }
}