<?php

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
        $user_info = (array)$user_info;

        // explode json packed claims
        $user_info = self::_checkJsonfields($user_info);

        // remap openID fields into local fields
        $user_info = self::_fieldsMap($user_info);

        $user_info = (object)$user_info;

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


    private static function _checkJsonfields($userdata)
    {

        foreach ($userdata as $key => $claim) {
            if (IDER_Helpers::isJSON($claim)) {
                $subclaims = json_decode($claim);

                // break down the claim
                foreach ($subclaims as $subkey => $subclaim) {
                    $userdata[$key . '.' . $subkey] = $subclaim;
                }

                // delete the original claim
                unset($userdata[$key]);
            }
        }

        return $userdata;
    }


}