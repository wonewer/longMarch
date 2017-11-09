<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 16/7/25
 * Time: 上午10:14
 */

namespace mqClient;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Rabbitmq
{
    private $host;
    private $port;
    private $name;
    private $pass;
    private $vhost;
    private $connection;
    private $channel;
    private $exchange;
    private $queue;
    private $durable;
    private $act;
    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @return mixed
     */
    public function getVhost()
    {
        return $this->vhost;
    }

    /**
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return mixed
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return mixed
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @return mixed
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @return mixed
     */
    public function getDurable()
    {
        return $this->durable;
    }

    /**
     * @return mixed
     */
    public function getAct()
    {
        return $this->act;
    }

    public function __construct($host, $port, $name, $pass, $queue, $durable, $act)
    {
        $this->host = $host;
        $this->port = $port;
        $this->name = $name;
        $this->pass = $pass;
        $param = explode('.', $queue);
        $this->vhost = isset($param[0]) ? $param[0]: 'test';
        $this->exchange = isset($param[1])? $param[1]: 'test';
        $this->queue = isset($param[2])? $param[2] : 'test';
        $this->durable = $durable;
        $this->act = $act;
    }

    public function connection()
    {
        $this->connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->name,
            $this->pass,
            $this->vhost,
            false, 'AMQPLAIN', null, 'en_US', 60, 60, null, false, 30
        );
        $this->channel = $this->connection->channel();
        $this->channel->basic_qos(null, 1, null);
        if ($this->act && $this->channel) {
            $this->channel->confirm_select();
        }
    }

    public function send($json, $callback=null)
    {
        if ($this->act && $this->channel) {
            if (is_callable($callback) && $callback instanceof \Closure) {
                $this->channel->set_ack_handler(
                    $callback
                );
            }
            $this->channel->set_nack_handler(
                function (AMQPMessage $message) {
                    file_put_contents('mq.log', $message->body . PHP_EOL, FILE_APPEND);
                }
            );
        }
        $is_json = function ($string) {
            json_decode($string);
            return (json_last_error() == JSON_ERROR_NONE);
        };
        if (!$is_json($json)) {
            throw  new \Exception("json error");
        }
        if (!$this->channel) {
            throw  new \Exception("链接失败");
        }
        $param = array('content_type' => 'application/json');
        if ($this->durable) {
            $param = array(
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            );
        }
        $this->act && $this->channel->wait_for_pending_acks();
        $this->channel->basic_publish(new AMQPMessage($json, $param), $this->exchange);
        $this->act && $this->channel->wait_for_pending_acks();
    }

    public function recive($callback=null)
    {
        $this->channel->queue_declare($this->queue, false, $this->durable, false, false);
        $this->channel->queue_bind($this->queue, $this->exchange);
        $consumerTag = 'consumer';
        if (!is_callable($callback) || !($callback instanceof \Closure)) {
            $callback = function ($message)
            {
                echo "\n--------\n";
                echo $message->body;
                echo "\n--------\n";
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                // Send a message with the string "quit" to cancel the consumer.
                if ($message->body === 'quit') {
                    $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
                }
            };
        }
        $this->channel->basic_consume($this->queue, $consumerTag, false, false, false, false, $callback);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function count()
    {
        $connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->name,
            $this->pass,
            $this->vhost
        );
        $channel = $this->connection->channel();
        $message = $channel->basic_get($this->queue,false);
        $channel->close();
        $connection->close();
        $count = 0;
        isset($message->delivery_info['message_count']) && $count = $message->delivery_info['message_count'];
        $count > 0 && $count++;
        return $count;
    }

    public function close()
    {
        if (!$this->channel || !$this->connection) {
            throw  new \Exception("链接失败");
        }
        try{
            $this->channel->close();
        }catch (\Exception $e){
            if(preg_match("/NOT_FOUND - no exchange '.*' in vhost '.*'/",$e->getMessage())){
                $this->connection();
                $this->channel->exchange_declare($this->exchange, 'fanout', false, $this->durable, false);
                $this->channel->close();
            }
            throw  new \Exception($e);
        }
        $this->connection->close();
    }

}
