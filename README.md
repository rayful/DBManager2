# DBManager2
A new ORM Driver for mongoDB driver for PHP(PHP7)

require: PHP5.5+, MongoDB Driver.

# Install
composer require rayful/db-manager2

# History
https://github.com/rayful/DBManager

## 2.01版本
- Fix Bug, DataSet parseRequest()的强制参数类型去除
- Fix Bug, DBManager的insert、update、delete三个方法同时兼容数组及对象，并且入库前会将入参转换为数组，使代码执行稳定性更强（待验证）

## 2.0版本
- 去除几个函数，包括：DataSet=>setByRequest与readRequest，变成parseRequest($request);
- 分页依赖变成最新版rayful/pagination 2.0，留下paginate方法，但不包含样式，最新分页详细说明请见:https://github.com/rayful/Pagination
- 增加DBManager的批量操作功能，包括批量入库、更新及删除，请见DBManager内的三个方法，分别是insert、update、delete及flush方法。也可以见下面的例子。
- 魔术方法统一用_request_（开头）+请求变量名称。

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
```
