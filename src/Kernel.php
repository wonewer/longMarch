<?php
namespace LongMarch;
use LongMarch\ForCanal\Aop;
use LongMarch\ForCanal\JsonIter;
use LongMarch\MqClient\MQ;

/**
 * Created by PhpStorm.
 * User: wonewer
 * Date: 2017/11/8
 * Time: 16:10
 */
class Kernel
{
    /**
     * @var 注册的业务空間
     */
    private $conf;
    private $mqConf;
    private $dbConf;
    private function parseConf()
    {
       $this->mqConf = $this->conf['mq'];
       $this->dbConf[$this->conf['db']['name']] = $this->conf['db']['relation'];
    }
    public function register($conf)
    {
        $this->conf = $conf;
        $this->parseConf();
        return $this;
    }

    public function run()
    {
        $callback = function ($message){
            try {
                $json = preg_replace('/[\s\0]+/','',$message->body);
                $data = json_decode($json);
                $arrayiter = new JsonIter($data);
                new Aop($arrayiter,$this->dbConf);
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
                $info = PHP_EOL . 'start=======' . date('Y-m-d H:i:s') . '=========' . PHP_EOL;
                $info .= $json . PHP_EOL;
                $info .= $e->getMessage() . PHP_EOL;
                foreach ($e->getTrace() as $tkey => $tval) {
                    if (isset($tval['file']) && isset($tval['line'])) {
                        $info .= $tval['file'] . 'line:' . $tval['line']. PHP_EOL;
                    }
                }
                $info .= 'end==============='.PHP_EOL;
                echo $info;
                $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
            }
        };
        $mq = MQ::instance($this->mqConf['host'],$this->mqConf['port'],$this->mqConf['name'],$this->mqConf['pass'],$this->mqConf['query']);
        $mq->connection();
        $mq->recive($callback);
        while (isset($mq->callbacks) && count($mq->callbacks)){
            $mq->wait();
        }
    }
}
