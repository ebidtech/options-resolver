<?php

/**
 * OptionsResolver.php
 *
 * Unauthorized copying or dissemination of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 *
 * @author     Diogo Teixeira <diogo.teixeira@emailbidding.com>
 * @copyright  Copyright (C) Wondeotec SA - All Rights Reserved
 * @license    LICENSE.txt
 */

namespace EBT\OptionsResolver\Model\OptionsResolver;

use EBT\Validator\Model\Validator\Validator;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class OptionsResolver extends \Symfony\Component\OptionsResolver\OptionsResolver
{
    /* Valid cast types. */
    const TYPE_CAST_INT    = 'int';
    const TYPE_CAST_FLOAT  = 'float';
    const TYPE_CAST_BOOL   = 'bool';

    /**
     * Contains all valid types for cast.
     *
     * @var array
     */
    protected $validTypes = array(
        self::TYPE_CAST_INT,
        self::TYPE_CAST_FLOAT,
        self::TYPE_CAST_BOOL,
    );

    /**
     * Defines type casts to perform before value validation.
     *
     * @var array
     */
    protected $casts = array();

    /**
     * {@inheritDoc}
     */
    public function remove($optionNames)
    {
        parent::remove($optionNames);

        /* This must be done after the parent to ensure that the parent's locked state is enforced. */
        foreach ((array) $optionNames as $option) {
            unset($this->casts[$option]);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        parent::clear();

        /* This must be done after the parent to ensure that the parent's locked state is enforced. */
        $this->casts = array();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(array $options = array())
    {
        /* Apply any type casts before resolving. */
        $options = $this->resolveCasts($options);

        /* Resolve the options. */
        return parent::resolve($options);
    }

    /**
     * Sets a cast for a specific option. The given option will be cast to the given type before any validation, if
     * possible.
     *
     * @param string $option Option name.
     * @param string $type   Type to cast the option to.
     *
     * @throws UndefinedOptionsException
     * @throws InvalidArgumentException
     */
    public function setCast($option, $type)
    {
        /* Ensure the option exists: not very important, but ensures consistent behavior with underlying code. */
        if (! $this->isDefined($option)) {
            throw new UndefinedOptionsException(
                sprintf(
                    'The option "%s" does not exist. Defined options are: "%s".',
                    $option,
                    implode('", "', $this->getDefinedOptions())
                )
            );
        }

        /* Ensure that a valid type to cast to is given. */
        if (! Validator::isRequiredExistingValue($type, $this->validTypes)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The type cast "%s" does not exist. Defined type casts are: "%s".',
                    $type,
                    implode('", "', $this->validTypes)
                )
            );
        }

        /* Add the type cast. */
        $this->casts[$option] = $type;
    }

    /**
     * Resolves a variable casts in the initial options.
     *
     * @param array $options Options to cast.
     *
     * @return array The casted options.
     */
    protected function resolveCasts(array $options = array())
    {
        /* Create a copy of the options to work on. */
        $castedOptions = $options;

        /* Iterate every options and cast as needed. */
        foreach ($options as $option => $value) {

            /* No cast was defined, continue. */
            if (! isset($this->casts[$option])) {

                continue;
            }
            $type = $this->casts[$option];

            $castedOptions[$option] = $this->castToType($value, $type);
        }

        return $castedOptions;
    }

    /**
     * Applies specific cast logic for each type.
     *
     * @param mixed  $value Value to cast.
     * @param string $type  Type to cast to.
     *
     * @return mixed Casted value.
     */
    protected function castToType($value, $type)
    {
        switch ($type) {
            case self::TYPE_CAST_INT:
                $newValue = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
                $value = (null === $newValue) ? $value : $newValue;
                break;
            case self::TYPE_CAST_BOOL:
                $newValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $value = (null === $newValue) ? $value : $newValue;
                break;
            case self::TYPE_CAST_FLOAT:
                $newValue = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                $value = (null === $newValue) ? $value : $newValue;
                break;
        }

        return $value;
    }
}
