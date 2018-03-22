<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2018/3/20
 * Time: 下午2:24
 */

namespace ShWang\VirtControl;


class VirtualMachine
{
    /**
     * @var mixed 链接
     * */
    protected $conn;
    /**
     * @var array
     * */
    protected $domains = [];

    /**
     * @var mixed 错误
     * */
    protected $lastError;

    protected $enabled = false;

    protected $allowCached = true;

    protected $hypervisor = '';

    public function __construct ($uri = false, $login = false, $pwd = false, $debug=false) {
        if ($debug)
            $this->setLogfile($debug);
        if ($uri != false) {
            $this->enabled = true;
            $this->connect($uri, $login, $pwd);
            $this->init();
        }
    }

    public function connect ($uri, $username = null, $password = null) {
        if ($username !== null && $password !== null) {
            $this->conn = libvirt_connect($uri, false, [
                VIR_CRED_AUTHNAME => $username,
                VIR_CRED_PASSPHRASE => $password
            ]);
        } else {
            $this->conn = libvirt_connect($uri, false);
        }
    }

    protected function init () {
        $this->domains = [];
        $this->domains['name'] = [];
        $this->domains['uuid'] = [];
        $domains = $this->getDomains();
        foreach ($domains as $domain) {
            $domain = new Domain($this, $domain);
            $this->domains['name'][$domain->getName()] = $domain;
            $this->domains['uuid'][$domain->getUuid()] = $domain;
        }
    }

    /**
     * @var Domain $domain
     * */
    public function addDomain ($domain) {
        $this->domains['name'][$domain->getName()] = $domain;
        $this->domains['name'][$domain->getUuid()] = $domain;
    }

    public function getDomainByName ($name) {
        return $this->domains['name'][$name];
    }

    public function getDomainByUuid ($uuid) {
        return $this->domains['uuid'][$uuid];
    }

    protected function getDomains() {
        $tmp = libvirt_list_domains($this->conn);
        return ($tmp) ? $tmp : $this->setLastError();
    }

    protected function setLastError()
    {
        $this->lastError = libvirt_get_last_error();
        return false;
    }

    protected function setLogfile($filename)
    {
        if (!libvirt_logfile_set($filename))
            return $this->setLastError();
        return true;
    }

    public function enabled() {
        return $this->enabled;
    }

    public function isConnected() {
        return $this->conn ? true : false;
    }

    public function getCapabilities() {
        $tmp = libvirt_connect_get_capabilities($this->conn);
        return ($tmp) ? $tmp : $this->setLastError();
    }
    public function getDefaultEmulator() {
        $tmp = libvirt_connect_get_capabilities($this->conn, '//capabilities/guest/arch/domain/emulator');
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function generateConnectionUri($hv, $remote, $remote_method, $remote_username, $remote_hostname, $session=false) {
        $append_type = '';
        if ($hv == 'qemu') {
            if ($session)
                $append_type = 'session';
            else
                $append_type = 'system';
        }
        if (!$remote) {
            if ($hv == 'xen')
                return 'xen:///';
            if ($hv == 'qemu')
                return 'qemu:///'.$append_type;
            return false;
        }
        if ($hv == 'xen')
            return 'xen+'.$remote_method.'://'.$remote_username.'@'.$remote_hostname;
        else
            if ($hv == 'qemu')
                return 'qemu+'.$remote_method.'://'.$remote_username.'@'.$remote_hostname.'/'.$append_type;
        return false;
    }

    public function printResources() {
        return libvirt_print_binding_resources();
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getHostName() {
        return libvirt_connect_get_hostname($this->conn);
    }
}