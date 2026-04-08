<?php

namespace App\Console\Commands;

use App\Models\ProjectTask;
use Illuminate\Console\Command;

class FixFinalMilestoneColor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:final-milestone-color';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修复任务的 Final 里程碑颜色，将 NULL 值设置为默认的 #52c41a';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('开始修复 Final 里程碑颜色...');

        $count = ProjectTask::whereNull('final_milestone_color')
            ->update([
                'final_milestone_color' => '#52c41a',
                'final_milestone_name' => 'Final'
            ]);

        $this->info("成功修复 {$count} 个任务的 Final 里程碑颜色");

        return 0;
    }
}
