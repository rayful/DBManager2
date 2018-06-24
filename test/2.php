<?php
/**
 * Created by PhpStorm.
 * User: kingmax
 * Date: 2018/6/24
 * Time: 下午2:19
 */

define("MONGO_HOST", "mongodb://127.0.0.1");
define("MONGO_DB", "test");

require __DIR__ . "/../vendor/autoload.php";

//2.1新功能示例【1】：日期的入库和显示
$user = new stdClass();
$user->created = \rayful\MongoDB\DatetimeUtility::getNowDatetime();
echo \rayful\MongoDB\DatetimeUtility::toString($user->created);

//2.1新功能示例【2】：MongoGridFS的支持
$gridFSManager = new \rayful\MongoDB\GridFSManager();
$id = $gridFSManager->upload('','abc.jpg', '/Users/kingmax/Downloads/全部行为.jpg');

$bytes = $gridFSManager->downloadBytes('', $id);
