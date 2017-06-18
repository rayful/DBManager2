<?php
/**
 * Created by PhpStorm.
 * User: kingmax
 * Date: 2017/5/14
 * Time: 上午9:31
 */

namespace rayful\MongoDB;


use MongoDB\Driver\Manager;

abstract class DBManager
{
    private static $Manager;

    public function getManager()
    {
        if(is_null(self::$Manager)){
            self::$Manager = new Manager($this->host());
        }
        return self::$Manager;
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

    /**
     * 返回本对象的数据库集合名称
     * @return string
     */
    abstract protected function collectionName();

    private function host()
    {
        if(defined("MONGO_HOST")){
            return MONGO_HOST;
        }else{
            throw new \Exception("You must define MONGO_HOST constant.");
        }
    }

    private function DBName()
    {
        if(defined("MONGO_DB")){
            return MONGO_DB;
        }else{
            throw new \Exception("You must define MONGO_DB constant.");
        }
    }
}