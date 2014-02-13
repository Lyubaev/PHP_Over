<?php
/**
 * PHP_Over
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_Over
 * @author    Kirill Lyubaev <lubaev.ka@gmail.com>
 * @copyright 2014 Kirill Lyubaev
 * @license   http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://pear.php.net/package/
 */
/**
 * TRUE если текущая версия PHP ниже версии 5.4
 */
define('PHP_VERSION_LT54',
    version_compare(PHP_VERSION, '5.4', '<') ? true : false);

/**
 * PHP_Over
 *
 * @category  PHP
 * @package   PHP_Over
 * @author    Kirill Lyubaev <lubaev.ka@gmail.com>
 * @copyright 2014 Kirill Lyubaev
 * @license   http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version   Beta: 1.0
 * @link      http://pear.php.net/package/
 */
class PHP_Over
{
    /**
     * Номер ошибки.
     *
     * Аргумент не может быть вызван в качестве функции.
     * @var int
     */
    const ERRNO_ARG_NOT_CALLABLE = 0x1;

    /**
     * Номер ошибки.
     *
     * Аргумент фунции не может быть необязательным.
     * @var int
     */
    const ERRNO_IS_OPTIONAL_ARGS = 0x2;

    /**
     * Номер ошибки.
     *
     * Количество аргументов и количество указанных типов не совпадает.
     * @var int
     */
    const ERRNO_INVALID_SIZE_ARG = 0x3;

    /**
     * Номер ошибки.
     *
     * Порядок аргументов функции указан неверно.
     * @var int
     */
    const ERRNO_INVALID_ARG_LIST = 0x4;

    /**
     * Номер ошибки.
     *
     * Указанный тип для аргумента не соответствует ожидаемому типу.
     * @var int
     */
    const ERRNO_INVALID_ARG_TYPE = 0x5;

    /**
     * Номер ошибки.
     *
     * Указанный тип для аргумента неподдерживается.
     * @var int
     */
    const ERRNO_INVALID_VAL_TYPE = 0x6;

    /**
     * Номер ошибки.
     *
     * Невалидный псевдоним функции.
     * @var int
     */
    const ERRNO_INVALID_NAME_FN = 0x7;

    /**
     * Номер ошибки.
     *
     * Указанный тип для аргумента должен быть строкой.
     * @var int
     */
    const ERRNO_ARGS_NOT_STRING = 0x8;

    /**
     * Номер ошибки.
     *
     * Переопределение неизвестной функции.
     * @var int
     */
    const ERRNO_OVERLOAD_NEXIST = 0x9;

    /**
     * Номер ошибки.
     *
     * Функция уже была определена ранее.
     * @var int
     */
    const ERRNO_OVERLOAD_EXIST = 0xa;

    /**
     * Номер ошибки.
     *
     * Вызов неопределенной ранее функции.
     * @var int
     */
    const ERRNO_BAD_FUNC_CALL = 0xb;

    /**
     * Номер ошибки.
     *
     * Вызов неопределенного метода.
     * @var int
     */
    const ERRNO_BAD_METH_CALL = 0xc;

    /**
     * Строковое представление типа.
     *
     * @var string
     */
    const TYPE_RESOURCE = 'resource';

    /**
     * Строковое представление типа.
     *
     * @var string
     */
    const TYPE_BOOLEAN = 'boolean';

    /**
     * Строковое представление типа.
     *
     * @var string
     */
    const TYPE_INTEGER = 'integer';

    /**
     * Строковое представление типа.
     *
     * @var string
     */
    const TYPE_DOUBLE = 'double';

    /**
     * Строковое представление типа.
     *
     * @var string
     */
    const TYPE_STRING = 'string';

    /**
     * Строковое представление типа.
     *
     * @var string
     */
    const TYPE_OBJECT = 'object';

    /**
     * Строковое представление типа.
     *
     * @var string
     */
    const TYPE_ARRAY = 'array';

    /**
     * Внутренний экзеипляр этого класса.
     * Предназначен для статических вызовов в клиентском коде.
     *
     * @var self
     */
    static private $_instance;

    /**
     * Хэш массив используемых псевдонимов функций.
     *
     * Содержит псевдоним функции и его хэш строку.
     *
     * @var array
     */
    static private $_listOfHashes;

    /**
     * Словарь сообщений об ошибках при работе класса.
     *
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
     * Текущий номер указателя.
     *
     * @var int
     */
    private $_indexOfPointers;

    /**
     * Содержит указатели на зарегестрированные функции.
     *
     * Структура этого массива многомерна. Индекс первого измерения
     * соответствует количеству аргументов регестрируемой функции.
     * Количество вложенных измерений равно количеству аргументов функции.
     * Каждый индекс вложенного измерения содержит тип аргумента регестрируемой
     * функции. Первое вложенное измрение это первый аргумент, второе измерение
     * это второй аргумент и т.д. Значением последненго измерения будет
     * указатель. Указатель это объект класса stdClass, внутри которого
     * содержится номер (идентификатор) указателя.
     *
     * @var array
     */
    private $_pointersToOverloadedFunction;

    /**
     * Соответствие объекты-данные.
     *
     * В роли объектов выступают указатели на зарегестрированную функцию.
     * В роли данных выступает массив, содержащий отражение зарегестрированной
     * функции, а также дополниельные данные, которые необходимы для ее вызова.
     *
     * @var SplObjectStorage
     */
    private $_reflectionsOfOverloadedFunctions;

    /**
     * Конструктор этого класса.
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
     * @method TRUE load(mixed $alias, string|array $types, callable $callable)
     * Статический вариант метода PHP_Over::overload()
     * @method int ride(mixed $alias, string|array $types, callable|bool $cOrB)
     * Статический вариант метода PHP_Over::override()
     * @method mixed invoke($alias, mixed $arg)
     * Статический вариант метода PHP_Over::invokeTo()
     * @method mixed invokeArgs($alias, array $args)
     * Статический вариант метода PHP_Over::invokeArgsTo()
     *
     * @param string $method    Имя вызываемого метода.
     * @param array  $arguments Числовой массив, содержащий параметры,
     *                          переданные в вызываемый метод $method.
     *
     * @return mixed Возвращает результат выполнения перегруженного метода.
     * @throws InvalidArgumentException Исключение, если не указан псевдоним
     *                                  для регестрируемой функции.
     * @throws BadMethodCallException Исключение, если вызов относится к
     *                                неопределенному методу.
     * @see PHP_Over::overload(), PHP_Over::override()
     * @see PHP_Over::invokeTo(), PHP_Over::invokeArgsTo()
     */
    static public function __callStatic($method, $arguments)
    {
        $name = array_shift($arguments);

        if (!isset($name)) {
            throw new InvalidArgumentException(self::$_errorMsg[self::ERRNO_INVALID_NAME_FN]);
        }

        $initMethod = "_initover{$method}";
        if (method_exists(__CLASS__, $initMethod)) {
            $self      = self::_getInstance();
            $args      = $self->_parseArgs($arguments);
            $fixedData = $self->_getFixedData($args[0], self::_fetchHash($name));

            return $self->$initMethod($args[1], $fixedData);
        }

        $initMethod = "{$method}to";
        if (method_exists(__CLASS__, $initMethod)) {
            $self = self::_getInstance();
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
     * <code>
     * $closure1 = function() {
     *   return 1;
     * };
     *
     * $php_over = new PHP_Over();
     *
     * <p>Регестрирует замыкание без аргументов.</p>
     * $php_over->overload($closure);
     *
     * <p>Регестрирует функцию с именем chmod, которая должна быть
     * перегружена по двум параметрам, первый их которых имеет тип string,
     * второй имеет тип integer.</p>
     * $php_over->overload('%s', '%i', 'chmod');
     *
     * <p>Регестрирует функцию с именем strrev, которая должна быть
     * перегружена по одному параметру тип которого string.</p>
     * $php_over->overload(array('%s'), 'strrev');
     * </code>
     *
     * @param array|string $types    Массив, либо ноль и более параметров
     *                               содержащий строковое значение ожидаемого
     *                               типа для аргументов функции, которая должна
     *                               быть выполнена как перегруженная.
     * @param callable     $callable Значение, которое может быть вызвано.
     *
     * @return self Этот метод возвращает TRUE, если регистрация прошла успешно.
     *
     * @throws InvalidArgumentException Исключение если:
     *         a). последний аргумент передаваемый в метод не может быть
     *             вызван в качестве функции;
     *         b). первый аргумент передаваемый в метод массив, а общее
     *             количество передаваемых аргументов больше 2.
     */
    public function overload()
    {
        if (isset($this)) {
            $args      = $this->_parseArgs(func_get_args());
            $fixedData = $this->_getFixedData($args[0], null);

            $this->_initOverload($args[1], $fixedData);

            return $this;
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
     * перегружаемую функцию. В этом случае значение $callableOrStrictMatch
     * должно быть типа callable.
     * Метод также позволяет удалить ранее добавленную перегружаемую функцию.
     * В этом случае значение $callableOrStrictMatch должно быть типа boolean
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
     * $typesOfFunctionArguments.
     * Пример:
     * PHP_Over::override(array("string", "integer",), false);
     *
     * В этом примере будут удалены все ранее добавленные перегружаемые функции,
     * которые принимали два и более аргумента и типы двух первых аргументов,
     * соответствуют указанным значениям типов для этих аргументов.
     *
     * @param string[] $typesOfFunctionArguments Массив, содержащий значения
     * типов для аргументов перегруженной функции.
     * Порядок значений в массиве соответствует порядку аргументов перегруженной
     * функции.
     * @param callable|boolean $callableOrStrictMatch Значение, которое
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

            $result = $this->_initOverride($args[1], $fixedData);

            return $this;
        }

        return static::__callStatic('ride', func_get_args());
    }

    /**
     * !
     * Инициирует вызов зарегестрированной функции.
     *
     * @param mixed Ноль или более параметров, передаваемые в зарегестрированную
     * функцию.
     *
     * @return mixed Возвращает результат выполнения зарегестрированной функции.
     */
    public function invokeTo()
    {
        return isset($this) ? $this->_initInvoke(func_get_args()) : static::__callStatic('invoke',
                func_get_args());
    }

    /**
     * !
     * Инициирует вызов зарегестрированной функции.
     *
     * @param array $arguments Передаваемые в функцию параметры в виде массива.
     *
     * @return mixed Возвращает результат выполнения зарегестрированной функции.
     */
    public function invokeArgsTo(array $arguments = null)
    {
        return isset($this) ? $this->_initInvoke((array) $arguments) : static::__callStatic('invokeargs',
                func_get_args());
    }

    /**
     * !
     * Магический метод __invoke перехватывает вызов объекта как функции и
     * инициирует вызов зарегестрированной функции.
     *
     * @param mixed Ноль или более параметров, передаваемые в зарегестрированную
     * функцию.
     *
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
        $ret      = array(null, null);
        $lastElem = end($arguments);

        if (is_callable($lastElem) || is_bool($lastElem)) {
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

        $reflectionData = $this->_getDataReflection($callable);
        $reflectionFunc = $reflectionData[0];

        if ($reflectionFunc->getNumberOfParameters() > $reflectionFunc->getNumberOfRequiredParameters()) {
            throw new LogicException(self::$_errorMsg[self::ERRNO_IS_OPTIONAL_ARGS]);
        }

        if ($reflectionFunc->getNumberOfParameters() <> $fixedData->size) {
            throw new LogicException(self::$_errorMsg[self::ERRNO_INVALID_SIZE_ARG]);
        }

        $parameters = $reflectionFunc->getParameters();

        $fixedData->rewind();
        while ($fixedData->valid()) {
            $parameter = $parameters[$fixedData->key()];

            if (!$this->_compareExpectedType($parameter, $fixedData->current())) {
                throw new DomainException(self::$_errorMsg[self::ERRNO_INVALID_ARG_TYPE]);
            }

            if ($parameter->isPassedByReference()) {
                $reflectionData['ByRef'][] = $parameter->getPosition();
            }

            $fixedData->next();
        }

        if (!$this->_addFunction($reflectionData, $fixedData)) {
            throw new LogicException(self::$_errorMsg[self::ERRNO_OVERLOAD_EXIST]);
        }

        return true;
    }

    /**
     * Метод инициирует переопределение функции, которая была добавлена ранее.
     *
     * @param callable|boolean|NULL $callableOrStrictMatch
     * @param SplFixedArray $fixedData Данные, которые включат в себя некоторую
     * информацию, на основе которой будет определена перегружаемая пользователем
     * функция.
     * @return boolean
     * @throws LogicException
     * @throws @var:$this@mtd:_editFunction
     * @throws InvalidArgumentException
     */
    private function _initOverride($callableOrStrictMatch,
        SplFixedArray $fixedData
    ) {
        if (is_callable($callableOrStrictMatch)) {
            $ride = $this->_editFunction($callableOrStrictMatch, $fixedData);

            if ($ride instanceof LogicException) {
                throw $ride;
            }

            return true;
        }

        return $this->_removeFunction($callableOrStrictMatch, $fixedData);
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

        $typesOfFunctionArguments = array();
        ++$i;
        while ($i--) {
            $typesOfFunctionArguments[] = gettype($arguments[$i]);
        }

        $fixedData = $this->_getFixedData(array_reverse($typesOfFunctionArguments),
            $hash);
        $pointerId = $this->_getPointer($fixedData,
            $this->_pointersToOverloadedFunction);

        if ($pointerId && $this->_reflectionsOfOverloadedFunctions->contains($pointerId)) {

            $reflectionData = $this->_reflectionsOfOverloadedFunctions->offsetGet($pointerId);
            $reflectionFunc = $reflectionData[0];

            if (isset($reflectionData['ByRef'])) {
                foreach ($reflectionData['ByRef'] as $num) {
                    $arguments[$num] = & $arguments[$num];
                }
            }

            return ($reflectionFunc instanceof ReflectionMethod) ? $reflectionFunc->invokeArgs($reflectionData[1],
                    $arguments) : $reflectionFunc->invokeArgs($arguments);
        }

        throw new BadFunctionCallException(self::$_errorMsg[self::ERRNO_BAD_FUNC_CALL]);
    }

    private function _addFunction(array $reflectionData,
        SplFixedArray $fixedData
    ) {
        $pointerId = $this->_setPointer($fixedData,
            (object) ++$this->_indexOfPointers);

        if ($pointerId) {
            $this->_reflectionsOfOverloadedFunctions->attach($pointerId,
                $reflectionData);
            return true;
        }

        return false;
    }

    private function _removeFunction($strictMatchTypes, SplFixedArray $fixedData
    ) {
        $ret         = 0;
        $pointersId  = array();
        $refPointers = & $this->_pointersToOverloadedFunction;

        if ($fixedData->hash) {
            if (!isset($refPointers[$fixedData->hash])) {
                return $ret;
            }

            $refPointers     = & $refPointers[$fixedData->hash];
            $fixedData->hash = null;
        }

        if ($strictMatchTypes || $strictMatchTypes === null) {
            $pointersId = $this->_deletePointer($fixedData, $refPointers);
        } else {
            $keys  = array_keys($refPointers);
            $range = range(-1, $fixedData->size - 1);

            foreach (array_diff($keys, $range) as $size) {
                $fixedData->size = $size;
                $pointersId      = array_merge($pointersId,
                    $this->_deletePointer($fixedData, $refPointers));
            }
        }

        $ret = $i   = sizeof($pointersId);
        while ($i--) {
            $this->_reflectionsOfOverloadedFunctions->detach($pointersId[$i]);
        }

        unset($refPointers);
        return $ret;
    }

    private function _editFunction($callable, SplFixedArray $fixedData)
    {
        $pointersId = $this->_deletePointer($fixedData,
            $this->_pointersToOverloadedFunction);

        if (empty($pointersId)) {
            return new LogicException(self::$_errorMsg[self::ERRNO_OVERLOAD_NEXIST]);
        }

        try {
            $this->_initOverload($callable, $fixedData);
        } catch (LogicException $exc) {
            $this->setPointer($fixedData, $pointersId[0]);
            return $exc;
        }

        $this->_reflectionsOfOverloadedFunctions->detach($pointersId[0]);
        return true;
    }

    private function _findPointers(array $container)
    {
        $ret   = array();
        $aiter = new RecursiveArrayIterator($container);
        $iiter = new RecursiveIteratorIterator($aiter,
            RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iiter as $child) {
            if (is_object($child)) {
                $ret[] = $child;
            }
        }

        return $ret;
    }

    private function _setPointer(SplFixedArray $fixedData, stdClass $pointerId)
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

        $ref = $pointerId;
        return $pointerId;
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

        $size         = sizeof($fixedData);
        $firstLoop    = true;
        $lastValue    = null;
        $refContainer = & $container;

        do {
            $fixedData->rewind();
            while ($size > $fixedData->key()) {
                if ($firstLoop && !isset($container[$fixedData->current()])) {
                    break 2;
                }
                $container = & $container[$fixedData->current()];
                $fixedData->next();
            }
            $firstLoop = false;

            if (is_object($container)) {
                $ret[]     = $container;
                $container = null;
            } else {
                if (isset($lastValue)) {
                    if (1 > sizeof($container[$lastValue])) {
                        unset($container[$lastValue]);
                    } else {
                        break 1;
                    }
                } else {
                    $ret       = $this->_findPointers($container);
                    $container = null;
                }
            }

            if (isset($fixedData[--$size])) {
                $lastValue = $fixedData[$size];
            }
            $container = & $refContainer;
        } while ($size > 0);

        if (isset($lastValue) && (1 > sizeof($container[$lastValue]))) {
            unset($container[$lastValue]);
        }

        unset($refContainer);
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
    private function _getDataReflection($callable)
    {
        if (is_string($callable) && strpos($callable, '::')) {
            $callable = explode('::', $callable);
        }

        return is_array($callable) ? array(new ReflectionMethod($callable[0],
                $callable[1]),
            is_string($callable[0]) ? null : $callable[0]) : array(new ReflectionFunction($callable));
    }

    /**
     * !
     * Метод сравнивает ожидаемое значение аргумента функции с установленным
     * значением для этого аргумента.
     *
     * @param ReflectionParameter $parameter Отражение аргумента.
     * @param string $typeOfArg Строковое значение установленной аргумента.
     * @return boolean Возвращает TRUE, если ожидаемое значение аргумента функции
     * совпадает с установленным значением для этого аргумента. В остальных
     * случаях метод вернет FALSE.
     */
    private function _compareExpectedType(ReflectionParameter $parameter,
        $typeOfArg
    ) {
        if ($parameter->isArray() && $typeOfArg !== self::TYPE_ARRAY) {
            return false;
        }

        if (!PHP_VERSION_LT54 && $parameter->isCallable()
            && $typeOfArg !== self::TYPE_OBJECT
            && $typeOfArg !== self::TYPE_STRING
            && $typeOfArg !== self::TYPE_ARRAY
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
    static private function _getInstance()
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
