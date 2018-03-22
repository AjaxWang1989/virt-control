<?php
define('VIR_DOMAIN_XML_INACTIVE', '');
define('VIR_MIGRATE_LIVE', '');
//libvirt_virConnectCredType
define('VIR_CRED_AUTHNAME', '');
define('VIR_CRED_ECHOPROMPT', '');
define('VIR_CRED_REALM', '');
define('VIR_CRED_PASSPHRASE', '');
define('VIR_CRED_NOECHOPROMPT', '');

function libvirt_connect($uri, $e, $user = []){}

function libvirt_domain_lookup_by_name($conn, $name){}

function libvirt_domain_is_active($res){}

function libvirt_domain_create($res){}

function libvirt_domain_destroy($res){}

function libvirt_domain_undefine($res){}

function libvirt_domain_xml_xpath($res, $path, $flags){}

function libvirt_domain_lookup_by_uuid_string($con, $uuid){}

function libvirt_connect_get_capabilities($con, $path = ''){}

function libvirt_domain_get_name($res){}

function libvirt_domain_define_xml($conn, $xml){}

function libvirt_image_create($conn, $image, $size, $driver){}

function libvirt_get_last_error (){}

function libvirt_list_domains($conn){}

function libvirt_domain_get_info($res){}

function libvirt_domain_nic_add($res, $mac, $network, $model){}

function libvirt_domain_nic_remove($res, $mac){}

function libvirt_domain_create_xml($res, $xml){}

function libvirt_image_remove($con, $image){}

function libvirt_domain_migrate_to_uri($res, $uri, $live, $name, $bandwidth){}

function libvirt_domain_migrate($res, $conn, $live, $name, $bandwidth){}

function libvirt_logfile_set($file){}

function libvirt_print_binding_resources(){}

function libvirt_domain_disk_add($res, $image, $dev, $type, $driver){}

function libvirt_domain_change_vcpus($res, $num){}

function libvirt_domain_change_memory($res, $memory, $maxMem){}

function libvirt_domain_change_boot_devices($res, $first, $second){}

function libvirt_domain_get_screenshot($res, $host, $size){}

function libvirt_domain_get_screen_dimensions($res, $host){}

function libvirt_domain_send_keys($res, $host, $keys){}

function libvirt_domain_send_pointer_event($res, $host, $x, $y, $clicked, $release){}

function libvirt_domain_disk_remove($res, $dev){}

function libvirt_connect_get_hostname($res){}

function libvirt_domain_get_block_info($res, $disk){}

function libvirt_domain_get_network_info($res, $disk){}

function libvirt_connect_get_machine_types($conn){}

function libvirt_connect_get_information($conn){}

function libvirt_connect_get_uri($conn){}

function libvirt_connect_get_hypervisor($conn){}

function libvirt_connect_is_encrypted($conn){}

function libvirt_connect_is_secure($conn){}

function libvirt_connect_get_all_domain_stats($conn, $stats, $flags){}

function libvirt_connect_get_maxvcpus($conn){}

function libvirt_connect_get_sysinfo($conn){}

function libvirt_connect_get_emulator($conn, $arch){}

function libvirt_connect_get_nic_models($conn, $arch){}

function libvirt_connect_get_soundhw_models($conn, $arch, $flags){}

function free_resources_on_connection($conn){}

function libvirt_domain_suspend ($res){}

function libvirt_domain_resume($res){}

function libvirt_domain_shutdown($res){}

function libvirt_domain_managedsave($res){}

function libvirt_domain_reboot($res, $flags){}

function libvirt_domain_lookup_by_uuid($conn, $uuid){}

function libvirt_domain_lookup_by_id($conn, $id){}

function libvirt_domain_get_uuid_string($res){}

function libvirt_domain_get_screenshot_api($res, $screenID){}

function libvirt_domain_get_uuid($res){}

function libvirt_domain_get_id($res){}

function libvirt_domain_get_next_dev_ids($res){}

function libvirt_domain_new($conn, $name, $arch, $memMB, $maxMemMB, $vcpus, $iso_image, $disks, $networks, $flags){}

function libvirt_domain_memory_peek($res, $start, $size, $flags){}

function libvirt_domain_memory_stats($res, $flags){}

function libvirt_domain_update_device($res, $xml){}

function libvirt_domain_block_commit($res, $disk, $base, $top, $bandwidth, $flags){}

function libvirt_domain_block_job_abort($res, $path, $flags){}

function libvirt_domain_block_job_set_speed($res, $path, $bandwidth, $flags){}

function libvirt_domain_interface_stats($res, $path){}

function libvirt_domain_get_connect($res){}