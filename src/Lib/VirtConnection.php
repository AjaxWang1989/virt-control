<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2018/3/21
 * Time: 上午9:12
 */

namespace ShWang\VirtControl\Lib;


class VirtConnection
{
    /**
     * url use to connect to the libvirt daemon
     * @var string
     * */
    private $_url = '';

    /**
     * flag whether to use read-only connection or not
     * @var boolean
     * */
    private $_readonly = true;

    /**
     * array of connection credentials
     * @var array
     * */
    private $_credentials = [];

    /**
     * libvirt connection resource
     * @var Resource
     * */
    private $_connection = null;

    /**
     * array of machine types for the connection incl. maxCpus if appropriate
     * @var array
     * */
    private $_machineTypes = null;

    /**
     * connection resource information
     * @var array
     * */
    private $_connectionInfo = null;

    /**
     * connection URI string
     * @var string
     * */
    private $_uri = null;

    /**
     *hostname of the host node
     * @var string
     * */
    private $_hostName = null;

    /**
     * array of hypervisor information
     * @var array
     * */
    private $_hypervisor = null;

    /**
     * the information whether the connection is encrypted or not.
     * @var int
     * */
    private $_encrypted = 0;

    /**
     * the information whether the connection is secure or not.
     * @var boolean
     * */
    private $_secure = false;

    /**
     * maximum number of VCPUs per VM on the hypervisor connection
     * @var int
     * */
    private $_maxVcpus = 0;

    /**
     *  the system information from connection if available
     * @var \SimpleXMLElement
     * */
    private $_sysInfo = null;


    /**
     * construct function of VirtConnection
     * @param string $url
     * @param boolean $readonly
     * @param array $credentials
     * */
    public function __construct (string $url = null, boolean $readonly = null, array $credentials = null) {
        if (isset($argv) && isset($argc) && $argc === 0 && is_resource($argv[0])) {
            $this->_connection = $argv[0];
        }else{
            $this->_url         = $url;
            $this->_readonly    = $readonly;
            $this->_credentials = $credentials;
            $this->connect();
        }
    }

    /**
     * set url to connect to  the libvirt daemon
     * @param string $url
     * @return VirtConnection
     * */
    public function setUrl (string $url) {
        $this->_url = $url;
        return $this;
    }

    /**
     * set flag whether to use read-only connection or not
     * @param boolean $readonly
     * @return VirtConnection
     * */
    public function setReadonly (boolean $readonly) {
        if (!empty($readonly)) {
            $this->_readonly = $readonly;
        }
        return $this;
    }

    /**
     * connect to the specified libvirt daemon using the specified URL,
     * user can also set the readonly flag and/or set credentials for connection.
     * @return VirtConnection
     * */
    public function connect () {
        $this->_connection = libvirt_connect($this->_url, $this->_readonly, $this->_credentials);
        return $this;
    }

    /**
     * @return boolean judge the libvirt daemon has connected
     * */
    public function isConnected () {
        return !!$this->_connection;
    }

    /**
     * Function is used to get machine types supported by hypervisor on the connection
     * @return array
     * @throws
     * */
    public function getMachineTypes () {
        if (!$this->_machineTypes) {
           $this->_machineTypes = libvirt_connect_get_machine_types($this->_connection);
        }
        if ($this->_machineTypes === false) {
            throw new VirtLastError();
        }
        return $this->_machineTypes ;
    }

    /**
     * Function is used to get the information about the connection.
     * @return array
     * @throws
     * */
    public function getConnectionInfo () {
        if (!$this->_connectionInfo) {
            $this->_connectionInfo = libvirt_connect_get_information($this->_connection);
        }

        if ($this->_connectionInfo === false) {
            throw new VirtLastError();
        }
        return $this->_connectionInfo ;
    }

    /**
     * Function is used to get the connection URI. This is useful to check the
     * hypervisor type of host machine when using "null" uri to libvirt_connect().
     * @return string|boolean connection URI string or FALSE for error
     * @throws
     * */
    public function getConnectionUri () {
        if (!$this->_uri) {
            $this->_uri = libvirt_connect_get_uri($this->_connection);
        }

        if ($this->_uri === false) {
            throw  new VirtLastError();
        }
        return $this->_uri ;
    }

    /**
     * Function is used to get the hostname of the guest associated with the connection.
     * @return  string|boolean hostname of the host node or FALSE for error
     * @throws
     * */
    public function getConnectionHostName () {
        if (!$this->_hostName) {
            $this->_hostName = libvirt_connect_get_hostname($this->_connection);
        }

        if ($this->_hostName === false) {
            throw  new VirtLastError();
        }
        return $this->_hostName ;
    }

    /**
     *Function is used to get the information about the hypervisor on the connection identified by the
     * connection pointer.
     * @return array
     * @throws
     * */
    public function getHypervisor () {
        if (!$this->_hypervisor) {
            $this->_hypervisor = libvirt_connect_get_hypervisor($this->_connection);
        }

        if ($this->_hypervisor === false) {
            throw new VirtLastError();
        }
        return $this->_hypervisor ;
    }

    /**
     * Function is used to get the information whether the connection is encrypted or not
     * @return int  1 if encrypted, 0 if not encrypted, -1 on error
     * @throws
     * */
    public function isEncrypted () {
        if (!$this->_encrypted) {
            $this->_encrypted = libvirt_connect_is_encrypted($this->_connection);
        }

        if ($this->_encrypted === -1) {
            throw  new VirtLastError();
        }
        return $this->_encrypted;
    }

    /**
     *Query statistics for all domains on a given connection
     * @param int $stats the statistic groups from VIR_DOMAIN_STATS_*
     * @param int $flags the filter flags from VIR_CONNECT_GET_ALL_DOMAINS_STATS_*
     * @return array|boolean assoc array with statistics or false on error
     * @throws
     * */
    public function getAllDomainStats (int $stats, int $flags) {
        $stats = libvirt_connect_get_all_domain_stats($this->_connection, $stats, $flags);
        if ($stats === false) {
            throw new VirtLastError();
        }
        return $stats;
    }

    /**
     *Function is used to get maximum number of VCPUs per VM on the hypervisor connection
     * @return int
     * @throws
     * */
    public function getMaxVcpus () {
        if (!$this->_maxVcpus) {
            $this->_maxVcpus = libvirt_connect_get_maxvcpus($this->_connection);
        }

        if ($this->_maxVcpus === false) {
            throw new VirtLastError();
        }
        return $this->_maxVcpus;
    }

    /**
     * Function is used to get the system information from connection if available
     * @return \SimpleXMLElement|boolean
     * @throws
     * */
    public function getSysInfo () {
        if (!$this->_sysInfo) {
            $this->_sysInfo = libvirt_connect_get_sysinfo($this->_connection);
        }

        if ($this->_sysInfo === false) {
            throw new VirtLastError();
        }
        return $this->_sysInfo;
    }

    /**
     *Function is used to get the capabilities information from the connection.
     * @param string $xpath optional xPath query to be applied on the result
     * @return \SimpleXMLElement|boolean  capabilities XML from the connection or FALSE for error
     * @throws
     * */
    public function getCapabilities (string $xpath) {
        $capabilities = libvirt_connect_get_capabilities($this->_connection, $xpath);
        if ($capabilities === false) {
            throw new VirtLastError();
        }
        return $capabilities;
    }

    /**
     * Function is used to get the emulator for requested connection/architecture.
     * @param string $arch optional architecture string, can be NULL to get default
     * @return string path to the emulator
     * @throws
     * */
    public function getEmulator (string $arch = null) {
        $emulator = libvirt_connect_get_emulator($this->_connection, $arch);
        if ($emulator === false) {
            throw new VirtLastError();
        }
        return$emulator;
    }

    /**
     *Function is used to get NIC models for requested connection/architecture.
     * @param string $arch optional architecture string, can be NULL to get default
     * @return array
     * @throws
     * */
    public function getNICModels (string $arch) {
        $models = libvirt_connect_get_nic_models($this->_connection, $arch);
        if ($models === false) {
            throw new VirtLastError();
        }
        return $models;
    }

    /**
     * Function is used to get sound hardware models for requested connection/architecture.
     * @param string $arch [string]:	optional architecture string, can be NULL to get default
     * @param int $flags [int]:	flags for getting sound hardware. Can be either 0 or VIR_CONNECT_SOUNDHW_GET_NAMES
     * @return array	 array of models
     * @throws
     * */
    public function getSoundHWModels (string $arch, int $flags) {
        $models = libvirt_connect_get_soundhw_models($this->_connection, $arch, $flags);
        if ($models) {
            return $models;
        }
        throw new VirtLastError();
    }

    /**
     * get the resource of connection
     * @return Resource
     * */
    public function getConnection () {
        return $this->_connection;
    }

    /**
     *Function is used to lookup for domain by it's name.
     * @param string $name
     * @return Resource  libvirt domain resource
     * */
    public function getDomainByName (string $name) {
        return libvirt_domain_lookup_by_name($this->_connection, $name);
    }
    /**
     * Function is used to get the domain by it's UUID that's accepted in string format
     * @param string $uuid domain UUID [in string format] to look for
     * @return Resource libvirt domain resource
     * */
    public function getDomainByUuidString (string $uuid) {
        return libvirt_domain_lookup_by_uuid_string($this->_connection, $uuid);
    }

    /**
     * Function is used to get the domain by it's UUID that's accepted in string format
     * @param string $uuid  binary defined UUID to look for
     * @return Resource libvirt domain resource
     * */
    public function getDomainByUuid (string $uuid) {
        return libvirt_domain_lookup_by_uuid($this->_connection, $uuid);
    }
    /**
     * Function is used to get the domain by it's ID that's accepted in string format
     * @param string $id  domain id to look for
     * @return Resource libvirt domain resource
     * */
    public function getDomainById (string $id) {
        return libvirt_domain_lookup_by_id($this->_connection, $id);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->_sysInfo = null;
        $this->_connectionInfo = null;
        $this->_hypervisor = null;
        $this->_maxVcpus = null;
        $this->_encrypted = null;
        $this->_hostName = null;
        $this->_secure = null;
        $this->_uri = null;
        $this->_machineTypes = null;
        $this->_url = null;
        $this->_readonly = null;
        $this->_credentials = null;
        free_resources_on_connection($this->_connection);
        $this->_connection = null;
    }
}