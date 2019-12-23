<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 01:41
 */

namespace EasySwoole\Component\Process;
use EasySwoole\Component\Timer;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Process;
use Swoole\Coroutine\Scheduler;

/**
 * 自定义swoole进程抽象类
 */
abstract class AbstractProcess
{
    private $swooleProcess;
    /** @var Config */
    private $config;


    /**
     * name  args  false 2 true
     * AbstractProcess constructor.
     * @param string $processName
     * @param null $arg
     * @param bool $redirectStdinStdout
     * @param int $pipeType
     * @param bool $enableCoroutine
     */
    function __construct(...$args)
    {
        $arg1 = array_shift($args);
        if($arg1 instanceof Config){
            $this->config = $arg1;
        }else{
            $this->config = new Config();
            $this->config->setProcessName($arg1);
            $arg = array_shift($args);
            $this->config->setArg($arg);
            $redirectStdinStdout = (bool)array_shift($args) ?: false;
            $this->config->setRedirectStdinStdout($redirectStdinStdout);
            $pipeType = array_shift($args);
            $pipeType = $pipeType === null ? Config::PIPE_TYPE_SOCK_DGRAM : $pipeType;
            $this->config->setPipeType($pipeType);
            $enableCoroutine = (bool)array_shift($args) ?: false;
            $this->config->setEnableCoroutine($enableCoroutine);
        }
        //创建swoole进程
        $this->swooleProcess = new Process([$this,'__start'],$this->config->isRedirectStdinStdout(),$this->config->getPipeType(),$this->config->isEnableCoroutine());
    }

    public function getProcess():Process
    {
        return $this->swooleProcess;
    }

    public function addTick($ms,callable $call):?int
    {
        return Timer::getInstance()->loop(
            $ms,$call
        );
    }

    public function clearTick(int $timerId):?int
    {
        return Timer::getInstance()->clear($timerId);
    }

    public function delay($ms,callable $call):?int
    {
        return Timer::getInstance()->after($ms,$call);
    }

    /*
     * 服务启动后才能获得到pid
     */
    public function getPid():?int
    {
        if(isset($this->swooleProcess->pid)){
            return $this->swooleProcess->pid;
        }else{
            return null;
        }
    }
    
    /**
     * swoole服务开启后执行
     */
    function __start(Process $process)
    {
        /*
         * swoole自定义进程协程与非协程的兼容
         * 开一个协程，让进程推出的时候，执行清理reactor
         */
        Coroutine::create(function (){

        });
        if(PHP_OS != 'Darwin' && !empty($this->getProcessName())){
            $process->name($this->getProcessName());
        }
        
        /**
         * 添加socket管道事件
         */
        swoole_event_add($this->swooleProcess->pipe, function(){
            try{
                //从管道中读取数据
                $this->onPipeReadable($this->swooleProcess);
            }catch (\Throwable $throwable){
                $this->onException($throwable);
            }
        });
        
        /**
         * 添加异步信号(kill)监听
         */
        Process::signal(SIGTERM,function ()use($process){
            //删除socket管道事件
            swoole_event_del($process->pipe);
            /*
             * 清除全部定时器
             */
            \Swoole\Timer::clearAll();
            Process::signal(SIGTERM, null);
            Event::exit();
        });
        
        /**
         * 注册php中止时执行的函数
         */
        register_shutdown_function(function () {
            $schedule = new Scheduler();
            $schedule->add(function (){
                try{
                    $this->onShutDown();
                }catch (\Throwable $throwable){
                    $this->onException($throwable);
                }
                \Swoole\Timer::clearAll();
            });
            $schedule->start();
        });

        try{
            //运行进程启动的回调
            $this->run($this->config->getArg());
        }catch (\Throwable $throwable){
            $this->onException($throwable);
        }
    }

    public function getArg()
    {
        return $this->config->getArg();
    }

    public function getProcessName()
    {
        return $this->config->getProcessName();
    }

    protected function getConfig():Config
    {
        return $this->config;
    }

    protected function onException(\Throwable $throwable,...$args){
        throw $throwable;
    }

    protected abstract function run($arg);

    protected function onShutDown()
    {

    }

    protected function onPipeReadable(Process $process)
    {
        /*
         * 由于Swoole底层使用了epoll的LT模式，因此swoole_event_add添加的事件监听，
         * 在事件发生后回调函数中必须调用read方法读取socket中的数据，否则底层会持续触发事件回调。
         */
        $process->read();
    }
}