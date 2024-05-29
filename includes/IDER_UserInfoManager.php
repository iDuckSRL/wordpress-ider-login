<?php

use IDERConnect\IDERHelpers;

/**
 * Function to transform and map json to local fields.
 *
 * @package     WordPress
 * @subpackage  Ider
 * @author      Davide Lattanzio <plugins@jlm.srl>
 * @since       1.0
 *
 */
class IDER_UserInfoManager
{
    static function normalize($user_info)
    {
        $user_info = IDERHelpers::toArray($user_info);
        $user_info = IDERHelpers::remapFields($user_info);

        // explode json packed claims
        $user_info = self::_flattenFields($user_info);

        // remap openID fields into local fields
        $user_info = self::_fieldsMap($user_info);

        $user_info = (object) $user_info;

        return $user_info;
    }

    private static function _fieldsMap($userdata)
    {
        $fields = array();

        $fields = apply_filters('ider_fields_map', $fields);

        foreach ($fields as $localkey => $remotekey) {
            if (!empty($userdata[$remotekey])) {
                $userdata[$localkey] = $userdata[$remotekey];
                //unset($userdata[$remotekey]);
            }
        }

        return $userdata;
    }

    private static function _flattenFields($userdata)
    {
        $result = [];

        $flatten = function ($data, $parentKey = '') use (&$flatten, &$result) {
            foreach ($data as $key => $value) {
                $newKey = $parentKey === '' ? $key : $parentKey . '.' . $key;
                if (is_array($value) || is_object($value)) {
                    $flatten((array)$value, $newKey);
                } else {
                    $result[$newKey] = $value;
                }
            }
        };
    
        $flatten($userdata);
    
        return $result;
    }
}