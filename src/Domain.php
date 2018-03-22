<?php
namespace  ShWang\VirtControl;
class Domain
{
    /**
     * @var Kvm
     * */
    protected $host = null;

    /**
     * @var string
     * */
    protected $name = '';

    /**
     * @var string
     * */
    protected $uuid = '';

    protected $resource = null;

    protected $mac = '';

    protected $mem = MEM_DEFAULT;

    protected $maxMem = MEM_DEFAULT;

    protected $network = null;

    protected $cpu = 1;

    protected $arch = 'x86_64';

    protected $clock = null;

    protected $state = null;

    protected $netWorkCards = null;

    protected $disk = [];

    protected $lastError = null;

    protected $features = ['apic', 'acpi', 'pae'];

    protected $vncPort = '';

    protected $xml = '';

    protected $description = '';

    protected $persistent = true;

    protected $uri = '';

    protected $emulator = null;

    public function __construct($kvm, $disk = [], $name = '', $persistent = true, $clock = 'utc', $arch = 'x86_64', $cpu = 1, $mem = MEM_DEFAULT,
                                $maxMem = MEM_DEFAULT, $features = ['apic', 'acpi', 'pae']) {
        $this->host = $kvm;

        $this->disk = $disk;

        $this->name = $name;

        $this->clock = $arch;

        $this->cpu = $cpu;

        $this->mem = $mem;

        $this->maxMem = $maxMem;

        $this->features = $features;

        $this->persistent = $persistent;

        $this->resource = $this->host->getDomainByName ($name);

        if (!$this->resource) {
            $this->create ();
        }else{
            $this->reset ();
        }
    }

    public function reset () {
        $this->name = $this->getResourceName();
        $this->emulator = $this->getResourceEmulator();
        $this->uuid = $this->getResourceUuid();
        $this->clock = $this->getResourceClock();
        $this->cpu = $this->getResourceVcpu();
        $this->mem = $this->getResourceMemory();
        $this->maxMem = $this->getResourceMaxMemory();
        $this->features = $this->getResourceFeatures();
        $this->disk = $this->getDiskStats();
    }

    public function getUuid () {
        return $this->uuid;
    }

    public function getName () {
        return $this->name;
    }

    protected function getResourceName () {
        return libvirt_domain_get_name($this->resource);
    }

    protected function getResourceUuid () {
        $tmp = $this->getXpath('//domain/uuid', false);
        $var = $tmp[0];
        unset($tmp);
        return $var;
    }

    public function getResourceVncPort() {
        $tmp = $this->getXpath('//domain/devices/graphics/@port', false);
        $var = (int)$tmp[0];
        unset($tmp);
        return $var ;
    }

    public function getResourceVcpu() {
        $tmp = $this->getXpath('//domain/vcpu', false);
        $var = (int)$tmp[0];
        unset($tmp);
        return $var ;
    }

    protected function getResourceArch() {
        $tmp = $this->getXpath('//domain/os/type/@arch', false);
        $var = $tmp[0];
        unset($tmp);
        return $var;
    }

    protected function getResourceMemory() {
        $tmp = $this->getXpath('//domain/currentMemory', false);
        $var = $tmp[0];
        unset($tmp);
        return $var;
    }

    protected function getResourceMaxMemory() {
        $tmp = $this->getXpath('//domain/memory', false);
        $var = $tmp[0];
        unset($tmp);
        return $var;
    }

    protected function getResourceDescription() {
        $tmp = $this->getXpath('//domain/description', false);
        $var = $tmp[0];
        unset($tmp);
        return $var;
    }

    protected function getResourceClock() {
        $tmp = $this->getXpath( '//domain/clock', false);
        $var= $tmp[0];
        unset($tmp);
        return $var;
    }

    protected function getResourceFeatures() {
        return $this->getXpath( '//domain/features', false);
    }

    protected function getResourceBootDevices() {
        $tmp = $this->getXpath( '//domain/os/boot/@dev', false);
        if (!$tmp)
            return false;
        $devices = array();
        for ($i = 0; $i < $tmp['num']; $i++)
            $devices[] = $tmp[$i];
        return $devices;
    }

    /**
     * @param string $network
     * @param boolean $model
     * */
    public function addNIC($network, $model = false) {
        if ($model === 'default') {
            $model = false;
        }
        $this->network = $network;
        $this->resource = libvirt_domain_nic_add($this->resource, $this->mac, $this->network, $model);
    }

    public function removeNIC() {
        libvirt_domain_nic_remove($this->resource, $this->mac);
    }

    public function createImage($image, $size, $driver) {
        $tmp = libvirt_image_create($this->host->connection(), $image, $size, $driver);
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function removeImage($image, $ignore_error_codes=false ) {
        $tmp = libvirt_image_remove($this->host->connection(), $image);
        if ((!$tmp) && ($ignore_error_codes)) {
            $err = libvirt_get_last_error();
            $comps = explode(':', $err);
            $err = explode('(', $comps[sizeof($comps)-1]);
            $code = (int)Trim($err[0]);
            if (in_array($code, (array)$ignore_error_codes))
                return true;
        }
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function migrateToUri($uri, $live = false, $bandwidth = 100) {
        $tmp = libvirt_domain_migrate_to_uri($this->resource, $uri, $live ? VIR_MIGRATE_LIVE : 0, $this->name, $bandwidth);
        $this->uri = $uri;
        return ($tmp) ? $tmp : $this->setLastError();
    }

    /**
     * @param Kvm $host
     * @param boolean $live
     * @param int $bandwidth
     * @return mixed
     * */
    public function migrate($host, $live = false, $bandwidth = 100) {
        $tmp = libvirt_domain_migrate($this->resource, $host->connection(), $live ? VIR_MIGRATE_LIVE : 0, $this->name, $bandwidth);
        $this->host = $host;
        return ($tmp) ? $tmp : $this->setLastError();
    }


    protected function getInfo() {
        return libvirt_domain_get_info($this->resource);
    }

    protected function getXpath($xpath, $inactive) {
        $flags = 0;
        if ($inactive) {
            $flags = VIR_DOMAIN_XML_INACTIVE;
        }
        $tmp = libvirt_domain_xml_xpath($this->resource, $xpath, $flags);
        if (!$tmp) {
            return $this->setLastError();
        }
        return $tmp;
    }

    protected function setLastError() {
        $this->lastError = libvirt_get_last_error();
        return false;
    }


    protected function macByte($val) {
        if ($val < 16) {
            return '0' . dechex($val);
        }
        return dechex($val);
    }

    protected function generateMacAddress() {
        $prefix = "52:54:00";

        return $prefix . ':' .
            $this->macByte(rand() % 256) . ':' .
            $this->macByte(rand() % 256) . ':' .
            $this->macByte(rand() % 256);
    }

    protected function generateUuid() {

        return $this->host->generateDomainUuid();
    }

    public function create() {
        $diskXML = '';
        foreach ($this->disk as $item){
            if ($item['size']) {
                $item['file'] = str_replace(' ', '_', $item['file']);
                if (!$this->host->createImage($item['file'], $item['size'], $item['driver']))
                    return false;
            }
            $path = $item['file'];

            $diskXML .= diskXML($path, $item['driver'], $item['bus']);
        }

        $netXML = netXML($this->generateMacAddress());
        $this->uuid = $this->generateUuid();
        $this->emulator = $this->host->getDefaultEmulator();
        $tmp = libvirt_domain_create_xml($this->host->connection(), virtDomainXML(
            $this->name, $this->mem, $this->maxMem, $this->uuid, $this->arch, $this->fs(), $this->clock, $this->cpu,
            $this->emulator, $diskXML, $netXML
        ));
        $tmp ?  : $this->setLastError();
        if($this->persistent){
            $tmp = libvirt_domain_define_xml($this->host->connection(), virtDomainXML(
                $this->name, $this->mem, $this->maxMem, $this->uuid, $this->arch, $this->fs(), $this->clock, $this->cpu,
                $this->emulator, $diskXML, $netXML
            ));
            $tmp ?  : $this->setLastError();
        }
        $this->resource = $tmp ? $tmp : null;
        return $this;
    }

    protected function fs() {
        $fs = '';
        foreach ($this->features as $feature){
            $fs .= "<{$feature}/>";
        }
        return $fs;
    }

    public function start() {

    }

    public function stop() {

    }

    public function restart() {

    }


    public function addDisk( $img, $dev, $type='scsi', $driver='raw') {
        $tmp = libvirt_domain_disk_add($this->resource, $img, $dev, $type, $driver);
        return ($tmp) ? $tmp : $this->setLastError();
    }


    public function changeCpuNum($num) {
        $tmp = libvirt_domain_change_vcpus($this->resource, $num);
        return ($tmp) ? $tmp : $this->setLastError();
    }


    public function changeMemoryAllocation($memory, $maxMem) {
        $tmp = libvirt_domain_change_memory($this->resource, $memory, $maxMem);
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function changeBootDevices($first, $second) {
        $tmp = libvirt_domain_change_boot_devices($this->resource, $first, $second);
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function getHostName(){
        return '';
    }

    public function getScreensHot() {
        $tmp = libvirt_domain_get_screenshot($this->resource, $this->getHostName(), 8 );
        if (Graphics::isBMPStream($tmp)) {
            $gc = new Graphics();
            $fn = tempnam("/tmp", "php-virt-control.tmp");
            $fn2 = tempnam("/tmp", "php-virt-control.tmp");
            $fp = fopen($fn, "wb");
            fputs($fp, $tmp);
            fclose($fp);
            unset($tmp);
            if ($gc->ConvertBMPToPNG($fn, $fn2) == false) {
                unlink($fn);
                return false;
            }
            $fp = fopen($fn2, "rb");
            $tmp = fread($fp, filesize($fn2));
            fclose($fp);
            unlink($fn2);
            unlink($fn);
            unset($gc);
        }
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function getScreensHotThumbnail( $w=120 ) {
        $screen = $this->getScreensHot();
        $imgFile = tempnam("/tmp", "libvirt-php-tmp-resize-XXXXXX");;
        if ($screen) {
            $fp = fopen($imgFile, "wb");
            fwrite($fp, $screen);
            fclose($fp);
        }
        if (file_exists($imgFile) && $screen) {
            list($width, $height) = getimagesize($imgFile);
            $h = ($height / $width) * $w;
        } else {
            $w = $h = 1;
        }
        $new = imagecreatetruecolor($w, $h);
        if ($screen) {
            $img = imagecreatefrompng($imgFile);
            imagecopyresampled($new,$img,0,0,0,0, $w,$h,$width,$height);
            imagedestroy($img);
        }
        else {
            $c = imagecolorallocate($new, 255, 255, 255);
            imagefill($new, 0, 0, $c);
        }
        imagepng($new, $imgFile);
        imagedestroy($new);
        $fp = fopen($imgFile, "rb");
        $data = fread($fp, filesize($imgFile));
        fclose($fp);
        unlink($imgFile);
        return $data;
    }

    public function getScreenDimensions() {
        $tmp = libvirt_domain_get_screen_dimensions($this->resource, $this->getHostName() );
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function sendKeys($keys) {
        $tmp = libvirt_domain_send_keys($this->resource, $this->getHostName(), $keys);
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function sendPointerEvent( $x, $y, $clicked = 1, $release = false ) {
        $tmp = libvirt_domain_send_pointer_event($this->resource, $this->getHostName(), $x, $y, $clicked, $release);
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function removeDisk( $dev) {
        $tmp = libvirt_domain_disk_remove($this->resource, $dev);
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function getCdromStats($sort=true) {
        $buses =  $this->getXpath('//domain/devices/disk[@device="cdrom"]/target/@bus', false);
        $disks =  $this->getXpath('//domain/devices/disk[@device="cdrom"]/target/@dev', false);
        $files =  $this->getXpath('//domain/devices/disk[@device="cdrom"]/source/@file', false);
        $ret = array();
        for ($i = 0; $i < $disks['num']; $i++) {
            $tmp = libvirt_domain_get_block_info($this->resource, $disks[$i]);
            if ($tmp) {
                $tmp['bus'] = $buses[$i];
                $ret[] = $tmp;
            }
            else {
                $this->setLastError();
                $ret[] = array(
                    'device' => $disks[$i],
                    'file'   => $files[$i],
                    'type'   => '-',
                    'capacity' => '-',
                    'allocation' => '-',
                    'physical' => '-',
                    'bus' => $buses[$i]
                );
            }
        }
        if ($sort) {
            for ($i = 0; $i < sizeof($ret); $i++) {
                for ($ii = 0; $ii < sizeof($ret); $ii++) {
                    if (strcmp($ret[$i]['device'], $ret[$ii]['device']) < 0) {
                        $tmp = $ret[$i];
                        $ret[$i] = $ret[$ii];
                        $ret[$ii] = $tmp;
                    }
                }
            }
        }
        unset($buses);
        unset($disks);
        unset($files);
        return $ret;
    }

    public function getDiskStats( $sort=true ) {
        $buses =  $this->getXpath( '//domain/devices/disk[@device="disk"]/target/@bus', false);
        $disks =  $this->getXpath( '//domain/devices/disk[@device="disk"]/target/@dev', false);
        $files =  $this->getXpath( '//domain/devices/disk[@device="disk"]/source/@file', false);
        $ret = array();
        for ($i = 0; $i < $disks['num']; $i++) {
            $tmp = libvirt_domain_get_block_info($this->resource, $disks[$i]);
            if ($tmp) {
                $tmp['bus'] = $buses[$i];
                $ret[] = $tmp;
            }
            else {
                $this->setLastError();
                $ret[] = array(
                    'device' => $disks[$i],
                    'file'   => $files[$i],
                    'type'   => '-',
                    'capacity' => '-',
                    'allocation' => '-',
                    'physical' => '-',
                    'bus' => $buses[$i]
                );
            }
        }
        if ($sort) {
            for ($i = 0; $i < sizeof($ret); $i++) {
                for ($ii = 0; $ii < sizeof($ret); $ii++) {
                    if (strcmp($ret[$i]['device'], $ret[$ii]['device']) < 0) {
                        $tmp = $ret[$i];
                        $ret[$i] = $ret[$ii];
                        $ret[$ii] = $tmp;
                    }
                }
            }
        }
        unset($buses);
        unset($disks);
        unset($files);
        return $ret;
    }

    public function getNicInfo() {
        $macs =  $this->getXpath('//domain/devices/interface/mac/@address', false);
        if (!$macs)
            return $this->setLastError();
        $ret = array();
        for ($i = 0; $i < $macs['num']; $i++) {
            $tmp = libvirt_domain_get_network_info($this->resource, $macs[$i]);
            if ($tmp)
                $ret[] = $tmp;
            else {
                $this->setLastError();
                $ret[] = array(
                    'mac' => $macs[$i],
                    'network' => '-',
                    'nic_type' => '-'
                );
            }
        }
        return $ret;
    }

    public function getType() {
        $tmp = $this->getXpath( '//domain/@type', false);
        if ($tmp['num'] == 0)
            return $this->setLastError();
        $ret = $tmp[0];
        unset($tmp);
        return $ret;
    }

    public function getResourceEmulator() {
        $tmp =  $this->getXpath('//domain/devices/emulator', false);
        if ($tmp['num'] == 0)
            return $this->setLastError();
        $ret = $tmp[0];
        unset($tmp);
        return $ret;
    }

    public function getNetworkCards() {
        $nics =  $this->getXpath('//domain/devices/interface[@type="network"]', false);
        if (!is_array($nics))
            return $this->setLastError();
        return $nics['num'];
    }

    public function getDiskCapacity( $physical=false, $disk='*', $unit='?') {
        $tmp = $this->getDiskStats();
        $ret = 0;
        for ($i = 0; $i < sizeof($tmp); $i++) {
            if (($disk == '*') || ($tmp[$i]['device'] == $disk))
                if ($physical)
                    $ret += $tmp[$i]['physical'];
                else
                    $ret += $tmp[$i]['capacity'];
        }
        unset($tmp);
        return $this->formatSize($ret, 2, $unit);
    }

    public function getDiskCount() {
        $tmp = $this->getDiskStats();
        $ret = sizeof($tmp);
        unset($tmp);
        return $ret;
    }

    public function formatSize($value, $decimals, $unit='?') {
        if ($value == '-')
            return 'unknown';
        /* Autodetect unit that's appropriate */
        if ($unit == '?') {
            /* (1 << 40) is not working correctly on i386 systems */
            if ($value > 1099511627776) {
                $unit = 'T';
            }else{
                if ($value > (1 << 30)) {
                    $unit = 'G';
                } else {
                    if ($value > (1 << 20)) {
                        $unit = 'M';
                    } else {
                        if ($value > (1 << 10))
                            $unit = 'K';
                        else
                            $unit = 'B';
                    }
                }

            }

        }
        $unit = strtoupper($unit);
        switch ($unit) {
            case 'T':
                return number_format($value / (float)1099511627776, $decimals, '.', ' ').' TB';
            case 'G':
                return number_format($value / (float)(1 << 30), $decimals, '.', ' ').' GB';
            case 'M':
                return number_format($value / (float)(1 << 20), $decimals, '.', ' ').' MB';
            case 'K':
                return number_format($value / (float)(1 << 10), $decimals, '.', ' ').' kB';
            case 'B':
                return $value.' B';
        }
        return false;
    }
}