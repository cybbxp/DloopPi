<?php

namespace App\Module\Table;

use App\Models\User;
use App\Tasks\LineTask;
use App\Tasks\PushTask;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class OnlineData extends AbstractData
{
    /**
     * 上线
     * @param $userid
     * @return float|int|mixed
     */
    public static function online($userid)
    {
        $table = self::instance()->getTable();
        if (!$table) {
            return 0;
        }
        $key = "online::" . $userid;
        $value = $table->incr($key, 'value');
        if ($value === 1) {
            // 通知上线
            Task::deliver(new LineTask($userid, true));
            // 推送离线时收到的消息
            Task::deliver(new PushTask("RETRY::" . $userid));
        }
        return $value;
    }

    /**
     * 离线
     * @param $userid
     * @return float|int|mixed
     */
    public static function offline($userid)
    {
        $table = self::instance()->getTable();
        if (!$table) {
            return 0;
        }
        $key = "online::" . $userid;
        $value = $table->decr($key, 'value');
        if ($value === 0) {
            // 更新最后在线时间
            User::whereUserid($userid)->update([
                'line_at' => Carbon::now()
            ]);
            // 通知下线
            Task::deliver(new LineTask($userid, false));
            // 清除在线状态
            $table->del($key);
        }
        return $value;
    }

    /**
     * 获取在线状态
     * @param $userid
     * @return int
     */
    public static function live($userid)
    {
        $table = self::instance()->getTable();
        if (!$table) {
            return 0;
        }
        $key = "online::" . $userid;
        return intval($table->get($key));
    }
}
