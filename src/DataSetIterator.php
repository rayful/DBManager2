<?php
/**
 * Created by PhpStorm.
 * User: kingmax
 * Date: 2017/5/20
 * Time: 下午5:49
 */

namespace DB\Mongo;


use Traversable;

class DataSetIterator extends \IteratorIterator
{

    /**
     * @var Data
     */
    private $DataSetIterated;

    public function __construct(Traversable $iterator, $DataSetIterated)
    {
        parent::__construct($iterator);
        $this->DataSetIterated = $DataSetIterated;
    }

    public function current()
    {
        $data = parent::current();
        $Object = $this->DataSetIterated;
        $Object->set($data);
        return $Object;
    }
}