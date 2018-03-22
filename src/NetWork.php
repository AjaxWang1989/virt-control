<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2018/3/20
 * Time: 下午1:37
 */

namespace ShWang\VirtControl;


class NetWork
{
    /**
     * @var Kvm
     * */
    protected $host = null;

    /**
     * @var boolean
     * */
    protected $active = true;

    protected $mac = '';

    protected $networkResource = null;
    public function __construct ($host) {
        $this->host = $host;
    }
}