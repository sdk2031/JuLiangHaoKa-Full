<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'backup:auto' => 'app\command\AutoBackup',
        'generate:order:snapshots' => 'app\command\GenerateOrderSnapshots',
        'fix:longbao:orders' => 'app\command\FixLongbaoOrders',
        'fix:balance:logs' => 'app\command\FixBalanceLogs',
    ],
];
