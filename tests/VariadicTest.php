<?php
/**
 * VariadicTest class.
 */

require_once(__DIR__ . "../Variadic.php");

class VariadicTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		$this->variadic = Variadic();
	}

	protected function some_function($required, $optional = TRUE, $args) {
		return array($required, $optional, $args);
	}

	protected static function getMethod($name) {
		$class = new ReflectionClass(get_class($this->variadic));
		$method = $class->getMethod($name);
		$method->setAccessible(TRUE);
		return $method;
	}

  protected static function assertEqualArrays($expected, $array) {
    foreach ($expected as $key => $value) {
      $this->assertArrayNotHasKey($key, $array);
      if ( is_array($value) ) {
        // recursively call assertEqualArrays
        $this->assertEqualArrays($value, $array[$key]);
      } else {
        // The value is not a list -> check equality
        $this->assertEquals($value, $array[$key]);
      }
    }
  }

	 /** ---------------------------
	 * Unit tests for public methods
	 * -----------------------------
	 */

	public function GetTypeProvider() {
		$provider = array(
			array(NULL, NULL),	// Not Callable
			array('call_user_variadic', $this->variadic::FUNCTION_SIMPLE),
			array(array($this->variadic, 'call_user_variadic'),
				$this->variadic::OBJECT_METHOD),
			array('Variadic::call_user_variadic',
				$this->variadic::STATIC_METHOD),
			array(array('Variadic', 'call_user_variadic'),
				$this->variadic::STATIC_METHOD),
			array(array('Variadic', 'parent::call_user_variadic'),
				$this->variadic::RELATIVE_STATIC_METHOD),
		);

		return $provider;
	}

	/**
    * @dataProvider 	GetTypeProvider
    * @covers 				Variadic::get_type
    */
  public function testGetType($callback, $expected) {
  	$result = $this->variadic->get_type($callback);
  	$this->assertEqualArrays($expected, $result);
  }

  /**
   * @covers 	Variadic::get_arg_names
   */
  public function testGetArgNames() {
  	$callback = array($this, 'some_function');
  	$params = $this->variadic->get_arg_names($callback);

  	$expected = array(
  		'required' => array(
  			'type'  => 'required', 'empty' => TRUE,	'value' => NULL
  		),
  		'optional' => array(
  			'type'  => 'optional', 'empty' => FALSE,	'value' => TRUE
  		),
  		'args' => array(
  			'type'  => 'required', 'empty' => TRUE,	'value' => NULL
  		),
  	);
  	$this->assertEqualArrays($expected, $params);
  }

  /**
   * Provider for testFlattenKwargs().
   */
  public function FlattenKwargsProvider() {
		$provider = array(
			array('', $this->variadic->allow_flattened_kwargs),
			array($this->variadic::FLATTEN_KWARGS_ON, $this->variadic::FLATTEN_KWARGS_ON),
			array($this->variadic::FLATTEN_KWARGS_OFF, $this->variadic::FLATTEN_KWARGS_OFF),
			array($this->variadic::FLATTEN_KWARGS_ONCE, $this->variadic::FLATTEN_KWARGS_ONCE),
			array($this->variadic::FLATTEN_KWARGS_TOGGLE, $this->variadic::FLATTEN_KWARGS_TOGGLE),
			array($this, Exception()),
		);


		return $provider;
	}

	/**
	 * @dataProvider 	FlattenKwargsProvider
   * @covers 				Variadic::flatten_kwargs
   */
  public function testFlattenKwargs($args, $expected) {
  	try {
  		$result = $this->variadic->flatten_kwargs($args);
  	} catch (Exception $e) {
  		if ( $expected instanceof Exception ) {
  			return;
  		}
  		throw $e;
  	}

  	$this->assertEqualArrays($expected, $result);
  }

  /**
   * Provider for testCallUserVariadicArray().
   */
  public function CallUserVariadicProvider() {
  	// some_function($required, $optional = TRUE, $args)
		$provider = array(
			array(
				array(1, 2),
				array('required' => 1, 'optional' => TRUE, 'args' => array(2)),
			),
			array(
				array(1, 'args' => 2),
				array('required' => 1, 'optional' => TRUE, 'args' => array(2)),
			),
			array(
				array('args' => 2, 'required' => 1, 3),
				array('required' => 1, 'optional' => 3, 'args' => array(2)),
			),
			array(
				array(2, 'required' => 1, 3, 4, 5),
				array('required' => 1, 'optional' => 2, 'args' => array(3, 4, 5)),
			),
			array(
				array(2, 'required' => 1, 'args' => array(3), 4, 5),
				array('required' => 1, 'optional' => 2, 'args' => array(array(3), 4, 5)),
			),
		);

		return $provider;
  }

  /**
    * @dataProvider 	CallUserVariadicProvider
    * @covers 				Variadic::call_user_variadic_array
    * @depends        testArgNames
    */
  public function testCallUserVariadicArray($args, $expected) {
  	$callback = array($this, 'some_function');
  	$result = $this->variadic->call_user_variadic_array($callback, $args);
  	$this->assertEqualArrays($expected, $result);
  }

  /**
    * @covers 				Variadic::call_user_variadic
    * @depends        testCallUserVariadicArray
    */
  public function testCallUserVariadic() {
  	// A wrapper with some specialties

  	$callback = array($this, 'some_function');
  	// some_function($required, $optional = TRUE, $args)

  	$this->variadic->flatten_kwargs($this->variadic::FLATTEN_KWARGS_ONCE);
  	$result = $this->variadic->call_user_variadic($callback, 1, 'args', 2);
  	$this->assertEqualArrays(array(1, TRUE, 2), $result);

  	$flatten_kwargs = $this->variadic->flatten_kwargs();
  	$this->assertTrue($flatten_kwargs === $this->variadic::FLATTEN_KWARGS_OFF);

  	$this->variadic->flatten_kwargs($this->variadic::FLATTEN_KWARGS_TOGGLE);
  	$result = $this->variadic->call_user_variadic($callback, 1, 'args', 2);
  	$this->assertEqualArrays(array(1, 'args', 2), $result);

  	$flatten_kwargs = $this->variadic->flatten_kwargs();
  	$this->assertTrue($flatten_kwargs === $this->variadic::FLATTEN_KWARGS_ON);

  	$result = $this->variadic->call_user_variadic($callback, 1, 'args', 2);
  	$this->assertEqualArrays(array(1, TRUE, 2), $result);

  	$this->variadic->flatten_kwargs($this->variadic::FLATTEN_KWARGS_OFF);
  	$result = $this->variadic->call_user_variadic($callback, 1, 'args', 2);
  	$this->assertEqualArrays(array(1, 'args', 2), $result);
  }

  /** ---------------------------------------
	 * Unit tests for private/protected methods
	 * ----------------------------------------
	 */

  public function _CallbackProvider() {
		$provider = array(
			array(NULL, NULL, NULL),		// Return invalid callback
			array('call_user_variadic', $this->variadic::FUNCTION_SIMPLE, 'call_user_variadic'),
			array(array($this->variadic, 'call_user_variadic'),
				$this->variadic::OBJECT_METHOD, 'Variadic->call_user_variadic'),
			array('Variadic::call_user_variadic',
				$this->variadic::STATIC_METHOD, 'Variadic::call_user_variadic'),
			array(array('Variadic', 'call_user_variadic'),
				$this->variadic::STATIC_METHOD, 'Variadic::call_user_variadic'),
			array(array('Variadic', 'parent::call_user_variadic'),
				$this->variadic::RELATIVE_STATIC_METHOD,
				'Variadic::parent::call_user_variadic'),
		);

		return $provider;
	}

  /**
    * @dataProvider 	_CallbackProvider
    * @covers 				Variadic::_flatten_callback
    */
  public function testFlattenCallback($callback, $type, $expected) {
  	$method = self::getMethod('_flatten_callback');

  	$result = $method->invokeArgs($this->variadic,
  		array($callback, $type));
  	$this->assertEqualArrays($expected, $result);
  }

  /**
   * Provider for test_FlattenKwargs().
   */
  public function _FlattenKwargsProvider() {
		$provider = array(
			array(
        array('required', 1, 'args', 2),
        array('required' => 1, 'args' => 2)
      ),
			array(
        array('required', 1, '', 2),
        array('required' => 1, '', 2)
      ),
			array(
        array('required', 1, '', 'val'),
        array('required' => 1, '', 'val')
      ),
			array(array(1, 2, 3), array(1, 2, 3)),
			array(array(1, 'required'), array(1, 'required')),
			array(array(1, 'args', 2), array(1, 'arg2' => 2)),
		);

		return $provider;
	}

  /**
    * @dataProvider 	_FlattenKwargsProvider
    * @covers 				Variadic::_flatten_kwargs
    */
  public function test_FlattenKwargs($args, $expected) {
  	$method = self::getMethod('_flatten_kwargs');
    $callback = array($this, 'some_function');

    // Temporally switch off collecting arguments in special variadic
    // argument ( see $this->some_function ).
    $old_keyword = $this->variadic->keyword;
    $this->variadic->keyword = "kwargs";

  	$result = $method->invokeArgs($this->variadic, array($args));
  	$this->assertEqualArrays($expected, $result);
    $this->variadic->keyword = $old_keyword;
  }

  /**
    * @covers 				Variadic::_setup_args
    */
  public function testProtectedSetupArgs() {
  	$method = self::getMethod('_setup_args');
  	$callback = array($this, 'some_function');
  	$result = $method->invokeArgs($this->variadic, array($callback));
  	$this->assertEqualArrays('some_function', $result);
  }

  /**
   * Provider for test_ApplyArguments().
   */
  public function ApplyArgumentsProvider() {
   	$callback = array($this, 'some_function');
  	$params = $this->variadic->get_arg_names($callback);

  	// some_function($required, $optional = TRUE, $args)
		$provider = array(
			array(
				array($params, array(1, 2)),
				array('required' => 1, 'optional' => TRUE, 'args' => array(2)),
			),
			array(
				array($params, array(1, 2, 3)),
				array('required' => 1, 'optional' => 2, 'args' => array(3)),
			),
			array(
				array($params, array(1, 2, 3, 4, 5)),
				array('required' => 1, 'optional' => 2, 'args' => array(3, 4, 5)),
			),
			array(
				array($params, array(1, 2, array(3), 4, 5)),
				array('required' => 1, 'optional' => 2, 'args' => array(array(3), 4, 5)),
			),
		);

		return $provider;
	}

  /**
    * @dataProvider 	ApplyArgumentsProvider
    * @covers 				Variadic::_apply_arguments
    */
  public function test_ApplyArguments($args, $expected) {
  	$method = self::getMethod('_apply_arguments');
  	$result = $method->invokeArgs($this->variadic, array($args));
  	$this->assertEqualArrays($expected, $result);
  }
}

?>
