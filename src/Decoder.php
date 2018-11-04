<?php

declare(strict_types = 1);

/*
 * This file is part of the Runalyze/Ubjson.
 * (c) RUNALYZE <mail@runalyze.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Runalyze\Ubjson;

use Runalyze\Ubjson\Exception\DecodeException;

class Decoder
{
    /** @var string */
    const EOF = 'EOF';

    /** @var string */
    const DATA = 'DATA';

    /** @var string */
    protected $Source;

    /** @var int */
    protected $Length;

    /** @var int */
    protected $Offset = 0;

    /** @var int */
    protected $Token = self::EOF;

    /** @var mixed */
    protected $TokenValue = null;

    /** @var bool */
    protected $SystemLittleEndian;

    public function __construct(string $source)
    {
        $this->Source = $source;
        $this->Length = strlen($source);

        $this->SystemLittleEndian = pack('S', 0xFF) === pack('v', 0xFF);
    }
    
    /**
     * @return mixed
     */
    public function decode()
    {
        $this->getNextToken();

        return $this->decodeValue();
    }
    
    /**
     * @return mixed
     */
    protected function decodeValue()
    {
        switch ($this->Token) {
            case self::DATA:
                $result = $this->TokenValue;
                $this->getNextToken();

                return $result;

            case Types::ARRAY_OPEN:
            case Types::OBJECT_OPEN:
                return $this->decodeStruct();
        }

        return null;
    }

    /**
     * @return string|int string from Types or internal int for EOF or DATA
     */
    protected function getNextToken()
    {
        $this->Token = self::EOF;
        $this->TokenValue = null;

        if ($this->Offset >= $this->Length) {
            return $this->Token;
        }

        $val = null;
        ++$this->Offset;
        $token = $this->Source{$this->Offset-1};
        $this->Token = self::DATA;

        switch ($token) {
            case Types::INT8:
                $this->TokenValue = $this->unpack('c', 1);
                break;
            case Types::UINT8:
                $this->TokenValue = $this->unpack('C', 1);
                break;
            case Types::INT16:
                $this->TokenValue = $this->unpack('s', 2);
                break;
            case Types::INT32:
                $this->TokenValue = $this->unpack('l', 4);
                break;
            case Types::INT64:
                throw new DecodeException('INT64 is not supported in PHP');
                break;
            case Types::FLOAT:
                $this->TokenValue = $this->unpack('f', 4);
                break;
            case Types::DOUBLE:
                throw new DecodeException('DOUBLE is not supported in PHP');
                break;
            case Types::TRUE:
                $this->TokenValue = true;
                break;
            case Types::FALSE:
                $this->TokenValue = false;
                break;
            case Types::NULL:
                $this->TokenValue = null;
                break;
            case Types::CHAR:
                $this->TokenValue = $this->read(1);
                break;
            case Types::NOOP:
                throw new DecodeException('NOOP is not supported in PHP');
                break;
            case Types::STRING:
            case Types::HIGH_PRECISION:
                ++$this->Offset;
                $len = 0;

                switch ($this->Source{$this->Offset-1}) {
                    case Types::INT8:
                        $len = $this->unpack('c', 1);
                        break;
                    case Types::UINT8:
                        $len = $this->unpack('C', 1);
                        break;
                    case Types::INT16:
                        $len = $this->unpack('s', 2);
                        break;
                    case Types::INT32:
                        $len = $this->unpack('l', 4);
                        break;
                    default:
                        //unsupported
                        $this->Token = null;
                }

                $this->TokenValue = '';

                if ($len) {
                    $this->TokenValue = $this->read($len);
                }
                break;
            case Types::OBJECT_OPEN:
                $this->Token = Types::OBJECT_OPEN;
                break;
            case Types::OBJECT_CLOSE:
                $this->Token = Types::OBJECT_CLOSE;
                break;
            case Types::ARRAY_OPEN:
                $this->Token = Types::ARRAY_OPEN;
                break;
            case Types::ARRAY_CLOSE:
                $this->Token = Types::ARRAY_CLOSE;
                break;
            case Types::OPTIMIZED_COUNT:
                $this->Token = Types::OPTIMIZED_COUNT;
                break;
            case Types::OPTIMIZED_TYPE:
                $this->Token = Types::OPTIMIZED_TYPE;
                break;
            default:
                $this->Token = self::EOF;
        }

        return $this->Token;
    }

    protected function readValueByType(string $type)
    {
        switch ($type) {
            case Types::INT8:
                return $this->unpack('c', 1);
            case Types::UINT8:
                return $this->unpack('C', 1);
            case Types::INT16:
                return $this->unpack('s', 2);
            case Types::INT32:
                return $this->unpack('l', 4);
            case Types::FLOAT:
                return $this->unpack('f', 4);
       }

        throw new DecodeException('Unknown container type.');
    }

    protected function decodeStruct(): array
    {
        $key = 0;
        $tokenOpen = $this->Token;
        $result = [];
        
        $structEnd = array(Types::OBJECT_CLOSE, Types::ARRAY_CLOSE);
        $tokenCurrent = $this->getNextToken();

        if ($tokenCurrent == Types::OPTIMIZED_TYPE) {
            $arrayType = $this->read(1);

            if (Types::OPTIMIZED_COUNT !== $this->read(1)) {
                throw new DecodeException('Optimized container must specify count after type.');
            }

            $this->getNextToken();
            $arrayCount = $this->TokenValue;

            for ($i = 0; $i < $arrayCount; ++$i) {
                $result[] = $this->readValueByType($arrayType);
            }

            ++$this->Offset;
        } else {
            while ($tokenCurrent && !in_array($tokenCurrent, $structEnd)) {
                if ($tokenOpen == Types::OBJECT_OPEN) {
                    $key = $this->TokenValue;
                    $tokenCurrent = $this->getNextToken();
                }
    
                $value = $this->decodeValue();
                $tokenCurrent = $this->Token;
                
                $result[$key] = $value;
                
                if (in_array($tokenCurrent, $structEnd)) {
                    break;
                }
    
                if ($tokenOpen != Types::OBJECT_OPEN) {
                    ++$key;
                }
            }
        }

        $this->getNextToken();

        return $result;
    }

    protected function read(int $bytes = 1): string
    {
        $result = substr($this->Source, $this->Offset, $bytes);
        $this->Offset += $bytes;
        
        return $result;
    }

    /**
     * @return mixed
     */
    protected function unpack(string $flag, int $bytes)
    {
        $value = null;

        if ($this->Length < $this->Offset + $bytes) {
            throw new DecodeException('Invalid ubjson data');
        } else {
            $packed = $this->read($bytes);

            switch ($flag) {
                case 's':
                case 'l':
                case 'f':
                    $swap = $this->SystemLittleEndian;
                    break;
                default:
                    $swap = false;
            }

            if ($swap) {
                $packed = strrev($packed);
            }

            list(, $value) = unpack($flag, $packed);
        }

        return $value;
    }
}
