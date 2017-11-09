<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 16/7/25
 * Time: 上午10:15
 */

namespace mqClient;
class MQ
{
    static $_instance;

    public static function instance($host, $port, $name, $pass, $queue, $durable = true, $act = false)
    {
        if (is_null(self::$_instance) || !isset (self::$_instance)) {
            self::$_instance = new Rabbitmq($host, $port, $name, $pass, $queue, $durable, $act);
        } else {
            $rabbitmq = self::$_instance;
            if ($rabbitmq->getHost() != $host || $rabbitmq->getPort() != $port
                || $rabbitmq->getName() != $name || $rabbitmq->getPass() != $pass
                || $rabbitmq->getQueue() != $queue || $rabbitmq->getDurable() != $durable
                || $rabbitmq->getAct() != $act
            ) {
                self::$_instance = new Rabbitmq($host, $port, $name, $pass, $queue, $durable, $act);
            }
        }
        return self::$_instance;
    }


}