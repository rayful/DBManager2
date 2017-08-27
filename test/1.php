<?php
/**
 * Created by PhpStorm.
 * User: kingmax
 * Date: 2017/6/18
 * Time: 上午9:47
 */

define("MONGO_HOST", "mongodb://127.0.0.1");
define("MONGO_DB", "test");

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../src/DBManager.php";
require __DIR__ . "/../src/Data.php";
require __DIR__ . "/../src/DataSet.php";


class UserManager extends \rayful\MongoDB\DBManager
{

    /**
     * 返回本对象的数据库集合名称
     * @return string
     */
    protected function collectionName()
    {
        return "user";
    }
}

class User extends \rayful\MongoDB\Data
{
    public $username;

    public $password;

    public $truename;

    /**
     * 必须实现，一般返回这条数据的名称(可以是关乎这条数据的任何标识)，用于直接打印这个对象的时候将返回什么。
     * @return String
     */
    public function name()
    {
        return $this->truename;
    }

    /**
     * 声明数据库管理实例
     * @example return new ProductManager();    //是一个DBManager的子类实例
     * @return \rayful\MongoDB\DBManager
     */
    public function DBManager()
    {
        return new UserManager();
    }
}

class Users extends \rayful\MongoDB\DataSet
{

    /**
     * 声明迭代器返回的对象实例
     * @example return new Product();   //Product是Data的子类
     * @return \rayful\MongoDB\Data
     */
    protected function iterated()
    {
        return new User();
    }
}

//增
$User = new User();
$User->username = "kingmax";
$User->password = md5("123456");
$User->save();

//查
$User = new User(['username' => "kingmax"]);
print_r($User);

//改
$User->password = md5("1234567");
$User->save();

//快捷修改
$User->update(['$set' => ['truename' => '杨灵']]);
$User = new User(['username' => "kingmax"]);
print_r($User);

//删
$User->delete();
echo "Is exists? " . $User->isExists() ? "true\n" : "false\n";

//批量插入及查询
for ($i = 0; $i <= 10; $i++) {
    $User = new User();
    $User->username = "user" . $i;
    $User->truename = "name" . $i;
    $User->insert();
}

$Users = new Users();
$Users->find(['username' => ['$in' => ['user1', 'user2', 'user3']]])->sort(['truename' => -1])->limit(3);
foreach ($Users as $User) {
    echo $User . "\n";
}

//批量删
echo "Removing ... \n";
$Users->remove();

//看看剩下来的
$Users = new Users();
echo "Remove left: " . $Users->count() . "\n";

//同样可以批量改
$Users->update(['$set' => ['truename' => 'kingmax New']]);
foreach ($Users as $User) {
    echo $User . " delete .. \n";
    $User->delete();
}

echo "total:" . $Users->count() . "\n";

//批量分批插入
$User1 = new User();
$User1->truename = "Leo";

$User2 = new User();
$User2->truename = "Mark";

$User = new User();
$User->username = "kingmax";
$User->save();

$User3 = new User(['username'=>'kingmax']);
$User3->truename = "杨灵";

$User4 = ['username'=>'soul'];
$User5 = ['_id'=>$User->_id, 'truename'=>'张三'];

$UserManager = new UserManager();
$UserManager->insert($User1);
$UserManager->insert($User2);
$UserManager->update($User3);
$UserManager->insert($User4);
$UserManager->update($User5);

$result = $UserManager->flush();

print_r($result);

$UserManager = new UserManager();
$UserManager->delete($User1);
$UserManager->delete($User2);
$UserManager->delete($User3);
$UserManager->delete($User4);

$result = $UserManager->flush();

print_r($result);