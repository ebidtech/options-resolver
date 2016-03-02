<?php

/**
 * This file is a part of the Validator library.
 *
 * (c) 2015 Ebidtech
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EBT\OptionsResolver\Model\OptionsResolver;

use EBT\OptionsResolver\Exception\ResolverException;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

/**
 * EBT\OptionsResolver\Model\OptionsResolver\OptionsResolver
 */
class OptionsResolver extends \Symfony\Component\OptionsResolver\OptionsResolver
{
    const TYPE_CAST_INT = 'int';
    const TYPE_CAST_BOOL = 'bool';
    const TYPE_CAST_FLOAT = 'float';

    /**
     * @var array
     */
    protected $casts = [];

    /**
     * @var array
     */
    protected $validCastTypes = [
        self::TYPE_CAST_INT,
        self::TYPE_CAST_FLOAT,
        self::TYPE_CAST_BOOL,
    ];

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
        $this->casts = [];

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Merges options with the default values stored in the container and validates them.
     *
     * Exceptions are thrown if:
     *  - Undefined options are passed;
     *  - Required options are missing;
     *  - Options have invalid types;
     *  - Options have invalid values.
     *
     * @param array $options               A map of option names to values.
     * @param bool  $allowUndefinedOptions If set to true undefined options will not raise an error.
     *
     * @return array Merged and validated options.
     *
     * @throws ResolverException
     */
    public function resolve(array $options = [], $allowUndefinedOptions = false)
    {
        $options = $this->clearUndefinedOptions($options, $allowUndefinedOptions);
        $options = $this->resolveCasts($options);

        try {

            return parent::resolve($options);
        } catch (\Exception $e) {

            throw new ResolverException(
                sprintf('An error occurred while resolving the options: %s', $e->getMessage())
            );
        }
    }

    /**
     * Sets a cast for a specific option. The given option will be cast to the given type before any validation, if
     * possible.
     *
     * @param string          $option Option name.
     * @param string|\Closure $type   Type to cast the option to or a closure to be lazily evaluated.
     *
     * @return OptionsResolver Returns itself.
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

        /* Check if a closure was passed as argument. */
        if ($type instanceof \Closure) {
            $refClosure = new \ReflectionFunction($type);

            /* The closure must have exactly one parameter, and it should be required. */
            if (2 !== $refClosure->getNumberOfParameters() + $refClosure->getNumberOfRequiredParameters()) {
                throw new InvalidArgumentException(
                    'The given closure must define exactly one required parameter (the value to cast).'
                );
            }
            $this->casts[$option] = $type;

            return $this;
        }

        /* Ensure that a valid type to cast to is given. */
        if (! in_array($type, $this->validCastTypes)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The type cast "%s" does not exist. Defined type casts are: "%s".',
                    $type,
                    implode('", "', $this->validCastTypes)
                )
            );
        }

        /* Add the type cast. */
        $this->casts[$option] = $type;

        return $this;
    }

    /**
     * Checks whether an option was marked for type casting.
     *
     * @param string $option Name of the option.
     *
     * @return bool TRUE is the option is marked for casting, FALSE otherwise.
     */
    public function isCast($option)
    {
        return isset($this->casts[$option]);
    }

    /**
     * Clears undefined options before resolving, if the appropriate flag is set.
     *
     * @param array $options               Map of option names to values.
     * @param bool  $allowUndefinedOptions If set to true undefined options will not raise an error.
     *
     * @return array Clean options.
     */
    protected function clearUndefinedOptions(array $options, $allowUndefinedOptions)
    {
        if (! $allowUndefinedOptions) {

            return $options;
        }

        /* Find out undefined options by computing the difference between given and defined options. */
        $undefinedOptions = array_diff(
            array_keys($options),
            $this->getDefinedOptions()
        );

        /* Remove undefined options by computing the difference between given and undefined options. */

        return array_diff_key(
            $options,
            array_flip($undefinedOptions)
        );
    }

    /**
     * Resolves a variable casts in the initial options.
     *
     * @param array $options Options to cast.
     *
     * @return array Options after applying the relevant casts.
     */
    protected function resolveCasts(array $options = [])
    {
        /* Create a copy of the options to work on. */
        $castOptions = $options;

        /* Iterate every options and cast as needed. */
        foreach ($options as $option => $value) {

            /* No cast was defined, continue. */
            if (! isset($this->casts[$option])) {

                continue;
            }
            $type = $this->casts[$option];

            $castOptions[$option] = ($type instanceof \Closure)
                ? $type($value)
                : $this->castToType($value, $type);
        }

        return $castOptions;
    }

    /**
     * Applies specific cast logic for each type.
     *
     * @param mixed  $value Value to cast.
     * @param string $type  Type to cast to.
     *
     * @return mixed Cast value.
     */
    protected function castToType($value, $type)
    {
        switch ($type) {
            case self::TYPE_CAST_INT:
                $newValue = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
                $value    = (null === $newValue) ? $value : $newValue;
                break;
            case self::TYPE_CAST_BOOL:
                $newValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $value    = (null === $newValue) ? $value : $newValue;
                break;
            case self::TYPE_CAST_FLOAT:
                $newValue = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                $value    = (null === $newValue) ? $value : $newValue;
                break;
        }

        return $value;
    }
}
