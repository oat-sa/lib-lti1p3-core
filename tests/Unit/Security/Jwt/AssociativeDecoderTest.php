<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Jwt;

use Lcobucci\JWT\Parsing\Decoder;
use OAT\Library\Lti1p3Core\Security\Jwt\AssociativeDecoder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AssociativeDecoderTest extends TestCase
{
    public function testItExtendVendorEncoder(): void
    {
        $this->assertInstanceOf(Decoder::class, new AssociativeDecoder());
    }

    public function testItJsonDecodeAsAssociativeArray(): void
    {
        $this->assertEquals(['a' => 'b'], (new AssociativeDecoder())->jsonDecode('{"a":"b"}'));
    }

    public function testItThrowARuntimeExceptionOnInvalidJson(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error while decoding to JSON: Syntax error');

        (new AssociativeDecoder())->jsonDecode('invalid');
    }
}
