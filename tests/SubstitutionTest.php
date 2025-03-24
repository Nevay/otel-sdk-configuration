<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use InvalidArgumentException;
use Nevay\OTelSDK\Configuration\Environment\ArrayEnvSource;
use Nevay\OTelSDK\Configuration\Environment\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Internal\Substitution;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SubstitutionTest extends TestCase {

    #[DataProvider('substitutionProvider')]
    public function testSubstitution(string $input, string $expected): void {
        $this->assertSame($expected, Substitution::process($input, new EnvSourceReader([
            new ArrayEnvSource([
                'STRING_VALUE' => 'value',
                'BOOL_VALUE' => 'true',
                'INT_VALUE' => '1',
                'FLOAT_VALUE' => '1.1',
                'HEX_VALUE' => '0xdeadbeef',
                'INVALID_MAP_VALUE' => "value\nkey:value",
                'DO_NOT_REPLACE_ME' => 'Never use this value',
                'REPLACE_ME' => '${DO_NOT_REPLACE_ME}',
                'VALUE_WITH_ESCAPE' => 'value$$',

                'FOO' => 'a',
                'BAR' => 'b',
                'BAZ' => 'c',
            ]),
        ])));
    }

    #[DataProvider('substitutionInvalidProvider')]
    public function testSubstitutionFailure(string $input): void {
        $this->expectException(InvalidArgumentException::class);

        Substitution::process($input, new EnvSourceReader([]));
    }

    public static function substitutionProvider(): iterable {
        yield ['${STRING_VALUE}', 'value'];
        yield ['${BOOL_VALUE}', 'true'];
        yield ['${INT_VALUE}', '1'];
        yield ['${FLOAT_VALUE}', '1.1'];
        yield ['${HEX_VALUE}', '0xdeadbeef'];
        yield ['"${STRING_VALUE}"', '"value"'];
        yield ['"${BOOL_VALUE}"', '"true"'];
        yield ['"${INT_VALUE}"', '"1"'];
        yield ['"${FLOAT_VALUE}"', '"1.1"'];
        yield ['"${HEX_VALUE}"', '"0xdeadbeef"'];
        yield ['${env:STRING_VALUE}', 'value'];
        yield ['${INVALID_MAP_VALUE}', "value\nkey:value"];
        yield ['foo ${STRING_VALUE} ${FLOAT_VALUE}', 'foo value 1.1'];
        yield ['${UNDEFINED_KEY}', ''];
        yield ['${UNDEFINED_KEY:-fallback}', 'fallback'];
        yield ['${REPLACE_ME}', '${DO_NOT_REPLACE_ME}'];
        yield ['${UNDEFINED_KEY:-${STRING_VALUE}}', '${STRING_VALUE}'];
        yield ['$${STRING_VALUE}', '${STRING_VALUE}'];
        yield ['$$${STRING_VALUE}', '$value'];
        yield ['$$$${STRING_VALUE}', '$${STRING_VALUE}'];
        yield ['$${STRING_VALUE:-fallback}', '${STRING_VALUE:-fallback}'];
        yield ['$${STRING_VALUE:-${STRING_VALUE}}', '${STRING_VALUE:-value}'];
        yield ['${UNDEFINED_KEY:-$${UNDEFINED_KEY}}', '${UNDEFINED_KEY:-${UNDEFINED_KEY}}'];
        yield ['${VALUE_WITH_ESCAPE}', 'value$$'];
        yield ['a $$ b', 'a $ b'];
        yield ['a $ b', 'a $ b'];

        yield ['$${FOO} ${BAR} $${BAZ}', '${FOO} b ${BAZ}'];

        yield ['${env:-test}', 'test'];
    }

    public static function substitutionInvalidProvider(): iterable {
        yield ['${}'];
        yield ['${STRING_VALUE:?error}'];
        yield ['${file:test}'];
        yield ['${0abc}'];
    }
}
