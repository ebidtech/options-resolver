<?php

/**
 * This file is a part of the Validator library.
 *
 * (c) 2015 Ebidtech
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EBT\OptionsResolver\Tests\Unit\Model\OptionsResolver;

use EBT\OptionsResolver\Model\OptionsResolver\OptionsResolver;
use EBT\OptionsResolver\Tests\BaseUnitTestCase;

/**
 * EBT\OptionsResolver\Tests\Unit\Model\OptionsResolver\OptionsResolverTest
 *
 * @coversDefaultClass EBT\OptionsResolver\Model\OptionsResolver\OptionsResolver
 * @group              unit
 */
class OptionsResolverTest extends BaseUnitTestCase
{
    /**
     * @var OptionsResolver
     */
    protected $optionsResolver;

    /**
     * Tests that an exception is thrown when trying to set a cast for an undefined exception.
     *
     * @expectedException        \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @expectedExceptionMessage The option "test_option" does not exist
     *
     * @covers ::setCast
     */
    public function testSetCastUndefinedOptionFailure()
    {
        $this->optionsResolver->setCast('test_option', 'int');
    }

    /**
     * Tests that an exception is thrown when trying to set a cast for a type that is not supported.
     *
     * @expectedException        \Symfony\Component\OptionsResolver\Exception\InvalidArgumentException
     * @expectedExceptionMessage The type cast "test_type" does not exist
     *
     * @covers ::setCast
     */
    public function testSetCastUnsupportedCastFailure()
    {
        $this->optionsResolver->setDefined('test_option');
        $this->optionsResolver->setCast('test_option', 'test_type');
    }

    /**
     * Tests that an exception is thrown when a closure with no arguments is given.
     *
     * @expectedException        \Symfony\Component\OptionsResolver\Exception\InvalidArgumentException
     * @expectedExceptionMessage The given closure must define exactly one required parameter (the value to cast).
     *
     * @covers ::setCast
     */
    public function testSetCastClosureWithWrongNumberOfArgumentsFailure()
    {
        $this->optionsResolver->setDefined('test_option');
        $this->optionsResolver->setCast(
            'test_option',
            function () {

                return null;
            }
        );
    }

    /**
     * Tests that an exception is thrown when a closure with no arguments is given.
     *
     * @expectedException        \Symfony\Component\OptionsResolver\Exception\InvalidArgumentException
     * @expectedExceptionMessage The given closure must define exactly one required parameter (the value to cast).
     *
     * @covers ::setCast
     */
    public function testSetCastClosureWithWrongNumberOfRequiredArgumentsFailure()
    {
        $this->optionsResolver->setDefined('test_option');
        $this->optionsResolver->setCast(
            'test_option',
            function ($value = null) {

                return null;
            }
        );
    }

    /**
     * Tests that the isCast method returns true when a cast is defined and false when it is not.
     *
     * @covers ::isCast
     */
    public function testIsCast()
    {
        /* Option not cast. */
        $this->optionsResolver->setDefined('test_option');
        $this->assertFalse($this->optionsResolver->isCast('test_option'));

        /* Option cast. */
        $this->optionsResolver->setCast('test_option', 'int');
        $this->assertTrue($this->optionsResolver->isCast('test_option'));
    }

    /**
     * Tests that the resolve method fails when a cast cannot be performed.
     *
     * @param string $option       Name of the option.
     * @param mixed  $allowedTypes Allowed type for that option.
     * @param string $cast         Type to cast the option to.
     * @param mixed  $value        Value of the option.
     *
     * @dataProvider invalidSetCastDataProvider
     *
     * @expectedException \EBT\OptionsResolver\Exception\ResolverException
     *
     * @covers ::setCast
     */
    public function testSetCastFailure($option, $allowedTypes, $cast, $value)
    {
        $this->optionsResolver->setDefined($option);
        $this->optionsResolver->setAllowedTypes($option, $allowedTypes);
        $this->optionsResolver->setCast($option, $cast);

        $this->optionsResolver->resolve([$option => $value]);
    }

    /**
     * Tests success cases for the resolve method when using setCast.
     *
     * @param string $option       Name of the option.
     * @param mixed  $allowedTypes Allowed type for that option.
     * @param string $cast         Type to cast the option to.
     * @param mixed  $value        Value of the option.
     * @param string $expectedType Expected option type after resolved.
     *
     * @dataProvider validSetCastDataProvider
     *
     * @covers ::setCast
     * @covers ::resolveCasts
     * @covers ::castToType
     */
    public function testSetCastSuccess($option, $allowedTypes, $cast, $value, $expectedType)
    {
        $this->optionsResolver->setDefined($option);
        $this->optionsResolver->setAllowedTypes($option, $allowedTypes);
        $this->optionsResolver->setCast($option, $cast);
        $this->optionsResolver->setDefined('option_not_to_cast');
        $options = [
            $option              => $value,
            'option_not_to_cast' => '1',
        ];

        $result = $this->optionsResolver->resolve($options);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey($option, $result);
        $this->assertArrayHasKey('option_not_to_cast', $result);
        $this->assertEquals($expectedType, gettype($result[$option]));
        $this->assertEquals('string', gettype($result['option_not_to_cast']));
    }

    /**
     * Tests that closure based casts work as expected.
     *
     * @covers ::setCast
     * @covers ::resolveCasts
     */
    public function testSetCastClosureSuccess()
    {
        $this->optionsResolver->setDefined('option1');
        $this->optionsResolver->setCast(
            'option1',
            function ($value) {

                return (int) $value;
            }
        );

        $result = $this->optionsResolver->resolve(['option1' => '123']);
        $this->assertEquals(['option1' => 123], $result);
    }

    /**
     * Tests that undefined options can be ignore if the appropriate flag is set, and are removed from the result.
     *
     * @covers ::resolve
     * @covers ::clearUndefinedOptions
     */
    public function testResolveUndefinedOptionsAllowed()
    {
        $options = [
            'option1' => 1,
            'option2' => 2,
        ];
        $this->optionsResolver->setRequired('option1');

        $result = $this->optionsResolver->resolve($options, true);
        $this->assertEquals(['option1' => 1], $result);
    }

    /**
     * Tests that undefined options still raise exceptions by default.
     *
     * @expectedException \EBT\OptionsResolver\Exception\ResolverException
     * @expectedExceptionMessage option2
     *
     * @covers ::resolve
     * @covers ::clearUndefinedOptions
     */
    public function testResolveUndefinedOptionsNotAllowed()
    {
        $options = [
            'option1' => 1,
            'option2' => 2,
        ];
        $this->optionsResolver->setRequired('option1');

        $this->optionsResolver->resolve($options);
    }

    /**
     * Tests that when cleared is called the defined casts are also removed.
     *
     * @covers ::clear
     */
    public function testClear()
    {
        $this->optionsResolver->setDefined('option1');
        $this->optionsResolver->setRequired('option1');
        $this->optionsResolver->setCast('option1', 'int');

        $this->assertTrue($this->optionsResolver->isDefined('option1'));
        $this->assertTrue($this->optionsResolver->isRequired('option1'));
        $this->assertTrue($this->optionsResolver->isCast('option1'));

        $this->optionsResolver->clear();
        $this->assertFalse($this->optionsResolver->isDefined('option1'));
        $this->assertFalse($this->optionsResolver->isRequired('option1'));
        $this->assertFalse($this->optionsResolver->isCast('option1'));
    }

    /**
     * Tests that when options are removed their defined type casts are also removed.
     *
     * @covers ::remove
     */
    public function testRemove()
    {
        $this->optionsResolver->setDefined('option1');
        $this->optionsResolver->setCast('option1', 'int');
        $this->optionsResolver->setDefined('option2');
        $this->optionsResolver->setCast('option2', 'int');

        $this->assertTrue($this->optionsResolver->isDefined('option1'));
        $this->assertTrue($this->optionsResolver->isCast('option1'));
        $this->assertTrue($this->optionsResolver->isDefined('option2'));
        $this->assertTrue($this->optionsResolver->isCast('option2'));

        $this->optionsResolver->remove('option1');
        $this->assertFalse($this->optionsResolver->isDefined('option1'));
        $this->assertFalse($this->optionsResolver->isCast('option1'));
        $this->assertTrue($this->optionsResolver->isDefined('option2'));
        $this->assertTrue($this->optionsResolver->isCast('option2'));
    }

    /**
     * Provider of invalid data for setCast tests.
     *
     * @return array;
     */
    public function invalidSetCastDataProvider()
    {
        return [
            ['test', 'int', 'int', '123a'],
            ['test', 'bool', 'bool', 'y'],
            ['test', 'bool', 'bool', '0.0'],
            ['test', 'bool', 'bool', '1.0'],
            ['test', 'int', 'int', '123.0'],
            ['test', 'int', 'int', 123.5],
            ['test', 'int', 'float', 123.5],
            [
                'test',
                'int',
                function ($value) {

                    return is_int($value) ? (int) $value : $value;
                },
                'not an integer',
            ],
        ];
    }

    /**
     * Provider of valid data for setCast tests.
     *
     * @return array;
     */
    public function validSetCastDataProvider()
    {
        return [
            ['test', 'bool', 'bool', 'true', 'boolean'],
            ['test', 'bool', 'bool', 'yes', 'boolean'],
            ['test', 'bool', 'bool', 1, 'boolean'],
            ['test', 'bool', 'bool', 1.0, 'boolean'],
            ['test', 'bool', 'bool', '1', 'boolean'],
            ['test', 'bool', 'bool', 'false', 'boolean'],
            ['test', 'bool', 'bool', 'no', 'boolean'],
            ['test', 'bool', 'bool', 0, 'boolean'],
            ['test', 'bool', 'bool', 0.0, 'boolean'],
            ['test', 'bool', 'bool', '0', 'boolean'],
            ['test', 'string', 'int', '123a', 'string'],
            ['test', 'int', 'int', 123.0, 'integer'],
            ['test', 'int', 'int', '10', 'integer'],
            ['test', 'int', 'int', '     10', 'integer'],
            ['test', 'int', 'int', '10     ', 'integer'],
            ['test', 'int', 'int', '          10         ', 'integer'],
            ['test', 'float', 'float', 123, 'double'],
            ['test', 'float', 'float', '10', 'double'],
            ['test', 'float', 'float', '10.5', 'double'],
            ['test', 'float', 'float', '     10.5', 'double'],
            ['test', 'float', 'float', '10.5     ', 'double'],
            ['test', 'float', 'float', '          10         ', 'double'],
            [
                'test',
                'bool',
                function ($value) {

                    return 'si' === $value;
                },
                'si',
                'boolean',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /* Create the options resolver. */
        $this->optionsResolver = new OptionsResolver();
    }
}
