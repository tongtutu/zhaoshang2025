<?php
/**
 * USER常量定义
 *
 * @author        shuguang <5565907@qq.com>
 * @copyright     (c) 2007-2099 cookman.cn. All rights reserved.
 */

namespace bagesoft\constant;

class UserConst
{
    const WORKER_GID = 3; //产业研究员
    const MANAGER_GID = 5; //项目经理
    const BT_MANAGER_GID = 6; //招投标经理
    const VICE_MANAGER_GID = 7; //招商总监

    const STATUS = [
        self::STATUS_ACTIVE => '正常',
        self::STATUS_LOCK => '锁定',
        self::STATUS_DISABLE => '禁用',
    ];
    const STATUS_ACTIVE = 1;
    const STATUS_LOCK = 2;
    const STATUS_DISABLE = 3;
}
