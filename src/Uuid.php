<?php

namespace Debva\Nix;

class Uuid
{
    const SYSFS_PATTERN = '/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/i';

    const IFCONFIG_PATTERN = '/[^:]([0-9a-f]{2}([:-])[0-9a-f]{2}(\2[0-9a-f]{2}){4})[^:]/i';

    const SECOND_INTERVALS = 10000000;

    const MICROSECOND_INTERVALS = 10;

    const GREGORIAN_TO_UNIX_INTERVALS = 0x01b21dd213814000;

    protected $node;

    public function __construct()
    {
        $this->node = $this->getNodeFromSystem();
    }

    public function v1($generate = 1)
    {
        $guids = [];

        for ($i = 0; $i < $generate; $i++) {
            $guids[] = $this->uuidFromBytesAndVersion($this->timeGenerator(), 1);
        }

        return count($guids) > 1 ? array_unique($guids) : reset($guids);
    }

    public function v4($generate = 1)
    {
        $guids = [];

        for ($i = 0; $i < $generate; $i++) {
            $guids[] = $this->uuidFromBytesAndVersion($this->randomGenerator(16), 4);
        }

        return count($guids) > 1 ? array_unique($guids) : reset($guids);
    }

    protected function getNodeFromSystem()
    {
        $node = $this->getSysfs();

        if ($node === '') {
            $node = $this->getIfconfig();
        }

        return str_replace([':', '-'], '', $node);
    }

    protected function getValidNode()
    {
        $node = $this->node;

        if (is_int($node)) {
            $node = dechex($node);
        }

        if (!preg_match('/^[A-Fa-f0-9]+$/', (string) $node) || strlen((string) $node) > 12) {
            throw new \Exception('Invalid node value', 400);
        }

        return (string) hex2bin(str_pad((string) $node, 12, '0', STR_PAD_LEFT));
    }

    protected function getSysfs()
    {
        $mac = '';

        $phpOs = constant('PHP_OS');

        if (strtoupper($phpOs) === 'LINUX') {
            $addressPaths = glob('/sys/class/net/*/address', GLOB_NOSORT);

            if ($addressPaths === false || count($addressPaths) === 0) {
                return '';
            }

            $macs = [];

            array_walk($addressPaths, function ($addressPath) use (&$macs) {
                if (is_readable($addressPath)) {
                    $macs[] = file_get_contents($addressPath);
                }
            });

            $macs = array_filter(array_map('trim', $macs), function ($address) {
                return $address !== '00:00:00:00:00:00'
                    && preg_match(self::SYSFS_PATTERN, $address);
            });

            $mac = reset($macs);
        }

        return (string) $mac;
    }

    protected function getIfconfig()
    {
        $disabledFunctions = strtolower((string) ini_get('disable_functions'));

        if (strpos($disabledFunctions, 'passthru') !== false) {
            return '';
        }

        $phpOs = constant('PHP_OS');

        ob_start();
        switch (strtoupper(substr($phpOs, 0, 3))) {
            case 'WIN':
                passthru('ipconfig /all 2>&1');

                break;
            case 'DAR':
                passthru('ifconfig 2>&1');

                break;
            case 'FRE':
                passthru('netstat -i -f link 2>&1');

                break;
            case 'LIN':
            default:
                passthru('netstat -ie 2>&1');

                break;
        }

        $ifconfig = (string) ob_get_clean();

        if (preg_match_all(self::IFCONFIG_PATTERN, $ifconfig, $matches, PREG_PATTERN_ORDER)) {
            foreach ($matches[1] as $iface) {
                if ($iface !== '00:00:00:00:00:00' && $iface !== '00-00-00-00-00-00') {
                    return $iface;
                }
            }
        }

        return '';
    }

    protected function calculateTime($seconds, $microseconds)
    {
        $uuidTime = ((int) $seconds * self::SECOND_INTERVALS)
            + ((int) $microseconds * self::MICROSECOND_INTERVALS)
            + self::GREGORIAN_TO_UNIX_INTERVALS;

        return $this->toHex(str_pad(dechex($uuidTime), 16, '0', STR_PAD_LEFT));
    }

    protected function timeGenerator()
    {
        $node = $this->getValidNode();

        $clockSeq = mt_rand(0, 0x3fff);

        $time = gettimeofday();

        $uuidTime = $this->calculateTime($time['sec'], $time['usec']);

        $timeHex = str_pad($uuidTime, 16, '0', STR_PAD_LEFT);

        if (strlen($timeHex) !== 16) {
            throw new \Exception(sprintf('The generated time of \'%s\' is larger than expected', $timeHex), 400);
        }

        $timeBytes = (string) hex2bin($timeHex);

        return $timeBytes[4] . $timeBytes[5] . $timeBytes[6] . $timeBytes[7]
            . $timeBytes[2] . $timeBytes[3]
            . $timeBytes[0] . $timeBytes[1]
            . pack('n*', $clockSeq)
            . $node;
    }

    protected function applyVersion($timeHi, $version)
    {
        $timeHi = (int) $timeHi & 0x0fff;
        $timeHi |= (int) $version << 12;
        return $timeHi;
    }

    protected function applyVariant($clockSeq)
    {
        $clockSeq = (int) $clockSeq & 0x3fff;
        $clockSeq |= 0x8000;
        return $clockSeq;
    }

    protected function toHex($value)
    {
        $value = strtolower($value);

        if (startsWith($value, '0x')) {
            $value = substr($value, 2);
        }

        if (!preg_match('/^[A-Fa-f0-9]+$/', $value)) {
            throw new \Exception('Value must be a hexadecimal number', 400);
        }

        return $value;
    }

    protected function fromBytes($bytes)
    {
        $base16Uuid = bin2hex($bytes);

        return substr($base16Uuid, 0, 8)
            . '-'
            . substr($base16Uuid, 8, 4)
            . '-'
            . substr($base16Uuid, 12, 4)
            . '-'
            . substr($base16Uuid, 16, 4)
            . '-'
            . substr($base16Uuid, 20, 12);
    }

    protected function randomGenerator($length)
    {
        $bytes = '';
        $length = (int) $length;

        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length);
        } else {
            $bytes = '';
            for ($i = 0; $i < $length; $i++) {
                $bytes .= chr(mt_rand(0, 255));
            }
        }

        return $bytes;
    }

    protected function uuidFromBytesAndVersion($bytes, $version)
    {
        $unpackedTime = unpack('n*', substr($bytes, 6, 2));
        $timeHi = (int) $unpackedTime[1];
        $timeHiAndVersion = pack('n*', $this->applyVersion($timeHi, (int) $version));

        $unpackedClockSeq = unpack('n*', substr($bytes, 8, 2));
        $clockSeqHi = (int) $unpackedClockSeq[1];
        $clockSeqHiAndReserved = pack('n*', $this->applyVariant($clockSeqHi));

        $bytes = substr_replace($bytes, $timeHiAndVersion, 6, 2);
        $bytes = substr_replace($bytes, $clockSeqHiAndReserved, 8, 2);

        return $this->fromBytes($bytes);
    }
}
