<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 在 Octane 环境下绑定 swoole 实例
        $this->app->singleton('swoole', function ($app) {
            // 尝试从全局变量获取 Swoole Server 实例
            if (isset($GLOBALS['swoole_server'])) {
                return $GLOBALS['swoole_server'];
            }

            // 如果在 Octane 环境中，创建一个代理对象来访问 Octane Tables
            if ($app->bound('octane.table')) {
                return new class($app) {
                    private $app;
                    public $worker_id = 0;
                    public $taskworker = false;
                    public $setting = ['worker_num' => 1];

                    public function __construct($app) {
                        $this->app = $app;
                    }

                    public function __get($name) {
                        // 访问 Octane Table
                        try {
                            return $this->app->make('octane.table')->get($name);
                        } catch (\Exception $e) {
                            // 如果表不存在，返回一个空的 Swoole Table
                            $table = new \Swoole\Table(1024);
                            $table->column('value', \Swoole\Table::TYPE_STRING, 10000);
                            $table->create();
                            return $table;
                        }
                    }

                    public function task($data) {
                        // 在 Octane 环境下，直接同步执行任务
                        return true;
                    }

                    public function sendMessage($data, $workerId) {
                        return true;
                    }
                };
            }

            // 如果都不在，返回一个模拟对象
            return new class {
                private $tables = [];
                public $worker_id = 0;
                public $taskworker = false;
                public $setting = ['worker_num' => 1];

                public function __get($name) {
                    if (!isset($this->tables[$name])) {
                        $table = new \Swoole\Table(10240);
                        $table->column('value', \Swoole\Table::TYPE_STRING, 10000);
                        $table->create();
                        $this->tables[$name] = $table;
                    }
                    return $this->tables[$name];
                }

                public function task($data) {
                    return true;
                }

                public function sendMessage($data, $workerId) {
                    return true;
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Database\Query\Builder::macro('rawSql', function(){
            return array_reduce($this->getBindings(), function($sql, $binding){
                return preg_replace('/\?/', is_numeric($binding) ? $binding : "'".$binding."'" , $sql, 1);
            }, $this->toSql());
        });

        \Illuminate\Database\Eloquent\Builder::macro('rawSql', function(){
            return ($this->getQuery()->rawSql());
        });
    }
}
