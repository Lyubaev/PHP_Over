<?php

class PHP_Over
{
    const ERRNO_ARG_NOT_CALLABLE = 0x1;
    const ERRNO_IS_OPTIONAL_ARGS = 0x2;
    const ERRNO_INVALID_SIZE_ARG = 0x3;
    const ERRNO_INVALID_ARG_LIST = 0x4;
    const ERRNO_INVALID_ARG_TYPE = 0x5;
    const ERRNO_INVALID_VAL_TYPE = 0x6;
    const ERRNO_INVALID_NAME_FN  = 0x7;
    const ERRNO_ARGS_NOT_STRING  = 0x8;
    const ERRNO_OVERLOAD_NEXIST  = 0x9;
    const ERRNO_OVERLOAD_EXIST   = 0xa;
    const ERRNO_BAD_FUNC_CALL    = 0xb;
    const ERRNO_BAD_METH_CALL    = 0xc;
    const TYPE_RESOURCE          = 'resource';
    const TYPE_BOOLEAN           = 'boolean';
    const TYPE_INTEGER           = 'integer';
    const TYPE_DOUBLE            = 'double';
    const TYPE_STRING            = 'string';
    const TYPE_OBJECT            = 'object';
    const TYPE_ARRAY             = 'array';

    /**
     * Внутренний экзеипляр этого класса.
     * Предназначен для статических вызовов в клиентском коде.
     * @var self
     */
    static private $_instance;

    /**
     * Хэш массив используемых псевдонимов функций.
     * Содержит псевдоним функции и его хэш строку.
     * @var array
     */
    static private $_listOfHashes;

    /**
     * Словарь сообщений об ошибках при работе класса.
     * @var array
     */
    static private $_errorMsg = array(
        1  => 'Переданный аргумент должен быть типа callable',
        2  => 'Аргумент регистрируемой функции является необязательным',
        3  => 'Количество аргументов не равно количеству указанных типов',
        4  => 'Неверный список аргументов',
        5  => 'Несоответствие ожидаемому типу аргумента',
        6  => 'Тип аргумента неизвестен',
        7  => 'Неверный псевдоним функции',
        8  => 'Определение типа аргумента должен быть типа string',
        9  => 'Попытка переопределить ранее незарегистрированную функцию',
        10 => 'Попытка определить ранее зарегистрированную функцию',
        11 => 'Вызов неопределенной ранее функции',
        12 => 'Вызов неопределенного метода',
    );

    /**
     * Номер указателя.
     * Это значение уникально в пределах одно экземпляра.
     * @var int
     */
    private $_indexOfPointers;

    /**
     * Содержит указатели на зарегестрированные функции.
     * Структура этого массива многомерна. Индекс первого измерения соответствует
     * количеству аргументов регестрируемой функции. Количество вложенных
     * измерений равно количеству аргументов функции. Каждый индекс вложенного
     * измерения содержит тип аргумента регестрируемой функции. Первое вложенное
     * измрение это первый аргумент, второе измерение это второй аргумент и т.д.
     * Значением последненго измерения будет указатель.
     * Указатель это объект класса stdClass, внутри которого содержится
     * номер (идентификатор) указателя.
     * @var array
     */
    private $_pointersToOverloadedFunction;

    /**
     * Соответствие объекты-данные.
     * В роли объектов выступают указатели на зарегестрированную функцию.
     * В роли данных выступает массив, содержащий отражение зарегестрированной
     * функции, а также дополниельные данные, которые необходимы для ее вызова.
     * @var SplObjectStorage
     */
    private $_reflectionsOfOverloadedFunctions;

    /**
     * Конструктор этого класса.
     * @return void
     */
    public function __construct()
    {
        $this->_indexOfPointers                  = 1000;
        $this->_pointersToOverloadedFunction     = array();
        $this->_reflectionsOfOverloadedFunctions = new SplObjectStorage();
    }

    /**
     * Магический метод __callStatic перехватывает вызов недоступных статических
     * методов класса.
     *
     * @method TRUE load(mixed $alias, string|array $types, callable $callable) Статический вариант метода overload();
     * @method int ride(mixed $alias, string|array $types, callable|boolean $callOrBool) Статический вариант метода override();
     * @method mixed invoke($alias, mixed $arg) Статический вариант метода invokeTo();
     * @method mixed invokeArgs($alias, array $args) Статический вариант метода invokeArgsTo();
     * @param string $method Имя вызываемого метода.
     * @param array $arguments Числовой массив, содержащий параметры,
     * переданные в вызываемый метод $method.
     * @return mixed Возвращает результат выполнения перегруженного метода.
     * @throws InvalidArgumentException Исключение, если не указан псевдоним
     * для регестрируемой функции.
     * @throws BadMethodCallException Исключение, если вызов относится к
     * неопределенному методу.
     */
    static public function __callStatic($method, $arguments)
    {
        $name = array_shift($arguments);
        if (!isset($name)) {
            throw new InvalidArgumentException(self::$_errorMsg[self::ERRNO_INVALID_NAME_FN]);
        }

        $initMethod = "_initover{$method}";
        if (method_exists(__CLASS__, $initMethod)) {
            $self      = self::getInstance();
            $args      = $self->_parseArgs($arguments);
            $fixedData = $self->_getFixedData($args[0], self::_fetchHash($name));

            return $self->$initMethod($args[1], $fixedData);
        }

        $initMethod = "{$method}to";
        if (method_exists(__CLASS__, $initMethod)) {
            $self = self::getInstance();
            if (stripos($initMethod, 'args')) {
                $arguments[] = false;
                $arguments   = $self->_parseArgs($arguments);
                $arguments   = $arguments[0];
            }

            return $self->_initInvoke($arguments, self::_fetchHash($name));
        }

        throw new BadMethodCallException(self::$_errorMsg[self::ERRNO_BAD_METH_CALL]);
    }

    /**
     * Метод регестрирует функцию, которая должна быть вызвана и выполнена
     * по заданному количеству и типам аргументов.
     *
     * Значения ожидаемых типов для аргументов могут быть следуюущими:
     * string | %s | integer | int | %i | double | %d | float | %f | real
     * boolean | bool | %b | array | %a | object | %o | resource | %r
     *
     * Пример:
     * overload($callable) : Регестрирует функцию без аргументов.
     * overload(['type'[,'type'[,...]]] $callable) : Регестрирует функцию
     * по заданному количеству и типам аргументов, которые перечислены в качестве
     * параметоров вызова, предшевствующие последнему аргументу.
     * overload([array('type'),] $callable) : Регестрирует функцию по заданному
     * количеству и типам аргументов, которые перечислены в массиве, указанном
     * в первом параметре вызова.
     *
     * @param string|array $types Массив, либо ноль и более параметров содержащий
     * значения ожидаемого типа для аргументов функции, которая должна быть
     * выполнена как перегруженная.
     * @param callable $callable Значение, которое может быть вызвано.
     * @return TRUE Этот метод возвращает TRUE, если регистрация прошла успешно.
     * @throws InvalidArgumentException Исключение если:
     *         a). последний аргумент передаваемый в метод не callable;
     *         b). первый аргумент передаваемый в метод массив, а общее количество
     *             передаваемых аргументов больше 2.
     */
    public function overload()
    {
        if (isset($this)) {
            $args      = $this->_parseArgs(func_get_args());
            $fixedData = $this->_getFixedData($args[0], null);

            return $this->_initOverload($args[1], $fixedData);
        }

        return static::__callStatic('load', func_get_args());
    }

    /**
     * Этот метод позваляет переопределить либо удалить, в зависимости от типа и
     * количества передаваемых аргументов, ранее зарегестрированную функцию,
     * которая должна быть выполнена как перегруженная.
     *
     * Для того, чтобы воспользоваться переопределением функции, последним
     * аргументом передаваемым в метод должно быть значение типа "callable".
     *
     * Чтобы удалить ранее зарегестрированнцю функцию, последним аргументом
     * передаваемым в метод должно быть значение типа "boolean".
     *
     * При отсутствии последнего аргумента, т.е. когда аргумент либо отсутствует,
     * либо он имеет другой тип, отличный от типов "callable", "boolean" или
     * "NULL", будет выполнено удаление ранее зарегестрированной функции.
     *
     * Все аргументы, которые предшевствуют последнему аргументу, (если последний
     * аргумент имеет тип "callable" или "boolean" или "NULL"), должны иметь тип
     * "string" или "array".
     *
     * Предшествующие этому аргументу другие аргументы, если таковые имеются,
     * должны содержать значения типов для аргументов функции, которая должна
     * быть перегружена.
     *
     * Аргументы, которые предшевствуют последнему аргументу (если последний
     * аргумент имеет тип "callable" или "boolean" или "NULL"), должны иметь тип
     * "string" или "array".
     *
     * Если первый аргумент типа "string", то и все последующие аргументы, если
     * таковые имеются, за исключением последнего аргумента, должны быть также
     * типа "string". В противном случае будет выброшено исключение
     * InvalidArgumentException.
     *
     * Если первый аргумент типа "array",
     *
     *
     *
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
     * @param callable|boolean $callable_or_strict_match Значение, которое
     * может быть вызвано или
     * @return true|int Метод возвращает TRUE, в случае успешного
     * переопределения ранее добавленной перегружаемой функции.
     * В случае удаления метод возвращает число, количестов функций которых
     * было удалено.
     */
    public function override()
    {
        if (isset($this)) {
            $args      = $this->_parseArgs(func_get_args());
            $fixedData = $this->_getFixedData($args[0], null);

            return $this->_initOverride($args[1], $fixedData);
        }

        return static::__callStatic('ride', func_get_args());
    }

    /**
     * !
     * Инициирует вызов зарегестрированной функции.
     *
     * @param mixed Ноль или более параметров, передаваемые в зарегестрированную функцию.
     * @return mixed Возвращает результат выполнения зарегестрированной функции.
     */
    public function invokeTo()
    {
        return isset($this)
            ? $this->_initInvoke(func_get_args())
            : static::__callStatic('invoke', func_get_args());
    }

    /**
     * !
     * Инициирует вызов зарегестрированной функции.
     *
     * @param array $arguments Передаваемые в функцию параметры в виде массива.
     * @return mixed Возвращает результат выполнения зарегестрированной функции.
     */
    public function invokeArgsTo(array $arguments = null)
    {
        return isset($this)
            ? $this->_initInvoke((array) $arguments)
            : static::__callStatic('invokeargs', func_get_args());
    }

    /**
     * !
     * Магический метод __invoke перехватывает вызов объекта как функции и
     * инициирует вызов зарегестрированной функции.
     *
     * @param mixed Ноль или более параметров, передаваемые в зарегестрированную функцию.
     * @return mixed Возвращает результат выполнения зарегестрированной функции.
     */
    public function __invoke()
    {
        return $this->_initInvoke(func_get_args());
    }

    /**
     * Метод разбирает аргументы, передаваемые в методы overload и override.
     * Возвращает массив,
     *
     * @param array $arguments
     * @return array
     * @throws InvalidArgumentException
     */
    private function _parseArgs(array $arguments)
    {
        $ret       = array(null, null);
        $last_elem = end($arguments);

        if (is_callable($last_elem) || is_bool($last_elem)) {
            $ret[1] = array_pop($arguments);
        }

        $ret[0] = $arguments;

        if (isset($arguments[0]) && is_array($arguments[0])) {

            if (array_key_exists(1, $arguments)) {
                throw new InvalidArgumentException(self::$_errorMsg[self::ERRNO_INVALID_ARG_LIST]);
            }

            $ret[0] = $arguments[0];
        }

        return $ret;
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
    private function _initOverload($callable, SplFixedArray $fixedData)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException(self::$_errorMsg[self::ERRNO_ARG_NOT_CALLABLE]);
        }

        $reflection_data = $this->_getDataReflection($callable);
        $reflection_func = $reflection_data[0];

        if ($reflection_func->getNumberOfParameters() > $reflection_func->getNumberOfRequiredParameters()) {
            throw new LogicException(self::$_errorMsg[self::ERRNO_IS_OPTIONAL_ARGS]);
        }

        if ($reflection_func->getNumberOfParameters() <> $fixedData->size) {
            throw new LogicException(self::$_errorMsg[self::ERRNO_INVALID_SIZE_ARG]);
        }

        $parameters = $reflection_func->getParameters();

        $fixedData->rewind();
        while ($fixedData->valid()) {
            $parameter = $parameters[$fixedData->key()];

            if (!$this->_compareExpectedType($parameter, $fixedData->current())) {
                throw new DomainException(self::$_errorMsg[self::ERRNO_INVALID_ARG_TYPE]);
            }

            if ($parameter->isPassedByReference()) {
                $reflection_data['ByRef'][] = $parameter->getPosition();
            }

            $fixedData->next();
        }

        if (!$this->_add_function($reflection_data, $fixedData)) {
            throw new LogicException(self::$_errorMsg[self::ERRNO_OVERLOAD_EXIST]);
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
     * @throws @var:$this@mtd:_edit_function
     * @throws InvalidArgumentException
     */
    private function _initOverride($callable_or_strict_match,
        SplFixedArray $fixedData
    ) {
        if (is_callable($callable_or_strict_match)) {
            $ride = $this->_edit_function($callable_or_strict_match, $fixedData);

            if ($ride instanceof LogicException) {
                throw $ride;
            }

            return true;
        }

        return $this->_remove_function($callable_or_strict_match, $fixedData);
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
        $arguments = array_values($arguments);
        $i         = sizeof($arguments);
        while ($i-- && $arguments[$i] === null) {
            array_splice($arguments, $i, 1);
        }

        $types_of_function_arguments = array();
        ++$i;
        while ($i--) {
            $types_of_function_arguments[] = gettype($arguments[$i]);
        }

        $fixedData  = $this->_getFixedData(array_reverse($types_of_function_arguments), $hash);
        $pointer_id = $this->_getPointer($fixedData, $this->_pointersToOverloadedFunction);

        if ($pointer_id && $this->_reflectionsOfOverloadedFunctions->contains($pointer_id)) {

            $reflection_data = $this->_reflectionsOfOverloadedFunctions->offsetGet($pointer_id);
            $reflection_func = $reflection_data[0];

            if (isset($reflection_data['ByRef'])) {
                foreach ($reflection_data['ByRef'] as $num) {
                    $arguments[$num] = & $arguments[$num];
                }
            }

            return ($reflection_func instanceof ReflectionMethod)
                ? $reflection_func->invokeArgs($reflection_data[1], $arguments)
                : $reflection_func->invokeArgs($arguments);
        }

        throw new BadFunctionCallException(self::$_errorMsg[self::ERRNO_BAD_FUNC_CALL]);
    }

    private function _add_function(array $reflection_data,
        SplFixedArray $fixedData
    ) {
        $pointer_id = $this->_setPointer($fixedData, (object) ++$this->_indexOfPointers);

        if ($pointer_id) {
            $this->_reflectionsOfOverloadedFunctions->attach($pointer_id, $reflection_data);
            return true;
        }

        return false;
    }

    private function _remove_function($strict_match_types,
        SplFixedArray $fixedData
    ) {
        $ret          = 0;
        $pointers_id  = array();
        $ref_pointers = & $this->_pointersToOverloadedFunction;

        if ($fixedData->hash) {
            if (!isset($ref_pointers[$fixedData->hash])) {
                return $ret;
            }

            $ref_pointers = & $ref_pointers[$fixedData->hash];
        }

        if ($strict_match_types || $strict_match_types === null) {
            $pointers_id = $this->_deletePointer($fixedData, $ref_pointers);
        } else {
            $keys  = array_keys($ref_pointers);
            $range = range(-1, $fixedData->size - 1);

            foreach (array_diff($keys, $range) as $size) {
                $fixedData->size = $size;
                $pointers_id     = array_merge($pointers_id, $this->_deletePointer($fixedData, $ref_pointers));
            }
        }

        $ret = $i   = sizeof($pointers_id);
        while ($i--) {
            $this->_reflectionsOfOverloadedFunctions->detach($pointers_id[$i]);
        }

        unset($ref_pointers);
        return $ret;
    }

    private function _edit_function(callable $callable, SplFixedArray $fixedData)
    {
        $pointers_id = $this->_deletePointer($fixedData, $this->_pointersToOverloadedFunction);
        if (empty($pointers_id)) {
            return new LogicException(self::$_errorMsg[self::ERRNO_OVERLOAD_NEXIST]);
        }

        try {
            $this->_initOverload($callable, $fixedData);
        } catch (LogicException $exc) {
            $this->setPointer($fixedData, $pointers_id[0]);
            return $exc;
        }

        $this->_reflectionsOfOverloadedFunctions->detach($pointers_id[0]);
        return true;
    }

    private function _findPointers(array $container)
    {
        $ret   = array();
        $aiter = new RecursiveArrayIterator($container);
        $iiter = new RecursiveIteratorIterator($aiter, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iiter as $child) {
            if (is_object($child)) {
                $ret[] = $child;
            }
        }

        return $ret;
    }

    private function _setPointer(SplFixedArray $fixedData, stdClass $pointer_id)
    {
        $ref = & $this->_pointersToOverloadedFunction;

        if (isset($fixedData->hash)) {
            if (!isset($ref[$fixedData->hash])) {
                $ref[$fixedData->hash] = array();
            }

            $ref = & $ref[$fixedData->hash];
        }

        if (!isset($ref[$fixedData->size])) {
            $ref[$fixedData->size] = array();
        }

        $ref = & $ref[$fixedData->size];

        $fixedData->rewind();
        while ($fixedData->valid()) {
            $current = $fixedData->current();

            if (!isset($ref[$current])) {
                $ref[$current] = array();
            }

            $ref = & $ref[$current];
            $fixedData->next();
        }

        if (is_object($ref)) {
            return false;
        }

        $ref = $pointer_id;
        return $pointer_id;
    }

    private function _getPointer(SplFixedArray $fixedData, array $container)
    {
        if ($fixedData->hash) {
            if (!isset($container[$fixedData->hash])) {
                return null;
            }

            $container = & $container[$fixedData->hash];
        }

        if (!isset($container[$fixedData->size])) {
            return null;
        }

        $container = & $container[$fixedData->size];

        $fixedData->rewind();
        while ($fixedData->valid()) {
            if (isset($container[$fixedData->current()])) {
                $container = & $container[$fixedData->current()];
                $fixedData->next();
                continue;
            }

            return null;
        }

        return $container;
    }

    private function _deletePointer(SplFixedArray $fixedData, array &$container)
    {
        $ret = array();

        if ($fixedData->hash) {
            if (!isset($container[$fixedData->hash])) {
                return $ret;
            }

            $container = & $container[$fixedData->hash];
        }

        if (!isset($container[$fixedData->size])) {
            return $ret;
        }

        $container = & $container[$fixedData->size];

        $size          = sizeof($fixedData);
        $first_loop    = true;
        $last_value    = null;
        $ref_container = & $container;

        do {
            $fixedData->rewind();
            while ($size > $fixedData->key()) {
                if ($first_loop && !isset($container[$fixedData->current()])) {
                    break 2;
                }
                $container = & $container[$fixedData->current()];
                $fixedData->next();
            }
            $first_loop = false;

            if (is_object($container)) {
                $ret[]     = $container;
                $container = null;
            } else {
                if (isset($last_value)) {
                    if (1 > sizeof($container[$last_value])) {
                        unset($container[$last_value]);
                    } else {
                        break 1;
                    }
                } else {
                    $ret       = $this->_findPointers($container);
                    $container = null;
                }
            }

            if (isset($fixedData[--$size])) {
                $last_value = $fixedData[$size];
            }
            $container = & $ref_container;
        } while ($size > 0);

        if (isset($last_value) && (1 > sizeof($container[$last_value]))) {
            unset($container[$last_value]);
        }

        unset($ref_container);
        return $ret;
    }

    /**
     * !
     * Метод создает отражения функции или метода.
     *
     * Этот метод создает и возвращает массив, первым значением которого является
     * объект отражения функции или метода. Есди отражается метод, то вторым
     * значением массива будет NULL, если метод статический, или объект, содержащий
     * этот метод.
     *
     * @param callable $callable Значение типа callable.
     * @return array Возвращает массив с отражением функции или метода.
     */
    private function _getDataReflection(callable $callable)
    {
        if (is_string($callable) && strpos($callable, '::')) {
            $callable = explode('::', $callable);
        }

        return is_array($callable)
            ? array(new ReflectionMethod($callable[0], $callable[1]), is_string($callable[0])
                ? null
                : $callable[0])
            : array(new ReflectionFunction($callable));
    }

    /**
     * !
     * Метод сравнивает ожидаемое значение аргумента функции с установленным
     * значением для этого аргумента.
     *
     * @param ReflectionParameter $parameter Отражение аргумента.
     * @param string $type_of_arg Строковое значение установленной аргумента.
     * @return boolean Возвращает TRUE, если ожидаемое значение аргумента функции
     * совпадает с установленным значением для этого аргумента. В остальных
     * случаях метод вернет FALSE.
     */
    private function _compareExpectedType(ReflectionParameter $parameter,
        $type_of_arg
    ) {
        if ($parameter->isArray() && $type_of_arg !== self::TYPE_ARRAY) {
            return false;
        }

        if ($parameter->isCallable()
            && $type_of_arg !== self::TYPE_OBJECT
            && $type_of_arg !== self::TYPE_STRING
            && $type_of_arg !== self::TYPE_ARRAY
        ) {
            return false;
        }

        return true;
    }

    /**
     * !
     * Метод структурирует данные, на основе которых будет выполнен поиск
     * зарегестрированной функции.
     *
     * Этот метод получает массив, содержащий значения типов для аргументов
     * зарегестрированной функции. Массив может быть ассоциативным.
     * В статическом исполнении $hash - это хэш строки псевдонима
     * зарегестрированной функции.
     *
     * @param array $types Массив, содержащий значения типов для аргументов.
     * @param string|null $hash Хэш строки псевдонима.
     * @return SPLFixedArray Возвращает структуру данных SPLFixedArray.
     * @throws InvalidArgumentException Будет выброшено исключение, если
     *         1). тип значения типа аргумента не строка.
     * @throws DomainException Будет выброшено исключение, если
     *         1). значение типа неизвестно.
     */
    private function _getFixedData(array $types, $hash)
    {
        $size = sizeof($types);
        $data = new SplFixedArray($size);

        $data->hash = $hash;
        $data->size = $size;
        end($types);

        while ($size--) {
            $type = current($types);

            if (!is_string($type)) {
                throw new InvalidArgumentException(self::$_errorMsg[self::ERRNO_ARGS_NOT_STRING]);
            }

            switch ($type) {
                case 'string': case '%s':
                    $data[$size] = self::TYPE_STRING;
                    break;

                case 'integer': case 'int': case '%i':
                    $data[$size] = self::TYPE_INTEGER;
                    break;

                case 'array': case '%a':
                    $data[$size] = self::TYPE_ARRAY;
                    break;

                case 'boolean': case 'bool': case '%b':
                    $data[$size] = self::TYPE_BOOLEAN;
                    break;

                case 'object': case '%o':
                    $data[$size] = self::TYPE_OBJECT;
                    break;

                case 'double': case 'float': case '%d': case '%f': case 'real':
                    $data[$size] = self::TYPE_DOUBLE;
                    break;

                case 'resource': case '%r':
                    $data[$size] = self::TYPE_RESOURCE;
                    break;

                default :
                    throw new DomainException(self::$_errorMsg[self::ERRNO_INVALID_VAL_TYPE]);
            }

            prev($types);
        }

        return $data;
    }

    /**
     * !
     * Метод возвращает экземпляр этого класса.
     * Метод создает и возвращает одиночку в классе для статического исполнения.
     *
     * @return self Возвращает экземпляр этого класса.
     */
    static private function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self;
            self::$_listOfHashes = array();
        }

        return self::$_instance;
    }

    /**
     * !
     * Метод возвращает строковое значение переменной.
     *
     * @param mixed $var Переменная, которую необходимо преобразовать в строку.
     * @return string Возвращает строковое значение переменной.
     */
    static private function _fetchString($var)
    {
        $type = gettype($var);

        if ($type === 'array' || $type === 'object') {
            $var = (string) (float) !!$var;
            return $var;
        }

        if ($type === 'boolean' || $type === 'resource') {
            $var = (string) (float) $var;
            return $var;
        }

        return (string) $var;
    }

    /**
     * !
     * Метод возвращает хэш строки псевдонима для зарегестрированной функции.
     *
     * @param mixed $var Псевдоним для зарегестрированной функции.
     * @return string Возвращает SHA1-хэш строки псевдонима функции.
     */
    static private function _fetchHash($var)
    {
        $str = self::_fetchString($var);

        if (!isset(self::$_listOfHashes[$str])) {
            self::$_listOfHashes[$str] = sha1($str);
        }

        return self::$_listOfHashes[$str];
    }

}
