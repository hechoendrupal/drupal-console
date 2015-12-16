<?php

/**
 * @file
 * Contains Drupal\Console\Helper\NestedArrayHelper.
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Helper\Helper;

class NestedArrayHelper extends Helper
{
    /**
     * Based on drupal class Drupal\Component\Utility\NestedArray
     *
     * Retrieves a value from a nested array with variable depth.
     *
     * This helper function should be used when the depth of the array element
     * being retrieved may vary (that is, the number of parent keys is variable).
     * It is primarily used for form structures and renderable arrays.
     *
     * Without this helper function the only way to get a nested array value with
     * variable depth in one line would be using eval(), which should be avoided:
     *
     * @code
     * // Do not do this! Avoid eval().
     * // May also throw a PHP notice, if the variable array keys do not exist.
     * eval('$value = $array[\'' . implode("']['", $parents) . "'];");
     * @endcode
     *
     * Instead, use this helper function:
     * @code
     * $value = NestedArray::getValue($form, $parents);
     * @endcode
     *
     * A return value of NULL is ambiguous, and can mean either that the requested
     * key does not exist, or that the actual value is NULL. If it is required to
     * know whether the nested array key actually exists, pass a third argument
     * that is altered by reference:
     * @code
     * $key_exists = NULL;
     * $value = NestedArray::getValue($form, $parents, $key_exists);
     * if ($key_exists) {
     *   // Do something with $value.
     * }
     * @endcode
     *
     * However if the number of array parent keys is static, the value should
     * always be retrieved directly rather than calling this function.
     * For instance:
     * @code
     * $value = $form['signature_settings']['signature'];
     * @endcode
     *
     * @param array $array
     *   The array from which to get the value.
     * @param array $parents
     *   An array of parent keys of the value, starting with the outermost key.
     * @param bool  $key_exists
     *   (optional) If given, an already defined variable that is altered by
     *   reference.
     *
     * @return mixed
     *   The requested nested value. Possibly NULL if the value is NULL or not all
     *   nested parent keys exist. $key_exists is altered by reference and is a
     *   Boolean that indicates whether all nested parent keys exist (TRUE) or not
     *   (FALSE). This allows to distinguish between the two possibilities when
     *   NULL is returned.
     *
     * @see NestedArray::setValue()
     * @see NestedArray::unsetValue()
     */
    public static function &getValue(array &$array, array $parents, &$key_exists = null)
    {
        $ref = &$array;
        foreach ($parents as $parent) {
            if (is_array($ref) && array_key_exists($parent, $ref)) {
                $ref = &$ref[$parent];
            } else {
                $key_exists = false;
                $null = null;
                return $null;
            }
        }
        $key_exists = true;
        return $ref;
    }

    /**
     * Sets a value in a nested array with variable depth.
     *
     * This helper function should be used when the depth of the array element you
     * are changing may vary (that is, the number of parent keys is variable). It
     * is primarily used for form structures and renderable arrays.
     *
     * Example:
     *
     * @code
     * // Assume you have a 'signature' element somewhere in a form. It might be:
     * $form['signature_settings']['signature'] = array(
     *   '#type' => 'text_format',
     *   '#title' => t('Signature'),
     * );
     * // Or, it might be further nested:
     * $form['signature_settings']['user']['signature'] = array(
     *   '#type' => 'text_format',
     *   '#title' => t('Signature'),
     * );
     * @endcode
     *
     * To deal with the situation, the code needs to figure out the route to the
     * element, given an array of parents that is either
     * @code    array('signature_settings', 'signature') @endcode
     * in the first case or
     * @code    array('signature_settings', 'user', 'signature') @endcode
     * in the second case.
     *
     * Without this helper function the only way to set the signature element in
     * one line would be using eval(), which should be avoided:
     * @code
     * // Do not do this! Avoid eval().
     * eval('$form[\'' . implode("']['", $parents) . '\'] = $element;');
     * @endcode
     *
     * Instead, use this helper function:
     * @code
     * NestedArray::setValue($form, $parents, $element);
     * @endcode
     *
     * However if the number of array parent keys is static, the value should
     * always be set directly rather than calling this function. For instance,
     * for the first example we could just do:
     * @code
     * $form['signature_settings']['signature'] = $element;
     * @endcode
     *
     * @param array $array
     *   A reference to the array to modify.
     * @param array $parents
     *   An array of parent keys, starting with the outermost key.
     * @param mixed $value
     *   The value to set.
     * @param bool  $force
     *   (optional) If TRUE, the value is forced into the structure even if it
     *   requires the deletion of an already existing non-array parent value. If
     *   FALSE, PHP throws an error if trying to add into a value that is not an
     *   array. Defaults to FALSE.
     *
     * @see NestedArray::unsetValue()
     * @see NestedArray::getValue()
     */
    public static function setValue(array &$array, array $parents, $value, $force = false)
    {
        $ref = &$array;
        foreach ($parents as $parent) {
            // PHP auto-creates container arrays and NULL entries without error if $ref
            // is NULL, but throws an error if $ref is set, but not an array.
            if ($force && isset($ref) && !is_array($ref)) {
                $ref = array();
            }
            $ref = &$ref[$parent];
        }
        $ref = $value;
    }

    /**
     * Replace a YAML key maintaining values
     * @param array   $array
     * @param array   $parents
     * @param $new_key
     */
    public static function replaceKey(array &$array, array $parents, $new_key)
    {
        $ref = &$array;
        foreach ($parents as $parent) {
            $father = &$ref;
            $key = $parent;
            $ref = &$ref[$parent];
        }

        $father[$new_key] = $father[$key];
        unset($father[$key]);
    }

    /**
     * @param $array1
     * @param $array2
     * @param bool   $negate if Negate is true only if values are equal are returned.
     * @return array
     */
    public function arrayDiff($array1, $array2, $negate = false, &$statisticts)
    {
        $result = array();
        foreach ($array1 as $key => $val) {
            if (isset($array2[$key])) {
                if (is_array($val) && $array2[$key]) {
                    $result[$key] = $this->arrayDiff($val, $array2[$key], $negate, $statisticts);
                    if (empty($result[$key])) {
                        unset($result[$key]);
                    }
                } else {
                    $statisticts['total'] += 1;
                    if ($val == $array2[$key] && $negate) {
                        $result[$key] = $array2[$key];
                        $statisticts['equal'] += 1;
                    } elseif ($val != $array2[$key] && $negate) {
                        $statisticts['diff'] += 1;
                    } elseif ($val != $array2[$key] && !$negate) {
                        $result[$key] = $array2[$key];
                        $statisticts['diff'] += 1;
                    } elseif ($val == $array2[$key] && !$negate) {
                        $result[$key] = $array2[$key];
                        $statisticts['equal'] += 1;
                    }
                }
            } else {
                if (is_array($val)) {
                    $statisticts['diff'] += count($val, COUNT_RECURSIVE);
                    $statisticts['total'] += count($val, COUNT_RECURSIVE);
                } else {
                    $statisticts['diff'] +=1;
                    $statisticts['total'] += 1;
                }
            }
        }

        return $result;
    }

    /**
     * Flat a yaml file
     * @param array  $array
     * @param array  $flatten_array
     * @param string $key_flatten
     */
    public function yamlFlattenArray(array &$array, &$flatten_array, &$key_flatten = '')
    {
        foreach ($array as $key => $value) {
            if (!empty($key_flatten)) {
                $key_flatten.= '.';
            }
            $key_flatten.= $key;

            if (is_array($value)) {
                $this->yamlFlattenArray($value, $flatten_array, $key_flatten);
            } else {
                if (!empty($value)) {
                    $flatten_array[$key_flatten] = $value;
                    $key_flatten = substr($key_flatten, 0, strrpos($key_flatten, "."));
                }
            }
        }

        // Start again with flatten key after recursive call
        $key_flatten = substr($key_flatten, 0, strrpos($key_flatten, "."));
    }

    /**
     * @param array $array
     * @param array $split_array
     * @param int   $indent_level
     * @param array $key_flatten
     * @param int   $key_level
     * @param bool  $exclude_parents_key
     */
    public function yamlSplitArray(array &$array, array &$split_array, $indent_level = '', &$key_flatten, &$key_level, $exclude_parents_key)
    {
        foreach ($array as $key => $value) {
            if (!$exclude_parents_key && !empty($key_flatten)) {
                $key_flatten.= '.';
            }

            if ($exclude_parents_key) {
                $key_flatten = $key;
            } else {
                $key_flatten .= $key;
            }

            if ($key_level == $indent_level) {
                if (!empty($value)) {
                    $split_array[$key_flatten] = $value;

                    if (!$exclude_parents_key) {
                        $key_flatten = substr($key_flatten, 0, strrpos($key_flatten, "."));
                    }
                }
            } else {
                if (is_array($value)) {
                    $key_level++;
                    $this->yamlSplitArray($value, $split_array, $indent_level, $key_flatten, $key_level, $exclude_parents_key);
                }
            }
        }

        // Start again with flatten key after recursive call
        if (!$exclude_parents_key) {
            $key_flatten = substr($key_flatten, 0, strrpos($key_flatten, "."));
        }

        $key_level--;
    }
    /**
     * Unsets a value in a nested array with variable depth.
     *
     * This helper function should be used when the depth of the array element you
     * are changing may vary (that is, the number of parent keys is variable). It
     * is primarily used for form structures and renderable arrays.
     *
     * Example:
     *
     * @code
     * // Assume you have a 'signature' element somewhere in a form. It might be:
     * $form['signature_settings']['signature'] = array(
     *   '#type' => 'text_format',
     *   '#title' => t('Signature'),
     * );
     * // Or, it might be further nested:
     * $form['signature_settings']['user']['signature'] = array(
     *   '#type' => 'text_format',
     *   '#title' => t('Signature'),
     * );
     * @endcode
     *
     * To deal with the situation, the code needs to figure out the route to the
     * element, given an array of parents that is either
     * @code    array('signature_settings', 'signature') @endcode
     * in the first case or
     * @code    array('signature_settings', 'user', 'signature') @endcode
     * in the second case.
     *
     * Without this helper function the only way to unset the signature element in
     * one line would be using eval(), which should be avoided:
     * @code
     * // Do not do this! Avoid eval().
     * eval('unset($form[\'' . implode("']['", $parents) . '\']);');
     * @endcode
     *
     * Instead, use this helper function:
     * @code
     * NestedArray::unset_nested_value($form, $parents, $element);
     * @endcode
     *
     * However if the number of array parent keys is static, the value should
     * always be set directly rather than calling this function. For instance, for
     * the first example we could just do:
     * @code
     * unset($form['signature_settings']['signature']);
     * @endcode
     *
     * @param array $array
     *   A reference to the array to modify.
     * @param array $parents
     *   An array of parent keys, starting with the outermost key and including
     *   the key to be unset.
     * @param bool  $key_existed
     *   (optional) If given, an already defined variable that is altered by
     *   reference.
     *
     * @see NestedArray::setValue()
     * @see NestedArray::getValue()
     */
    public static function unsetValue(array &$array, array $parents, &$key_existed = null)
    {
        $unset_key = array_pop($parents);
        $ref = &self::getValue($array, $parents, $key_existed);
        if ($key_existed && is_array($ref) && array_key_exists($unset_key, $ref)) {
            $key_existed = true;
            unset($ref[$unset_key]);
        } else {
            $key_existed = false;
        }
    }

    /**
     * Determines whether a nested array contains the requested keys.
     *
     * This helper function should be used when the depth of the array element to
     * be checked may vary (that is, the number of parent keys is variable). See
     * NestedArray::setValue() for details. It is primarily used for form
     * structures and renderable arrays.
     *
     * If it is required to also get the value of the checked nested key, use
     * NestedArray::getValue() instead.
     *
     * If the number of array parent keys is static, this helper function is
     * unnecessary and the following code can be used instead:
     *
     * @code
     * $value_exists = isset($form['signature_settings']['signature']);
     * $key_exists = array_key_exists('signature', $form['signature_settings']);
     * @endcode
     *
     * @param array $array
     *   The array with the value to check for.
     * @param array $parents
     *   An array of parent keys of the value, starting with the outermost key.
     *
     * @return bool
     *   TRUE if all the parent keys exist, FALSE otherwise.
     *
     * @see NestedArray::getValue()
     */
    public static function keyExists(array $array, array $parents)
    {
        // Although this function is similar to PHP's array_key_exists(), its
        // arguments should be consistent with getValue().
        $key_exists = null;
        self::getValue($array, $parents, $key_exists);
        return $key_exists;
    }

    /**
     * Merges multiple arrays, recursively, and returns the merged array.
     *
     * This function is similar to PHP's array_merge_recursive() function, but it
     * handles non-array values differently. When merging values that are not both
     * arrays, the latter value replaces the former rather than merging with it.
     *
     * Example:
     *
     * @code
     * $link_options_1 = array('fragment' => 'x', 'attributes' => array('title' => t('X'), 'class' => array('a', 'b')));
     * $link_options_2 = array('fragment' => 'y', 'attributes' => array('title' => t('Y'), 'class' => array('c', 'd')));
     *
     * // This results in array('fragment' => array('x', 'y'), 'attributes' => array('title' => array(t('X'), t('Y')), 'class' => array('a', 'b', 'c', 'd'))).
     * $incorrect = array_merge_recursive($link_options_1, $link_options_2);
     *
     * // This results in array('fragment' => 'y', 'attributes' => array('title' => t('Y'), 'class' => array('a', 'b', 'c', 'd'))).
     * $correct = NestedArray::mergeDeep($link_options_1, $link_options_2);
     * @endcode
     *
     * @param array ...
     *   Arrays to merge.
     *
     * @return array
     *   The merged array.
     *
     * @see NestedArray::mergeDeepArray()
     */
    public static function mergeDeep()
    {
        return self::mergeDeepArray(func_get_args());
    }

    /**
     * Merges multiple arrays, recursively, and returns the merged array.
     *
     * This function is equivalent to NestedArray::mergeDeep(), except the
     * input arrays are passed as a single array parameter rather than a variable
     * parameter list.
     *
     * The following are equivalent:
     * - NestedArray::mergeDeep($a, $b);
     * - NestedArray::mergeDeepArray(array($a, $b));
     *
     * The following are also equivalent:
     * - call_user_func_array('NestedArray::mergeDeep', $arrays_to_merge);
     * - NestedArray::mergeDeepArray($arrays_to_merge);
     *
     * @param array $arrays
     *   An arrays of arrays to merge.
     * @param bool  $preserve_integer_keys
     *   (optional) If given, integer keys will be preserved and merged instead of
     *   appended. Defaults to FALSE.
     *
     * @return array
     *   The merged array.
     *
     * @see NestedArray::mergeDeep()
     */
    public static function mergeDeepArray(array $arrays, $preserve_integer_keys = false)
    {
        $result = array();
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                // Renumber integer keys as array_merge_recursive() does unless
                // $preserve_integer_keys is set to TRUE. Note that PHP automatically
                // converts array keys that are integer strings (e.g., '1') to integers.
                if (is_integer($key) && !$preserve_integer_keys) {
                    $result[] = $value;
                }
                // Recurse when both values are arrays.
                elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    $result[$key] = self::mergeDeepArray(array($result[$key], $value), $preserve_integer_keys);
                }
                // Otherwise, use the latter value, overriding any previous value.
                else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }
}
