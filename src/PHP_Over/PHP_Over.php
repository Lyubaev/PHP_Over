<?php
/**
 * PHP_Over регистрирует значение, которое может быть вызвано как
 * функция, по заданному количеству и/или значению типа аргумента,
 * которое должно быть перегружено в процессе вызова.
 *
 * Скорость выполнения перегруженной функции ниже, чем вызов
 * реальной функции, поэтому данный класс рекомендованно использовать
 * в ознокомительных, поучительных и развлекательных целях.
 * Если PHP_Over класс будет полезен в реальных проектах, мне было бы приятно
 * узнать об этом.
 *
 * PHP version 5
 *
 * Copyright (c) 2014, Kirill Lyubaev
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  PHP
 * @package   PHP_Over
 * @author    Kirill Lyubaev <lubaev.ka@gmail.com>
 * @copyright 2014 Kirill Lyubaev
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD New
 * @link      http://pear.php.net/package/
 */
/**
 * TRUE если текущая версия PHP ниже версии 5.4
 */
define('PHP_VERSION_LT54',
    version_compare(PHP_VERSION, '5.4', '<') ? true : false);

/**
 * PHP_Over регистрирует значение, которое может быть вызвано как
 * функция, и которое должно быть перегружено по типу и/или количеству
 * аргументов.
 *
 * PHP не поддерживает перегрузку функции, также отсутствует возможность
 * переопределить или удалить объявленную ранее функцию, поэтому класс
 * PHP_Over лишь имитирует эти процессы функции, используя
 * объектно-ореинтированные возможности языка.
 *
 * Определение того, какую версию перегруженной функции вызвать,
 * происходит непосредственно в момент вызова, исходя из типа
 * и/или количества аргументов.
 *
 * Значение, которое может быть вызванно, должно быть типа callable
 * (далее callback).
 * Смотри ({@link http://php.net/manual/en/language.types.callable.php
 * callable}).
 *
 * Значение типа должно быть указанно в виде строки в нижнем регистре.
 * Значением типа может быть любое значение поддерживаемого типа PHP,
 * исключая тип NULL и псевдотипы.
 * Смотри ({@link http://php.net/manual/en/language.types.intro.php types}).
 *
 * Значением типа может быть псевдоним реального значения типа (псевдозначение).
 * Список значений и их псевдозначений:
 * boolean  | bool | %b
 * integer  | int  | %i
 * double   | real | float | %d | %f
 * string   | %s
 * array    | %a
 * object   | %o
 * resource | %r
 *
 * Количество аргументов callback и количество передаваемых значений типов
 * для аргументов callback должно совпадать.
 *
 * callback не может содержать необязательные аргументы, так как это
 * повлечет за собой неоднозначность.
 *
 * Если в сигнатуре callback указан принимаемый тип значения,
 * (например array или callable PHP 5.4), то значение типа должно
 * соответствовать этому значению.
 *
 * @category  PHP
 * @package   PHP_Over
 * @author    Kirill Lyubaev <lubaev.ka@gmail.com>
 * @copyright 2014 Kirill Lyubaev
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD New
 * @version   Beta: 1.0
 * @link      http://pear.php.net/package/
 */
class PHP_Over
{
    /**
     * @var int Аргумент не может быть вызван в качестве функции.
     */
    const ERRNO_ARG_NOT_CALLABLE = 0x1;

    /**
     * @var int Аргумент фунции не может быть необязательным.
     */
    const ERRNO_IS_OPTIONAL_ARGS = 0x2;

    /**
     * @var int Количество аргументов и количество указанных типов не совпадает.
     */
    const ERRNO_INVALID_SIZE_ARG = 0x3;

    /**
     * @var int Порядок аргументов функции указан неверно.
     */
    const ERRNO_INVALID_ARG_LIST = 0x4;

    /**
     * @var int Указанный тип для аргумента не соответствует ожидаемому типу.
     */
    const ERRNO_INVALID_ARG_TYPE = 0x5;

    /**
     * @var int Указанный тип для аргумента неподдерживается.
     */
    const ERRNO_INVALID_VAL_TYPE = 0x6;

    /**
     * @var int Невалидный псевдоним функции.
     */
    const ERRNO_INVALID_NAME_FN = 0x7;

    /**
     * @var int Указанный тип для аргумента должен быть строкой.
     */
    const ERRNO_ARGS_NOT_STRING = 0x8;

    /**
     * @var int Переопределение неизвестной функции.
     */
    const ERRNO_OVERLOAD_NEXIST = 0x9;

    /**
     * @var int Функция уже была определена ранее.
     */
    const ERRNO_OVERLOAD_EXIST = 0xa;

    /**
     * @var int Вызов неопределенной ранее функции.
     */
    const ERRNO_BAD_FUNC_CALL = 0xb;

    /**
     * @var int Вызов неопределенного метода.
     */
    const ERRNO_BAD_METH_CALL = 0xc;

    /**
     * @var string Строковое представление типа.
     */
    const TYPE_RESOURCE = 'resource';

    /**
     * @var string Строковое представление типа.
     */
    const TYPE_BOOLEAN = 'boolean';

    /**
     * @var string Строковое представление типа.
     */
    const TYPE_INTEGER = 'integer';

    /**
     * @var string Строковое представление типа.
     */
    const TYPE_DOUBLE = 'double';

    /**
     * @var string Строковое представление типа.
     */
    const TYPE_STRING = 'string';

    /**
     * @var string Строковое представление типа.
     */
    const TYPE_OBJECT = 'object';

    /**
     * @var string Строковое представление типа.
     */
    const TYPE_ARRAY = 'array';

    /**
     * Предназначен для статических вызовов в клиентском коде.
     *
     * @var self Экземпляр этого класса.
     */
    static private $_instance;

    /**
     * Хэш массив используемых псевдонимов функций.
     *
     * @var array Содержит псевдоним функции и его хэш строку.
     */
    static private $_listOfHashes;

    /**
     * @var array Словарь сообщений об ошибках при работе класса.
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
     * @var int Текущий номер указателя.
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
     * Магический метод __callStatic перехватывает вызов недоступных
     * статических методов класса.
     *
     * @method TRUE load(mixed $alias, string|array $types, callable $callable)
     * Статический вариант метода PHP_Over::overload()
     *
     * @method int ride(mixed $alias, string|array $types, callable|bool $cOrB)
     * Статический вариант метода PHP_Over::override()
     *
     * @method mixed invoke($alias, mixed $arg)
     * Статический вариант метода PHP_Over::invokeTo()
     *
     * @method mixed invokeArgs($alias, array $args)
     * Статический вариант метода PHP_Over::invokeArgsTo()
     *
     * @param string $method    Имя вызываемого метода.
     * @param array  $arguments Числовой массив, содержащий параметры,
     *                          переданные в вызываемый метод $method.
     *
     * @return mixed Возвращает результат выполнения перегруженного метода.
     *
     * @throws InvalidArgumentException Исключение, если не указан псевдоним
     *                                  для регестрируемой функции.
     * @throws BadMethodCallException   Исключение, если вызов относится к
     *                                  неопределенному методу.
     * @see PHP_Over::overload(), PHP_Over::override()
     * @see PHP_Over::invokeTo(), PHP_Over::invokeArgsTo()
     */
    static public function __callStatic($method, $arguments)
    {
        $name = array_shift($arguments);

        if (!isset($name)) {
            throw new InvalidArgumentException(
            self::$_errorMsg[self::ERRNO_INVALID_NAME_FN]);
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

        throw new BadMethodCallException(
        self::$_errorMsg[self::ERRNO_BAD_METH_CALL]);
    }

    /**
     * Метод регестрирует функцию, которая должна быть перегружена
     * по заданному количеству аргументов, а также по указанным типам
     * для заданных аргуметов.
     *
     * Возможные значения типов для аргументов могут быть следуюущими:
     * string | %s | integer | int | %i | double | %d | float | %f | real
     * boolean | bool | %b | array | %a | object | %o | resource | %r
     *
     * @param array|string $types    Массив, либо ноль и более параметров
     *                               содержащий строковое значение ожидаемого
     *                               типа для аргументов функции, которая должна
     *                               быть выполнена как перегруженная.
     * @param callable     $callable Значение, которое может быть вызвано.
     *
     * @return self|TRUE При вызове метода в контексте объект, возвращает
     *                   объект, экземпляр этого класса. При вызове метода
     *                   в контексте класса возвращает TRUE.
     *
     * @throws InvalidArgumentException Исключение если:
     *                                  1). последний аргумент передаваемый
     *                                      в метод не может быть вызван в
     *                                      качестве функции;
     *                                  2). первый аргумент передаваемый в
     *                                      метод - массив, а общее количество
     *                                      передаваемых аргументов больше 2.
     */
    public function overload()
    {
        if (isset($this)) {
            list($types, $callable) = $this->_parseArgs(func_get_args());
            $fixedData = $this->_getFixedData($types, null);

            $this->_initOverload($callable, $fixedData);

            return $this;
        }

        return static::__callStatic('load', func_get_args());
    }

    /**
     * Метод позваляет переопределить либо удалить, в зависимости от типа и
     * количества передаваемых аргументов, ранее зарегестрированную функцию,
     * которая должна быть выполнена как перегруженная.
     *
     * Чтобы переопределить ранее зарегестрированную функцию, последним
     * аргументом передаваемым в метод должно быть значение, которое может быть
     * вызвано как функция.
     *
     * При попытке переопределить функцию, которая не была зарегестрирована
     * ранее, будет выброшено исключение.
     *
     * Чтобы удалить ранее зарегестрированную функцию, последним
     * аргументом передаваемым в метод может быть TRUE, FALSE либо отсутствие
     * аргумента. Последнее идентично значению TRUE.
     *
     * Все аргументы, которые предшествуют последнему аргументу
     * (далее аргументы типов), либо все аргументы включительно, если
     * последний аргумент отсутствует (например, при удалении), должны
     * быть либо строкового типа, либо быть массивом, либо полное их отсутствие.
     *
     * Для удаления или переопределения функции, которая не принимала
     * ни один аргумент, аргументы типов можно опустить.
     *
     * Пример:
     * $obj = new PHP_Over;
     *
     * Удалить функцию, перегружаемаю без аргументов.
     * $obj->override();
     *
     * Удалить функцию, перегружаемаю без аргументов.
     * $obj->override(true);
     *
     * Удалить все зарегестрированные функции в этом объекте,
     * которые должны быт перегружены.
     * $obj->override(false);
     *
     * Переопределить функцию на функцию zend_version.
     * $obj->override('zend_version');
     *
     * Количество аргументов типов соответствует количеству аргументов функции,
     * а их значения должны указывать на тип аргументов функции, которая
     * должна быть перегружена.
     *
     * Пример:
     * $obj = new PHP_Over;
     *
     * Удалить ранее зарегестрированную функцию, которая принимала один
     * аргумент типа string.
     * $obj->override('string');
     *
     * Удалить все ранее зарегестрированные функции, которые принимали
     * минимум один аргумент, и тип первого аргумента соответствует типу
     * double.
     * $obj->override('double', false);
     *
     * Переопределить ранее зарегестрированную функцию, которая принимала один
     * аргумент типа array на функцию array_pop.
     * $obj->override('array', 'array_pop');
     *
     *
     * Аргументы типов можно передать в виде массива. В этом случае значения
     * указывающие на тип для аргументов функции, должны быть перечислены
     * в массиве, а сам массив должен быть единственным аргументом из аргументов
     * типа.
     *
     * Пример:
     * $obj = new PHP_Over;
     *
     * Удалить ранее зарегестрированную функцию, которая принимала один
     * аргумент типа string.
     * $obj->override(array('string'));
     *
     * Удалить все ранее зарегестрированные функции, которые принимали
     * минимум два аргумента, где тип первого аргумента соответствует типу
     * object, тип второго аргумента соответствует типу bool.
     * $obj->override(array('object','bool'), false);
     *
     * Переопределить ранее зарегестрированную функцию, которая принимала один
     * аргумент типа array на функцию array_pop.
     * $obj->override(array('array'), 'array_pop');
     *
     * Если первым аргументом передан массив, то следующим аргументом
     * за ним долен быть аргумент тип которого соответствует типам callable
     * или boolean, либо отсутутствие аргумента. В остальных случаях
     * будет выброшено исключение, сообщающее о непраильном списке
     * аргументов.
     *
     * @param array|string  $types      Массив, либо ноль и более параметров
     *                                  содержащий строковое значение типов
     *                                  для аргументов функции, которую
     *                                  необходимо переопределить или
     *                                  удалить.
     * @param callable|bool $callOrBool Значение, которое может быть вызвано,
     *                                  либо TRUE или FALSE.
     *
     * @return self|int|TRUE При вызове метода в контексте объект, возвращает
     *                       объект, экземпляр этого класса. При вызове метода
     *                       в контексте класса, при удалении вернет количество
     *                       удаленных функций, при переопределении вернет TRUE.
     */
    public function override()
    {
        if (isset($this)) {
            list($types, $callOrBool) = $this->_parseArgs(func_get_args());
            $fixedData = $this->_getFixedData($types, null);

            $this->_initOverride($callOrBool, $fixedData);

            return $this;
        }

        return static::__callStatic('ride', func_get_args());
    }

    /**
     * Инициирует вызов зарегестрированной функции.
     *
     * @param mixed  Ноль или более параметров, передаваемые в
     *               зарегестрированную функцию.
     *
     * @return mixed Возвращает результат выполнения зарегестрированной функции.
     */
    public function invokeTo()
    {
        return isset($this) ?
            $this->_initInvoke(func_get_args()) :
            static::__callStatic('invoke', func_get_args());
    }

    /**
     * Инициирует вызов зарегестрированной функции с массивом параметров.
     *
     * @param array $arguments Передаваемые в функцию параметры в виде массива.
     *
     * @return mixed Возвращает результат выполнения зарегестрированной функции.
     */
    public function invokeArgsTo(array $arguments = null)
    {
        return isset($this) ?
            $this->_initInvoke((array) $arguments) :
            static::__callStatic('invokeargs', func_get_args());
    }

    /**
     * Магический метод __invoke перехватывает вызов объекта как функции и
     * инициирует вызов зарегестрированной функции.
     *
     * @param mixed  Ноль или более параметров, передаваемые в
     *               зарегестрированную функцию.
     *
     * @return mixed Возвращает результат выполнения зарегестрированной функции.
     */
    public function __invoke()
    {
        return $this->_initInvoke(func_get_args());
    }

    /**
     * Метод разбирает аргументы, передаваемые в методы overload и override.
     *
     * Возвращает массив с индексами 0 и 1.
     * Значение с индексом 0 это массив указанных типов для аргументов функции.
     * Значение с индексом 1 это одно из значений типа callable, boolean, NULL.
     *
     * @param array $arguments Аргумент, которые были приняты
     *                         методом overload или override.
     *
     * @return array Возвращает массив с индексами 0 и 1.
     *
     * @throws InvalidArgumentException Исключение если:
     *                                  1). список разбираемых аргументов
     *                                      составлен неверно;
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
     * Внутренний метод регистрации функции, которая должна быть
     * перегружена.
     *
     * @param callable      $callable  Значение, которое может быть вызвано.
     * @param SplFixedArray $fixedData Набор данных, содержащий информацию о
     *                                 типах и количестве аргументов.
     *
     * @return TRUE Возвращает TRUE в случае успеха.
     *
     * @throws LogicException Исключение если:
     *                        1). аргумент регестрируемой функции,
     *                            является необязательным;
     *                        2). количество аргументов регестрируемой функции
     *                            и количество указанных типов не соответствует;
     *                        3). регестрируемая функция уже была определена
     *                            ранее.
     * @throws DomainException Исключение если:
     *                         1). указанный тип для аргумента
     *                             не соответствует ожидаемому типу.
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
     * Внутренний метод переопределения функции, которая должна быть
     * перегружена.
     *
     * @param callable|boolean|NULL $callableOrStrictMatch Значение которое
     *                                                     может быть вызвано,
     *                                                     либо TRUE или FALSE
     *                                                     или NULL.
     * @param SplFixedArray         $fixedData             Набор данных,
     *                                                     содержащий информацию
     *                                                     о типах и количестве
     *                                                     аргументов.
     *
     * @return boolean|int При удалении вернет количество удаленных функций,
     *                     при переопределении вернет TRUE.
     *
     * @throws @var:$this@mtd:_editFunction Будет выброшено исключение,
     *                                      которое может возникнуть при
     *                                      регистрации новой функции в методе
     *                                      _initOverload().
     * @see PHP_Over::_initOverload()
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
     * Внутренний метод вызывающий зарегестрированную функцию с массивом
     * параметоров, по заданному количеству и значениям типов этих
     * параметов.
     *
     * Значения типа NULL, которые могут присутствовать в конце списка
     * переданных аргументов будут вырезаны. Это сделано с целью передавать
     * все аргументы функции заглушки. Смотри demo-2.php
     *
     * Если зарегестрированная функция ожидает получить аргумент по ссылке, то
     * такой аргумент будет передан по ссылке.
     *
     * @param array  $arguments Передаваемые в функцию параметры в виде массива.
     * @param string $hash      Псевдоним функции в виде хэш-строки.
     *
     * @return mixed Возвращает результат выполнения зарегестрированной функции.
     *
     * @throws BadFunctionCallException Исключение если:
     *                                  1). вызов неопределенной ранее функции.
     */
    private function _initInvoke(array $arguments, $hash = null)
    {
        $fixedData = new SplFixedArray();
        $arguments = array_values($arguments);
        $i         = sizeof($arguments);

        while ($i--) {
            if ($arguments[$i] === null) {
                array_pop($arguments);
                continue;
            }

            $fixedData->setSize(1 + $i);
            do {
                $fixedData[$i] = gettype($arguments[$i]);
            } while ($i > 0 && $i--);
        }

        $fixedData->hash = $hash;
        $fixedData->size = $fixedData->getSize();

        $pointerId = $this->_getPointer($fixedData,
            $this->_pointersToOverloadedFunction);

        if ($pointerId && $this->_reflectionsOfOverloadedFunctions->contains($pointerId)) {

            $reflectionData = $this->_reflectionsOfOverloadedFunctions->offsetGet($pointerId);

            if (isset($reflectionData['ByRef'])) {
                foreach ($reflectionData['ByRef'] as $num) {
                    $arguments[$num] = & $arguments[$num];
                }
            }

            return ($reflectionData[0] instanceof ReflectionFunction) ?
                $reflectionData[0]->invokeArgs($arguments) :
                $reflectionData[0]->invokeArgs($reflectionData[1], $arguments);
        }

        throw new BadFunctionCallException(self::$_errorMsg[self::ERRNO_BAD_FUNC_CALL]);
    }

    /**
     * Внутренний метод создает новый указатель на функцию, которая
     * должна быть перегружена, и производит попытку установить указатель.
     *
     * @param array         $reflectionData Массив, содержащий отражение
     *                                      функции или метода, которое будет
     *                                      вызвано при перегрузке.
     * @param SplFixedArray $fixedData      Набор данных, содержащий информацию
     *                                      о типах и количестве аргументов.
     *
     * @return boolean Возвращает TRUE, если указатель был установлен,
     *                 иначе возвращается FALSE.
     *
     * @see PHP_Over::_initInvoke(), PHP_Over::_getFixedData().
     */
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

    /**
     * Внутренний метод удаляет указатель на функцию, которая должна быть
     * перегружена, а также данные отражения, которые связаны с этим указателем.
     *
     * В зависимости от параметра $strictMatchTypes метод находит указатель,
     * либо группу указаетлей, если он(и) установлены.
     *
     * @param type          $strictMatchTypes Если установлено в FALSE, будут
     *                                        удалены все указатели
     *                                        на зарегистрированные функции,
     *                                        количество аргументов у которых
     *                                        больше или равно количеству типов
     *                                        в наборе данных $fixedData,
     *                                        а их значения соответствуют
     *                                        значениям типов для аргументов
     *                                        функции. Смотри override().
     * @param SplFixedArray $fixedData        Набор данных, содержащий
     *                                        информацию о типах и количестве
     *                                        аргументов.
     *
     * @return int Возвращает количество функций, которое было удалено.
     */
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

    /**
     * Внутренний метод предпринимает попытку переопределить ранее
     * зарегестрированную функцию на новую, и регистрирует ее в случае
     * успеха.
     *
     * Метод удаляет старый указатель на функцию, прежде чем зарегестрирвать
     * новый.
     * Метод не выбрасывает исключение, а лишь создает его объет,
     * и возвращает его в вызываемый метод, если старый указатель не найден.
     *
     * Если указатель найден и извлечен, то на его место будет установлен новый
     * указатель, для чего вызов передается в метод _initOverload().
     * Если в методе _initOverload() будет выброшено исключение, то старый
     * указатель будет восстановлен, а пойманный объект исключения
     * будет возвращет в вызываемый метод.
     *
     * @param type          $callable  Значение которое может быть вызвано.
     * @param SplFixedArray $fixedData Набор данных, содержащий информацию
     *                                 о типах и количестве аргументов.
     *
     * @return LogicException|TRUE     Возвращает объект исключения,
     *                                 если в процессе переопределения возникла
     *                                 исключительная ситуация,
     *                                 иначе возвращает TRUE.
     *
     * @see PHP_Over::_initOverload().
     */
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

    /**
     * Метод наход все указатели в переданном массиве, и возвращает
     * новый массив содержащий все найденные указатели.
     *
     * @param array $container Массив, в котором будет производиться поиск
     *                         объетов-указателей.
     *
     * @return array Возвращает новый массив содержащий все найденные указатели.
     */
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

    /**
     * Метод устанавливает указатель по заданному набору данных $fixedData.
     *
     * @param SplFixedArray $fixedData Набор данных, содержащий информацию
     *                                 о типах и количестве аргументов.
     * @param stdClass      $pointerId Устанавливаемый объект-указатель.
     *
     * @return stdClass|FALSE Возвращает установленный объект-указатель,
     *                        иначе FALSE, если указатель уже был установлен
     *                        ранее.
     */
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

    /**
     * Метод находит все указатели по заданному набору данных $fixedData.
     *
     * В отличии от метода _deletePointer(), этот метод не удаляет указатель.
     *
     * @param SplFixedArray $fixedData Набор данных, содержащий информацию
     *                                 о типах и количестве аргументов.
     * @param array         $container Массив, содержащий указатели.
     *
     * @return stdClass|NULL Возвращает найденный объект-указатель либо NULL.
     */
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

    /**
     * Метод находит все указатели по заданному набору данных $fixedData
     * в массиве $container, и вырезает их, таким образом модифицируя
     * исходный массив.
     *
     * @param SplFixedArray $fixedData Набор данных, содержащий информацию
     *                                 о типах и количестве аргументов.
     * @param array         $container Массив, содержащий указатели.
     *
     * @return array Возвращает массив, содержащий все найденные указатели.
     */
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
     * Метод создает отражения функции или метода.
     *
     * Этот метод создает и возвращает массив, первым значением которого
     * является объект отражения функции или метода. Есди отражается метод,
     * то вторым значением массива будет NULL, если метод статический,
     * или объект, содержащий этот метод.
     *
     * @param callable $callable Значение которое может быть вызвано.
     *
     * @return array Возвращает массив, содержащий объект отражения
     *               вызываемого значения, а также объект метода, если
     *               вызываемое значение это нестатический метод этого объекта.
     */
    private function _getDataReflection($callable)
    {
        if (is_string($callable) && strpos($callable, '::')) {
            $callable = explode('::', $callable);
        }

        return is_array($callable) ?
            array(new ReflectionMethod($callable[0], $callable[1]),
            !is_string($callable[0]) ? $callable[0] : null) :
            array(new ReflectionFunction($callable));
    }

    /**
     * Метод сравнивает ожидаемое значение аргумента функции с установленным
     * значением для этого аргумента.
     *
     * @param ReflectionParameter $parameter Объект отражения аргумента.
     * @param string              $typeOfArg Указанное значение типа для этого
     *                                       аргумента.
     *
     * @return boolean Возвращает TRUE, если ожидаемое значение аргумента
     *                 функции совпадает с установленным значением для этого
     *                 аргумента. В остальных случаях метод вернет FALSE.
     */
    private function _compareExpectedType(ReflectionParameter $parameter,
        $typeOfArg
    ) {
        if ($parameter->isArray() && $typeOfArg !== self::TYPE_ARRAY) {
            return false;
        }

        if (!PHP_VERSION_LT54 && $parameter->isCallable() && $typeOfArg !== self::TYPE_OBJECT
            && $typeOfArg !== self::TYPE_STRING && $typeOfArg !== self::TYPE_ARRAY
        ) {
            return false;
        }

        return true;
    }

    /**
     * Метод структурирует данные, на основе которых будет выполнен поиск
     * зарегестрированной функции.
     *
     * Этот метод получает массив, содержащий значения типов для аргументов
     * зарегестрированной функции. Массив может быть ассоциативным.
     * В статическом исполнении $hash - это хэш строки псевдонима
     * зарегестрированной функции.
     *
     * @param array       $types Массив, содержащий значения
     *                           типов для аргументов.
     * @param string|null $hash  Хэш строки псевдонима.
     *
     * @return SPLFixedArray Возвращает набор данных в виде
     *                       объекта SPLFixedArray.
     *
     * @throws InvalidArgumentException Исключение если:
     *                                  1). аргумент, содержащий значение типа
     *                                      не string.
     * @throws DomainException          Исключение если:
     *                                  1). указанное значение типа
     *                                      неподдерживается.
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
     * Метод возвращает экземпляр этого класса.
     *
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
     * Метод возвращает строковое значение переменной.
     *
     * @param mixed $var Переменная, которую необходимо преобразовать в строку.
     *
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
     * Метод возвращает хэш строки псевдонима для зарегестрированной функции.
     *
     * @param mixed $var Псевдоним для зарегестрированной функции.
     *
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
