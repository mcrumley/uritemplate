<?php
/*
 * This file is part of the UriTemplate package.
 *
 * (c) Michael Crumley <m@michaelcrumley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UriTemplate;

/**
 * RFC 6570 URI Template processor
 * http://tools.ietf.org/html/rfc6570
 */
class UriTemplate
{

const TEMPLATE_OPEN = '{';
const TEMPLATE_CLOSE = '}';
const OPERATORS = '+#./;?&';

private static $OPERATOR_SETTINGS = array(
    ''  => array('start' => '' , 'join' => ',', 'ifemp' => '' , 'named' => false, 'safe' => ''),
    '+' => array('start' => '' , 'join' => ',', 'ifemp' => '' , 'named' => false, 'safe' => ":/?#[]@!$&'()*+,;="),
    '.' => array('start' => '.', 'join' => '.', 'ifemp' => '' , 'named' => false, 'safe' => ''),
    '/' => array('start' => '/', 'join' => '/', 'ifemp' => '' , 'named' => false, 'safe' => ''),
    ';' => array('start' => ';', 'join' => ';', 'ifemp' => '' , 'named' => true , 'safe' => ''),
    '?' => array('start' => '?', 'join' => '&', 'ifemp' => '=', 'named' => true , 'safe' => ''),
    '&' => array('start' => '&', 'join' => '&', 'ifemp' => '=', 'named' => true , 'safe' => ''),
    '#' => array('start' => '#', 'join' => ',', 'ifemp' => '' , 'named' => false, 'safe' => ":/?#[]@!$&'()*+,;="),
);

/**
 * Expand template as a URI Template using variables.
 *
 * Throws a UriTemplate exception if the template is invalid.
 * The exception object has a getErrors() method that returns a list of individual errors.
 *
 * @param  string $template  URI template
 * @param  mixed  $variables Array or object containing values to insert
 * @param  array  $options   Optional settings - open, close, sortKeys
 *                           open - string to mark the beginning of a substitution expression
 *                           close - string to mark the end of a substitution expression
 *                           sortKeys - sort arrays by key in output
 * @return string            Expanded URI
 */
public static function expand($template, $variables, array $options = array())
{
    if (is_object($variables)) {
        $variables = get_object_vars($variables);
    } elseif (!is_array($variables)) {
        throw new UriTemplateException('$variables must be an array or object');
    }

    $result = $template;
    $errors = array();
    $expressions = self::getExpressions($template, $options);
    $i = count($expressions);
    while ($i-- > 0) {
        try {
            $expression = $expressions[$i];
            if (!$expression['valid']) {
                throw new UriTemplateException('Malformed expression: "%s" at offset %d', array($expression['complete'], $expression['offset']));
            }
            $sub = UriTemplate::expandExpression($expression['expression'], $variables, isset($options['keySort']) ? (bool)$options['keySort'] : false);
            $result = substr_replace($result, $sub, $expression['offset'], strlen($expression['complete']));
        } catch (UriTemplateException $e) {
            $errors[] = $e->getMessage();
            continue;
        }
    }

    if ($errors) {
        throw new UriTemplateException('Invalid URI Template string: "%s"', array($template), $errors);
    }
    return $result;
}

/**
 * Get a list of errors in a URI Template.
 *
 * Some errors can only be detected when processing the template.
 * E.g. using the prefix modifier on an array value ("{var:2}" where var is an array)
 *
 * @param  string $template  URI template
 * @param  array  $options   Optional settings - open, close
 *                           open - string to mark the beginning of a substitution expression
 *                           close - string to mark the end of a substitution expression
 * @return array             List of parser errors (an empty array means there were no errors)
 */
public static function getErrors($template, array $options = array()) {
    $errors = array();
    foreach (self::getExpressions($template, $options) as $expression) {
        if (!$expression['valid']) {
            $errors[] = 'Malformed expression: "'.$expression['complete'].'" at offset '.$expression['offset'];
            continue;
        }
        try {
            $expression = self::parseExpression($expression['expression']);
            foreach ($expression['varspecs'] as $varspec) {
                try {
                    $varspec = self::parseVarspec($varspec);
                } catch (UriTemplateException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        } catch (UriTemplateException $e) {
            $errors[] = $e->getMessage();
        }
    }
    return $errors;
}

/**
 * Get a list of variables used in a URI Template.
 *
 * @param  string $template  URI template
 * @param  array  $options   Optional settings - open, close
 *                           open - string to mark the beginning of a substitution expression
 *                           close - string to mark the end of a substitution expression
 * @return array             List of variables referenced by the template
 */
public static function getVariables($template, array $options = array())
{
    $varnames = array();
    foreach (self::getExpressions($template, $options) as $expression) {
        if ($expression['valid']) {
            try {
                $expression = self::parseExpression($expression['expression']);
                foreach ($expression['varspecs'] as $varspec) {
                    try {
                        $varspec = self::parseVarspec($varspec);
                        $varnames[] = $varspec['varname'];
                    } catch (UriTemplateException $e) {
                        continue;
                    }
                }
            } catch (UriTemplateException $e) {
                continue;
            }
        }
    }
    return array_unique($varnames);
}

private static function expandExpression($template, $variables, $keySort)
{
    $expression = self::parseExpression($template);
    $result = array();
    foreach ($expression['varspecs'] as $varspec) {
        $varspec = self::parseVarspec($varspec);

        if (isset($variables[$varspec['varname']])) {
            $value = $variables[$varspec['varname']];
            if (is_object($value)) {
                $value = get_object_vars($value);
            }
        } else {
            $value = null;
        }

        if ((is_array($value) || is_object($value)) && $varspec['prefix'] !== null) {
            throw new UriTemplateException('Prefix modifier used with array value: %s', array($template));
        }

        if (is_array($value)) {
            $value = array_filter($value, function ($v) {
                return $v !== null;
            });
        }
        if (($value !== null && count($value) !== 0) || ($value === array() && $expression['ifemp'] && $varspec['explode'] === false)) {
            $result[] = self::expandVar($expression, $varspec, $value, $keySort);
        }
    }
    if (count($result) > 0) {
        return $expression['start'] . implode($expression['join'], $result);
    } else {
        return '';
    }
}

private static function getExpressions($template, array $options)
{
    $open = self::TEMPLATE_OPEN;
    $close = self::TEMPLATE_CLOSE;
    extract($options, EXTR_IF_EXISTS);

    // shortcut if there are no variables to expand
    if (strpos($template, $open) === false && strpos($template, $close) === false) {
        return array();
    }

    $preg_open = preg_quote($open, '/');
    $preg_close = preg_quote($close, '/');
    $expressions = array();
    if (preg_match_all("/(({$preg_open}(?<expression>.*?)((?<close>{$preg_close})|$))|{$preg_close})/", $template, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($matches as $match) {
            $offset = $match[0][1];
            $valid = isset($match['expression']) && isset($match['close']);
            $expression = $valid ? $match['expression'][0] : '';
            $complete = $match[0][0];
            $expressions[] = array('offset' => $offset, 'valid' => $valid, 'expression' => $expression, 'complete' => $complete);
        }
    }
    return $expressions;
}

private static function urlQuote($value, $safe = '')
{
    $safe = str_split($safe);
    $safe_url = array_map('rawurlencode', $safe);
    $value = rawurlencode($value);
    return str_replace($safe_url, $safe, $value);
}

private static function keyVal($keys, $values, $join, $explode, $ifemp, $safe)
{
    if ($explode) {
        $separator = '=';
    } else {
        $join = ',';
        $separator = ',';
    }
    $keyVals = array();
    foreach ($keys as $i => $key) {
        $value = $values[$i];
        if ($value !== '') {
            $keyVals[] = self::urlQuote($key, $safe) . $separator . self::urlQuote($value, $safe);
        } else {
            $keyVals[] = self::urlQuote($key, $safe) . $ifemp;
        }
    }
    return implode($join, $keyVals);
}

private static function arrVal($values, $join, $explode, $safe)
{
    $arrVals = array();
    foreach ($values as $val) {
        $arrVals[] = self::urlQuote($val, $safe);
    }
    return implode($explode ? $join : ',', $arrVals);
}

private static function expandVar($expression, $varspec, $value, $keySort)
{
    if (is_array($value)) {
        return self::expandArray($expression, $varspec, $value, $keySort);
    } else {
        return self::expandString($expression, $varspec, $value);
    }
}

private static function expandArray($expression, $varspec, $value, $keySort)
{
    if ($expression['named'] && !$varspec['explode']) {
        $start = $varspec['varname'] . '=';
    } else {
        $start = '';
    }

    if (self::realArray($value)) {
        if ($expression['named'] && $varspec['explode']) {
            return self::keyVal(array_fill(0, count($value), $varspec['varname']), $value, $expression['join'], $varspec['explode'], $expression['ifemp'], $expression['safe']);
        }
        return $start . self::arrVal($value, $expression['join'], $varspec['explode'], $expression['safe']);
    } else {
        if ($keySort) {
            ksort($value);
        }
        return $start . self::keyVal(array_keys($value), array_values($value), $expression['join'], $varspec['explode'], $expression['ifemp'], $expression['safe']);
    }
}

private static function expandString($expression, $varspec, $value)
{
    if ($expression['named']) {
        if (strlen($value) === 0) {
            $start = $varspec['varname'] . $expression['ifemp'];
        } else {
            $start = $varspec['varname'] . '=';
        }
    } else {
        $start = '';
    }
    return $start . self::urlQuote($varspec['prefix'] === null ? $value : substr($value, 0, $varspec['prefix']), $expression['safe']);
}

private static function parseExpression($expression)
{
    if ($expression === '') {
        throw new UriTemplateException('Empty expression');
    }
    $operator = '';
    if (strpos(self::OPERATORS, substr($expression, 0, 1)) !== false) {
        $operator = substr($expression, 0, 1);
        $varspecs = substr($expression, 1);
    } else {
        $varspecs = $expression;
    }
    $varspecs = explode(',', $varspecs);
    return array_merge(array('operator'=>$operator, 'varspecs'=>$varspecs), self::$OPERATOR_SETTINGS[$operator]);
}

private static function parseVarspec($varspec)
{
    static $preg_varspec = '/^(?<varname>(?:[0-9a-zA-Z_]|(?:%[0-9a-fA-F]{2}))+([.](?:[0-9a-zA-Z_]|(?:%[0-9a-fA-F]{2}))+)*)(?:(?:[:](?<prefix>[1-9][0-9]{0,3}))|(?<explode>[*]))?$/';
    if (!preg_match($preg_varspec, $varspec, $matches)) {
        throw new UriTemplateException('Malformed varspec: "%s"', array($varspec));
    }
    $varname = $matches['varname'];
    $prefix = isset($matches['prefix']) && $matches['prefix'] !== '' ? intval($matches['prefix']) : null;
    $explode = isset($matches['explode']) && $matches['explode'] === '*';
    return array('varname'=>$varname, 'explode'=>$explode, 'prefix'=>$prefix);
}

private static function realArray(array $var)
{
    return count($var) === 0 ? true : array_keys($var) === range(0, count($var)-1);
}

}
