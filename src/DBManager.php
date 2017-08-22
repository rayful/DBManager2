<?php
/**
 * Created by PhpStorm.
 * User: kingmax
 * Date: 2017/5/14
 * Time: 上午9:31
 */

namespace rayful\MongoDB;


use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;

abstract class DBManager
{
    private static $manager;

    private static $bulkWrite;

    /**
     * 返回本对象的数据库集合名称
     * @return string
     */
    abstract protected function collectionName();

    /**
     * 默认读取全局常量MONGO_HOST,可以在子类自定义override
     * @return string
     * @throws \Exception
     */
    protected function host()
    {
        if(defined("MONGO_HOST")){
            return MONGO_HOST;
        }else{
            throw new \Exception("You must define MONGO_HOST constant.");
        }
    }

    /**
     * 默认读取全局常量MONGO_DB,可以在子类自定义override
     * @return string
     * @throws \Exception
     */
    protected function DBName()
    {
        if(defined("MONGO_DB")){
            return MONGO_DB;
        }else{
            throw new \Exception("You must define MONGO_DB constant.");
        }
    }

    public function getManager()
    {
        if(is_null(self::$manager)){
            self::$manager = new Manager($this->host());
        }
        return self::$manager;
    }

    public function getNamespace()
    {
        return $this->DBName().".".$this->collectionName();
    }

    public function getDBName()
    {
        return $this->DBName();
    }

    public function getCollectionName()
    {
        return $this->collectionName();
    }

    public function ensureIndex($indexKey)
    {
        $indexName = implode("-", array_keys($indexKey));
        $command = new Command([
            "createIndexes" => $this->getCollectionName(),
            "indexes" => [[
                "name" => $indexName,
                "key" => $indexKey,
                "ns" => $this->getNamespace(),
            ]],
        ]);
        return $this->getManager()->executeCommand($this->getDBName(), $command);
    }

    protected function getBulkWrite()
    {
        if(is_null(self::$bulkWrite)){
            self::$bulkWrite = new BulkWrite();
        }
        return self::$bulkWrite;
    }

    /**
     * 批量插入（需要用flush去服务器端执行）
     * @param Data $data
     */
    public function insert(Data &$data)
    {
        if(is_object($data) && is_null($data->_id)) unset($data->_id);
        $id = $this->getBulkWrite()->insert($data);
        $data->_id = $id;
    }

    /**
     * 批量更新（需要用flush去服务器端执行）
     * @param Data $data
     */
    public function update(Data $data)
    {
        $newObject = $data->toArray();  unset($newObject['_id']);
        $this->getBulkWrite()->update(['_id' => $data->_id], ['$set'=>$newObject]);
    }

    /**
     * 批量删除（需要用flush去服务器端执行）
     * @param Data $data
     */
    public function delete(Data $data)
    {
        $this->getBulkWrite()->delete(['_id' => $data->_id]);
    }

    public function flush()
    {
        $result = $this->getManager()->executeBulkWrite($this->getNamespace(), $this->getBulkWrite());
        self::$bulkWrite = null;
        return $result;
    }
}