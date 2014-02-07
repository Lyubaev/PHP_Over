<?php 
/**
 * Класс PHP_Over
 * 
 * @license MIT
 * @author Lyubaev Kirill <lubaev.ka@gmail.com>
 * @copyright (c) 2014, Lyubaev Kirill
 */
class PHP_Over
{
  const TYPE_RESOURCE = 'resource';
  const TYPE_BOOLEAN  = 'boolean';
  const TYPE_INTEGER  = 'integer';
  const TYPE_DOUBLE   = 'double';
  const TYPE_STRING   = 'string';
  const TYPE_OBJECT   = 'object';
  const TYPE_ARRAY    = 'array';
  
  const POINTER = '*Pointer id #';
  
  const ERROR_IS_OPTIONAL_ARG           = 0x1;
  const ERROR_NUMBER_OF_TYPES           = 0x2;
  const ERROR_TYPE_MISMATCH             = 0x3;
  const ERROR_OVERLOAD_EXISTS           = 0x4;
  const ERROR_CALL_TO_UNDEFINED_METHOD  = 0x5;
  const ERROR_OVERRIDE_FUNC_NOT_EXISTS  = 0x6;
  const ERROR_OVERRIDE_ARRAY_MISSING    = 0x7;
  const ERROR_REMOVE_BOOL_MISSING       = 0x8;
  const ERROR_OVERLOAD_UNDEFINED        = 0x9;
  const ERROR_OVERLOAD_CALLABLE_MISSING = 0xA;
  const ERROR_OVERLOAD_STRING_MISSING   = 0xB;
  const ERROR_ARGUMENTS_LIST            = 0xC;
  
  static private $_instance,
                 $_list_of_hashes;
  
  private $_number_of_pointers,
          $_pointers_to_overloaded_function,
          $_reflections_of_overloaded_functions,
          $_error_msg = array(
              1 => 'Аргументы перегруженной функции содержат значения по умолчанию',
              2 => 'Количество аргументов перегруженной функции не соответствует количеству указанных типов',
              3 => 'Аргумент перегруженной функции ожидает получить тип не соответствующий указанному типу',
              4 => 'Перегруженная функция была определена ранее',
              5 => 'Вызов неопределенного метода',
              6 => 'Переопределение функции невозможно. Перегруженная функция не найдена',
              7 => 'Переопределение функции невозможно. Ожидает получить массив содержащий значения типов',
              8 => 'Удаление функции невозможно. Ожидает получить булев тип',
              9 => 'Обращение к неопределенной функции',
             10 => 'Регистрация функции невозможна. Ожидает получить значение типа "callable"',
             11 => 'Список аргуметов определяющих тип, должен быть типа "string"',
             12 => 'Некорректный список аргументов метода',
             );
  
  public function __construct()
  {
    $this->_number_of_pointers = 0;
    $this->_pointers_to_overloaded_function = array();
    $this->_reflections_of_overloaded_functions = new SplObjectStorage;
  }
  
  /**
   * Магический метод __call перехватывает вызовы необъявленных методов, а также
   * вызов статических методов в контексте объекта.
   * 
   * @param string $name Имя вызываемого метода.
   * @param array $arguments Числовой массив, содержащий параметры, переданные 
   * в вызываемый метод $name
   * @throws BadMethodCallException Создается исключение, если обратный вызов 
   * относится к неопределенному методу.
   */
  public function __call($name, $arguments)
  {
    throw new BadMethodCallException(
            $this->_error_msg[ self::ERROR_CALL_TO_UNDEFINED_METHOD ] . ' ' . __CLASS__ . "::$name()");
  }
  
  /**
   * Метод регестрирует функцию, которая должна быть вызвана и выполнена как
   * перегруженная функция, по заданному количеству и типам аргументов.
   * 
   * Значения ожидаемых типов для аргументов могут быть следуюущими:
   * string | %s | integer | int | %i | double | %d | float | %f | real
   * boolean | bool | %b | array | %a | object | %o | resource | %r
   * 
   * <samp>
   * <p>Пример:</p>
   * overload($callable);<br />
   * overload(['int'[,'string'[,...]]] $callable);<br />
   * overload([array('int'),] $callable);<br />
   * </samp>
   * 
   * @method true overload(callable $callable) Регестрирует функцию, которая
   * должна быть вызвана и выполнена как перегруженная функция без аргументов.
   * @method true overload(string $type, callable $callable) Регестрирует 
   * функцию, которая должна быть вызвана и выполнена как перегруженная функция
   * по заданному количеству и типам аргументов, которые перечислены в качестве
   * параметоров вызова.
   * @method true overload(array $types, callable $callable) Регестрирует 
   * функцию, которая должна быть вызвана и выполнена как перегруженная функция
   * по заданному количеству и типам аргументов, которые перечислены в массиве,
   * указанном в первом параметре вызова.
   * @param array|string $types Массив, либо ноль и 
   * более параметров содержащий значения ожидаемого типа для аргументов функции,
   * которая должна быть выполнена как перегруженная.
   * @param callable $callable Значение, которое может быть вызвано.
   * @return true Этот метод возвращает истину, если регистрация прошла успешно.
   * @throws InvalidArgumentException Будет выброшено исключение, если:
   *         1). значение, которое должно быть вызвано не callable.
   *         2). передано более двух аргументов и первый аргумент - массив.
   */
  public function overload()
  {
    $param = func_get_args();
    $callable = array_pop($param);
    $types_of_function_arguments = $param;
    
    if ( ! is_callable( $callable ))
    {
      throw new InvalidArgumentException( $this->_error_msg[ self::ERROR_OVERLOAD_CALLABLE_MISSING ] );
    }
    
    if (isset($param[0]) && is_array( $param[0] ))
    {
      if (array_key_exists( 1, $param ))
      {
        throw new InvalidArgumentException( 
                $this->_error_msg[ self::ERROR_ARGUMENTS_LIST ] . 
                ' ' . __METHOD__ );
      }
      
      $types_of_function_arguments = $param[0];
    }
    
    $fixedData = $this->_getFixedData($types_of_function_arguments, null);
    return $this->_initOverload($callable, $fixedData);
  }
  
  /**
   * Метод способен переопределить ранее добавленную (зарегестрированную) 
   * перегружаемую функцию. В этом случае значение $callable_or_strict_match 
   * должно быть типа callable.
   * Метод также позволяет удалить ранее добавленную перегружаемую функцию.
   * В этом случае значение $callable_or_strict_match должно быть типа boolean 
   * или NULL.
   * 
   * Если второй аргумент опущен или установлен в true, то в этом случае будет
   * удалена перегружаемая функция, которая строго соответствует количеству
   * и типам указанных для аргументов.
   * Пример:
   * PHP_Over::override(array("double"));
   * PHP_Over::override(array("double"), true);
   * 
   * Два примера идентичны между собой и демонстрируют, как можно удалить
   * ранее добавленную перегруженную функцию, которая принимала один аргумент
   * типа double.
   * 
   * Если второй аргумент установлен в false, то в этом случае будут удалены
   * все ранее добавленные перегружаемые функции, начальная сигнатура которых
   * соответствует типам указанных для аргументов, а количество принимаемых 
   * аргументов больше или равно количеству значений указанных в массиве 
   * $types_of_function_arguments.
   * Пример:
   * PHP_Over::override(array("string", "integer",), false);
   * 
   * В этом примере будут удалены все ранее добавленные перегружаемые функции,
   * которые принимали два и более аргумента и типы двух первых аргументов,
   * соответствуют указанным значениям типов для этих аргументов.
   * 
   * @param string[] $types_of_function_arguments Массив, содержащий значения 
   * типов для аргументов перегруженной функции. 
   * Порядок значений в массиве соответствует порядку аргументов перегруженной 
   * функции.
   * @param callable|boolean|null $callable_or_strict_match Значение, которое 
   * может быть вызвано или
   * @return true|int Метод возвращает истину, в случае успешного 
   * переопределения ранее добавленной перегружаемой функции.
   * В случае удаления метод возвращает число, количестов функций которых
   * было удалено.
   */
  public function override()
  {
    $param = func_get_args();
    $callable_or_strict_match = array_pop($param);
    $types_of_function_arguments = $param;
    
    if (isset($param[0]) && is_array( $param[0] ))
    {
      if (array_key_exists( 1, $param ))
      {
        throw new InvalidArgumentException( 
                $this->_error_msg[ self::ERROR_ARGUMENTS_LIST ] . 
                ' ' . __METHOD__ );
      }
      
      $types_of_function_arguments = $param[0];
    }
    
    $fixedData = $this->_getFixedData($types_of_function_arguments, null);
    return $this->_initOverride($callable_or_strict_match, $fixedData, true);
  }
  
  /**
   * Магический метод __invoke перехватывает вызов объект как функции и 
   * инициирует вызов зарегестрированной функции, которая должна быть 
   * выполнена как перегруженная функция.
   * 
   * @param mixed Ноль или более параметров, передаваемые в 
   * зарегестрированную функцию.
   * @return mixed Возвращает результат выполнения зарегестрированной функции.
   */
  public function __invoke()
  {
    return $this->_initInvoke(func_get_args());
  }
  
  /**
   * Инициирует вызов зарегестрированной функции, которая должна быть 
   * выполнена как перегруженная функция.
   * 
   * @param mixed Ноль или более параметров, передаваемые в зарегестрированную 
   * функцию.
   * @return mixed Возвращает результат выполнения зарегестрированной функции.
   */
  public function invokeTo()
  {
    return $this->_initInvoke(func_get_args());
  }
  
  /**
   * Инициирует вызов зарегестрированной функции, которая должна быть 
   * выполнена как перегруженная функция.
   * 
   * @param array|null $arguments Передаваемые в функцию параметры в виде массива.
   * @return mixed Возвращает результат выполнения зарегестрированной функции.
   */
  public function invokeArgsTo(array $arguments = null)
  {
    return $this->_initInvoke(( array )$arguments);
  }
  
  /**
   * Инициирует вызов зарегестрированной функции, которая должна быть 
   * выполнена как перегруженная функция.
   * 
   * Это статический вариант метода invokeTo().
   * 
   * В качестве псевдонима допускается значение любого типа. Однако только
   * значения типа string или integer или float являются корректными значениями.
   * Значения остальных типов будут преобразованы к типу string, в том числе 
   * и типы integer и float, по следующим правилам:
   * Значения типа array и object будут преобразованы к boolean.
   * Значения типа boolean, NULL, resource будут преобразованы к float.
   * Значения типа integer и float будут преобразованы к string.
   * 
   * @param mixed $alias_of_function Любое допустимое значение, которое будет 
   * служить псевдонимом (идентификатором) для обращения к зарегестрированной
   * функции.
   * @param mixed Ноль или более параметров, передаваемые в зарегестрированную 
   * функцию.
   * @return mixed Возвращает результат выполнения зарегестрированной функции.
   */
  static protected function invoke($alias_of_function)
  {
    $hash = self::_fetchHash($alias_of_function);
    $args = array_slice(func_get_args(), 1);
    return self::getInstance()->_initInvoke($args, $hash);
  }
  
  /**
   * Инициирует вызов зарегестрированной функции, которая должна быть 
   * выполнена как перегруженная функция.
   * 
   * Это статический вариант метода invokeArgsTo().
   * 
   * В качестве псевдонима допускается значение любого типа. Однако только
   * значения типа string или integer или float являются корректными значениями.
   * Значения остальных типов будут преобразованы к типу string, в том числе 
   * и типы integer и float, по следующим правилам:
   * Значения типа array и object будут преобразованы к boolean.
   * Значения типа boolean, NULL, resource будут преобразованы к float.
   * Значения типа integer и float будут преобразованы к string.
   * 
   * @param mixed $alias_of_function Любое допустимое значение, которое будет 
   * служить псевдонимом (идентификатором) для обращения к зарегестрированной
   * функции.
   * @param array|null $arguments Передаваемые в функцию параметры в виде массива.
   * @return mixed Возвращает результат выполнения зарегестрированной функции.
   */
  static protected function invokeArgs($alias_of_function, array $arguments = null)
  {
    $hash = self::_fetchHash($alias_of_function);
    return self::getInstance()->_initInvoke(( array )$arguments, $hash);
  }
  
  
  
  
  
  
  
  
  
  
  
  /**
   * Метод инициирует добавление функции, которая должна быть перегружена в 
   * процессе ее вызова.
   * 
   * @param callable $callable Значение, которое может быть вызвано.
   * @param SplFixedArray $fixedData Данные, которые включат в себя некоторую 
   * информацию, на основе которой будет определена перегружаемая пользователем 
   * функция.
   * @return true Этот метод возвращает истину, если добавление прошла успешно.
   * @throws LogicException Будет выброшено исключение, если:
   *         1). Аргументы перегруженной функции содержат значения по умолчанию.
   *         2). Количество аргументов перегруженной функции не соответствует 
   *             количеству указанных типов.
   *         3). Перегруженная функция была определена ранее.
   * @throws DomainException Будет выброшено исключение, если:
   *         1). Аргумент перегруженной функции ожидает получить тип 
   *             не соответствующий указанному типу.
   */
  private function _initOverload(callable $callable, SplFixedArray $fixedData)
  {
    $reflection_data = $this->_getDataReflection( $callable );
    $reflection_func = $reflection_data[0];
    
    if ($reflection_func->getNumberOfParameters() > $reflection_func->getNumberOfRequiredParameters())
	{
	  throw new LogicException( $this->_error_msg[ self::ERROR_IS_OPTIONAL_ARG ] );
    }
    
    if ($reflection_func->getNumberOfParameters() <> $fixedData->size)
	{
	  throw new LogicException( $this->_error_msg[ self::ERROR_NUMBER_OF_TYPES ] );
    }
    
    $parameters = $reflection_func->getParameters();
    for ($fixedData->rewind(); $fixedData->valid(); $fixedData->next())
    {
      $parameter = $parameters[ $fixedData->key() ];
      
      if ( ! ($this->_compareExpectedType($parameter, $fixedData->current())) )
      {
        throw new DomainException( $this->_error_msg[ self::ERROR_TYPE_MISMATCH ] );
      }
      
      if ($parameter->isPassedByReference())
      {
        $reflection_data['ByRef'][] = $parameter->getPosition();
      }
    }
    
    if ( ! $this->_add_function($reflection_data, $fixedData))
    {
      throw new LogicException( $this->_error_msg[ self::ERROR_OVERLOAD_EXISTS ] );
    }
    
    return true;
  }
  
  /**
   * Метод инициирует переопределение функции, которая была добавлена ранее.
   * 
   * @param type $callable_or_strict_match
   * @param SplFixedArray $fixedData Данные, которые включат в себя некоторую 
   * информацию, на основе которой будет определена перегружаемая пользователем 
   * функция.
   * @param boolean $types_is_array Этот параметр указывает, был ли передан 
   * массив, содержащий значения и количество типов
   * @return boolean
   * @throws LogicException
   * @throws @var:$this@mtd:_override_function
   * @throws InvalidArgumentException
   */
  private function _initOverride($callable_or_strict_match, SplFixedArray $fixedData, $types_is_array)
  {
    if (is_callable( $callable_or_strict_match ))
    {
      if ( ! $types_is_array)
      {
        throw new LogicException( $this->_error_msg[ self::ERROR_OVERRIDE_ARRAY_MISSING ] );
      }
      
      $result = $this->_override_function($callable_or_strict_match, $fixedData);
      
      if ($result instanceof LogicException)
      {
        throw $result;
      }
      
      return true;
    }
    
    if ( ! is_bool( $callable_or_strict_match ) && $callable_or_strict_match!==null)
    {
      throw new InvalidArgumentException( $this->_error_msg [ self::ERROR_REMOVE_BOOL_MISSING ] );
    }
    
    // Remove.
    return $this->_remove_function($callable_or_strict_match, $fixedData, $types_is_array);
  }
  
  /**
   * Метод выполняет зарегестрированную ранее как перегруженную функцию
   * и возвращает результат ее выполнения.
   * 
   * Поиск зарегестрированной функции выполняется по количеству передаваемых
   * аргументов а также по типу этих аргументов, определяемых с помощью функции
   * gettype().
   * 
   * Значения типа NULL, которые могут присутствовать в конце списка 
   * переданных аргументов будут вырезаны. Это сделано с целью передавать 
   * все аргументы функции посреднека, которые по умолчанию принимают NULL.
   * 
   * Если зарегестрированная функция ожидает получить аргумент по ссылке, то
   * такой аргумент будет передан по ссылке в вызов отражения функции.
   * 
   * @param array $arguments Передаваемые в функцию параметры в виде массива.
   * @param string $hash Хэш псевдонима зарегестрированной функции.
   * ( Только в статическом вызове. ) 
   * @return mixed Возвращает результат выполнения зарегестрированной функции.
   * @throws BadFunctionCallException Будет выброшено исключение, если:
   *         1). функци по заданному количеству и типам аргументов не была
   *         зарегестрирована.
   */
  private function _initInvoke(array $arguments, $hash = null)
  {
    $arguments = array_values( $arguments );
    
    $i = count($arguments);
    while ( $i-- && $arguments[ $i ]===null )
    {
      array_splice($arguments, $i, 1);
    }
    
    $types_of_function_arguments = array();
    $i += 1;
    while ( $i-- )
    {
      $types_of_function_arguments[] = gettype( $arguments[ $i ] );
    }
    
    $fixedData = $this->_getFixedData(array_reverse($types_of_function_arguments), $hash);
    $pointer_id = $this->_getPointer($fixedData, $this->_pointers_to_overloaded_function);
    
    if ($pointer_id && $this->_reflections_of_overloaded_functions->contains($pointer_id))
    {
      $reflection_data = $this->_reflections_of_overloaded_functions->offsetGet($pointer_id);
      $reflection_func = $reflection_data[0];
      
      if (isset( $reflection_data['ByRef'] ))
      {
        foreach ($reflection_data['ByRef'] as $num)
        {
          $arguments[ $num ] =& $arguments[ $num ];
        }
      }
      
      return ( $reflection_func instanceof ReflectionMethod )
          ? $reflection_func->invokeArgs( $reflection_data[1], $arguments )
          : $reflection_func->invokeArgs( $arguments );
    }
    
    throw new BadFunctionCallException( $this->_error_msg[ self::ERROR_OVERLOAD_UNDEFINED ] );
  }
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  /**
   * 
   * @param array $reflection_data
   * @param SplFixedArray $fixedData
   * @return boolean
   */
  private function _add_function(array $reflection_data, SplFixedArray $fixedData)
  {
    $pointer_id = $this->_setPointer($fixedData, self::POINTER . ++$this->_number_of_pointers);
    
    if ($pointer_id)
    {
      $this->_reflections_of_overloaded_functions->attach($pointer_id, $reflection_data);
      return true;
    }
    
    return false;
  }
  
  /**
   * 
   * @param type $strict_match_types
   * @param SplFixedArray $fixedData
   * @param type $types_is_array
   * @return int
   */
  private function _remove_function($strict_match_types, SplFixedArray $fixedData, $types_is_array)
  {
    $ret = 0;
    $pointers_id = array();
    $ref_pointers = null;
    
    if ($fixedData->hash)
    {
      if ( ! isset($this->_pointers_to_overloaded_function[ $fixedData->hash ]))
      {
        return $ret;
      }
      
      $ref_pointers =& $this->_pointers_to_overloaded_function[ $fixedData->hash ];
    }
    else
    {
      $ref_pointers =& $this->_pointers_to_overloaded_function;
    }
    
    // Статический вызов.
    // Удалить все перегруженные функции с заданным псевдонимом.
    if ( ! $types_is_array)
    {
      $pointers_id = $this->_findPointers($ref_pointers);
      unset($this->_pointers_to_overloaded_function[ $fixedData->hash ]);
    }
    else
    {
      if ($strict_match_types || $strict_match_types===null)
      {
        $pointers_id = $this->_deletePointer($fixedData, $ref_pointers);
      }
      else
      {
        $dimension = array_diff(
                       array_keys($ref_pointers),
                       range(0, $fixedData->size-1));
        foreach ($dimension as $size)
        {
          $fixedData->size = $size;
          $pointers_id = array_merge(
                           $pointers_id,
                           $this->_deletePointer($fixedData, $ref_pointers));
        }
      }
    }
    
    $ret = $i = count($pointers_id);
    while ( $i-- )
    {
      $this->_reflections_of_overloaded_functions->detach($pointers_id[ $i ]);
    }
    
    unset($ref_pointers);
    return $ret;
  }
  
  /**
   * 
   * @param callable $callable
   * @param SplFixedArray $fixedData
   * @return \LogicException|boolean
   */
  private function _override_function(callable $callable, SplFixedArray $fixedData)
  {
    $pointers_id = $this->_deletePointer($fixedData, $this->_pointers_to_overloaded_function);
    
    if ( ! $pointers_id)
    {
      return new LogicException( $this->_error_msg[ self::ERROR_OVERRIDE_FUNC_NOT_EXISTS ] );
    }
    
    try
    {
      $this->_initOverload($callable, $fixedData);
    }
    catch ( LogicException $exc )
    {
      //Restore pointer
      $this->setPointer( $fixedData, $pointers_id[0] );
      return $exc;
    }
    
    $this->_reflections_of_overloaded_functions->detach( $pointers_id[0] );
    return true;
  }
  
  /**
   * 
   * @param array $container
   * @return \RecursiveIteratorIterator
   */
  private function _findPointers(array $container)
  {
    $ret = array();
    foreach (new RecursiveIteratorIterator(
               new RecursiveArrayIterator($container),
                 RecursiveIteratorIterator::SELF_FIRST) as $heap)
    {
      if (is_object( $heap ))
      {
        $ret[] = $heap;
      }
    }
    return $ret;
  }
  
  /**
   * 
   * @param SplFixedArray $fixedData
   * @param type $pointer_id
   * @return boolean
   */
  private function _setPointer(SplFixedArray $fixedData, /*string object*/ $pointer_id)
  {
    $ref =& $this->_pointers_to_overloaded_function;
    
    if (isset( $fixedData->hash ))
    {
      if ( ! isset($ref[ $fixedData->hash ]))
      {
        $ref[ $fixedData->hash ]=array();
      }
      $ref =& $ref[ $fixedData->hash ];
    }
    
    if ( ! isset($ref[ $fixedData->size ]))
    {
      $ref[ $fixedData->size ]=array();
    }
    $ref =& $ref[ $fixedData->size ];
    
    for ($fixedData->rewind(); $fixedData->valid(); $fixedData->next())
    {
      if ( ! isset( $ref[ $fixedData->current() ] ))
      {
        $ref[ $fixedData->current() ]=array();
      }
      $ref =& $ref[ $fixedData->current() ];
    }
    
    if (is_object( $ref ))
    {
      return false;
    }
    
    if ( ! is_object( $pointer_id))
    {
      $pointer_id = ( object )$pointer_id;
    }
    
    $ref = $pointer_id;
    
    unset($ref);
    return $pointer_id;
  }
  
  /**
   * 
   * @param SplFixedArray $fixedData
   * @param array $container
   * @return null
   */
  private function _getPointer(SplFixedArray $fixedData, array $container)
  {
    if ($fixedData->hash)
    {
      if ( ! isset( $container[ $fixedData->hash ]))
      {
        return null;
      }
      $container =& $container[ $fixedData->hash ];
    }
    
    if ( ! isset( $container[ $fixedData->size ] ))
    {
      return null;
    }
    $container =& $container[ $fixedData->size ];
    
    $fixedData->rewind();
    while ($fixedData->valid())
    {
      if( ! isset($container[ $fixedData->current() ]) ) 
      {
        $container = null;
        break;
      }
      $container =& $container[ $fixedData->current() ];
      $fixedData->next();
    }
    return $container;
  }
  
  /**
   * 
   * @param SplFixedArray $fixedData
   * @param array $container
   * @return type
   */
  private function _deletePointer(SplFixedArray $fixedData, array &$container)
  {
    if ($fixedData->hash && isset( $container[ $fixedData->hash ] ))
    {
      $container =& $container[ $fixedData->hash ];
    }
    
    if (isset( $container[ $fixedData->size ] ))
    {
      $container =& $container[ $fixedData->size ];
    }
    
    $ref_container =& $container;
    $last_value = null;
    $first_loop = true;
    $size = count($fixedData);
    $ret = array();
    
    while ($size)
    {
      $fixedData->rewind();
      while ($size > $fixedData->key())
      {
        if( $first_loop && ! isset($container[ $fixedData->current() ]) ) 
        {
          break 2;
        }
        $container =& $container[ $fixedData->current() ];
        $fixedData->next();
      }
      $first_loop = false;
      
      if (is_object( $container ))
      {
        $ret[] = $container;
        $container = null;
      }
      else
      {
        if (isset( $last_value ))
        {
          // count( null ) equal 0
          if (1 > count( $container[ $last_value ] ))
            unset( $container[ $last_value ] );
          else
            break 1;
        }
        else
        {
          $ret = $this->_findPointers($container);
          $container = null;
        }
      }
      
      $container =& $ref_container;
      $last_value = $fixedData[ --$size ];
    }
    
    if (isset( $last_value ) && (1 > count( $container[ $last_value ] )))
    {
      unset( $container[ $last_value ] );
    }
    
    unset( $ref_container );
    return $ret;
  }
  
  /**
   * 
   * @param callable $callable
   * @return type
   */
  private function _getDataReflection(callable $callable)
  {
    if (is_string( $callable ) && strpos( $callable, '::' ))
	{
	  $callable = explode( '::', $callable );
	}
    
    return is_array( $callable ) 
      ? array( new ReflectionMethod( $callable[0], $callable[1] ), is_string( $callable[0] ) ? null : $callable[0] )
      : array( new ReflectionFunction( $callable ) );
  }
  
  /**
   * 
   * @param ReflectionParameter $reflection_parameter
   * @param type $type_of_argument
   * @return boolean
   */
  private function _compareExpectedType(ReflectionParameter $reflection_parameter, $type_of_argument)
  {
    if ($reflection_parameter->isArray() && $type_of_argument !== self::TYPE_ARRAY)
    {
      return false;
    }
    
    if ($reflection_parameter->isCallable() &&
        $type_of_argument !== self::TYPE_OBJECT &&
        $type_of_argument !== self::TYPE_STRING &&
        $type_of_argument !== self::TYPE_ARRAY)
    {
      return false;
    }
    
    return true;
  }
  
  /**
   * 
   * @param array $types_of_function_arguments
   * @param type $hash
   * @return type
   * @throws DomainException
   */
  private function _getFixedData(array $types_of_function_arguments, $hash)
  {
    $size = count( $types_of_function_arguments );
    $data = new SplFixedArray( $size );
    $data->hash = $hash;
    $data->size = $size;
    
    for (reset($types_of_function_arguments); $data->valid(); $data->next())
    {
      list(, $type) = each($types_of_function_arguments);
      
      if ( ! is_string( $type ))
      {
        throw new InvalidArgumentException( $this->_error_msg[ self::ERROR_OVERLOAD_STRING_MISSING ] );
      }
      
      switch ($type)
      {
        case '%s': $type = self::TYPE_STRING;   break;
        case '%o': $type = self::TYPE_OBJECT;   break;
        case '%a': $type = self::TYPE_ARRAY;    break;   
        case '%r': $type = self::TYPE_RESOURCE; break;
        case '%i':
       case 'int': $type = self::TYPE_INTEGER;  break;
        case '%b':
      case 'bool': $type = self::TYPE_BOOLEAN;  break;
        case '%d': 
        case '%f':
     case 'float':
      case 'real': $type = self::TYPE_DOUBLE;   break;
      }
      
      $data[ $data->key() ] = $type;
    }
    $data->rewind();
    
    return $data;
  }
  
  /**
   * 
   * @param type $name
   * @param type $arguments
   * @return type
   */
  static public function __callStatic($name, $arguments)
  {
    if (method_exists(__CLASS__, $name))
    {
      $method=new ReflectionMethod(__CLASS__, $name);
      if ($method->isProtected())
      {
        return call_user_func_array("self::$name", $arguments);
      }
    }
    self::getInstance()->__call($name, null);
  }
  
  /**
   * 
   * @param type $alias_of_function
   * @param array $types_of_function_arguments
   * @param callable $callable
   * @return type
   */
  static protected function load($alias_of_function, array $types_of_function_arguments, callable $callable)
  {
    $hash = self::_fetchHash($alias_of_function);
    $fixedData = self::getInstance()->_getFixedData($types_of_function_arguments, $hash);
    return self::getInstance()->_initOverload($callable, $fixedData);
  }
  
  /**
   * 
   * @param type $alias_of_function
   * @param array $types_of_function_arguments
   * @param type $callable_or_strict_match
   * @return type
   */
  static protected function ride($alias_of_function, array $types_of_function_arguments = null, $callable_or_strict_match = null)
  {
    $hash = self::_fetchHash($alias_of_function);
    $is_array = $types_of_function_arguments===null ? false : true;
    $fixedData = self::getInstance()->_getFixedData(( array )$types_of_function_arguments, $hash);
    return self::getInstance()->_initOverride($callable_or_strict_match, $fixedData, $is_array);
  }
  
  /**
   * 
   * @return self
   */
  static private function getInstance()
  {
    if ( ! self::$_instance)
    {
      self::$_instance = new self;
      self::$_list_of_hashes = array();
    }
    
    return self::$_instance;
  }
  
  /**
   * Метод преобразует полученное значение к строке
   * @param mixed $var
   * @return string
   */
  static private function _fetchString($var)
  {
    if (is_array( $var ) || is_object( $var ))
    {
      $var = !!$var;
    }
    
    if (is_resource( $var ) || is_bool( $var) || $var===null)
    {
      $var = ( float )$var;
    }
    
    if (is_int( $var ) || is_float( $var ))
    {
      $var = ( string )$var;
    }
    
    return $var;
  }
  
  /**
   * Получить хэш псевдонима (идентификатора) перегружаемой функции 
   * в статическом вызове.
   * 
   * @param mixed $var Псевдоним, которое является идентификатором перегружаемой
   * функции.
   * @return string Возвращает SHA1-хэш строки идентификатора.
   */
  static private function _fetchHash($var)
  {
    $string = self::_fetchString($var);
    
    if ( ! isset( self::$_list_of_hashes[ $string ] ))
    {
      self::$_list_of_hashes[ $string ] = sha1( $string );
    }
    
    return self::$_list_of_hashes[ $string ];
  }
  
  function debugTo()
  {
    echo "\npointers\n";
    print_r( $this->_pointers_to_overloaded_function );
    echo nl2br(str_repeat('+', 70) . PHP_EOL . PHP_EOL);
    
    echo "\nreflections\n";
    print_r( $this->_reflections_of_overloaded_functions );
    echo nl2br(str_repeat('+', 70) . PHP_EOL . PHP_EOL);
  }
  
  static function debug()
  {
    echo "\npointers\n";
    print_r( self::getInstance()->_pointers_to_overloaded_function );
    echo nl2br(str_repeat('+', 70) . PHP_EOL . PHP_EOL);
    
    echo "\nreflections\n";
    print_r( self::getInstance()->_reflections_of_overloaded_functions );
    echo nl2br(str_repeat('+', 70) . PHP_EOL . PHP_EOL);
  }
}