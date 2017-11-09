<?php
namespace LongMarch\ForCanal;

/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2017/3/6
 * Time: 上午11:23
 */
class Aop
{
    private $dbName;
    private $tableName;
    private $eventType;
    public $model;
    private $updateStatus;

    public function __construct($Iterator,$conf)
    {
        foreach ($Iterator as $key => $value) {
            switch ($key) {
                case 'dbName' :
                    $this->dbName = $value;
                    break;
                case 'tableName' :
                    $this->tableName = $value;
                    break;
                case 'eventType' :
                    $this->eventType = $value;
                    break;
                case 'contents' :
                    $this->updateStatus = $value->update;
                    unset($value->update);
                    $this->model = $value;
                    break;
            }
        }
        $this->modelType($Iterator,$conf);
    }

    private function modelType($Iterator,$conf)
    {
        if (isset($conf[$this->dbName][$this->tableName])) {
            $modelStr = $conf[$this->dbName][$this->tableName];
        } else {
            return false;
        }
        $this->model = $this->cast($modelStr, $this->model);
        $this->model->eventType = $this->eventType;
        $this->model->updateStatus = $this->updateStatus;
        $this->model->hook($Iterator);
    }

    /**
     * @param $destination \Entities\category\PiCategory
     * @param $sourceObject 属性json
     * @return mixed
     */
    private function cast($destination, $sourceObject)
    {
        if (is_string($destination)) {
            $destination = new $destination();
        }
        $sourceReflection = new \ReflectionObject($sourceObject);
        $destinationReflection = new \ReflectionObject($destination);
        $sourceProperties = $sourceReflection->getProperties();
        foreach ($sourceProperties as $sourceProperty) {
            $sourceProperty->setAccessible(true);
            $name = $sourceProperty->getName();
            $value = $sourceProperty->getValue($sourceObject);
            if ($destinationReflection->hasProperty($name)) {
                $propDest = $destinationReflection->getProperty($name);
                $propDest->setAccessible(true);
                $propDest->setValue($destination, $value);
            } else {
                $destination->$name = $value;
            }
        }
        return $destination;
    }
}