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

class Encoder
{
    /** @var bool */
    protected $SystemLittleEndian;

    public function __construct()
    {
        $this->SystemLittleEndian = pack('S', 0xFF) === pack('v', 0xFF);
    }

    public function encode($value): string
    {
        $result = null;

        if (is_object($value)) {
            $result = $this->encodeObject($value);
        } elseif (is_array($value)) {
            $result = $this->encodeArray($value);
        } elseif (is_int($value) || is_float($value)) {
            $result = $this->encodeNumeric($value);
        } elseif (is_string($value)) {
            $result = $this->encodeString($value);
        } elseif ($value === null) {
            $result = Types::NULL;
        } elseif (is_bool($value)) {
            $result = $value ? Types::TRUE : Types::FALSE;
        }

        return $result;
    }

    protected function encodeArray(array $array): string
    {
        if (!empty($array) && (array_keys($array) !== range(0, count($array) - 1))) {
            $result = Types::OBJECT_OPEN;

            foreach ($array as $key => $value) {
                $key = (string)$key;
                $result .= $this->encodeString($key).$this->encode($value);
            }

            $result .= Types::OBJECT_CLOSE;
        } else {
            $result = Types::ARRAY_OPEN;
            $length = count($array);
            $arrayType = $this->guessArrayType($array);

            if (null !== $arrayType) {
                $result .= Types::OPTIMIZED_TYPE.$arrayType.Types::OPTIMIZED_COUNT.$this->encode($length);

                for ($i = 0; $i < $length; $i++) {
                    $result .= $this->encodeNumericAs($array[$i], $arrayType, true);
                }
            } else {
                for ($i = 0; $i < $length; $i++) {
                    $result .= $this->encode($array[$i]);
                }
            }

            $result .= Types::ARRAY_CLOSE;
        }
        
        return $result;
    }

    protected function guessArrayType(array $array)
    {
        $num = count($array);

        if (is_int($array[0])) {
            $min = $array[0];
            $max = $array[0];

            for ($i = 1; $i < $num; ++$i) {
                if (null !== $array[$i]) {
                    if (!is_int($array[$i])) {
                        return null;
                    }

                    if ($array[$i] < $min) {
                        $min = $array[$i];
                    }

                    if ($array[$i] > $max) {
                        $max = $array[$i];
                    }
                }
            }

            if ($min >= 0 && $max < 255) {
                return Types::UINT8;
            } elseif ($min >= -128 && $max < 127) {
                return Types::INT8;
            } elseif ($min >= -32768 && $max < 32767) {
                return Types::INT16;
            } elseif ($min >= -2147483648 && $max < 2147483647) {
                return Types::INT32;
            } else {
                // unsupported
                //return Types::INT64;
            }
        } elseif (is_float($array[0])) {
            for ($i = 1; $i < $num; ++$i) {
                if (!is_float($array[$i])) {
                    return null;
                }
            }

            return Types::FLOAT;
        }

        return null;
    }

    /**
     * @param stdClass $object
     * @return string
     */
    protected function encodeObject($object): string
    {
        if ($object instanceof Iterator) {
            $propCollection = (array)$object;
        } else {
            $propCollection = get_object_vars($object);
        }
        
        return $this->encodeArray($propCollection);
    }

    protected function encodeString(string $string): string
    {
        $result = null;
        $len = strlen($string);

        if ($len == 1) {
            $result = $prefix = Types::CHAR.$string;
        } else {
            $prefix = Types::STRING;
            if (preg_match('/^[\d]+(:?\.[\d]+)?$/', $string)) {
                $prefix = Types::HIGH_PRECISION;
            }
            $result = $prefix.$this->encodeNumeric(strlen($string)).$string;
        }
        
        return $result;
    }
    
    /**
     * @param int|float $numeric
     * @return string
     */
    protected function encodeNumeric($numeric): string
    {
        if (is_int($numeric)) {
            if (256 > $numeric && -129 < $numeric) {
                if (0 < $numeric) {
                    return $this->encodeNumericAs($numeric, Types::UINT8);
                } else {
                    return $this->encodeNumericAs($numeric, Types::INT8);
                }
            } elseif (32768 > $numeric && -32769 < $numeric) {
                return $this->encodeNumericAs($numeric, Types::INT16);
            } elseif (2147483648 > $numeric && -2147483649 < $numeric) {
                return $this->encodeNumericAs($numeric, Types::INT32);
            }
        } elseif (is_float($numeric)) {
            return $this->encodeNumericAs($numeric, Types::FLOAT);
        }

        throw new DecodeException(sprintf('Cannot encode numeric "%s".', (string)$numeric));
    }

    protected function encodeNumericAs($numeric, $type, $skipType = false): string
    {
        $packFormats = [
            Types::UINT8 => 'C',
            Types::INT8 => 'c',
            Types::INT16 => 's',
            Types::INT32 => 'l',
            Types::FLOAT => 'f'
        ];
        $packed = pack($packFormats[$type], $numeric);

        if ($this->SystemLittleEndian && $type !== Types::UINT8 && $type !== Types::INT8) {
            $packed = strrev($packed);
        }

        return $skipType ? $packed : $type.$packed;
    }
}
