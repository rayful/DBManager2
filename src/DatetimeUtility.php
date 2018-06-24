<?php
/**
 * Created by PhpStorm.
 * User: kingmax
 * Date: 2018/6/23
 * Time: 上午11:27
 */

namespace rayful\MongoDB;


use MongoDB\BSON\UTCDateTime;

class DatetimeUtility
{
    public static function toString($dateTime, $format = "Y-m-d H:i")
    {
        if ($dateTime instanceof UTCDateTime) {
            return $dateTime->toDateTime()->setTimezone(new \DateTimeZone('Asia/Shanghai'))->format($format);
        }
    }

    public static function getNowDatetime()
    {
        return new UTCDateTime(time()*1000);
    }
}