<?php

namespace EasySwoole\Rpc\NodeManager;

use EasySwoole\RedisPool\RedisPool;
use EasySwoole\Rpc\ServiceNode;
use EasySwoole\Utility\Random;

class RedisManager implements NodeManagerInterface
{
    protected $redisKey;

    protected $pool;

    function __construct(RedisPool $pool, string $hashKey = 'rpc')
    {
        $this->redisKey = $hashKey;
        $this->pool = $pool;
    }
    
    /**
     * 根据服务名称，获取所有有效服务节点
     */
    function getServiceNodes(string $serviceName, ?string $version = null): array
    {
        $redis = $this->pool->getObj(15);
        try {
            $nodes = $redis->hGetAll("{$this->redisKey}_{$serviceName}");
            $nodes = $nodes ?: [];
            $ret = [];
            foreach ($nodes as $nodeId => $node) {
                //根据参数，创建服务节点
                $node = new ServiceNode(json_decode($node,true));
                /**
                 * @var  $nodeId
                 * @var  ServiceNode $node
                 */
                //节点超时未使用，删除节点信息
                if (time() - $node->getLastHeartBeat() > 30) {
                    $this->deleteServiceNode($node);
                }
                //根据版本号获取节点
                if ($version && $version != $node->getServiceVersion()) {
                    continue;
                }
                $ret[$nodeId] = $node;
            }
            return $ret;
        } catch (\Throwable $throwable) {
            //如果该redis断线则销毁
            $this->pool->unsetObj($redis);
        } finally {
            $this->pool->recycleObj($redis);
        }
        return [];
    }
    
    /**
     * 根据服务名称，获取任一有效服务节点
     */
    function getServiceNode(string $serviceName, ?string $version = null): ?ServiceNode
    {
        $list = $this->getServiceNodes($serviceName, $version);
        if (empty($list)) {
            return null;
        }
        return Random::arrayRandOne($list);
    }
    
    /**
     * 删除服务节点信息
     */
    function deleteServiceNode(ServiceNode $serviceNode): bool
    {
        $redis = $this->pool->getObj(15);
        try {
            $redis->hDel($this->generateNodeKey($serviceNode), $serviceNode->getNodeId());
            return true;
        } catch (\Throwable $throwable) {
            $this->pool->unsetObj($redis);
        } finally {
            $this->pool->recycleObj($redis);
        }
        return false;
    }
    
    /**
     * 刷新节点的最近使用时间
     */
    function serviceNodeHeartBeat(ServiceNode $serviceNode): bool
    {
        if (empty($serviceNode->getLastHeartBeat())) {
            $serviceNode->setLastHeartBeat(time());
        }
        $redis = $this->pool->getObj(15);
        try {
            $redis->hSet($this->generateNodeKey($serviceNode), $serviceNode->getNodeId(), $serviceNode->__toString());
            return true;
        } catch (\Throwable $throwable) {
            $this->pool->unsetObj($redis);
        } finally {
            //这边需要测试一个对象被unset后是否还能被回收
            $this->pool->recycleObj($redis);
        }
        return false;
    }
    
    /**
     * 生成存储rpc信息的redis的key
     */
    protected function generateNodeKey(ServiceNode $node)
    {
        return "{$this->redisKey}_{$node->getServiceName()}";
    }
}
