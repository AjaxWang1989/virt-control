<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2018/3/21
 * Time: 上午9:11
 */

namespace ShWang\VirtControl\Lib;


class VirtDomain
{
    /**
     * @var Resource
     * */
    private $_domainResource = null;

    /**
     * @var VirtConnection
     * */
    private $_vConnection = null;

    /**
     * @var boolean
     * */
    private $_created = false;

    /**
     * @var boolean
     * */
    private $_started = false;

    /**
     * @var boolean
     * */
    private $_running = false;
    /**
     * @var boolean
     * */
    private $_saved = false;

    /**
     * @var boolean
     * */
    private $_lookup = false;

    /**
     * @var string
     * */
    private $_name = null;
    private $_uuidStr = null;
    private $_uuidBinary = null;
    private $_id = null;
    private $_arch = null;
    private $_memMB = null;
    private $_maxMemMB = null;
    private $_vcpus = null;
    private $_iosImage = null;
    private $_networks = null;
    private $_disks = null;
    private $_flags = null;

    public function __construct () {
    }

    public function start () {
        if (!$this->_created) {
            $xml = '';
            $this->createFromXml($xml);
        }else if (!$this->_started) {
            $this->create();
        }
        $this->_running = true;
    }

    public function suspend () {
        if ($this->_started && $this->_running) {
            libvirt_domain_suspend($this->_domainResource);
            $this->_running = false;
        }
    }

    public function stop () {
        if ($this->_started && $this->_running) {
            libvirt_domain_shutdown($this->_domainResource);
            $this->_running = false;
            $this->_started = false;
        }
    }

    public function resume () {
        if ($this->_started && !$this->_running) {
            libvirt_domain_resume($this->_domainResource);
            $this->_running = true;
        }
    }

    public function destroy () {
        if ($this->_domainResource) {
            libvirt_domain_destroy($this->_domainResource);
        }
    }

    /**
     * Function is used to reboot the domain identified by it's resource.
     * @param $xml
     * */
    public function defineFromXml (string $xml) {
        $this->destroy();
        $this->_domainResource = libvirt_domain_define_xml($this->_vConnection->getConnection(), $xml);
        $this->_created = true;
    }

    /**
     *Function is used to define the domain from XML string.
     * @param string $xml
     * */
    public function createFromXml (string $xml) {
        $this->destroy();
        $this->_domainResource = libvirt_domain_create_xml($this->_vConnection->getConnection(), $xml);
        if ($this->_domainResource) {
            $this->_created = true;
            $this->_started = true;
        }
    }

    /**
     *Function is used to create the domain identified by it's resource.
     * */
    public function create () {
        if (!$this->_created && $this->_domainResource) {
            $this->_domainResource = libvirt_domain_create($this->_domainResource);
            $this->_created = true;
            $this->_started = true;
        }
    }

    public function managedSave () {
        if (!$this->_saved){
            libvirt_domain_managedsave($this->_domainResource);
            $this->_saved = true;
        }
    }

    public function undefined () {
        if ($this->_lookup) {
            libvirt_domain_undefine($this->_domainResource);
            $this->_lookup = false;
        }
    }

    public function reboot (int $flags) {
        if ($this->_lookup) {
            libvirt_domain_reboot($this->_domainResource, $flags);
        }
    }

    public function getName () {
        if (!$this->_name) {
            $this->_name = libvirt_domain_get_name($this->_domainResource);
        }
        return $this->_name;
    }

    public function getUuidString () {
        if (!$this->_uuidStr) {
            $this->_uuidStr = libvirt_domain_get_uuid_string($this->_domainResource);
        }

        return $this->_uuidStr;
    }

    public function getScreenshotApi (string $screenID) {
        return libvirt_domain_get_screenshot_api($this->_domainResource, $screenID);
    }

    public function getScreenshot (string $server, int $scanCode) {
        return libvirt_domain_get_screenshot($this->_domainResource, $server, $scanCode);
    }

    public function getScreenDimensions (string $server) {
        return libvirt_domain_get_screen_dimensions($this->_domainResource, $server);
    }

    public function sendKeys (string $host, int $scanCode) {
        return libvirt_domain_send_keys($this->_domainResource, $host, $scanCode);
    }

    public function sendPointerEvent (string $server, int $x, int $y, int $clicked, int $release ) {
        return libvirt_domain_send_pointer_event($this->_domainResource, $server, $x, $y, $clicked, $release);
    }

    public function getUuidBinary () {
        if (!$this->_uuidBinary) {
            $this->_uuidBinary = libvirt_domain_get_uuid($this->_domainResource);
        }
        return $this->_uuidBinary;
    }

    public function getId () {
        if (!$this->_id){
            $this->_id = libvirt_domain_get_id($this->_domainResource);
        }
        return $this->_id;
    }

    public function getDevIds () {
        return libvirt_domain_get_next_dev_ids($this->_domainResource);
    }

    public function domainNew (string $name, string $arch, int $memMB, int $maxMemMB, int $vcpus, string $isoImage, array $disks, array $networks, int $flags) {
        $this->destroy();
        $this->_name = $name;
        $this->_arch = $arch;
        $this->_memMB = $memMB;
        $this->_maxMemMB = $maxMemMB;
        $this->_vcpus = $vcpus;
        $this->_iosImage = $isoImage;
        $this->_networks = $networks;
        $this->_disks = $disks;
        $this->_flags = $flags;
        libvirt_domain_new($this->_vConnection->getConnection(), $name, $arch, $memMB, $maxMemMB,
            $vcpus, $isoImage, $disks, $networks, $flags);
        $this->_domainResource = $this->getDomainByName($name);
    }

    private function getDomainByName (string $name) {
        return libvirt_domain_lookup_by_name($this->_vConnection->getConnection(), $name);
    }

    public function getMemoryPeek (int $start, int $size, int $flags) {
        return libvirt_domain_memory_peek($this->_domainResource, $start, $size, $flags);
    }

    public function getMemoryStats (int $flags) {
        return libvirt_domain_memory_stats($this->_domainResource, $flags);
    }

    public function updateDevice (string  $xml) {
        libvirt_domain_update_device($this->_domainResource, $xml);
    }

    public function  blockCommit(string $disk, string $base, string $top, int $bandwidth, int $flags) {
        libvirt_domain_block_commit($this->_domainResource, $disk, $base, $top, $bandwidth, $flags);
    }

    public function blockJobAbort (string $path, int $flags) {
        libvirt_domain_block_job_abort($this->_domainResource,  $path, $flags);
    }

    public function  blockJobSetSpeed (string $path, int $bandwidth, int $flags) {
        libvirt_domain_block_job_set_speed($this->_domainResource, $path, $bandwidth, $flags);
    }

    public function getNetworkInfo (string $mac) {
        return libvirt_domain_get_network_info($this->_domainResource, $mac);
    }

    public function getBlockInfo (string $dev) {
        return libvirt_domain_get_block_info($this->_domainResource, $dev);
    }

    public function getXmlByXpath (string $xpath, int $flags) {
        return libvirt_domain_xml_xpath($this->_domainResource, $xpath, $flags);
    }

    public function getInterfaceState (string  $path) {
        return libvirt_domain_interface_stats($this->_domainResource, $path);
    }

    public function getConnection () {
        if (!$this->_vConnection) {
            $connection = libvirt_domain_get_connect($this->_domainResource);
            $this->_vConnection = new VirtConnection($connection);
        }
        return $this->_vConnection;
    }
}