<?php
/**
 * Created by PhpStorm.
 * User: kingmax
 * Date: 2017/5/14
 * Time: 上午9:52
 */

namespace rayful\MongoDB;


use MongoDB\BSON\ObjectID;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use rayful\Tool\objectTool;

abstract class Data
{
    use objectTool;

    /**
     * 主键字段，除非自己指定，否则将自增。
     * @var ObjectID
     */
    public $_id;

    /**
     * @param mixed $param Primary Key or Query
     */
    public function __construct($param = null)
    {
        if ($param) {
            if (!is_array($param)) {  //Not a query, is Primary Key
                $this->setID($param);
            }
            $this->find($param);
        }
    }

    public function __toString()
    {
        return strval($this->name());
    }

    /**
     * 必须实现，一般返回这条数据的名称(可以是关乎这条数据的任何标识)，用于直接打印这个对象的时候将返回什么。
     * @return String
     */
    abstract public function name();

    /**
     * 声明数据库管理实例
     * @example return new ProductManager();    //是一个DBManager的子类实例
     * @return DBManager
     */
    abstract public function DBManager();

    /**
     * 根据参数自动在数据中找数据
     * @param string|ObjectID|array $param 智能类型，可以是数组(query),也可以是ID(可以是字符串类型或\MongoId类型)
     * @return $this
     */
    public function find($param)
    {
        $filter = $this->genQuery($param);
        $option = ['limit' => 1];
        $Query = new Query($filter, $option);
        $rows = $this->DBManager()->getManager()->executeQuery($this->DBManager()->getNamespace(), $Query)->toArray();
        return $this->set(current($rows));
    }

    public function isExists()
    {
        $Query = new Query($this->filter());
        return count($this->DBManager()->getManager()->executeQuery($this->DBManager()->getNamespace(), $Query)->toArray()) > 0;
    }

    public function save()
    {
        $Bulk = new BulkWrite();
        $Bulk->update($this->filter(), $this->toArrayAndRemoveEmptyId(), ['upsert' => true]);
        $Result = $this->DBManager()->getManager()->executeBulkWrite($this->DBManager()->getNamespace(), $Bulk);
        if($ids= $Result->getUpsertedIds())
            $this->setID($ids[0]);
        return $Result;
    }

    public function insert()
    {
        $this->checkIsExists(false);
        $Bulk = new BulkWrite();
        $id = $Bulk->insert($this->toArrayAndRemoveEmptyId());
        if($id)
            $this->setID($id);
        $Result = $this->DBManager()->getManager()->executeBulkWrite($this->DBManager()->getNamespace(), $Bulk);
        return $Result;
    }

    public function update($newObj)
    {
        $this->checkIsExists();
        $Bulk = new BulkWrite();
        $Bulk->update($this->filter(), $newObj);
        $Result = $this->DBManager()->getManager()->executeBulkWrite($this->DBManager()->getNamespace(), $Bulk);
        return $Result;
    }

    public function delete()
    {
        $this->checkIsExists();
        $Bulk = new BulkWrite();
        $Bulk->delete($this->filter());
        $Result = $this->DBManager()->getManager()->executeBulkWrite($this->DBManager()->getNamespace(), $Bulk);
        return $Result;
    }

    protected function checkIsExists($flag = true)
    {
        if ($this->isExists() != $flag) {
            throw new \Exception("This data " . ($flag ? "is not" : "is") . " exists, can not process.");
        }
    }

    private function toArrayAndRemoveEmptyId()
    {
        $array = $this->toArray();
        if (empty($array['_id'])) unset($array['_id']);
        return $array;
    }

    private function setID($id)
    {
        $this->_id = $id;
    }

    private function filter()
    {
        if($this->_id)
            return ['_id' => $this->_id];
        else
            return [false];
    }

    private function genQuery($param)
    {
        if (is_array($param)) {
            return $param;
        } else {
            return ['_id' => $param];
        }
    }
}