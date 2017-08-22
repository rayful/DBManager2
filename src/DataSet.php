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
use rayful\Tool\Pagination\Pagination;

abstract class DataSet implements \IteratorAggregate
{
    /**
     * 数据库查询过滤条件
     * @var array
     */
    protected $filter = [];

    /**
     * 数据库搜索条件
     * @var array
     */
    protected $sort = [];

    /**
     * 数据指针是否永不超时,当数据量很大时间执行时间很长的时候,需要通过setImmortal方法将此属性设置为true
     * @var bool
     */
    protected $immortal = false;

    /**
     * 最大个数
     * @var int
     */
    protected $limit;

    /**
     * 跳过个数
     * @var int
     */
    protected $skip = 0;

    /**
     * 当前位置
     * @var int
     */
    protected $position = 0;

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
     * 对象迭代器的实现
     * @return \Generator
     */
    public function getIterator()
    {
        foreach ($this->getCursor() as $data) {
            $dataObject = $this->iterated();
            $dataObject->set($data);
            yield strval($dataObject->_id) => $dataObject;
        }
    }

    /**
     * 主程序，真正到数据库去执行查询操作，并且返回数据库游标
     * @return Cursor
     */
    final public function getCursor()
    {
        $filter = $this->getFilter();
        $option = $this->genOption();

        $query = new Query($filter, $option);
        $cursor = $this->DBManager()->getManager()->executeQuery($this->DBManager()->getNamespace(), $query);
        return $cursor;
    }

    /**
     * 当前循环到哪一个位置(真正位置,考虑到skip这个属性)
     * @return int
     */
    public function getRealPosition()
    {
        return $this->getPosition() + $this->getSkip();
    }

    /**
     * ##################################################################
     * # 下列的该系列方法为了跟随旧的Mongo驱动，让程序员更好理解，这里不改名了。 #
     * #################################################################
     */

    final public function find(array $query)
    {
        $this->filter = $query;
        return $this;
    }

    final public function findOne(array $query, array $sort = [])
    {
        foreach ($this->find($query)->sort($sort)->limit(1) as $dataObject) {
            return $dataObject;
        }
    }

    final public function limit($limit)
    {
        $this->limit = intval($limit);
        return $this;
    }

    final public function sort(array $sort)
    {
        $this->sort = $sort;
        return $this;
    }

    final public function skip($skip)
    {
        $this->skip = intval($skip);
        return $this;
    }

    final public function immortal($liveForever = true)
    {
        $this->immortal = $liveForever;
        return $this;
    }

    final public function count()
    {
        $command = new Command([
            "count" => $this->DBManager()->getCollectionName(),
            "query" => $this->getFilter(),
        ]);
        $cursor = $this->DBManager()->getManager()->executeCommand($this->DBManager()->getDBName(), $command);
        $count = $cursor->toArray()[0]->n;
        return $count;
    }
    
    /**
     * ##################################################################
     * # --------------------- Getter Here --------------------------- #
     * #################################################################
     */

    final public function getFilter()
    {
        return $this->filter;
    }

    final public function getSort()
    {
        return $this->sort;
    }

    final public function getLimit()
    {
        return $this->limit;
    }

    final public function getSkip()
    {
        return $this->skip;
    }

    final public function getPosition()
    {
        return $this->position;
    }

    final public function isImmortal()
    {
        return $this->immortal;
    }

    /**
     * ##################################################################
     * # ------------------------- 附加方法----------------------------- #
     * #################################################################
     */
    
    public function ensureIndexAndFind($query)
    {
        $indexKey = array_fill_keys(array_keys($query), 1);
        $this->DBManager()->ensureIndex($indexKey);

        return $this->find($query);
    }

    public function update($newObject)
    {
        $bulkWrite = new BulkWrite();
        $bulkWrite->update($this->getFilter(), $newObject, ['multi' => 1]);
        $result = $this->DBManager()->getManager()->executeBulkWrite($this->DBManager()->getNamespace(), $bulkWrite);
        return $result;
    }

    public function remove()
    {
        if (!$this->getFilter()) throw new \Exception("批量删除必须指定一个删除条件(query)。请检查。");
        $bulkWrite = new BulkWrite();
        $bulkWrite->delete($this->getFilter());
        $result = $this->DBManager()->getManager()->executeBulkWrite($this->DBManager()->getNamespace(), $bulkWrite);
        return $result;
    }

    /**
     * 分页方法（具体调用见 https://github.com/rayful/Pagination）
     * 与limit方法类似，不同的是，他可以自动读取当前页数帮你查询时跳过相应纪录。
     * @param float $limit 每页纪录数
     * @return Pagination
     */
    public function paginate($limit)
    {
        $pagination = new Pagination($this->count(), $limit);
        $this->limit($limit)->skip($pagination->getSkipRecord());
        return $pagination;
    }

    /**
     * ## 魔术调用 ## 根据请求自动生成查询、过滤、排序、分页限制等条件
     * 注意：2.0版本以下的readRequest、setByRequest因为命名的原因已经被废弃。
     * @param \Traversable $request 请求的内容，一般为可遍历的数组
     * @return $this
     */
    public function parseRequest(\Traversable $request)
    {
        foreach ($request as $key => $value) {
            if ($value !== "") {    //允许为0等PHP认为的空值
                $method = "_request_" . $key;
                if (method_exists($this, $method)) {
                    $this->{$method}($value);
                }
            }
        }
        return $this;
    }

    /**
     * 这个用在根据ID精确找
     * @param string $value
     */
    protected function _request_id($value)
    {
        $this->find([
            '_id' => new ObjectID($value)
        ]);
    }

    /**
     * 这个用在传递ID集批量找
     * @param array $value
     */
    protected function _request_ids(array $value)
    {
        $this->find([
            '_id' => ['$in' => array_map(function ($id) {
                if (is_string($id)) {
                    return new ObjectID($id);
                }
            }, $value)]
        ]);
    }

    /**
     * 这个用在跨页全选,前端先通过getFilter()方法把当前搜索的filter serialize()+base64_encode()传递过来，后端就能找回之前搜索的条件然后批量进行操作
     * @param string $value
     */
    protected function _request_query($value)
    {
        $this->find(unserialize(base64_decode($value)));
    }

    /**
     * 这个用在前端指定每页显示多少个时有用
     * @param $value
     */
    protected function _request_limit($value)
    {
        $this->limit(intval($value));
    }

    /**
     * 这个用在前端指定排序方法时有用,可指定排序字段还有是正序还是反序
     * @param array $sorter
     * @example ['field'=>'used','type'=>'1'] ['field'=>'title','type'=>'-1']
     */
    protected function _request_sort(array $sorter)
    {
        if (!empty($sorter['field']) && !empty($sorter['type'])) {
            $this->sort([
                $sorter['field'] => (intval($sorter['type']) > 0 ? 1 : -1)
            ]);
        }
    }

    /**
     * 给子类调用，区别于find，这个方法会叠加查询条件。
     * @param array $query
     * @return $this
     */
    protected function appendQuery(array $query)
    {
        foreach ($query as $key => $value) {
            $this->filter[$key] = $value;
        }
        return $this;
    }

    private function genOption()
    {
        $option = [];
        if ($this->isImmortal()) $option['noCursorTimeout'] = true;
        if ($this->getSort()) $option['sort'] = $this->getSort();
        if ($this->getLimit()) $option['limit'] = $this->getLimit();
        if ($this->getSkip()) $option['skip'] = $this->getSkip();
        return $option;
    }
}