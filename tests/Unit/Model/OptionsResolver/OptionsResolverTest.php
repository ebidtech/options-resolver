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

class OptionsResolverTest extends BaseUnitTestCase
{
    /**
     * @var OptionsResolver
     */
    protected $optionsResolver;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /* Create the options resolver. */
        $this->optionsResolver = new OptionsResolver();
    }

    /**
     * Tests that an exception is thrown when trying to set a cast for an undefined exception.
     *
     * @expectedException        \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @expectedExceptionMessage The option "test_option" does not exist
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
     */
    public function testSetCastUnsupportedCastFailure()
    {
        $this->optionsResolver->setDefined('test_option');
        $this->optionsResolver->setCast('test_option', 'test_type');
    }

    /**
     * Tests that the isCast method returns true when a cast is defined and false when it is not.
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
     * @param string $option      Name of the option.
     * @param mixed $allowedTypes Allowed type for that option.
     * @param string $cast        Type to cast the option to.
     * @param mixed  $value       Value of the option.
     *
     * @dataProvider invalidSetCastDataProvider
     *
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testSetCastFailure($option, $allowedTypes, $cast, $value)
    {
        $this->optionsResolver->setDefined($option);
        $this->optionsResolver->setAllowedTypes($option, $allowedTypes);
        $this->optionsResolver->setCast($option, $cast);

        $this->optionsResolver->resolve(array($option => $value));
    }

    /**
     * Tests success cases for the resolve method when using setCast.
     *
     * @param string $option       Name of the option.
     * @param mixed $allowedTypes  Allowed type for that option.
     * @param string $cast         Type to cast the option to.
     * @param mixed  $value        Value of the option.
     * @param string $expectedType Expected option type after resolved.
     *
     * @dataProvider validSetCastDataProvider
     */
    public function testSetCastSuccess($option, $allowedTypes, $cast, $value, $expectedType)
    {
        $this->optionsResolver->setDefined($option);
        $this->optionsResolver->setAllowedTypes($option, $allowedTypes);
        $this->optionsResolver->setCast($option, $cast);

        $result = $this->optionsResolver->resolve(array($option => $value));

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey($option, $result);
        $this->assertEquals($expectedType, gettype($result[$option]));
    }

    /**
     * Provider of invalid data for setCast tests.
     *
     * @return array;
     */
    public function invalidSetCastDataProvider()
    {
        return array(
            array('test', 'int', 'int', '123a'),
            array('test', 'bool', 'bool', 'y'),
            array('test', 'bool', 'bool', '0.0'),
            array('test', 'bool', 'bool', '1.0'),
            array('test', 'int', 'int', '123.0'),
            array('test', 'int', 'int', 123.5),
            array('test', 'int', 'float', 123.5),
        );
    }

    /**
     * Provider of valid data for setCast tests.
     *
     * @return array;
     */
    public function validSetCastDataProvider()
    {
        return array(
            array('test', 'bool', 'bool', 'true', 'boolean'),
            array('test', 'bool', 'bool', 'yes', 'boolean'),
            array('test', 'bool', 'bool', 1, 'boolean'),
            array('test', 'bool', 'bool', 1.0, 'boolean'),
            array('test', 'bool', 'bool', '1', 'boolean'),
            array('test', 'bool', 'bool', 'false', 'boolean'),
            array('test', 'bool', 'bool', 'no', 'boolean'),
            array('test', 'bool', 'bool', 0, 'boolean'),
            array('test', 'bool', 'bool', 0.0, 'boolean'),
            array('test', 'bool', 'bool', '0', 'boolean'),
            array('test', 'string', 'int', '123a', 'string'),
            array('test', 'int', 'int', 123.0, 'integer'),
            array('test', 'int', 'int', '10', 'integer'),
            array('test', 'int', 'int', '     10', 'integer'),
            array('test', 'int', 'int', '10     ', 'integer'),
            array('test', 'int', 'int', '          10         ', 'integer'),
            array('test', 'float', 'float', 123, 'double'),
            array('test', 'float', 'float', '10', 'double'),
            array('test', 'float', 'float', '10.5', 'double'),
            array('test', 'float', 'float', '     10.5', 'double'),
            array('test', 'float', 'float', '10.5     ', 'double'),
            array('test', 'float', 'float', '          10         ', 'double'),
        );
    }
}
