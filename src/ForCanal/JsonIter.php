<?php
namespace LongMarch\ForCanal;
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2017/3/3
 * Time: 下午4:43
 */
class JsonIter extends RecursiveArrayIterator
{
    const CLOUMNNAME = 'columnName';
    const COLUMNVALUE = 'columnValue';
    const UPDATESTATUS = 'updateStatus';

    public function hasChildren()
    {
        return new self();
        $value = $this->current();
        $key = $this->key();
        if ($key == "content") {
            $obj = new self(array());
            return new self($obj);
        }
    }

    /**
     * @return mixed|stdClass
     * object(stdClass)#10 (7) {
     * ["id"]=>
     * string(1) "8"
     * ["name"]=>
     * string(3) "343"
     * ["is_available"]=>
     * string(1) "1"
     * ["insert_time"]=>
     * string(19) "2017-03-01 11:03:36"
     * ["last_update_time"]=>
     * string(19) "2017-03-01 11:03:36"
     * ["is_del"]=>
     * string(1) "0"
     * ["update"]=>
     * array(6) {
     * [0]=>
     * string(2) "id"
     * [1]=>
     * string(4) "name"
     * [2]=>
     * string(12) "is_available"
     * [3]=>
     * string(11) "insert_time"
     * [4]=>
     * string(16) "last_update_time"
     * [5]=>
     * string(6) "is_del"
     * }
     * }
     */
    public function current()
    {
        $value = parent::current();
        $key = $this->key();
        $ret = new stdClass();
        if ($key == 'contents') {
            $cloumnName = self::CLOUMNNAME;
            $cloumnValue = self::COLUMNVALUE;
            $updateStatus = self::UPDATESTATUS;
            $update = array();
            foreach ($value as $val) {
                $stdKey = $this->toCamelCase($val->$cloumnName);
                $stdValue = $val->$cloumnValue;
                $stdStatus = $val->$updateStatus;
                if ($stdStatus) {
                    $update[] = $stdKey;
                }
                $ret->$stdKey = $stdValue;
            }
            $ret->update = $update;
            $value = $ret;
        }

        return $value;
    }

    public function toCamelCase($str, $first_letter = false) {
        $arr = explode('_', $str);
        foreach ($arr as $key => $value) {
            $cond = $key > 0 || $first_letter;
            $arr[$key] = $cond ? ucfirst($value) : $value;
        }
        return implode('', $arr);
    }
}