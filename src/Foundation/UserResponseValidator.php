<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Foundation;

use Prinx\Str;
use Prinx\Utils\Date;

/**
 * Defines the methods to easily validate the user's response.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class UserResponseValidator
{
    /**
     * Custom errors.
     *
     * @var array
     */
    protected static $customErrors = [];

    /**
     * When defined, forces any validation rule not to use its default error
     * message but rather look for the error message in the $customErrors array
     * at the index $errorLookupIndex, if that index is not
     * empty.
     *
     * This is useful for the validation rules that are based on other
     * validation rules (like the `isAge` or `isAmount`)
     *
     * Let's say the developer has return as validation rule ['age' => 'Your
     * age is invalid, please']; Because the validation of age is based on
     * another validation rules, the developer will not get their error message
     * if the user enters an invalid input. Instead, they will have the error
     * message for each of the validation rules that made up the `age` rule.
     * Then this variable will help us to force thoses validations rules not to
     * use their own error message but to rather look for the error message in
     * the customErrors array at the index $errorLookupIndex, if this index is
     * not empty. It was a long explanation, but don't judge me. This is to
     * myself :)
     *
     * @var string
     */
    protected static $errorLookupIndex = '';

    /**
     * Validate the response against the defined rules.
     *
     * @param string       $response
     * @param string|array $validationRules
     *
     * @throws \RuntimeException
     *
     * @return \stdClass
     */
    public static function validate($response, $validationRules)
    {
        $validation = new \stdClass();
        $validation->validated = true;

        $rules = $validationRules;
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        } elseif (!is_array($rules)) {
            throw new \RuntimeException('The validation rules must be a string or an array');
        }

        foreach ($rules as $key => $value) {
            $ruleAndError = self::extractRuleAndError($key, $value);
            $rule = $ruleAndError['rule'];
            $customError = $ruleAndError['error'];

            $explodedRule = explode(':', $rule);
            $rule = Str::pascalCase($explodedRule[0]);
            $method = 'is'.$rule;

            if ($customError) {
                self::$customErrors[strtolower($rule)] = $customError;
            }

            if (method_exists(self::class, $method)) {
                $arguments = isset($explodedRule[1]) ? explode(',', $explodedRule[1]) : [];

                $specific = call_user_func([self::class, $method], $response, ...$arguments);

                if (!$specific->validated) {
                    $validation->validated = false;
                    $validation->error = $specific->error;
                }

                break;
            }

            throw new \RuntimeException('Unknown validation rule `'.$explodedRule[0].'`');
        }

        self::$errorLookupIndex = '';

        return $validation;
    }

    /**
     * Extract the validation rule and custom error from a line of the
     * validation array.
     *
     * @param string|int   $key
     * @param string|array $value
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    public static function extractRuleAndError($key, $value)
    {
        $rule = $value;
        $customError = '';

        if (is_string($key)) {
            $rule = $key;

            if (!is_string($value)) {
                throw new \RuntimeException("The custom validation error for the rule '".$rule."' must be a string");
            }

            $customError = $value;
        } elseif (is_array($value)) {
            if (empty($value)) {
                throw new \RuntimeException('One of the validation rules is an empty array. Kindly add a rule or remove the empty validation');
            }

            $rule = $value['rule'] ?? $value[0] ?? '';
            $errorIndex = isset($value['rule']) ? 0 : 1;
            $customError = $value['error'] ?? $value[$errorIndex] ?? '';
        }

        if (!$rule) {
            throw new \RuntimeException('Empty validation rule unsupported. Kindly add a rule or remove the empty validation');
        }

        return [
            'rule'  => $rule,
            'error' => $customError,
        ];
    }

    /**
     * Check if a value is string.
     *
     * @param mixed $str
     *
     * @return bool
     */
    public static function isString($str)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!is_string($str)) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['string'] ??
                'The response must be a string';
        }

        return $v;
    }

    public static function isMin($num, $min)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!(floatval($num) >= floatval($min))) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['min'] ??
                'The minimum is '.$min;
        }

        return $v;
    }

    public static function isMax($num, $max)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!(floatval($num) <= floatval($max))) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['max'] ??
                'The maximum is '.$max;
        }

        return $v;
    }

    public static function isMinLength($str, $minLen)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!Str::isMinLength($str, intval($minLen))) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['minlength'] ??
                'At least '.$minLen.' characters';
        }

        return $v;
    }

    public static function isMaxLength($str, $maxLen)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!Str::isMaxLength($str, intval($maxLen))) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['maxlength'] ??
                'At most '.$maxLen.' characters';
        }

        return $v;
    }

    public static function isMinLen($str, $num)
    {
        self::$errorLookupIndex = 'minlen';
        $validation = self::isMinLength($str, $num);
        self::$errorLookupIndex = '';

        return $validation;
    }

    public static function isMaxLen($str, $num)
    {
        self::$errorLookupIndex = 'maxlen';
        $validation = self::isMaxLength($str, $num);
        self::$errorLookupIndex = '';

        return $validation;
    }

    public static function isAlpha($str)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!Str::isAlphabetic($str)) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['alpha'] ??
                'Invalid character in the response';
        }

        return $v;
    }

    public static function isAlphabetic($str)
    {
        self::$errorLookupIndex = 'alphabetic';

        return self::validate($str, 'alpha');
    }

    public static function isAlphaNum($str)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!Str::isAlphanumeric($str)) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['alphanum'] ??
                'Invalid character in the response';
        }

        return $v;
    }

    public static function isAlphaNumeric($str)
    {
        self::$errorLookupIndex = 'alphanumeric';

        return self::validate($str, 'alphanum');
    }

    public static function isNumeric($str)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!Str::isNumeric($str)) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['numeric'] ??
                'The response must be a number';
        }

        return $v;
    }

    public static function isInteger($str)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!Str::isNumeric($str)) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['integer'] ??
                'The response must be an integer';
        }

        return $v;
    }

    public static function isInt($str)
    {
        self::$errorLookupIndex = 'int';

        return self::validate($str, 'integer');
    }

    public static function isFloat($str)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!Str::isFloatNumeric($str)) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['float'] ??
                'Number expected';
        }

        return $v;
    }

    public static function isAmount($str)
    {
        self::$errorLookupIndex = 'amount';

        return self::validate($str, 'numeric|min:0');
    }

    public static function isTel($str)
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!Str::isTelNumber($str)) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['tel'] ??
                'Invalid phone number.';
        }

        return $v;
    }

    public static function isRegex($str, $pattern)
    {
        $v = new \stdClass();
        $v->validated = true;

        $matched = preg_match($pattern, $str);
        if ($matched === 0) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['regex'] ??
                'The response does not match the pattern.';
        } elseif ($matched === false) {
            throw new \Exception('Error in the validation regex: '.$pattern);
        }

        return $v;
    }

    public static function isDate($date, $format = 'j/n/Y')
    {
        $v = new \stdClass();
        $v->validated = true;

        if (!Date::isDate($date, $format)) {
            $v->validated = false;
            $v->error = self::$customErrors[self::$errorLookupIndex] ??
            self::$customErrors['date'] ??
                'Invalid date.';
        }

        return $v;
    }

    public static function isAge($str)
    {
        self::$errorLookupIndex = 'age';

        return self::validate($str, [
            'integer' => 'The age must be a number',
            'min:0'   => 'The age must be greater than 0',
            'max:100' => 'The age must be less than 100',
        ]);
    }

    public static function isName($str)
    {
        self::$errorLookupIndex = 'name';

        return self::validate($str, 'alpha|min_len:3|max_len:50');
    }
}
