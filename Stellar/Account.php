<?

namespace App\Lib\Stellar;

class Account {

    private static $versionBytes = array(
        'accountId' => 0x30,
        'seed' => 0x90
    );

    public static function isValidAccountId($accountId)
    {

        try {
            $decoded = self::decodeCheck("accountId", $accountId);
            if (count($decoded) !== 32) {
                return false;
            }
        } catch (\Phalcon\Exception $e) {
            return false;
        }
        return true;
    }


    private static function decodeCheck($versionByteName, $encoded)
    {
        if (!is_string($encoded)) {
            throw new \Exception("encoded argument must be of type String");
        }

        $base32 = new Strkey\Base32();

        $decoded = $base32->decode($encoded, true);

        if(empty($decoded) || !is_array($decoded)){
            return false;
        }

        $versionByte = $decoded[0];
        $payload  = array_slice($decoded, 0, -2);

        $data     = array_slice($payload, 1);
        $checksum = array_slice($decoded, -2);


        if ($base32->encode($base32->decode($encoded)) != $encoded) {
            //throw new \Exception('invalid encoded string');
            return false;
        }

        $expectedVersion = self::$versionBytes[$versionByteName];
        if (empty($expectedVersion)) {
            //throw new \Exception($versionByteName . ' is not a valid version byte name.  expected one of "accountId" or "seed"');
            return false;
        }
        if ($versionByte != $expectedVersion) {
            //throw new \Exception('invalid version byte. expected ' . $expectedVersion . ', got ' . $versionByte);
            return false;
        }

        $expectedChecksum = self::calculateChecksum($payload);

        if (!self::verifyChecksum($expectedChecksum, $checksum)) {
            //throw new \Exception('invalid checksum');
            return false;
        }

        return $data;
    }

    private static function verifyChecksum($expected, $actual)
    {
        if (count($expected) !== count($actual)) {
            return false;
        }

        if (count($expected) === 0) {
            return true;
        }

        for ($i = 0; $i < count($expected); $i++) {
            if ($expected[$i] != $actual[$i]) {
                return false;
            }
        }

        return true;
    }

    private static function uInt16($value, $offset)
    {
        $value = +$value;
        $offset = $offset >> 0;

        $buffer = [];
        $buffer[$offset] = $value & 0xff;
        $buffer[$offset + 1] = $value >> 8;

        return $buffer;
    }

    private static function calculateChecksum($payload)
    {
        // This code calculates CRC16-XModem checksum of payload
        // and returns it as Buffer in little-endian order.
        $crc16 = new Strkey\CRC16XModem();
        $crc16->update($payload);

        return self::uInt16($crc16->getChecksum(), 0);
    }
}