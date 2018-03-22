<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2018/3/21
 * Time: 上午9:13
 */

namespace ShWang\VirtControl\Lib;


class VirtLastError extends \Exception
{
    public function __construct()
    {
        parent::__construct(libvirt_get_last_error());
    }
}