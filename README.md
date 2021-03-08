# PHPCS Type Sniff

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

Custom `phpcs` [CodeSniffer][link-phpcs] rule that:

- Enforces usage of PHP 7 type declarations (where possible)
- Enforces documenting array types with more specific types (e.g. `int[]`)
- Checks for useless PHPDoc blocks (no repeated information)
- Many more other checks

Example PHP class (comments on the right = `phpcs` warnings):

```php
<?php

namespace Fruits;

/**
 * Class Banana                     // useless description
 * @package Fruits                  // useless tag
 */
class Banana
{
    const C1 = [];                  // missing typed array doc type

    /** @var array */               // must use typed array doc type
    const C2 = [];

    /** @var array[] */             // must use specific typed array doc type
    const C3 = [[]];

    /** @var bool|false */          // redundant false type
    const C4 = false;

    /**
     * @var int                     // incompatible int type, missing null type
     */
    const C5 = null;

    /** @var int */
    const C6 = 1;                   // useless PHPDoc

    public $prop1 = [];             // missing typed array doc type

    /** @var array */               // must use typed array doc type
    public $prop2 = [];

    public $prop3;                  // missing @var tag, missing null doc type

    /** @var */                     // missing doc type, missing null doc type
    public $prop4;

    /** @var array[][] */           // must use specific typed array doc type, missing null doc type
    public $prop5;

    /** @var array|string[] */      // redundant array type, missing null doc type
    public $prop6;

    /** @var int|string */          // missing null doc type
    public $prop7 = null;

    /** @var int $prop8 */          // prop name must be removed, missing null doc type
    public $prop8;

    /** @var int */
    public $prop9;                  // Not initialized, missing null doc type

    /** @var int */
    public $prop10;                 // Initialized, missing null doc type

    /** @var int */
    public $prop11;

    /** @var int */
    public $prop12;

    public function __construct()
    {
        $this->prop10 = null;
        $this->prop11 = 11;
        $this->setProp12();
    }

    public function setProp12(): void
    {
        $this->prop12 = 1;
    }

    public function func1(
        $param1,                    // missing param type decl.
        int $param2
    ) {                             // missing return type decl.
    }

    /**
     * @param int|null  $param1
     * @param int|null  $param2
     * @param array     $param3     // must use typed array doc type
     *
     * @param           $param5     // suggested int doc type
     * @param           $param6     // missing doc type
     * @param array[]   $param7     // must use specific typed array doc type
     * @param bool|true $param8     // remove true doc type
     * @param null      $param9     // suggested compound doc type, e.g. int|null
     * @param string    $param10    // incompatible string type, missing int, null types
     * @param stdClass  $param11
     * @param bool|int  $param12
     *
     * @return void                 // useless tag
     */
    public function func2(
        $param1,                    // suggested ?int type decl.
        int $param2 = null,         // suggested ?int type decl.
        array $param3,
        $param4,                    // missing @param tag
        int $param5,
        $param6,
        array $param7,
        bool $param8,
        $param9 = null,             // missing type decl.
        ?int $param10 = null,
        stdClass $param11,
        $param12
    ): void {
    }

    /**
     * @return int
     */
    public function func3(): int    // useless PHPDoc
    {
    }

    /**
     * @param array<int, bool>           $arg1 // alternative array documentation
     * @param array{foo: bool, bar: int} $arg2 // supported, no warning
     * @param (int|string)[]             $arg3 //
     * @param array('key1' => int, ...)  $arg4 //
     */
    public function func4(
        array $arg1,
        array $arg2,
        array $arg3,
        array $arg4
    ): void {
    }
}
```

## Install

Via Composer

```bash
$ composer require --dev gskema/phpcs-type-sniff
```

## Usage

This is a standalone sniff file, you need to add it to your `phpcs.xml` file.

### Usage Without Reflection

Inspections for methods with `@inheritdoc` tag are skipped.
If a method does not have this tag, it is inspected. **This is the recommend setup**.

```xml
<ruleset name="your_ruleset">
    <!-- your configuration -->
    <rule ref="PSR2"/>

    <!-- phpcs-type-sniff configuration -->   
    <rule ref="./vendor/gskema/phpcs-type-sniff/src/Sniffs/CompositeCodeElementSniff.php"/>
</ruleset>
```

### Usage With Reflection

With reflection enabled, this sniff can assert if `@inheritoc` tag
is needed. Inspections for extended/implemented methods are skipped.
Reflections need to load actual classes, which is why we need to include
the autoloader. This option is good for inspecting extended methods, however using `ReflectionClass` may
cause `phpcs` crashes while editing (not possible to catch `FatalError`).

```xml
<ruleset name="your_ruleset">
    <!-- your configuration -->
    <rule ref="PSR2"/>

    <!-- phpcs-type-sniff configuration -->   
    <autoload>./vendor/autoload.php</autoload>
    <rule ref="./vendor/gskema/phpcs-type-sniff/src/Sniffs/CompositeCodeElementSniff.php">
        <properties>
            <property name="useReflection" value="true"/>
        </properties>
    </rule>
</ruleset>
```

## PHPCS Baseline

It may be hard to integrate new rules/standards into old projects because too many warnings
may be detected. If you would like to fix them later, but use the standard for all new code,
you can "save" warnings detected on current code, then ignore them on subsequent builds.
The code standard remains the same, but on subsequent builds you "subtract" the old (baseline)
warnings:
```shell
# Add this configuration option to phpcs.xml (see [configuration](#Configuration)):
# <property name="addViolationId" value="true"/>

# Generate report with ignored warnings.
# You may want to commit this file to your repository until you fix all the warnings.
# You may also update this file once in a while. 
./vendor/bin/phpcs --standard=phpcs.xml --report=checkstyle --report-file=baseline.xml

# Run you main code style check command (on build) to generate a report.
# This will contain all warnings, the ignored errors will be subtracted using a command below.
./vendor/bin/phpcs --standard=phpcs.xml --report=checkstyle --report-file=report.xml

# Run a custom PHP script (on build) that subtracts ignored warnings.
# First argument is the baseline report file with ignored warnings,
# second argument is target report file (that was just built).
php phpcs_baseline.php ./baseline.xml ./report.xml
```

**Note**: This only works for `Gskema.Sniffs.CompositeCodeElement.*` warnings, because
`violationId` is used to recognize old warnings.

## Configuration

Sniffs are registered and saved by their short class name.
This allows easily specifying configuration options for a specific code element sniff,
e.g. `FqcnMethodSniff.invalidTags`. All custom code sniff classes must have unique
short class names.

String `true/false` values are automatically converted to booleans.

```xml
<ruleset name="your_ruleset">
    <!-- your configuration -->
    <rule ref="PSR2"/>

    <!-- phpcs-type-sniff configuration -->   

    <!-- Includes an autoloader which is needed when using reflection API -->
    <!-- or custom code element sniff(s) -->
    <autoload>./vendor/autoload.php</autoload>

    <!-- Includes a standalone sniff to your custom coding standard -->
    <rule ref="./vendor/gskema/phpcs-type-sniff/src/Sniffs/CompositeCodeElementSniff.php">
        <properties>

            <!-- Enables usage of reflection API when inspecting extended classes. -->
            <!-- Autoloader is needed. -->
            <property name="useReflection" value="true"/>

            <!-- Appends violation ID to each error/warning. Default is false. -->
            <!-- ID is useful for tracking old errors (baselines), -->
            <!-- which you may want to ignore on build, but not in IDE. -->
            <property name="addViolationId" value="true"/>

            <!-- Disables one of the default code element sniffs -->
            <property name="FqcnConstSniff.enabled" value="false" />
            <property name="FqcnMethodSniff.enabled" value="false" />
            <property name="FqcnPropSniff.enabled" value="false" />
            <property name="FqcnDescriptionSniff.enabled" value="false" />

            <!-- Change violation report type for all sniffs. Default is warning. -->
            <property name="reportType" value="error" />

            <!-- Or change violation report type for individual sniffs. Default is warning. -->
            <property name="FqcnConstSniff.reportType" value="error" />
            <property name="FqcnMethodSniff.reportType" value="error" />
            <property name="FqcnPropSniff.reportType" value="warning" />
            <property name="FqcnDescriptionSniff.reportType" value="warning" />

            <!-- Tags that should be removed from method PHPDoc -->
            <property name="FqcnMethodSniff.invalidTags" type="array">
                <element value="@someTag1"/>
                <element value="@someTag2"/>
            </property>

            <!-- Description lines and tags that should be removed from FQCN PHPDoc -->
            <property name="FqcnDescriptionSniff.invalidPatterns" type="array">
                <element value="^Nothing.+Useful$"/>
            </property>
            <property name="FqcnDescriptionSniff.invalidTags" type="array">
                <element value="@api"/>
            </property>

            <!-- Disables reporting missing @param, @return tags in non-empty method PHPDoc -->
            <!-- when method type declarations are present -->
            <property name="FqcnMethodSniff.reportMissingTags" value="false"/>

            <!-- Disables reporting missing null type in basic getter return PHPDoc -->
            <!-- and return type declaration -->
            <property name="FqcnPropSniff.reportNullableBasicGetter" value="false"/>

            <!-- Disables reporting missing null type in property PHPDoc -->
            <!-- when there is no default value and no assigned value in __construct() -->
            <!--- Some false positives in exotic cases may be reported -->
            <property name="FqcnPropSniff.reportUninitializedProp" value="false"/>

            <!-- Your own custom code element sniff(s). Autoloader is needed. -->
            <!-- These classes implement CodeElementSniffInterface -->
            <property name="sniffs" type="array">
                <element value="\Acme\CustomCodeElementSniff" />
                <element value="\Acme\AnotherCustomMethodSniff" />
            </property>

            <!-- Configuration options for custom code element sniffs -->
            <property name="CustomCodeElementSniff.opt1" value="str1" />
            <!-- Specifying element key(s) will create an associative array -->
            <property name="AnotherCustomMethodSniff.arrayOpt1" type="array">
                <element key="key1" value="str1"/>
                <element key="key2" value="str2"/>
            </property>

        </properties>
    </rule>
</ruleset>
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/gskema/phpcs-type-sniff.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/gskema/phpcs-type-sniff/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/gskema/phpcs-type-sniff.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/gskema/phpcs-type-sniff.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/gskema/phpcs-type-sniff.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/gskema/phpcs-type-sniff
[link-travis]: https://travis-ci.org/gskema/phpcs-type-sniff
[link-scrutinizer]: https://scrutinizer-ci.com/g/gskema/phpcs-type-sniff/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/gskema/phpcs-type-sniff
[link-downloads]: https://packagist.org/packages/gskema/phpcs-type-sniff
[link-phpcs]: https://github.com/squizlabs/PHP_CodeSniffer
