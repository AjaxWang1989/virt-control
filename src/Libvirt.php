<?php
class Libvirt
{
    protected $uri = "qemu:///system";

    protected $conn;

    protected $lastError;

    protected static $instance;

    public static function make()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->connect($this->uri);
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

    protected function getDomainByName($name)
    {
        return @libvirt_domain_lookup_by_name($this->conn, $name);
    }

    protected function generateDomainUuid()
    {
        $uuid = $this->generate_uuid();

        while ($this->getDomainNameByUuid($uuid))
            $uuid = $this->generate_uuid();

        return $uuid;
    }

    protected function macbyte($val)
    {
        if ($val < 16) {
            return '0' . dechex($val);
        }
        return dechex($val);
    }

    protected function generateMacAddr()
    {
        $prefix = "52:54:00";

        return $prefix . ':' .
            $this->macbyte(rand() % 256) . ':' .
            $this->macbyte(rand() % 256) . ':' .
            $this->macbyte(rand() % 256);
    }

    public function getDomainNameByUuid($uuid)
    {
        $dom = @libvirt_domain_lookup_by_uuid_string($this->conn, $uuid);
        if (!$dom)
            return false;
        $tmp = libvirt_domain_get_name($dom);
        return ($tmp) ? $tmp : $this->setLastError();
    }

    protected function getDefaultEmulator()
    {
        $tmp = libvirt_connect_get_capabilities($this->conn, '//capabilities/guest/arch/domain/emulator');
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public function createDomainByXml($name, $disk)
    {
        $uuid = $this->generateDomainUuid();
        $emulator = $this->getDefaultEmulator();

        //$mem = 512 * 1024;
        //$maxmem = 512 * 1024;
        $mem = 1024 * 1024;
        $maxmem = 1024 * 1024;

        $arch = 'x86_64';
        $clock = 'utc';

        $vcpus = 1;

        $features = ['apic', 'acpi', 'pae'];

        $fs = '';
        for ($i = 0; $i < sizeof($features); $i++) {
            $fs .= '<' . $features[$i] . ' />';
        }

        if ($disk['size']) {
            $disk['image'] = str_replace(' ', '_', $disk['image']);
            if (!$this->createImage($disk['image'], $disk['size'], $disk['driver']))
                return false;
        }
        $path = $disk['image'];

        $diskstr = "<disk type='file' device='disk'>
						    <driver name='qemu' type='{$disk['driver']}' />
                            <source file='$path'/>
                            <target bus='{$disk['bus']}' dev='hda' />
                        </disk>";

        $netstr = "<interface type='network'>
                        <mac address='{$this->generateMacAddr()}' />
                        <source network='default' />
                   </interface>";

        $xml = "<domain type='kvm'>
					<name>$name</name>
					<currentMemory>$mem</currentMemory>
					<memory>$maxmem</memory>
					<uuid>$uuid</uuid>
					<os>
						<type arch='" . $arch . "'>hvm</type>
						<boot dev='hd'/>
					</os>
					<features>
					$fs
					</features>
					<clock offset=\"$clock\"/>
					<on_poweroff>destroy</on_poweroff>
					<on_reboot>destroy</on_reboot>
					<on_crash>destroy</on_crash>
					<vcpu>$vcpus</vcpu>
					<devices>
						<emulator>$emulator</emulator>
						$diskstr
						$netstr
						<input type='tablet' bus='usb'/>
						<input type='mouse' bus='ps2'/>
						<graphics type='vnc' port='-1' autoport='yes'>
							<listen type='address' address='0.0.0.0'/>
						</graphics>
						<console type='pty'/>
						<sound model='ac97'/>
						<video>
							<model type='cirrus'/>
						</video>
					</devices>
					</domain>";

        $tmp = libvirt_domain_define_xml($this->conn, $xml);
        return ($tmp) ? $tmp : $this->setLastError();

    }

    protected function createImage($image, $size, $driver)
    {
        $tmp = libvirt_image_create($this->conn, $image, $size, $driver);
        return ($tmp) ? $tmp : $this->setLastError();
    }

    public static function getFtpImages()
    {
        $path = FTP_PATH;

        $result = [];
        if (!is_dir($path) || !is_readable($path)) {
            return [];
        }

        $allFiles = scandir($path);
        foreach ($allFiles as $fileName) {

            if (is_file(realpath($path . '/' . $fileName)) && pathinfo($fileName, PATHINFO_EXTENSION) === 'qcow2') {

                if (DIRECTORY_SEPARATOR == '\\') {

                    $fileName = mb_convert_encoding($fileName, "utf-8", "gb2312,gbk");
                }

                $result[] = $fileName;
            }

        }

        return $result;

    }

    public static function getExpired()
    {
        $expired = Config::where('name', 'vm_expired')->value('value');

        return (int)($expired ?: 120);
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
	protected function generate_uuid($seed=false) {
			if (!$seed)
				$seed = time();
			srand($seed);

			$ret = array();
			for ($i = 0; $i < 16; $i++)
				$ret[] = $this->macbyte(rand() % 256);

			$a = $ret[0].$ret[1].$ret[2].$ret[3];
			$b = $ret[4].$ret[5];
			$c = $ret[6].$ret[7];
			$d = $ret[8].$ret[9];
			$e = $ret[10].$ret[11].$ret[12].$ret[13].$ret[14].$ret[15];

			return $a.'-'.$b.'-'.$c.'-'.$d.'-'.$e;
		}
}
?>