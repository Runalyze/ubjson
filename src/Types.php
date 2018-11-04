<?php

declare(strict_types = 1);

/*
 * This file is part of the Runalyze/Ubjson.
 * (c) RUNALYZE <mail@runalyze.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Runalyze\Ubjson;

/**
 * @see http://ubjson.org/type-reference/
 */
class Types
{
    /** @var string */
    const NOOP = 'N';

    /** @var string */
    const NULL = 'Z';

    /** @var string */
    const FALSE = 'F';

    /** @var string */
    const TRUE = 'T';

    /** @var string */
    const INT8 = 'i';

    /** @var string */
    const UINT8 = 'U';

    /** @var string */
    const INT16 = 'I';

    /** @var string */
    const INT32 = 'l';

    /** @var string */
    const INT64 = 'L';

    /** @var string */
    const FLOAT = 'd';

    /** @var string */
    const DOUBLE = 'D';

    /** @var string */
    const CHAR = 'C';

    /** @var string */
    const STRING = 'S';

    /** @var string */
    const HIGH_PRECISION = 'H';

    /** @var string */
    const ARRAY_OPEN = '[';

    /** @var string */
    const ARRAY_CLOSE = ']';

    /** @var string */
    const OBJECT_OPEN = '{';

    /** @var string */
    const OBJECT_CLOSE = '}';

    /** @var string */
    const OPTIMIZED_TYPE = '$';

    /** @var string */
    const OPTIMIZED_COUNT = '#';
}
