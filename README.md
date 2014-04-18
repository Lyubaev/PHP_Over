# PHP_Over

> Перегрузка функций — это механизм, который позволяет двум родственным функциям иметь одинаковые имена.

### Допустим
Допустим необходимо реализовать функции, которые будут вычислить объем следующих фигур:
* куб
* шар
* цилиндр
* конус
* пирамида
* прямоугольный параллелепипед

```php
// Объем куба
function Volume_Cube(/*int*/ $side)
{
    return pow($side, 3);
}

// Объем шара
function Volume_Sphere(/*double*/ $radius)
{
    return ((4/3) * M_PI * pow($radius, 3));
}

// Объем цилиндра
function Volume_Cylinder(/*double*/ $radius, /*int*/ $height)
{
    return (M_PI * pow($radius, 2) * $height);
}

// Объем конуса
function Volume_Сone(/*int*/ $height, /*double*/ $radius)
{
    return ((1/3) * M_PI * pow($radius, 2) * $height);
}

// Объем пирамиды
function Volume_Pyramid(/*int*/ $square, /*int*/ $height)
{
    return ((1/3) * $square * $height);
}

// Объем прямоугольного параллелепипеда
function Volume_Cuboid(/*int*/ $length, /*int*/ $width, /*int*/ $height)
{
    return ($length * $width * $height);
}
```

Это делает ситуацию сложнее, чем она есть на самом деле. Другими словами, при одних и тех же действиях (вычисление объема) программисту необходимо помнить имена всех шести функций вместо одного.

**PHP_Over** регистрирует значение, которое может быть вызвано как функция, по заданному количеству и/или значению типа аргумента, которое должно быть перегружено в процессе вызова.

```php
require 'src/php_over/PHP_Over.php';

$Volume = new PHP_Over;
$Volume
    ->overload('%i',
        function($side)
    {
        return pow($side, 3);
    })
    ->overload('%d',
        function($radius)
    {
        return ((4 / 3) * M_PI * pow($radius, 3));
    })
    ->overload('%d', '%i',
        function($radius, $height)
    {
        return (M_PI * pow($radius, 2) * $height);
    })
    ->overload('%i', '%d',
        function($height, $radius)
    {
        return ((1 / 3) * M_PI * pow($radius, 2) * $height);
    })
    ->overload('%i', '%i',
        function($square, $height)
    {
        return ((1 / 3) * $square * $height);
    })
    ->overload('%i', '%i', '%i',
        function($length, $width, $height)
    {
        return ($length * $width * $height);
    });
    
$Volume(5);        // 125
$Volume(5.);       // 523.5987755983
$Volume(3., 10);   // 282.74333882308
$Volume(10, 2.);   // 41.887902047864
$Volume(15, 9);    // 45
$Volume(15, 9, 3); // 405

```

Теперь достаточно знать только одно имя `$Volume`, а для вычисления объема требуемой фигуры необходимо указать то количество и те типы аргументов, которые требуются.

### Допустим
Допустим необходимо изменить набор фигур для которых требуется вычислить объем:
* ~~**куб**~~
* шар
* цилиндр
* конус
* ~~**пирамида**~~
* прямоугольный параллелепипед
* <u>**правильный тетраэдр**</u>
* <u>**призма**</u>

```php
// ... Part 1

$Volume
    ->override('%i',
        function($edge)
    {
        return (pow($edge, 3) * sqrt(2) / 12);
    })
    ->override('%i', '%i',
        function($square, $height)
    {
        return ($square * $height);
    });
    
$Volume->invokeTo(5);        // 14.73139127472
$Volume->invokeTo(5.);       // 523.5987755983
$Volume->invokeTo(3., 10);   // 282.74333882308
$Volume->invokeTo(10, 2.);   // 41.887902047864
$Volume->invokeTo(15, 9);    // 135
$Volume->invokeTo(15, 9, 3); // 405
```

### Допустим
Допустим необходимо избавиться от некоторых перегружаемых функций, в процессе выполнения:
* правильный тетраэдр
* **~~шар~~**
* **~~цилиндр~~**
* **~~конус~~**
* призма
* прямоугольный параллелепипед

```php
// ... Part 1
// ... Part 2

$Volume
    ->override('%i', '%d')
    ->override('%d', false);

function wrapperToVolume()
{
    global $Volume;

    try {
        return $Volume->invokeArgsTo(func_get_args());
    } catch (Exception $exp) {
        return $exp->getMessage();
    }
}

wrapperToVolume(5);         // 14.73139127472
wrapperToVolume(5.);        // Вызов неопределенной ранее функции
wrapperToVolume(3., 10);    // Вызов неопределенной ранее функции
wrapperToVolume(10, 2.);    // Вызов неопределенной ранее функции
wrapperToVolume(15, 9);     // 135
wrapperToVolume(15, 9, 3);  // 405
```

### Обзор класса

```php
PHP_Over {
    /* Методы */
    public __construct()
    public mixed __invoke ([ mixed $parameter [, mixed $... ]] )
    public mixed invokeTo ([ mixed $parameter [, mixed $... ]] )
    public mixed invokeArgsTo ( array $param_arr )
    public static mixed invoke ( mixed $alias, [mixed $parameter [, mixed $... ]] )
    public static mixed invokeArgs ( mixed $alias, array $param_arr )
    public PHP_Over overload ( string|array $types, callable $callback )
    public PHP_Over overload ( callable $callback )
    public PHP_Over override ();
    public PHP_Over override ( boolean $strictMatch );
    public PHP_Over override ( string|array $types );
    public PHP_Over override ( string|array $types, boolean $strictMatch );
    public PHP_Over override ( callable $callback );
    public PHP_Over override ( string|array $types, callable $callback );
    public static boolean load ( mixed $alias, callable $callback )
    public static boolean load ( mixed $alias, string|array $types, callable $callback )
    public static mixed ride ( mixed $alias );
    public static mixed ride ( mixed $alias, boolean $strictMatch );
    public static mixed ride ( mixed $alias, string|array $types );
    public static mixed ride ( mixed $alias, string|array $types, boolean $strictMatch );
    public static mixed ride ( mixed $alias, callable $callback );
    public static mixed ride ( mixed $alias, string|array $types, callable $callback );
}
```

* * *

Скорость выполнения перегруженной функции ниже, чем вызов реальной функции, поэтому данный класс рекомендовано использовать в ознакомительных, поучительных и развлекательных целях.
Если PHP_Over класс будет полезен в реальных проектах, мне было бы приятно узнать об этом.