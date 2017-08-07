# DBManager2
A new ORM Driver for mongoDB driver for PHP(PHP7)

require: PHP5.5+, MongoDB Driver.

# Install
composer require rayful/db-manager2

# History
https://github.com/rayful/DBManager

# Code Example:
```php

define("MONGO_HOST", "mongodb://127.0.0.1");
define("MONGO_DB", "test");

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../src/DataSetIterator.php";
require __DIR__ . "/../src/DBManager.php";
require __DIR__ . "/../src/Data.php";
require __DIR__ . "/../src/DataSet.php";


class UserManager extends \DB\Mongo\DBManager
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

class User extends \DB\Mongo\Data
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
     * @return \DB\Mongo\DBManager
     */
    public function DBManager()
    {
        return new UserManager();
    }
}

class Users extends \DB\Mongo\DataSet
{

    /**
     * 声明迭代器返回的对象实例
     * @example return new Product();   //Product是Data的子类
     * @return \DB\Mongo\Data
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
echo "Is exists? " . $User->isExists() ? "true" : "false";

//批量插入及查询
for ($i = 0; $i <= 10; $i++) {
    $User = new User();
    $User->username = "user" . $i;
    $User->truename = "name" . $i;
    $User->insert();
}

$Users = new Users();
$Users->find(['username' => ['$in' => ['user1', 'user2', 'user3']]])->sort(['truename' => -1])->limit(2);
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
```
