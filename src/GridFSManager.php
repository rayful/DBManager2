<?php
/**
 * Created by PhpStorm.
 * User: kingmax
 * Date: 2018/6/24
 * Time: 下午1:54
 */

namespace rayful\MongoDB;

use MongoDB\BSON\ObjectId;
use MongoDB\GridFS\Bucket;

class GridFSManager extends DBManager
{
    protected function collectionName()
    {
        return '';
    }

    public function downloadBytes($collection, $id)
    {
        $resource = $this->getGridBucket($collection)->openDownloadStream(new ObjectId(strval($id)));
        $bytes = stream_get_contents($resource);
        return $bytes;
    }

    public function upload($collection, $filename, $path)
    {
        $stream = fopen($path, 'rb');
        return $this->getGridBucket($collection)->uploadFromStream($filename, $stream);
    }

    public function getGridBucket($collection = null)
    {
        if ($collection)
            $opt = ['bucketName' => $collection];
        else
            $opt = [];

        return new Bucket($this->getManager(), $this->getDBName(), $opt);
    }
}