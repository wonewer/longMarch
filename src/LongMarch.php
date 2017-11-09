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
class LongMarch
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
                $json = $message->body;
                $data = json_decode($json);
                $arrayiter = new JsonIter($data);
                new Aop($arrayiter,$this->dbConf);
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
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