<?php
namespace app\command;

use app\common\service\AppPackRemoteService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AppPackWorker extends Command
{
    protected function configure()
    {
        $this->setName('apppack:worker')
            ->setDescription('Process queued APP pack tasks');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $count = (new AppPackRemoteService())->processQueuedTasks(1);
            $output->writeln('Processed APP pack tasks: ' . $count);
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('APP pack worker failed: ' . $e->getMessage());
            return 1;
        }
    }
}
