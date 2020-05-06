# Scheduler

##### 前言
laravel框架的Task Scheduling 功能很好用，使用了后，再也不需要去服务器配置
多个crontab定时任务了。但这个功能和laravel框架有一些耦合，不能独立作为一个组件使用，
所以把这个项目就是把laralvel中task Scheduling功能剥离出来了，不再依赖laravel框架就
可以使用。


##### 支持特性
1. 指定任务执行频率(与laravel中使用完全一致)
```
use Scheduler\Schedule;

$schedule=new Schedule();

$schedule->exec('php example1.php')->cron('* * * * * ');
$schedule->exec('php example2.php')->everyMinute();
$schedule->exec('php example3.php')->dailyAt('13:00');

$schedule->run();
```
2. 防止任务重复执行
```
use Scheduler\Schedule;


//需要使用缓存锁，现支持redis
$redis=new Predis\Client('tcp://10.0.0.1:6379')
$mutex=new CacheEventMutex(new RedisStore($redis))

$schedule=new Schedule($mutex);
$schedule->exec('php example1.php')->everyMinute()->withoutOverlapping();
$schedule->run();
```

3. 任务后台并行执行
```
use Scheduler\Schedule;
$schedule=new Schedule();
//runInBackground,这样任多个定时任务就可以并行运行了。如果不指定，多个任务之间就是串行的！！！

$schedule->exec('php example1.php')->cron('* * * * *')->runInBackground();
$schedule->exec('php example2.php')->cron('* * * * *')->runInBackground();
```


4. 任务输出重定向到文件

```
use Scheduler\Schedule;

$schedule=new Schedule();

$schedule->exec('php example1.php')->daily()->sendOutputTo($filePath);
$schedule->exec('php example1.php')->daily()->appendOutputTo($filePath);
$schedule->run();
```

5. 任务执行钩子

```
use Scheduler\Schedule;

$schedule=new Schedule();

$schedule->exec('php example1.php')
         ->daily()
         ->before(function () {
             // 任务执行前，需要执行的逻辑
         })
         ->after(function () {
             // 任务执行完成，需要执行的逻辑
         });
$schedule->run();
```
