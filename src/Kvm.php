<?php
namespace ShWang\VirtControl;

class Kvm{

    protected $conn;

    /**
     * @var array
     * */
    protected $domains = [];

    protected $lastError;

    protected $config = null;

    protected static $instance;

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct($config = [])
    {
        $this->config = array_merge([
            'uri' => 'qemu:///system'
        ], $config);
        $this->connect($this->config['uri']);
    }

    public function connect($uri, $username = null, $password = null)
    {
        if ($username !== null && $password !== null) {
            $this->conn = libvirt_connect($uri, false, [
                VIR_CRED_AUTHNAME => $username,
                VIR_CRED_PASSPHRASE => $password
            ]);
        } else {
            $this->conn = libvirt_connect($uri, false);
        }
    }

    public function createNewVM($name, $image, $backing = false)
    {
        $disk = [];

        $disk['image'] = ($backing ? BACKING_IMAGE_PATH : IMAGE_PATH) . $image;
        $disk['size'] = 0;
        $disk['bus'] = 'ide';
        $disk['driver'] = 'qcow2';

        return $this->createDomainByXml($name, $disk);
    }

    public function isVMRunning($name)
    {
        $dom = $this->getDomainByName($name);
        if ($dom) {
            return libvirt_domain_is_active($dom);
        }
        return false;
    }

    public function startVM($name)
    {
        $dom = $this->getDomainByName($name);
        if ($dom) {
            libvirt_domain_create($dom);
        }
    }

    public function stopVM($name)
    {
        $dom = $this->getDomainByName($name);
        if ($dom) {
            libvirt_domain_destroy($dom);
        }
    }

    public function destroyVM($name)
    {
        $dom = $this->getDomainByName($name);
        if ($dom) {
            //关机
            if (libvirt_domain_is_active($dom)) {
                libvirt_domain_destroy($dom);
            }
            //删除
            @libvirt_domain_undefine($dom);
        }
    }

    public function getDomainVncPort($name)
    {
        $tmp = $this->getDomainXpath($name, '//domain/devices/graphics/@port', false);
        $var = (int)$tmp[0];
        unset($tmp);
        return $var;
    }

    protected function getDomainXpath($name, $xpath, $inactive = false)
    {
        $dom = $this->getDomainByName($name);
        $flags = 0;
        if ($inactive) {
            $flags = VIR_DOMAIN_XML_INACTIVE;
        }
        $tmp = libvirt_domain_xml_xpath($dom, $xpath, $flags);
        if (!$tmp) {
            return $this->setLastError();
        }
        return $tmp;
    }

    public function connection()
    {
        return $this->conn;
    }

    public function getDomainByName($name)
    {
        return @libvirt_domain_lookup_by_name($this->conn, $name);
    }

    public function generateDomainUuid()
    {
        $uuid = $this->generateUuid();

        while ($this->getDomainNameByUuid($uuid))
            $uuid = $this->generateUuid();

        return $uuid;
    }

    protected function macByte($val)
    {
        if ($val < 16) {
            return '0' . dechex($val);
        }
        return dechex($val);
    }

    protected function generateMacAddress()
    {
        $prefix = "52:54:00";

        return $prefix . ':' .
            $this->macByte(rand() % 256) . ':' .
            $this->macByte(rand() % 256) . ':' .
            $this->macByte(rand() % 256);
    }

    public function getDomains()
    {
        $list = libvirt_list_domains($this->conn);
        $domains = [];
        foreach ($list as $item){
            $domains[] = new Domain($this, $item);
        }
    }

    public function getDomainNameByUuid($uuid)
    {
        $dom = @libvirt_domain_lookup_by_uuid_string($this->conn, $uuid);
        if (!$dom)
            return false;
        $tmp = libvirt_domain_get_name($dom);
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function getDefaultEmulator()
    {
        $tmp = libvirt_connect_get_capabilities($this->conn, '//capabilities/guest/arch/domain/emulator');
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function createDomainByXml($name, $disk, $vcpus = 1, $arch = 'x86_64', $clock = 'utc', $mem = MEM_DEFAULT,
                                      $maxMem = MEM_DEFAULT, $features = ['apic', 'acpi', 'pae'])
    {
        $fs = '';
        foreach ($features as $feature){
            $fs .= "<{$feature}/>";
        }

        if ($disk['size']) {
            $disk['image'] = str_replace(' ', '_', $disk['image']);
            if (!$this->createImage($disk['image'], $disk['size'], $disk['driver']))
                return false;
        }
        $path = $disk['image'];

        $diskXML = diskXML($path, $disk['driver'], $disk['bus']);

        $netXML = netXML($this->generateMacAddress());

        $tmp = libvirt_domain_define_xml($this->conn, virtDomainXML(
            $name, $mem, $maxMem, $this->generateDomainUuid(), $arch, $fs, $clock, $vcpus, $this->getDefaultEmulator(),
            $diskXML, $netXML
        ));
        return ($tmp) ? $tmp : $this->setLastError();

    }

    public function createImage($image, $size, $driver)
    {
        $tmp = libvirt_image_create($this->conn, $image, $size, $driver);
        return ($tmp) ? $tmp : $this->setLastError();
    }
    
    public function getLastError()
    {
        return $this->lastError;
    }

    protected function setLastError()
    {
        $this->lastError = libvirt_get_last_error();
        return false;
    }

    protected function generateUuid($seed=false) {
        if (!$seed)
            $seed = time();
        srand($seed);
        $ret = array();
        for ($i = 0; $i < 16; $i++){
            $ret[] = $this->macByte(rand() % 256);
        }
        $a = $ret[0].$ret[1].$ret[2].$ret[3];
        $b = $ret[4].$ret[5];
        $c = $ret[6].$ret[7];
        $d = $ret[8].$ret[9];
        $e = $ret[10].$ret[11].$ret[12].$ret[13].$ret[14].$ret[15];
        return $a.'-'.$b.'-'.$c.'-'.$d.'-'.$e;
    }
}