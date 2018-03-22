<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2018/3/20
 * Time: 上午9:57
 */
define('MEM_DEFAULT', 1024 * 1024);
function diskXML ($path, $driver, $bus) {
    return <<<XML
        <disk type="file" device="disk">
            <driver name="qemu" type="$driver" />
            <source file="$path"/>
            <target bus="$bus" dev="hda" />
        </disk>
XML;
}

function netXML ($macAddress) {
    return <<<NETXML
            <interface type="network">
                <mac address="$macAddress" />
                <source network="default" />
            </interface>
NETXML;
}

function virtDomainXML ($name, $mem, $maxMem, $uuid, $arch, $fs, $clock, $vcpus, $emulator, $diskXML, $netXML, $persistent) {
    $persistent = ($persistent ? '' : '<boot dev="cdrom"></boot>');
    return <<<DOMAINXML
        <domain type="kvm">
            <name>$name</name>
            <currentMemory>$mem</currentMemory>
            <memory>$maxMem</memory>
            <uuid>$uuid</uuid>
            <os>
                <type arch="$arch ">hvm</type>
                $persistent
                <boot dev="hd"/>
            </os>
            <features>
            $fs
            </features>
            <clock offset="$clock" />
            <on_poweroff>destroy</on_poweroff>
            <on_reboot>destroy</on_reboot>
            <on_crash>destroy</on_crash>
            <vcpu>$vcpus</vcpu>
            <devices>
                <emulator>$emulator</emulator>
                $diskXML
                $netXML
                <input type="tablet" bus="usb" />
                <input type="mouse" bus="ps2"/>
                <graphics type="vnc" port="-1" autoport="yes">
                    <listen type="address" address="0.0.0.0"/>
                </graphics>
                <console type="pty"/>
                <sound model="ac97"/>
                <video>
                    <model type="cirrus"/>
                </video>
            </devices>
        </domain>
DOMAINXML;
}