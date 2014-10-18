A complete guide to [Variadic 1.0][project]
===========================================

If you just want to use the new introduced variadic features, consider replacing all function calls `call_user_func` and `call_user_variadic_array` with `call_user_variadic` and `call_user_variadic_array` respectively and use the special variadic argument `$args` in your function head. In short, call

```php
<?php
$result = call_user_variadic_array($callback, $arguments);
// or
$result = call_user_variadic($callback, $arg1, $arg2, ...);
?>
```

That's it. As already noted in the [README](../README.md) both functions accept indexed arguments, where `call_user_variadic` uses a special syntax for writing key-value pairs as list of arguments:

```php
<?php
call_user_variadic($callback, 'arg1_name', $arg1, 'arg2_name', $arg2, ...);
?>
```

Please note, not all key-value pairs will be passed as such. It really depends on the callable object head e.g.

```php
<?php
function some_function($arg1_name, $args) {
	// process
}

$callback = 'some_function';
call_user_variadic($callback, 'arg1_name', $arg1, 'arg2_name', $arg2, ...);
?>
```

will pass the value `$arg1` to the parameter `$arg1_name`, whereas all other arguments can be found as an array in the special variadic argument parameter `$args`. For key-value pairs the order in the call doesn't matter, thus you can shuffle the array as you like as long as the key-value correspondence is kept intact. Also note, the special variadic argument parameter `$args` is always an array containing one or several arguments. Calls like

```php
<?php
function some_function($args) {
	// process
}

$callback = 'some_function';
call_user_variadic($callback, array($arg1));
call_user_variadic($callback, array($arg1, $arg2, ...));
call_user_variadic($callback, $arg1, $arg2, ...);
?>
```

will repopulate `$args` in the same way, but not

```php
<?php
call_user_variadic('some_function', array($arg1), $arg2, ...);
?>
```

(here the first argument will not be unpacked).

For special functions like

```php
<?php
function some_function($required_1, $optional = 2, $required_2) {
	// process
}
?>
```

you can write

```php
<?php
$callback = 'some_function';
call_user_variadic($callback, $arg_for_req1, $arg_for_req2);
call_user_variadic($callback, $arg_for_req1, $arg_for_opt, $arg_for_req2);
call_user_variadic($callback, 'required_1', $arg_for_req1, 'required_2', $arg_for_req2, $arg_for_opt);
call_user_variadic($callback, 'optional', $arg_for_opt, $arg_for_req1, $arg_for_req2);
?>
```

yielding all in the same callable object call.

## Class options

If you want to look under the hood and want to adjust some options, then it is advisable to create a new instance of the `Variadic` class

```php
<?php
$variadic = new Variadic();
?>
```

and use the class methods `call_user_variadic_array` and `call_user_variadic` instead of their respective global functions.

### A classic fallback

If you don't want the variadic features and the indexed argument support in `call_user_variadic`, then you can always set the properties

```php
<?php
$variadic->use_vargs = FALSE;
// and
$variadic->allow_flattened_kwargs = 'off';
// or
$variadic->flatten_kwargs('off');
?>
```

and subsequent calls of `call_user_variadic` and `call_user_variadic_array` will behave as the PHP built-in methods `call_user_func` and `call_user_variadic_array` respectively, except that **Variadic** will always check the number of arguments and throws an error, if more arguments are a passed to a callable object than it parameters has. If you want to know how many arguments a callable object has, call e.g.

```php
<?php
$params = $variadic->get_arg_names('round');
var_dump($params);
?>
```

and it will return an array of parameters each of type

```php
<?php
array(
	'type'  => 'required' or 'optional',
	'empty' => TRUE or FALSE,
	'value' => ...
);
?>
```

### Indexed argument support

The indexed argument support in `call_user_variadic` can be changed at any time with

```php
<?php
// $option can be one of 'on', 'off', 'once', 'toggle'
$variadic->flatten_kwargs($option);
?>
```

switching it `'on'` or `'off'`, temporally switch on (`'once'`) or temporally switch off (`'toggle'`) (the latter two for one `call_user_variadic` call). The current option can be retrieved by leaving out the `$option` parameter

```php
<?php
$flatten_kwargs = $variadic->flatten_kwargs();
?>
```

### Special variadic arguments

You already have seen the use of the special variadic argument in the beginning of the document. Here you can find some configurations you can make. First, you can change the name of the special variadic argument parameter. Just write

```php
<?php
$variadic->keyword = 'vargs';
?>
```

causing in all heads of callable objects, that the parameter `$vargs` instead of `$args` will be treated as the special variadic argument parameter.

Second, you can switch off/on the support of the the special variadic argument in the **Variadic** class with

```php
<?php
$variadic->use_vargs = TRUE;
// or
$variadic->use_vargs = FALSE;
?>
```

That's it.

---
Have fun!

Sommerregen

[project]: https://github.com/sommerregen/variadic/
