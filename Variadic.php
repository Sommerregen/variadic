<?php
/**
 * Variadic 1.0
 *
 * A class for handling variadic functions with a variable number of
 * arguments for PHP >= 5.3.
 *
 * Licensed under MIT or GPLv3, see LICENSE.
 *
 * @package 	Variadic
 * @version 	1.0
 * @link 			<https://github.com/sommerregen/variadic>
 * @author 		Benjamin Regler <sommergen@benjamin-regler.de>
 * @copyright 2014, Benjamin Regler
 * @license 	<http://opensource.org/licenses/GPL-3.0> 	GPL-3.0
 * @license 	<http://opensource.org/licenses/MIT> 			MIT
 */

/**
 * Variadic
 *
 * A class for handling variadic functions with a variable number of
 * arguments.
 */
class Variadic {

	/** --------
	 * Constants
	 * ---------
	 */

	/**
	 * Constant. Used in @get_type() to determine the type of the callable
	 * object (here: simple callback).
	 *
	 * @access public
	 * @var 	 string
	 */
	const FUNCTION_SIMPLE = 'function';

	/**
	 * Constant. Used in @get_type() to determine the type of the callable
	 * object (here: object method call).
	 *
	 * @access public
	 * @var 	 string
	 */
	const OBJECT_METHOD = 'method';

	/**
	 * Constant. Used in @get_type() to determine the type of the callable
	 * object (here: static class method call).
	 *
	 * @access public
	 * @var 	 string
	 */
	const STATIC_METHOD = 'static';

	/**
	 * Constant. Used in @get_type() to determine the type of the callable
	 * object (here: relative static class method call).
	 *
	 * @access public
	 * @var 	 string
	 */
	const RELATIVE_STATIC_METHOD = 'relative';

	/**
	 * Constant. Used in @call_user_variadic() to turn arguments into an
	 * indexed array of name/value pairs (see @flatten_kwargs()).
	 *
	 * Allow key-value pairs i.e. call_user_variadic('value', 2).
	 *
	 * @access public
	 * @var 	 string
	 */
	const FLATTEN_KWARGS_ON = 'on';

	/**
	 * Constant. Used in @call_user_variadic() to turn arguments into an
	 * indexed array of name/value pairs (see @flatten_kwargs()).
	 *
	 * Disable key-value pair support in call_user_variadic().
	 *
	 * @access public
	 * @var 	 string
	 */
	const FLATTEN_KWARGS_OFF = 'off';

	/**
	 * Constant. Used in @call_user_variadic() to turn arguments into an
	 * indexed array of name/value pairs (see @flatten_kwargs()).
	 *
	 * Allow key-value pairs in call_user_variadic() only once.
	 *
	 * @access public
	 * @var 	 string
	 */
	const FLATTEN_KWARGS_ONCE = 'once';

	/**
	 * Constant. Used in @call_user_variadic() to turn arguments into an
	 * indexed array of name/value pairs (see @flatten_kwargs()).
	 *
	 * Disable key-value pair support in call_user_variadic() only once.
	 *
	 * @access public
	 * @var 	 string
	 */
	const FLATTEN_KWARGS_TOGGLE = 'toggle';

	/** ----------------
	 * Public properties
	 * -----------------
	 */

	/**
	 * Switch to turn on/off that variable number of arguments should be
	 * parsed to the function as an array or not.
	 *
	 * @access public
	 * @var 	 boolean
	 */
	public $use_vargs = TRUE;

	/**
	 * The keyword (name of the parameter) to pass on the variable number
	 * of arguments.
	 *
	 * @access public
	 * @var 	 string
	 */
	public $keyword = 'args';

	/**
	 * Allow flattened key-value pairs in the function call
	 * call_user_variadic() e.g. call_user_variadic('value', 2).
	 *
	 * @access public
	 * @var 	 string
	 */
	public $allow_flattened_kwargs;


	/** ---------------------------
	 * Private/protected properties
	 * ----------------------------
	 */

	/**
	 * Static cache for storing informations about the parameters of a
	 * callable object.
	 *
	 * @access private
	 * @var 	 array
	 */
	protected static $_cache;

	/** --------------------
	 * Initialization method
	 * ---------------------
	 */

	/**
	 * Initialization of the Variadic class.
	 *
	 * @param string $allow_flattened_kwargs 	Set the option whether to
	 *                                        parse flattened key-value
	 *                                        pairs in call_user_variadic()
	 *                                        or not (see @flatten_kwargs).
	 */
	public function __construct($allow_flattened_kwargs = '') {
		// Initialize cache
		if ( is_null(self::$_cache) ) {
			self::$_cache = array();
		}

		// Set option for call_user_variadic()
		if ( strlen($allow_flattened_kwargs) == 0 ) {
			$allow_flattened_kwargs = static::FLATTEN_KWARGS_ON;
		}
		$this->flatten_kwargs($allow_flattened_kwargs);
	}

	/** -------------
	 * Public methods
	 * --------------
	 */

	/**
	 * Get the type of the callable.
	 *
	 * This can be one of the following:
	 * 		FUNCTION_SIMPLE 				= 'function'
	 * 	 	OBJECT_METHOD 					= 'method'
	 * 	 	STATIC_METHOD 					= 'static'
	 * 	 	RELATIVE_STATIC_METHOD 	= 'relative'.
	 *
	 * @param  callable $callback The callable to get the type for.
	 * @return integer 					  Returns the type of the callback, or
	 *                            NULL on error.
	 */
	public function get_type($callback) {
		// Check if $callback is a callable function
		if ( !is_callable($callback) ) {
			return;
		}

		if ( is_string($callback) ) {
			if ( stristr($callback, '::') === FALSE ) {
				// Type 1: Simple callback
				return static::FUNCTION_SIMPLE;
			} else {
				// Type 4: Static class method call (As of PHP 5.2.3)
				return static::STATIC_METHOD;
			}

		}	elseif ( is_array($callback) ) {
			if ( is_object($callback[0]) ) {
				// Type 2: Object method call
				return static::OBJECT_METHOD;

			} elseif ( is_string($callback[0]) ) {
				if ( stristr($callback[1], '::') === FALSE ) {
					// Type 3: Static class method call
					return static::STATIC_METHOD;
				} else {
					// Type 5: Relative static class method call (As of PHP 5.3.0)
					return static::RELATIVE_STATIC_METHOD;
				}
			} /* end if ( is_object ) */
		}
	}

	/**
	 * Get the argument names of the callable.
	 *
	 * @param  callable $callback The callable to get the argument names
	 *                            for.
	 * @return array          		An array of arguments of the callable.
	 *                            Each argument (parameter) is returned as
	 *                            an array of the type:
	 *
	 *                            	array(
	 *                            		'type'  => 'required|optional',
	 *                            		'empty' => TRUE | FALSE,
	 *                              	'value' => ...
	 *                              );
	 */
	public function get_arg_names($callback) {
		// Extract the type of the callback
		$type = $this->get_type($callback);

		// Initialize Reflector
		if ( $type == static::FUNCTION_SIMPLE ) {
			$reflector = new ReflectionFunction($callback);
		} else {
			list($class, $func_name) = $callback;
			// Resolve relative static class method call
			if ( $type == static::RELATIVE_STATIC_METHOD ) {
				$class = get_parent_class($class); 	// Support: parent only
				$func_name = end(explode('parent::', $func_name, 1));
			}
			$reflector = new ReflectionMethod($class, $func_name);
		}

		$parameters = array();
		// Get parameter(s) of the callable
		foreach ( $reflector->getParameters() as $param ) {
			if ( $param->isOptional() ) {
				// Initialize value if available
				if ( $param->isDefaultValueAvailable() ) {
					$value = $param->getDefaultValue();
					$empty = FALSE;
				} else {
					$value = NULL;
					$empty = TRUE;
				}

				// Default valued parameters are optional, the value is stored
				// under $parameters[$param->name]['value'].
				$parameters[$param->name] = array(
					'type'  => 'optional',
					'empty' => $empty,
					'value' => $value,
				);
			} else {
				// All required parameter values are NULL
				$parameters[$param->name] = array(
					'type'  => 'required',
					'empty' => TRUE,
					'value' => NULL,
				);
			}
		}
		return $parameters;
	}

	/**
	 * Set the option whether to parse flattened key-value pairs in
	 * call_user_variadic() or not.
	 *
	 * Currently four options are available:
	 * 	'on'			Allow key-value pairs i.e. call_user_variadic('value', 2).
	 * 	'off'			Disable key-value pair support in call_user_variadic().
	 * 	'once'		Allow key-value pairs in call_user_variadic() only once.
	 * 	'toggle'	Disable key-value pair support in call_user_variadic()
	 * 					  only once.
	 *
	 * @param  string $option The option used in call_user_variadic() for
	 *                        parsing additional arguments. If empty
	 *                        returns the current option.
	 */
	public function flatten_kwargs($option = '') {
		if ( strlen($option) == 0 ) {
			// Return current option
			return $this->allow_flattened_kwargs;
		}

		$options = array(
			static::FLATTEN_KWARGS_ON, static::FLATTEN_KWARGS_OFF,
			static::FLATTEN_KWARGS_ONCE, static::FLATTEN_KWARGS_TOGGLE,
		);

		// Check option
		if ( !in_array($option, $options) ) {
			if ( count($options) > 1 ) {
				$last = array_pop($options);
				throw new Exception(sprintf("Unknown option '%s'. It has to be one of the following options: '%s' or '%s'.",  $option, implode("', '", $options), $last));
			} else {
				throw new Exception(sprintf("Unknown option '%s'. It has to be one of the following options: '%s'.",  $option, $options[0]));
			}
			return;
		}

		// Set option
		$this->allow_flattened_kwargs = $option;
		return $this->allow_flattened_kwargs;
	}

	/**
	 * Call a callback with an array of parameters.
	 *
	 * @param  callable $callback The callable to be called.
	 * @param  array    $params   The parameters to be passed to the
	 *                            callback, as an (indexed) array.
	 * @return mixed							Returns the return value of the
	 *                            callback, or FALSE on error.
	 */
	public function call_user_variadic_array($callback, $params = array()) {
		// Check if $callback is a callable object
		if ( !is_callable($callback) ) {
			throw new Exception(sprintf("Callback '%s' is not callable.", var_export($callback, $return = TRUE)));
			return;
		}

		// Get the key of the parameter cache informations of the callback
		$cache_key = $this->_setup_args($callback);
		// Retrieve cache informations about the callable object
		$parameters = static::$_cache[$cache_key];
		// Apply arguments to parameters of the callable object
		$kwargs = $this->_apply_arguments($parameters, $params);
		// Do the function call
		return call_user_func_array($callback, $kwargs);
	}

	/**
	 * Call the callback (a variadic) given by the first parameter.
	 *
	 * @param  callable $callback   	 The callable to be called.
	 * @param  mixed		...$parameters Zero or more parameters to be
	 *                           	   	 passed to the callback.
	 * @return mixed							  	 Returns the return value of the
	 *                                 callback, or FALSE on error.
	 */
	public function call_user_variadic($callback) {
		// Get all parameters and passed them to call_user_variadic_array()
		$args = func_get_args();
		array_shift($args);

		// Key-value pair support
		$flatten_kwargs = $this->flatten_kwargs();
		// Allow flattened key-value pairs
		if ( ($flatten_kwargs === static::FLATTEN_KWARGS_ON) OR
				 ($flatten_kwargs === static::FLATTEN_KWARGS_ONCE) ) {
			// Flatten kwargs
			$args = $this->_flatten_kwargs($callback, $args);
			// Reset option, if desired
			if ( $flatten_kwargs === static::FLATTEN_KWARGS_ONCE ) {
				$this->flatten_kwargs(static::FLATTEN_KWARGS_OFF);
			}

		// Disable flattened key-value pairs only once
		}	elseif ( $flatten_kwargs === static::FLATTEN_KWARGS_TOGGLE ) {
			$this->flatten_kwargs(static::FLATTEN_KWARGS_ON);
		}

		// Call variadic function with indexed arguments now
		return $this->call_user_variadic_array($callback, $args);
	}

	/** -------------------------------
	 * Private/protected helper methods
	 * --------------------------------
	 */

	/**
	 * Return a flattened name of the callback.
	 *
	 * @param  callable $callback The callable to be flattened.
	 * @param  integer  $type     The type of the callback (see
	 *                            @get_type() for further informations).
	 * @return string             The flattened name of the callback.
	 */
	protected function _flatten_callback($callback, $type) {
		if ( is_array($callback) ) {
			// Get the right glue for callback
			if ( $type == static::OBJECT_METHOD ) {
				$callback[0] = get_class($callback[0]);
				return implode('->', $callback);
			}
			// Type 3/5: (Relative) static class method call
			return implode('::', $callback);
		}
		return $callback;
	}

	/**
	 * Turns the array returned by $args into an indexed array of
	 * name/value pairs that can be processed e.g. by extract().
	 *
	 * @param  callable $callback 	The callable to be called.
	 * @param  array 		$args 			The arguments to be passed as an array.
	 * @return array       					An indexed array of name/value pairs
	 *                             	processed from the arguments.
	 */
	protected function _flatten_kwargs($callback, $args) {
		// Turns the array returned by $args into an array of
		// name/value pairs that can be processed e.g. by extract().
		$count = count($args);
		$kwargs = array();

		// Turn arguments in name/value pairs only if the keys matches
		// callable object parameters
		$cache_key = $this->_setup_args($callback);
		$names = static::$_cache[$cache_key]['names'];

		$keyed = FALSE;
		// Loop through all parameter to search for name/value pairs
		for ( $i = 0; $i < $count; $i++ ) {
			if ( !$keyed AND is_string($args[$i]) AND (strlen($args[$i]) > 0)
			 	 AND in_array($args[$i], $names) AND ($i < $count - 1) ) {
				// A key is always a non-empty string followed by a value
				$keyed = $args[$i];
			} elseif ( $keyed ) {
				// We have a key. Now comes the value
				$kwargs[$keyed] = $args[$i];
				$keyed = FALSE;
			} else {
				// No name/value pair found or available
				$kwargs[] = $args[$i];
			}
		}

		return $kwargs;
	}

	/**
	 * Setup the parameter cache (retrieve arguments of a callable and
	 * set up processing tags).
	 *
	 * @param  callable $callback The callable to setup the cache for.
	 * @return string           	The cache key for the parameters of the
	 *                            callable.
	 */
	protected function _setup_args($callback) {
		// Extract the type of the callback
		$type = $this->get_type($callback);

		$cache_key = $this->_flatten_callback($callback, $type);
		if ( !isset(static::$_cache[$cache_key]) ) {
			// Update cache with the parameters	of the callback	(setup)
			$parameters = $this->get_arg_names($callback);

			$names = array_keys($parameters);
			$defaults = array_values($parameters);

      // Count number of parameters
			$total_args = count($names);
      // Filter out required parameter(s) and then count number of
      // optional parameter
			$optional = count(array_filter($defaults, function($e) {
				return ( $e['type'] === 'optional' ) ? TRUE : FALSE;
			}));

			// Calculate minimum and maximum number of needed parameters
			$min_args = $total_args - $optional;
			$max_args = $total_args;

			// Update cache
			static::$_cache[$cache_key] = array(
				'names'    => $names,
				'defaults' => $defaults,
				'num_args' => array($min_args, $max_args),
			);
		}
		return $cache_key;
	}

	/**
	 * Apply arguments to a given parameter array of a callable and sorts
	 * any ordered and keyword arguments.
	 *
	 * @param  array $params 	An array of arguments of the callable
	 *                        (see @get_arg_names() ).
	 * @param  array $args   	The parameters to be passed to the
	 *                        callback, as an indexed array.
	 * @return array         	Returns key-valued pair parameters.
	 */
	protected function _apply_arguments($params, $args = array()) {
		$names = $params['names'];
		$defaults = $params['defaults'];
		list($min_args, $max_args) = $params['num_args'];

		$num_args = count($args);
		// Check if number of arguments is less than the minimum number
		// of parameters
		if ( $num_args < $min_args ) {
			throw new Exception(sprintf("Missing arguments. Need at least %d arguments, got %d.", $min_args, $num_args));
			return;

		// and greater than the maximum number of parameters
		} elseif ( !$this->use_vargs AND ($num_args > $max_args) ) {
			throw new Exception(sprintf("Too many parameters. Need at least %d argument(s) and at most %d, got %d." , $min_args, $max_args, $num_args));
			return;
		}

		$ordered = array();
		$kwargs = array_combine($names, $defaults);
    // Repopulate arguments with key-value pairs
		foreach ( $args as $index => $arg ) {
			if ( is_string($index) ) {
        // We have a keyed parameter
				if ( in_array($index, $names) ) {
					$kwargs[$index] = array(
						'type'  => 'required',	// Make sure that optional arguments
																		// are only overwritten once
						'empty' => FALSE,
						'value' => $arg,
					);
				} else {
					if ( count($names) > 1 ) {
						$last = array_pop($names);
						throw new Exception(sprintf("Unknown parameter name '%s'. Parameter has to be one of the following names: '%s' or '%s'.", $index, implode("', '", $names), $last));
					} else {
						throw new Exception(sprintf("Unknown parameter name '%s'. Parameter has to be one of the following names: '%s'.", $index, $names[0]));
					}
					return;
				}
			} else {
        // We have an ordered parameter
				$ordered[] = $arg;
			}
		}

		$optional = $num_args - $min_args;
    // Mix keyword arguments with ordered arguments
		foreach ( $kwargs as $key => $value ) {
			$num_args = count($ordered);

			// Repopulate arguments with ordered parameters
			if ( ($value['type'] === 'required') AND $value['empty']
					 AND ($num_args > 0) ) {
				$kwargs[$key]['value'] = array_shift($ordered);
				$kwargs[$key]['empty'] = FALSE;

			// Replace an optional parameter with the argument
			} elseif ( ($value['type'] === 'optional') AND
					($optional > 0) AND ($num_args > 0) ) {
				$kwargs[$key]['value'] = array_shift($ordered);
				// Optional arguments can be empty, therefore set status
				$kwargs[$key]['empty'] = FALSE;
				$optional--;

			// Missing required parameter
			} elseif ( ($value['type'] === 'required') AND $value['empty'] ) {
				throw new Exception(sprintf("Missing required parameter for '%s'.", $key));
				return;
			}

			// Strip processing tags from values
			$kwargs[$key] = $kwargs[$key]['value'];
		}

		if ( $this->use_vargs AND in_array($this->keyword, $names) ) {
			$num_args = count($ordered);

			if ( $num_args == 0 ) {
				// There are no additional arguments left; cast the value of the
				// special variadic parameter to an array, if necessary
				if ( !is_array($kwargs[$this->keyword]) ) {
					$kwargs[$this->keyword] = array($kwargs[$this->keyword]);
				}
			} else {
				// There are additional arguments left; pass them to the special
				// variadic parameter
				$kwargs[$this->keyword] = array($kwargs[$this->keyword]);
				$kwargs[$this->keyword] = array_merge($kwargs[$this->keyword],
					$ordered);
			}
		}

		// Return key-valued pair parameters as an indexed array
		return $kwargs;
	}
}

/** ---------------
 * Global functions
 * ----------------
 */

/**
 * Call a callback with an array of parameters.
 *
 * @param  callable $callback The callable to be called.
 * @param  array    $params   The parameters to be passed to the
 *                            callback, as an indexed array.
 * @return mixed							Returns the return value of the
 *                            callback, or FALSE on error.
 */
function call_user_variadic_array($callback, $params = array()) {
	static $variadic = NULL;

	if ( is_null($variadic) ) {
		// Initialize static variable (i.e. instantiate Variadic)
		$variadic = new Variadic();
	}

	// Call the same named method from Variadic class
	return $variadic->call_user_variadic_array($callback, $params);
}

/**
 * Call the callback (a variadic) given by the first parameter.
 *
 * @param  callable $callback   	 The callable to be called.
 * @param  mixed		...$parameters Zero or more parameters to be passed
 *                           	   	 to the callback.
 * @return mixed							  	 Returns the return value of the
 *                                 callback, or FALSE on error.
 */
function call_user_variadic($callback) {
	// A wrapper for call_user_variadic_array
	$args = func_get_args();
	array_shift($args);
	return call_user_variadic_array($callback, $args);
}
?>
