# 基于Slim的PHP快速接口框架

## 文档修订历史记录
| 日期 | 版本 | 说明 | 作者 | 协作 |
| -----| -----| -----| -----| -----|
| 2016-09-28 | 2.0 | 初始版本 | 李文龙 | | 
| 2016-10-19 | 2.1 | 经过试用后根据使用者的反馈进行完善 | 李文龙 | 黄利科，高源| 

## 从零开始
### 安装composer
```
https://getcomposer.org/
```
### 引入Slim框架

PHP轻量级开发框架 ("slim/slim": "^3.5")

```
composer require slim/slim
```

### 引入Monolog
日志记录 ("monolog/monolog": "^1.21")

```
composer require monolog/monolog
```
### 引入Curl
发送HTTP请求 ("curl/curl": "^1.4")

```
composer require monolog/monolog 
```

### 引入PRedis 
Redis数据库操作 ("predis/predis": "^1.1")
```
composer require predis/predis
```

### 引入Redbean
轻量级ORM框架 ("gabordemooij/redbean": "^4.3")
```
composer require gabordemooij/redbean
```

### 目录结构
```
- root
    - base
        - b_base.php            全局基类
        - b_controller.php      控制层基类，继承全局基类
        - b_DAO.php             数据访问层基类，继承全局基类
        - b_model.php           Model层基类，继承全局基类，定义一系列数据有效性验证的基础方法
        - b_service.php         服务层基类，实现对CURL的封装，继承全局基类
        - config.php            框架层的配置文件
        - error_handler.php     异常处理，包括错误转码及错误日志的记录
        - global_error.php      框架级错误码定义，不可被应用层代码直接调用
        - invoker.php           Slim中间件，检查请求类型及记录请求日志
        - readme.md             目录说明
        - route.php             路由注册及查询方法封装
        - slim_bootstrap.php    项目启动代码，初始化Slim
    - vendor
        - Slim 3.5
        - Monolog 1.21
        - Curl 1.4
        - PRedis 1.1
        - Redbean 4.3
    - composer.json             composer的配置及autoload的配置
    - composer.lock
    - index.php                 加载error_handler, composer-autoload, Slim初始化
```


### 设计思路
#### 1. 页面重定向
通过Nginx将所有【找不到文件】的请求重定向到index.php页面
```
location /path/to/SlimFastAPI/ {
    try_files $uri $uri/ /path/to/SlimFastAPI/index.php?$query_string;
}
```
在index.php页面，先定义全局异常捕获的方法
```php
//异常处理
require 'base/error_handler.php';

//Require the lib that managed by comoser
require 'vendor/autoload.php';

//启动Slim
require 'base/slim_bootstrap.php';
```

#### 2. 异常处理模块
所有异常情况都通过PHP的Exception抛出，由此模块进行统一捕获，并返回500错误。根据不同的错误码定义错误的类型，统一定义错误信息，并将捕获到的错误通过异步接口记录到【错误管理系统】中。
- set_exception_handler 捕获抛出的异常
- set_error_handler 捕获运行时错误
- regist_shutdown_function 捕获语法错误
- 异常数据记录的接口地址定义在base/config.php中

#### 注意
- 如果是Base, BaseError, BaseController或BaseService出现错误，会导致Error_Handler代码出错，从而无法将错误正确返回

##### TODO
- IP地址无法正确记录（服务器配置的问题）
- 错误记录接口需要使用消息队列重新实现，【错误管理平台】待开发
- 记录成功返回错误编码，并通过接口返回给调用者

异常处理模块加载完成后，通过composer的autoload机制加载所有依赖项

#### 3. Composer Autoload
- 通过composer require 安装包的时候，会自动修改composer.json中的配置,不需要任何额外的操作
- 对于base层和app层的代码，需要手动修改composer.json文件，并在项目根目录下执行"composer dump-autoload"实现自动加载

类加载和文件加载示例：
```json
"autoload": {
    "classmap": [
        "base"
    ],
    "files": [
        "base/config.php"
    ]
}
```

Slim框架会在此时加载完成，下一步是通过slim_bootstrap.php文件实现Slim实例的初始化

#### 4. Slim 框架初始化
- Slim实例化并注册中间件,中间件实现content-type的检查与请求的输入和输出的记录

```
//注： 虽然中间件的功能也可以在注册业务路由时实现，但通过中间件可以处理通过常规方法注册的路由，更加通用

$app = new Slim\App([
    'settings' => [
        // Only set this if you need access to route within middleware
        'determineRouteBeforeAppMiddleware' => true
    ]
]);

//添加中间件
$app->add(new Invoker());
```
- 禁用Slim自带的异常处理功能并自定义【页面未找到】(PAGE_NOT_FOUND)和【请求类型不正确】(METHOD_NOT_ALLOWED)的处理方法
- 定义默认的路由（用于框架可用性测试）
    - "/" 首页默认返回项目名称及负责人信息
    - "/hello/{world}" Hello World, 为了情怀
    - "throw/{exception}", 异常测试，抛出指定Code的异常
- 注册业务路由（简化业务层的路由注册代码，但会在一定程序上影响PHP执行的性能，具体影响未进行测试）
    - 路由的实现中封装了通过Model加载参数并进行有效性验证的逻辑，简化Controller层的代码
    - 路由的定义中增加了自定义参数，实例化Model和Controller时会将该参数传递到构造函数中，由此实现一些定制化的功能（如跳过身份验证）

- Run Slim App

#### 5. 基类的定义
##### 全局基类
在Model层，数据操作层，业务层都会用到的方法
- isoDate 获取日期字符串,格式为 1970-01-01
- isoDateTime 获取时间字符串，格式为 1970-01-01 00:00:00
- addDebug 添加调试日志，只在DEBUG模式下生效，记录到logs/debug目录下
- addInfo 用于输出业务追踪日志，可指定输出目录 
- arraySelect 从一维关联数组中选择指定的列

#### Model基类
- 继承全局基类
- addRule / batchAddRule 添加验证规则
- load 从数组中加载数据，并对已注册了规则的KEY做有效性验证及类型转换,并将加载完成的数据返回
- paramError 封装抛出参数错误异常的代码（PARAM_ERR_CODE对应用层不可见）
- 参数有效性检查
    - checkByteLength 检查字节长度
    - checkString 参数是否为String，并支持正则匹配
    - checkStringLength 检查字符串长度（mb_strlen)
    - checkInt 参数是否为Int
    - checkDecimal 参数是否为Decimal, 并支持精度检查
    - checkMobile 参数是否为手机号（只限制了第一位为1，第二位为3~8）
    - checkEmail 参数是否为邮箱地址
    - checkUrl 参数是否为URL(http://或https://开头)
    - checkArray 参数是否为Array（非空）
    - checkIntArray 参数是否为IntArray
    - checkStringArray 参数是否为StringArray
    - checkIsoDateTime 参数是否为 1970-01-01 00:00:00 格式的时间
    - checkIsoDate 参数是否为 1970-01-01 格式的日期
    - checkRange 参数是否位于指定区间，支持数字，字符串及日期

#### DAO基类

    实现对Redbean基础操作的封装，在业务层不能直接使用Redbean框架，实现框架间解耦

    在DEBUG模式下,所有的数据库操作都会被记录到 logs/DAO目录下

- 在构造函数中初始化默认数据库的连接
- 在函数中关闭数据库连接
- switchDatabase 切换数据库，实现多数据库支持（不建议一个服务中使用多个数据库）
- insertRow 插入一行数据
- insertRows 插入多行数据
- selectRow 根据ID加载一行数据
- selectByFields 根据指定条件加载一行数据
- updateRow 根据ID更新一行数据
- updateByFields 根据指定条件更新数据
- deleteRow 根据ID删除一行数据
- deleteByFields 根据指定条件删除数据
- exec 执行一条SQL语句
- getAll 查询多行数据（同时支持build_query和参数绑定模式）
- getRow 查询单行数据（同时支持build_query和参数绑定模式）
- getCol 查询单列数据（同时支持build_query和参数绑定模式）
- getCell 查询单元数据（同时支持build_query和参数绑定模式）
- getAssoc 查询多行数据，并以SELECT的第一项做为返回数组的KEY（同时支持build_query和参数绑定模式）
- getAssocRow 查询单行数据，并以SELECT的第一项做为返回数组的KEY（同时支持build_query和参数绑定模式）
- genSlots 为数组类型的参数生成参数绑定占位符

##### TODO: 
- switchDatabase未测试
- GUID支持，并可通过配置切换

#### Controller基类
控制层基础操作的封装，主要功能是：

- 构造函数，目前无任何实现
- 析构函数，释放Redis连接
- checkParam 必填参数验证
- paramError 抛出参数错误的封装（PARAM_ERR_CODE对应用层不可见）
- getTicket 获取请求者身份标识（ticket不存在直接异常）
- authError 抛出身份认证错误的封装（AUTH_ERR_CODE对应用层不可见）
- returnArray 生成接口的标准返回值（return_code, return_msg, data)
- success 生成接口的标准成功返回值 (return_code使用默认值)
- error  生成接口的标准失败返回值 (return_code在自定义错误码的基础上添加项目前缀)
- getRedis 获取Redis连接
- getClientIp 获取客户端IP（注意如果Nginx使用了反向代理，要正确的设置proxy_set_header才能获取真实的客户端IP)
- getServerIp 获取服务器端IP (如果使用了服务器集群，则服务器端IP可以用于判断请求由哪一台服务器完成)

#### Service基类
对CURL发送HTTP请求进行封装，只包括下列四种常用的请求方法

- httpGet 
- httpPut
- httpPost
- httpDelete

#### 通用错误码定义

错误码的定义，业务层不能抛出框架级别的通用错误，所以此类不需要被业务层的CustomError类继承

- 错误码

| 编码 | 说明 |
| ---- | ---- |
| 100 | 身份验证错误 |
| 200 | 参数验证错误 |
| 300 | Redis错误 |
| 400 | Curl错误 |
| 500 | MySql错误 |

- 返回码

| 编码 | 说明 |
| ---- | ---- |
| 404 | 页面未找到 |
| 405 | 请求方法错误 |
| 20001 | 运行时错误 |
| 20002 | 运行时警告 |
| 20004 | 编译错误 |
| 20008 | 运行时通知 |
| 20010 | 其它异常 |
| 20100 | 身份认证失败 |
| 20200 | 参数错误 |
| 20300 | REDIS错误 |
| 20400 | CURL错误 |
| 20500 | MYSQL错误 |

## 框架的使用

### 目录结构 
```
- root
    - app
        - controller            控制层
            - c_app.php         业务控制层基类，继承控制层基类
            - c_demo.php      具体的实体操作实现
            - custom_error.php  业务层自定义错误码
        - DAO                   数据访问层
            - d_app.php         业务DAO层基类，继承数据访问层基类
            - d_entity.php      具体的实体数据库操作实现
        - model
            - m_app.php         业务实体层基类，继承Model层基类
            - m_demo.php      具体的实体数据有效性检查实现
        - route
            - route.php         路由文件的自动加载（route目录下所有前缀为"r_"的文件）
            - r_demo.php      具体业务的路由实现 
        - service
            - s_app.php         业务服务层基类，继承服务层基类
            - s_ip.php      具体的服务定义
        - config.php
        - config_beta.php
        - config_develop.php
        - config_production.php
```

### 配置文件定义
- 自动识别运行环境并加载配置文件
    
    ```php
    /**
     * 环境配置，自动识别环境并加载配置信息
     */


    if(isset($_SERVER['HTTP_HOST'])){
        $host = $_SERVER['HTTP_HOST'];
        if($host === 's.aikaka.com.cn'){
            $mode = 'production';
        }else if($host === 'beta.aikaka.com.cn'){
            $mode = 'beta';
        }else if($host === '192.168.2.2'){
            $mode = 'develop';
        }
    }else{
        $mode = $_SERVER['mode'];
    }

    if(!empty($mode)){
        require('config_' . $mode . '.php');
    }else{
        throw new Exception("未知的运行模式", E_ERROR);
    }
    ```
- 通用配置
    - PROJECT_NAME 项目名称
    - PROJECT_VERSION 版本号
    - PROJECT_OWNER 项目负责人
    - ERROR_PREFIX 错误码前缀

- 环境配置
    - DEBUG开关
    - MYSQL连接（可选）
    - REDIS连接（可选）

### 定义路由

#### 自动加载配置文件

由于文件不支持通过指定文件夹进行批量加载，自己定义一个入口实现批量加载文件，并在autoload中自动加载该入口文件

```php
$files = glob(__DIR__ . '/r_*.php');
if ($files === false) {
    throw new RuntimeException("Route files not found");
}
foreach ($files as $file) {
    require_once $file;
}
unset($file);
unset($files);
```

#### 路由配置

- 初始化Route实例，接收的参数为：
    - controller 实体控制类名称（包括命名空间）
    - model Model类名称（包括命名空间）（可选）
    - group 路由分组（可选）

- 批量添加配置，接收的参数为：
    - type 请求方式,只支持四种常用的请求方式 （GET,POST,PUT,DELETE）
    - uri 请求地址 ，真正的请求地址为 http://service_host/group/uri
    - method 业务方法（指定的Controller中定义的方法）
    - option 自定义参数

```
//初始化Route实例
$route = new Route('\Controller\EntityController', '\Model\Entity' , 'entity');

//批量添加配置
$route->batchRegist([
    ['POST', 'create', 'create'],
    ['GET', 'info', 'getInfo', ['skip_auth' => true]],    //跳过身份验证
    ['POST', 'list', 'getList'],
    ['POST', 'update', 'update'],
    ['POST', 'manageStatus', 'manageStatus'],
    ['POST', 'delete', 'delete']
]);
```

- 同时支持Slim原生的路由配置方式

```php
//Slim原生方法定义路由
$slim->group("/demo/", function(){
    $model = new \Model\Demo();

    $this->get("custom_route", function($request, $response) use ($model){
        //controller只能定义在路由方法中，否则构造函数中的getTicket方法会失败，因为ticket的处理放在Slim Middleware中
        $controller = new \Controller\DemoController(['skip_auth' => true]);

        $param = $_GET;
        $valid_param = $model->load($param);
        $response->withJson($valid_param, 200, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    });
});
```


### 业务逻辑处理
- 定义AppController类并继承BaseController

    此处多一级继承的好处是，一些业务相关的通用方法可以定义在AppController中，比如authenticate的具体实现，BaseController中只简单检查ticket是否存在，复杂的判断需要在AppController中实现，同样，业务层可以选择是在AppController的构造函数中统一进行身份验证，还是在具体的业务方法中选择性的进行验证

- 定义实体操作类，并继承AppController
    
    基本的业务操作包括
    - create
    - getInfo / getList

        getInfo和getList通常需要对数据进行同样的处理，如字段选择，状态转换等，此时一般会定义一个通用的 parse{Entity} 方法

    - update
    - manageStatus
    - delete
    - 其它特定需求

- 自定义业务错误码，所有业务处理的错误都应通在/app/controller/custom_error.php中统一定义，并通过BaseController的error方法返回


### 数据库访问
- 定义AppDAO类并继承BaseDAO 
    
    可在AppDAO层定义一些业务级别的通用数据库操作方法 

- 定义实体数据库访问类，继承AppDAO

    基本的业务操作包括
    - 状态定义
    - insert
    - update
    - load
    - delete
    - paging
    - updateStatus
    - 其它特定需求


### 模型定义，参数验证的规则 
- 定义AppModel并继承BaseModel

    可在AppModel层定义一些业务级别的通用参数有效性验证方法 ，也可将一些通用的字段的验证（如ID，时间，状态等）在该类的构造函数中统一初始化

- 定义实体模型类
    - 在构造函数中初始化指定参数的验证规则(支持批量添加)

    ```php
    //初始化验证规则
    $this->batchAddRule([
        'entity_id' => function($key, $value){
            return $this->checkInt($key, $value);
        }
    ]);
    ```

    - 未定义的参数将不做任何验证

#### TODO
- 支持通过正则表达式匹配参数名称

### 第三方服务调用
- 定义AppService并继承BaseService

    可在AppService层定义一些业务级别的通用数据解析

- 定义实体服务类并继承AppService

#### 注意：

所有第三方服务返回的结果都不能直接使用，必须按照预期的结果及格式进行结果判断和数据转换。

## Issues
- 通过 Docker-compose 启动的nginx服务无法正确获取客户端IP

    https://github.com/docker/docker/issues/25526

    解决方法为在宿主机或通过docker run 创建一个nginx服务用作反向代理服务器，通过如下设置将真实的客户端IP转发到服务所在的nginx服务
    ```
    server {
        listen 80 default_server;
        location / {
            proxy_set_header        Host $host;
            proxy_set_header        X-Real-IP $remote_addr;
            proxy_set_header        X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header        X-Forwarded-Proto $scheme;
            proxy_pass          http://localhost:8181;
            proxy_read_timeout  90;
        }
    }
    ```


## 规范
- 001: 应用层方法的注释最低要求是添加description和author，框架层必须额外添加param和return

```
/**
 * description
 * @param
 * @return
 * @author xxx
 */
```
- 002: 使用通用错误码，且不要重复定义
- 003: 所有逗号后面必须加空格，所有的运算符前后都必须加空格
- 004: 关于大小写
    - SQL语句关键字必须大写
    - PHP关键字，及常量(true,false,null等)必须使用小写。
    - 类名的字义必须使用StudlyCaps格式，
    - 常量的定义必须使用全大写加下划线的格式： UPPER_CASE_WITH_UNDERSCORE。
    - 方法名必须定义为camelCases格式。
    - 变量名必须定义为全小写加下划线的格式： $lowercase_with_underscore。
- 005: 字符串的定义使用单引号（双引号内部变量会解析,单引号则不解析），除非的确需要变量解析或转义
- 006: 所有类的方法和属性必须显示定义其可见性（private/protected/public）
- 007: 方法和控制语句的左大括号必须在当前行，右大括号必须另起一行。条件语句内即使只有一行代码，也必须要加{大括号}。
- 008: 不许使用SELECT * 或 COUNT(*)


上述规范一旦违反必须接受处罚
- 初犯每个错误罚款1元
- 再犯每个错误罚款5元
- 三次以上每个错误罚款10元
罚款由指定人员负责记录和保存

## 最佳实践
 - 任何重复两次及以上的代码或逻辑考虑进行封装
 - 如果数组中的key被使用超过一次，将其赋值给一个变量。
 - 方法的参数超过3个，才使用数组进行传递
 - 使用4个空格缩进。
 - 类定义的左右大括号都另起一行
 - PHP文件的末尾不要使用 ?> 标记（否则容易出现完全无法预料的错误）


## PHP技巧
 - PHP在实现了子类构造函数后不会自动调用父类构造函数，一定要通过parent::__construct() 显示调用


## 开发框架使用流程

- 创建项目目录
- 从gitlab下载最新稳定版本代码
- 上传到服务器并修改Nginx配置
- 修改项目名称，版本号，负责人邮箱及自定义错误前缀（自定义错误前缀需要提前申请，由指定负责人统一管理）
- 打开项目根目录, ** http://project-path/ ** 检查配置是否正确.
- 测试Hello World, ** http://project-path/hello/developer **, 
- 测试Throw Exception, ** http://project-path/throw/1 **
- 关闭DEBUG模式（配置文件中的DEBUG修改为false）, 再次调用Throw Exception, 检查是否能够收到通知邮件，测试成功后再重新开启DEBUG模式
- 在app目录下开始业务代码的开发


