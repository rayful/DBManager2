<?php
/**
 * Created by PhpStorm.
 * User: kingmax
 * Date: 2017/5/20
 * Time: 下午4:42
 */

namespace rayful\MongoDB;

use MongoDB\BSON\ObjectID;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Query;
use rayful\Tool\Pagination\MorePage;
use rayful\Tool\Pagination\Pagination;


abstract class DataSet implements \IteratorAggregate
{
    /**
     * 数据库游标
     * @var Cursor
     */
    protected $_cursor;

    /**
     * 数据库搜索条件
     * @var array
     */
    protected $_query = [];

    /**
     * 数据库搜索条件
     * @var array
     */
    protected $_sort = [];

    /**
     * 数据指针是否永不超时,当数据量很大时间执行时间很长的时候,需要通过setImmortal方法将此属性设置为true
     * @var bool
     */
    protected $_immortal = false;

    /**
     * 最大个数
     * @var int
     */
    protected $_limit;

    /**
     * 跳过个数
     * @var int
     */
    protected $_skip = 0;

    /**
     * 当前位置
     * @var int
     */
    protected $_position = 0;

    /**
     * 数据库搜索字段,不设定默认搜索全部字段
     * @var array
     */
    protected $_fields = [];

    /**
     * 分页类
     * @var Pagination
     */
    protected $_pagination;

    public function __construct($query = [])
    {
        $this->find($query);
    }

    public function __toString()
    {
        $names = [];
        foreach ($this as $Iterated) {
            $names[] = $Iterated->name();
        }
        return implode(",", $names);
    }

    public function __toArray()
    {
        $array = [];
        foreach ($this as $id => $Iterated) {
            $array[strval($id)] = $Iterated;
        }
        return $array;
    }

    public function toArray()
    {
        return $this->__toArray();
    }

    /**
     * 声明迭代器返回的对象实例
     * @example return new Product();   //Product是Data的子类
     * @return Data
     */
    abstract protected function iterated();

    /**
     * 返回本对象的数据库集合命名空间名称
     * @return DBManager
     */
    protected function DBManager()
    {
        return $this->iterated()->DBManager();
    }

    /**
     * 仅为了实现迭代器，一般不使用。
     * @return \Generator
     */
    public function getIterator()
    {
        $this->query();
        foreach ($this->getCursor() as $data){
            $DataObject = $this->iterated();
            $DataObject->set($data);
            yield $DataObject;
        }
    }

    /**
     * 当前循环到哪一个位置(真正位置,考虑到skip这个属性)
     * @return int
     */
    public function position()
    {
        return $this->getPosition() + $this->getSkip();
    }

    final public function query()
    {
        $filter = $this->_query;
        $option = [];
        if ($this->isImmortal())    $option['noCursorTimeout'] = true;
        if ($this->getSort())       $option['sort'] = $this->getSort();
        if ($this->getLimit())      $option['limit'] = $this->getLimit();
        if ($this->getSkip())       $option['skip'] = $this->getSkip();

        $Query = new Query($filter, $option);
        $Cursor = $this->DBManager()->getManager()->executeQuery($this->DBManager()->getNamespace(), $Query);

        $this->setCursor($Cursor);

        return $this;
    }

    final public function find(array $query)
    {
        $this->_query = $query;
        return $this;
    }

    final public function findOne(array $query, array $sort = [])
    {
        foreach ($this->find($query)->sort($sort)->limit(1) as $Iterated){
            return $Iterated;
        }
    }

    final public function ensureIndexAndFind($query)
    {
        $key = array_fill_keys(array_keys($query), 1);
        $indexName = implode("-", $key);

        $Command = new Command([
            "createIndexes" => $this->DBManager()->getCollectionName(),
            "indexes"       => [[
                "name" => $indexName,
                "key"  => $key,
                "ns"   => $this->DBManager()->getNamespace(),
            ]],
        ]);
        $this->DBManager()->getManager()->executeCommand($this->DBManager()->getDBName(), $Command);
        return $this->find($query);
    }

    final public function limit($limit)
    {
        $this->_limit = intval($limit);
        return $this;
    }

    final public function sort(array $sort)
    {
        $this->_sort = $sort;
        return $this;
    }

    final public function skip($skip)
    {
        $this->_skip = intval($skip);
        return $this;
    }

    private function getCursor()
    {
        return $this->_cursor;
    }

    private function setCursor(Cursor $Cursor)
    {
        $this->_cursor = $Cursor;
        return $this;
    }

    final public function getQuery()
    {
        return $this->_query;
    }

    final public function getSort()
    {
        return $this->_sort;
    }

    private function isImmortal()
    {
        return $this->_immortal;
    }

    final public function setImmortal($immortal)
    {
        $this->_immortal = $immortal;
        return $this;
    }

    final public function getLimit()
    {
        return $this->_limit;
    }

    final public function getSkip()
    {
        return $this->_skip;
    }

    final public function getPosition()
    {
        return $this->_position;
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function setFields($fields)
    {
        $this->_fields = $fields;
        return $this;
    }

    public function count()
    {
        $Command = new Command([
            "count" =>  $this->DBManager()->getCollectionName(),
            "query" =>  $this->getQuery(),
        ]);
        $Cursor = $this->DBManager()->getManager()->executeCommand($this->DBManager()->getDBName(), $Command);
        $count = $Cursor->toArray()[0]->n;
        return $count;
    }

    public function update($newObject)
    {
        $Bulk = new BulkWrite();
        $Bulk->update($this->getQuery(), $newObject, ['multi'=>1]);
        $Result = $this->DBManager()->getManager()->executeBulkWrite($this->DBManager()->getNamespace(), $Bulk);
        return $Result;
    }

    public function remove()
    {
        if (!$this->getQuery()) throw new \Exception("批量删除必须指定一个删除条件(query)。请检查。");
        $Bulk = new BulkWrite();
        $Bulk->delete($this->getQuery());
        $Result = $this->DBManager()->getManager()->executeBulkWrite($this->DBManager()->getNamespace(), $Bulk);
        return $Result;
    }

    public function readRequest()
    {
        if ($_REQUEST) {
            $this->setByRequest($_REQUEST);
        }
        return $this;
    }

    public function setByRequest(array $request)
    {
        foreach ($request as $requestId => $requestValue) {
            if ($requestValue !== "") {
                $method = "_request_" . $requestId;
                if (method_exists($this, $method)) {
                    $this->{$method}($requestValue);
                }
            }
        }
        return $this;
    }

    final protected function appendQuery(array $query)
    {
        foreach ($query as $key => $value) {
            $this->_query[$key] = $value;
        }
        return $this;
    }

    /**
     * 这个用在根据ID精确找
     * @param string $requestValue
     */
    protected function _request_id($requestValue)
    {
        $this->find([
            '_id' => new ObjectID($requestValue)
        ]);
    }

    /**
     * 这个用在传递ID集批量找
     * @param array $requestValue
     */
    protected function _request_ids(array $requestValue)
    {
        $this->find([
            '_id' => ['$in' => array_map(function ($id) {
                if (is_string($id)) {
                    return new ObjectID($id);
                }
            }, $requestValue)]
        ]);
    }

    /**
     * 这个用在跨页全选,前端先通过getQuery()方法把当前搜索的query serialize()+base64_encode()传递过来，后端就能找回之前搜索的条件然后批量进行操作
     * @param string $requestValue
     */
    protected function _request_query($requestValue)
    {
        $this->find(unserialize(base64_decode($requestValue)));
    }

    /**
     * 这个用在前端指定每页显示多少个时有用
     * @param $requestValue
     */
    protected function _request_limit($requestValue)
    {
        $this->limit(intval($requestValue));
    }

    /**
     * 这个用在前端指定排序方法时有用,可指定排序字段还有是正序还是反序
     * @param array $requestValue
     * @example ['field'=>'used','type'=>'1'] ['field'=>'title','type'=>'-1']
     */
    protected function _request_sort(array $requestValue)
    {
        if (!empty($requestValue['field']) && !empty($requestValue['type'])) {
            $this->sort([
                $requestValue['field'] => (intval($requestValue['type']) > 0 ? 1 : -1)
            ]);
        }
    }

    /**
     * 这里指定用哪个分页类来分类.如果返回Pagination的子类,可以改变默认的显示方式.
     * @return MorePage
     */
    protected function genPagination()
    {
        return new MorePage();
    }

    /**
     * 在foreach(即执行实际的数据库查询)前调用此方法将可以自动分页.直接打印此方法返回的实例将可以显示出分页.
     * @param string $key 通过URL Query的什么参数来传递当前页码值
     * @return Pagination
     */
    final public function paginate($key = "page")
    {
        if (is_null($this->_pagination)) {
            $Pagination = $this->genPagination()->setKey($key)->setTotal($this->count())->setLimit($this->_limit);
            $this->skip($Pagination->getSkip());
            $this->_pagination = $Pagination;
        }
        return $this->_pagination;
    }

}