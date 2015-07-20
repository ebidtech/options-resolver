# OptionsResolver
This projects extends [Symfony's OptionsResolver component](https://github.com/symfony/OptionsResolver), adding some useful features. For a list of all added features please see the **Usage** section of this readme.

## Installation

The recommended way to install is through composer.

Just create a `composer.json` file for your project:

``` json
{
    "require": {
        "ebidtech/options-resolver": "1.*"
    }
}
```

And run these two commands to install it:

```bash
$ curl -sS https://getcomposer.org/installer | php
$ composer install
```

Now you can add the autoloader, and you will have access to the library:

```php
<?php

require 'vendor/autoload.php';
```

## Usage

This component is used exactly in the same way of the original component. For a good reference about the original component please see its [documentation entry](http://symfony.com/doc/current/components/options_resolver.html).

The component is used as follows:
```php
// Original component instantiation, DON'T USE THIS.
// $options = new \Symfony\Component\OptionsResolver\OptionsResolver();

// Extended component instantiation, USE THIS.
$options = new EBT\OptionsResolver\Model\OptionsResolver\OptionsResolver();
```

### Option type casting

It is often useful to use Symfony's OptionResolver when dealing with API arguments, deserialization results, etc. However, sometimes values are given as string representations of their original values, for example, "123" instead of 123. In this case setting the allowed type to "int" won't validate, because in reality the value is a string.

To address this problem two methods have been created ```setCast($option, $cast)``` and ```isCast($option)```.

```php
//  Instantiates the options resolver and defines the option.
$options = new EBT\OptionsResolver\Model\OptionsResolver\OptionsResolver();
$options->setDefined('my_option');

$options->isCast('my_option'); // false, cast not set

// Marks "my_option" to be cast to bool. The cast will be applied before resolving the options.
$options->setCast('my_option', 'bool');

$options->isCast('my_option'); // true, cast already set
```

#### Allowed cast types

Currently the following cast types are allowed:
* int
* float
* bool

If an unsupported type cast is set an exception will be thrown. When a cast is set but the given **value is not castable** to that specific type, **the original value is kept**, and any additional validations are applied as normal.

**NOTE:** Currently type casting is handled with PHP's ```filter_var() method```. For additional examples of how it converts specific cases please check the [official documentation]() or this repository's tests.
