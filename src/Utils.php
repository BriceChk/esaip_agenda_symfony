<?php


namespace App;


class Utils {

    public static function jsonMsg($text) {
        return '[{ "message": "'.$text.'" }]';
    }


    public static function twoDigits($d) {
        if (0 <= $d && $d < 10) return "0" . $d;
        if (-10 < $d && $d < 0) return "-0" . (-1 * $d);
        return $d . "";
    }
}