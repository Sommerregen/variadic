[Variadic 1.0][project]
==========================================

> A class for handling variadic functions with a variable number of arguments for PHP >= 5.3.

[![Build Status](https://secure.travis-ci.org/sommerregen/variadic.png)](http://travis-ci.org/sommerregen/variadic) [Changelog](CHANGELOG.md)

## About

[Variadic functions][definition] are functions which take a variable number of arguments. As of this <abbr title="Request for Comments">[RFC][variadics]</abbr>, developed by [Nikita Popov][popov], this feature will be part of [PHP 5.6][php5.6].

Variadic functions allow you to capture a variable number of arguments to a function, combined with "normal" arguments passed in if you like. It's easiest to see with an example:

```php
<?php
function concatenate($transform, ...$strings) {
	$string = '';
	foreach ( $strings as $piece ) {
		$string .= $piece;
	}
	return $transform($string);
}

echo concatenate("strtoupper", "I'd ", "like ",
	4 + 2, " apples");
?>
```

The parameters list in the function declaration has the ... operator (referred to as either the _splat operator_ or the _scatter operator_) in it, and it basically means " ... and everything else should go into $strings". You can pass 2 or more arguments into this function and the second and subsequent ones will be added to the $strings array, ready to be used.

Variadic functions are already possible in PHP and have been throughout 4.x and 5.x in the form of `func_get_args()`, which is pretty gross. It's used for functions where you want to have an unlimited number of functions like the example from above:

```php
<?php
function concatenate($transform) {
	$strings = func_get_args();
	// Remove $transform from the array of arguments
	array_shift($strings);
	$strings = reset($strings);		// Faffing...

	$string = '';
	foreach ( $strings as $piece ) {
		$string .= $piece;
	}
	return $transform($string);
}

echo concatenate("strtoupper", "I'd ", "like ",
	4 + 2, " apples");
?>
```

This super-trivial example shows off the difference between the two approaches:

* The new variadic syntax would make it strikingly obvious what is happening in the function declaration, which is where arguments come from. Furthermore it makes it a lot easier to write self documenting code.
* See the faffing comment? With the new variadic syntax there is no need for horrendous faffing arounds with `array_shift` and Co.

Now here comes the reason why this project come into life:

> *Variadic* tries to incorporate the new variadic syntax for PHP >= 5.3 and implements a wrapper for variadic functions. Focus was put on easy use and simple integration in other projects.

## Getting Started

You have two options for adding **Variadic** to your project:

* [Download the latest release][download] or
* Clone the repository: `git clone git://github.com/sommerregen/variadic.git`

## How to use

The only file required is `Variadic.php`, so copy that to your include directory.

The typical flow of **Variadic** is to call the built-in functions `call_user_variadic` or `call_user_variadic_array` like the PHP built-in functions `call_user_func` and `call_user_func_array` and define a function like:

```php
<?php
require('Variadic.php');

function concatenate($transform, $args) {
	$string = '';
	foreach ( $args as $piece ) {
		$string .= $piece;
	}
	return $transform($string);
}

echo call_user_variadic('concatenate', "strtoupper", "I'd ",
	"like ", 4 + 2, " apples") . "<br>";
// or
echo call_user_variadic_array('concatenate', array("strtoupper",
	"I'd ", "like ", 4 + 2, " apples"));
// both result in "I'D LIKE 6 APPLES"
?>
```

Note the subtleties between this syntax and the new variadic syntax: The splat operator was omitted and instead of using `$strings` as a parameter we use the special variadic argument `$args`, which will incorporate all nice features from the new syntax.

**Variadic** comes with other useful extensions. It repopulates the callable objects parameter from an indexed array of arguments, e.g.

```php
<?php
echo call_user_variadic_array('concatenate', array("I'd ",
	"like ", 4 + 2, " apples", 'transform' => "strtoupper"));
// result: "I'D LIKE 6 APPLES"
?>
```

As you can see, it doesn't even matter where indexed arguments appear, because all index arguments are delegated to their respective parameters of the function. You can also pass index arguments to `call_user_variadic` using a special syntax, where the name of the callable object's parameter always comes first, followed by its value (here: the parameter name 'transform' marks the key and "strtoupper" the value). The rest is passed to the `$args` parameter:

```php
<?php
echo call_user_variadic('concatenate', 'transform', "strtoupper",
	'args', array("I'd ", "like ", 4 + 2, " apples"));
// result: "I'D LIKE 6 APPLES"
?>
```

Please note, when changing the head e.g. `function concatenate($transform, $args)` to `function concatenate($transform, $strings)`, then the above examples won't apply anymore. This is due to the special role of the `$args` parameter (here referred to as _special variadic argument_). You can change the special variadic argument name by creating an instance of `Variadic` and set the property `keyword` to any name you like, i.e.

```php
<?php

$variadic = new Variadic();
$variadic->keyword = 'strings';
?>
```

You can adjust many more settings. If you want to do so, please read the [documentation](docs/documentation.md) for a complete guide of usage.

## Contributing

You can contribute at any time! Before opening any issue, please search for existing issues and review the [guidelines for contributing](CONTRIBUTING.md).

After that please note:

* If you find a bug or would like to make a feature request or suggest an improvement, [please open a new issue][issues]. If you have any interesting ideas for additions to the syntax please do suggest them as well!
* Feature requests are more likely to get attention if you include a clearly described use case.
* If you wish to submit a pull request, please make again sure that your request match the [guidelines for contributing](CONTRIBUTING.md) and that you keep track of adding unit tests for any new or changed functionality.

### Support and donations

Feel free to support me at any time. Donations keep this project alive. You can always [![Flattr](https://api.flattr.com/button/flattr-badge-large.png)][flattr] or send me some bitcoins to 1HQdy5aBzNKNvqspiLvcmzigCq7doGfLM4 .

## License

Copyright (c) 2014 [Benjamin Regler][github]. See also the list of [contributors] who participated in this project. Dual-licensed for use under the terms of the [MIT license][mit-license] or [GPLv3 license][gpl-license] (license terms can be also be found in [LICENSE](LICENSE)).

[project]: https://github.com/sommerregen/variadic/

[definition]: https://en.wikipedia.org/wiki/Variadic_function
[def-glossar]: http://cplus.about.com/od/glossar1/g/variadicdefn.htm

[variadics]: https://wiki.php.net/rfc/variadics
[popov]: https://twitter.com/nikita_ppv
[php5.6]: http://php.net/manual/de/migration56.new-features.php#migration56.new-features.variadics "PHP 5.6 Feature list"

[download]: https://github.com/sommerregen/variadic/zipball/master "Download Variadic"
[issue-guidelines]: https://github.com/necolas/issue-guidelines "Issue Guidelines by Nicolas Gallagher"
[issues]: https://github.com/sommerregen/variadic/issues "GitHub Issues for Variadic"
[pull-requests]: https://github.com/sommerregen/variadic/pulls "GitHub pull requests for Variadic"

[github]: https://github.com/sommerregen/ "GitHub account from Benjamin Regler"
[contributors]: https://github.com/sommerregen/variadic/blob/master/contributors "List of contributors to the project"

[mit-license]: http://www.opensource.org/licenses/mit-license.php "MIT license"
[gpl-license]: http://opensource.org/licenses/GPL-3.0 "GPL-3.0 license"

[me]: mailto:sommergen@benjamin-regler.de
[flattr]: https://flattr.com/submit/auto?user_id=Sommerregen&url=https://github.com/sommerregen/variadic "Flatter my GitHub project"
