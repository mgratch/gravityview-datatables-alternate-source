# `[gv_math]` shortcode

The `[gv_math]` shortcode enables advanced math calculations and integrates with values populated by Gravity Forms and GravityView.

#### Available shortcode parameters

* `decimals` - The number of decimals to display. If undefined, displays the number of decimals returned by the math result.
* `format` - Whether or not to format the number. If set to `raw` - Display the number, unformatted. Otherwise,  format the number using WordPress' [number_format_i18n()](https://codex.wordpress.org/Function_Reference/number_format_i18n) function.
* `formula` - Instead of defining the formula inside the shortcode you can define it using the `formula` parameter. [More details below](#formula-parameter)
* `debug` - Show or hide error messages. Pass `true` or `1` to enable. (Default: `false`)
* `default_value` - If a value is not valid use the default value. Pass `true` or `1` to enable, or `skip` to skip the invalid value. (Default: `skip`)
* `notices` - Display notices to end users if there are warnings or errors associated with the formula. Pass `true` or `1` to enable. (Default: `false`)
    * `gravityview/math/accuracy_message` shortcode available to override default `warning` notice. (Default: `* Results may not be accurate.`)
    * `gravityview/math/no_results_message` shortcode available to override default `Error` notice. (Default: `** No Results Currently Available.`)
    * `gravityview/math/admin_notice` shortcode available to override default Admin notice. (Default: `You can only see this message because you are logged in and have permissions.`)
    
#### Parameters available when using Gravity Forms Merge Tags

* `scope` - If not defined, the shortcode performs [pure math](#pure-math).
    * [`(undefined)`](#pure-math) - Pure math calculations (default)
	* [`form`](#form-scope) - Use the sum of all the form values
	* [`entry`](#entry-scope) - Use the values of a single entry
	* [`visible`](#visible-scope) - Use the values of only visible entries for the current GravityView View
	* [`View`](#view-scope) - Use the values of all valid entries for the current GravityView View (not currently functional)
* `id` - The ID of the form, entry, or View (if `scope` is defined). Default: undefined

[Math functions](#math-functions) and [constants](#math-constants) are available in all scopes.

### Using shortcode parameters

#### The `decimals` parameter (Options: from `0` to `16`)

Specify the number of decimals you want to display.

- `[gv_math formula="PI" decimals="16" /]` = `3.1415926535897931`
- `[gv_math formula="PI" decimals="2" /]` = `3.14`
- `[gv_math formula="PI" decimals="0" /]` = `3`

Leave blank to use the number of decimals output by the equation.

- `[gv_math formula="5/2" /]` = `2.5`
- `[gv_math formula="5/2" decimals="2" /]` = `2.50`

There's a limit of 16 decimal places.

#### The `format` parameter (Option: `raw`)

- `[gv_math formula="PI * 1000000" /]` = `3,141,592.6536` (number-formatted)
- `[gv_math formula="PI * 1000000" format="raw" /]` = `3141592.6536` (no thousands separators)

#### The `formula` parameter <a id="formula-parameter"></a>

> Note: if content is also being passed inside `[gv_math]` shortcode tags, the `formula` parameter will be ignored.

You can define the mathematical formula using the `formula` parameter instead of inside the `[gv_math][/gv_math]` shortcode tags. Instead of `[gv_math] {formula} [/gv_math]`, you can use `[gv_math formula="{formula}" /]`).

**Example:**

```
[gv_math formula="10 * 10" /]
```

is the same as:

```
[gv_math] 10 * 10 [/gv_math]
```

-------------

## Pure Math Calculation: <a id="pure-math"></a>

**Pure math calculations don't require GravityView or Gravity Forms**.

You can perform pure calculations. Anything inside the shortcode (or defined using the `formula` shortcode parameter) is processed as a mathematical equation and is calculated. There should be no other text in the formula, just math!

**Basic math example:**

```
[gv_math] 2 * 300 [/gv_math]
```

Will return: `600`

**Example using the `PI` constant and `avg()` function:**

You can use constants and formula together:

```
[gv_math] PI / avg(7, 8, 9) [/gv_math]
```

Will return: `75.398223686`

**Example showing the use of the `decimals` parameter:**

```
[gv_math decimals="2" formula="PI" /]
```

Will return: `3.14`

**Another example showing the use of the `decimals` parameter:**

```
[gv_math decimals="0"] PI [/gv_math]
```

Will return: `3` *(mathematicians, please don't get mad)*

```
[gv_math formula="pow( 5, 2 )" /]
```

Will return: `25` because the function is asking for `5^2`. See the [Math Functions](#math-functions) section for more other mathematical formulas available.

--------

## Calculations for a single Gravity Forms Entry <a id="entry-scope"></a>

> For this example, Entry #123 has a field named "Number" with a value of 40, and a field named "OtherNumber" with a value of 100

**Display the value of a single numeric field:**

```
[gv_math scope="entry" id="123"]{Number:5}[/gv_math]
```

Will return: `40`

**Perform math functions:**

```
[gv_math scope="entry" id="123"]
avg( 0, {Number:5} )
[/gv_math]
```

Will return: `20` because it is the average of `0` and `40`

**Use multiple field values:**
```
[gv_math scope="entry" id="123"]
sum( 1, 5, 10, 20, {Number:5}, {OtherNumber:6} )
[/gv_math]
```

Will return: `176` because it is the sum of `1 + 5 + 10 + 20 + 40 + 100`.

**Field calculations**

```
There were [gv_math scope="entry" id="123" formula="{Number:5} + {OtherNumber:6}" /] Canada geese on the grass.
```

Will return: `There were 140 Canada geese on the grass.`

### When using in a GravityView Custom Content field

If you want to perform calculations using the current entry, **use `scope="entry"` without defining the `id` parameter**. This works for the Custom Content **Field**, not Custom Content Widget.

![Use in a Custom Content field](https://www.evernote.com/l/AARa9lSUreNMOqZ82jb7KTmEfktsLmnzLxUB/image.png)

------------------------------------------


## Form scope: calculations based on all entries in a Gravity Forms form <a id="form-scope"></a>

**Format: `[gv_math scope="form" id="{form id}"]`**

When working in the "Form" scope, the results are all based on the field values **for all form entries** that are not in the trash.

There are also special modifiers you can use to the Gravity Forms merge tags to fetch different values:

- `:sum` The sum of all field values for the form (this is also the default behavior for the `form` scope)
- `:max` The highest field value
- `:min` The lowest field value
- `:avg` The average of all field values
- `:count` The number of entries processed

> For the examples below, the form has 102 entries, with the Number field having values from -50 to +51

**Get the sum of field values**

The default behavior is to output a sum, so if you define `:sum` in the merge tag, it will return the same result as if you didn't. `{Number:5}` is the same as `{Number:5:sum}`.

```
[gv_math scope="form" id="9"]{Number:5}[/gv_math]
```

Will return: `51` because the sum of all numbers between `-50` and `50` is `51`.

**Get the number of form entries**

```
[gv_math scope="form" id="9"]{Number:5:count}[/gv_math]
```

Will return: `102` because there are 102 entries in the form and none are in the trash.


**Get the average of field values**

```
[gv_math scope="form" id="9"]{Number:5:avg}[/gv_math]
```

Will return: `0.5` because the average of all numbers between `-50` and `50` is `0.5`.

**Get the highest field value**

```
[gv_math scope="form" id="9"]{Number:5:max}[/gv_math]
```

Will return: `51` because the highest value for a Number field in our example form is `51`.

**Get the lowest field value**

```
[gv_math scope="form" id="9"]{Number:5:min}[/gv_math]
```

Will return: `-50` because the lowest value for a Number field in our example form is `-50`.

-------------------------

## Scope: Visible <a id="visible-scope"></a>

Use `scope="visible"` to use the visible entries as the value source. This works within a GravityView [Custom Content Field](http://docs.gravityview.co/article/111-using-the-custom-content-field)
or Custom Content Widget, as well as when placed on the same page as a `[gravityview]` shortcode.

The results will be modified based on the entries displayed in the View, so this:

```
### Sales summary:

* Total number of sales: [gv_math scope="visible" formula="{Sales:12:count}" format="raw" /]
* Total revenue: $[gv_math scope="visible" formula="{Product:3} + {Another Product:4}" decimals="2" /]
* Cost of goods sold:  $[gv_math scope="visible" formula="{Commission:3} + {CPC:4}" decimals="2" /]
* Profit:  $[gv_math scope="visible" decimals="2"] ( {Product:3} + {Another Product:4} ) - ( {Commission:3} + {CPC:4} ) [/gv_math]

[gravityview]
```

will become this:

```
### Sales summary:

* Total number of sales: 1300
* Total revenue: $39,430
* Cost of goods sold:  $8,173
* Profit:  $31,257

[gravityview]
```

**Note: The `scope="visible"` functionality will not work with the GravityView DataTables Extension**

-------------------

## Notes on shortcode use

- **Decimals require a leading number:** `50 + .5` is invalid because the decimal doesn't have a number in front of it. Use `50 + 0.5` instead.
- **If using a Gravity Forms merge tag for a price**, make sure the add `:price` to the end of the merge tag. This will convert the currency format (with currency symbol like $) to a number format:
    - **Total fields:** `{Total:32}` becomes `{Total:32:price}`
    - **Product fields:** `{Product Name (Price):28.2}` becomes `{Product Name (Price):28:price}` - note that you need to remove the `.2` from the end of the field number (`28.2` to `28`, for example)
- **Nest away!** You can nest parenthesis to your heart's delight.
_An equation like `( 1 + 2 ) + ( 3 / ( 4 * ( 5 + 6 ) ) )` will work just fine._
- **Extra spacing is not a problem** - you can use extra spacing in the formula inside the `[gv_math]` tags; line breaks inside the `formula` parameter aren't allowed.
Example of extra spacing that still works:
```
[gv_math]
avg(
    ( 100 / 3747 ),
    ( 48672 * 2746 )
)
[/gv_math]
```
### Mathematical precision
<a id="precision"></a>**The precision of the math functions is not exact** for numbers with many decimal digits.
The math library uses PHP's `float` definition, which is has a precision of roughly 14 decimal places [learn more about floats](http://floating-point-gui.de/basic/).
Because of this issue of precision, if the `decimal` parameter is not defined, the shortcode automatically limits
precision to 16 decimal places. This `16` limit can be overridden using the `gravityview/math/precision` filter.

```
// Turn off the limit altogether
add_filter( 'gravityview/math/precision', '__return_false' );

// Or you can modify the precision limit (here, to 14)
add_filter( 'gravityview/math/precision', function() { return 14; } );
```

----------------------------

## Math Functions <a id="math-functions"></a>

You can use the following mathematical functions:

* `abs()` - Returns the absolute value of a number [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/abs)
* `acos()` - Returns the arccosine (in radians) of a number [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/acos)
* `asin()` - Returns the arcsine (in radians) of a number [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/asin)
* `atan()` - Returns the arctangent (in radians) of a number [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/atan)
* `avg( num1, num2, etc )` - Returns the mean of all the items passed to the function.  
_Example:_ `avg( 3, 9, 27 )` returns `13` ( The sum of the numbers, `39`, divided by the item count `3`)
* `ceil()` - Returns the smallest integer greater than or equal to a given number. [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/ceil)  
_Example:_ `ceil( 10.284742 )` returns `11`
* `cos()` - Returns the cosine of a number [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/cos)
* `count()` - Returns the number of items passed to the function [Learn more](http://php.net/manual/en/function.count.php)  
_Example:_ `count( 1, 2, 3, 4 )` returns `4`
* `deg2rad()` - Returns the number of radians converted from the number in degrees [Learn more](http://php.net/manual/en/function.deg2rad.php)
* `exp()` - Returns e<sup>x</sup>, where x is the argument, and e is Euler's constant, the base of the natural logarithms. [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/exp)
* `floor()` - Returns the largest integer less than or equal to a given number [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/floor)
* `log( number, e )` -  Returns the natural logarithm (base e) of a number [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/log)
* `max( num1, num2, etc )` - Returns the largest of zero or more numbers [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/max)
* `min( num1, num2, etc )` - Returns the smallest of zero or more numbers [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/min)
* `pow( base, exponent )` - Returns the base to the exponent power, that is, base<sup>exponent</sup> [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/pow)
* `rad2deg()` - Returns the number of degrees converted from a radian number [Learn more](http://php.net/manual/en/function.rad2deg.php)
* `sin()` - Returns the sine of a number [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/sin)
* `sqrt()` - Returns the square root of a number [Learn more](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/sqrt)
* `sum( num1, num2, etc )` - Returns the total resulting from the addition of the items passed to the function

Any function can have math inside of it.

### Function examples:

#### Function: `floor()`

> In this example, a Gravity Forms merge tag is used. This is optional. For this example, `{Number:5}` equals `51`

```
[gv_math scope="form" id="9" decimals="0"]floor( {Number:5} + 0.6 )[/gv_math]
```

Returns: `51` - the function is performing calculations on `floor( 51.6 )`, so the next lowest integer to `51.6` is `51`.

```
[gv_math]floor( 51 + 0.6 )[/gv_math]
```

Returns: `51` - the function is performing calculations on `floor( 51.6 )`, so the next lowest integer to `51.6` is `51`.

```
[gv_math decimals="4"] 51 + 0.6 [/gv_math]
```

Will return `51.6000` because the `[gv_math]` shortcode has defined a `decimals` parameter with a value of `2`, so that # of decimals is used to output the value. If the `decimals` parameter hadn't been set, the value would have been `51.6`.

#### Function: `ceil()`

```
[gv_math]
ceil( ( 51 * 2 ) + ( 15 / 2 ) )
[/gv_math]
```

Will return: `110` because the formula inside the function (`( 51 * 2 ) + ( 15 / 2 )`) equals `109.5`. That value is then passed to the `ceil()` function, which finds the next highest integer. The next highest integer is `110`.

-------------------------------------

## Math Constants <a id="math-constants"></a>

The number values represented below are defined by PHP. We know the numbers below aren't all limited to 13 decimal places.

* `PI` - &pi; constant (Defaults to 13 decimal places: `3.1415926535898`)
* `PI_2` - &pi; constant / 2 (`1.5707963267949`)
* `PI_4` - &pi; constant / 4 (`0.78539816339745`)
~~~* `E` - _e_ constant (`2.718281828459`)~~
* `SQRT_PI` - Square root of &pi; (`0.63661977236758`)
* `SQRT_2` -  Square root of 2 (`1.4142135623731`)
* `SQRT_3` - Square root of 3 (`1.7320508075689`)
* `LN_PI` - The natural logarithm of &pi; (`1.1447298858494`)