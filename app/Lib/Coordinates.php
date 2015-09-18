<?php
/**
 * Class Coordinates
 */
Class Coordinates {

    //DMStoDEC and DECtoDMS based from http://www.web-max.ca/PHP/misc_6.php

    /**
     * [DMSParse description]
     * @param [string] $str       [description]
     */
    private static function DMSParse($str){
        //format expected: 15.30°N, 45.88°W, etc
        $coords = [];
        $str = str_replace(html_entity_decode('&deg'), '_', $str);
        $str = str_replace(';', '', $str);
        $pos = strpos($str,'_');
        $geoDir = substr ($str, $pos + 1, 1);
        preg_match_all('/\d+/', $str, $coords);

        return [
            'coordinates' => $coords[0],
            'geoDir' => $geoDir
        ];
    }

    /**
     * [DMStoDEC description]
     * @param [int] $deg       [description]
     * @param [int] $min       [description]
     * @param [int] $sec       [description]
     * @param [char] $dir       [description]
     */
    public static function DMStoDEC($deg, $min, $sec, $dir){
    // Converts DMS ( Degrees / minutes / seconds ) 
    // to decimal format longitude / latitude
        $result = $deg+((($min*60)+($sec))/3600);
        if ($dir == 'S' or $dir == 'W'){
            $result *= -1;
        }

        return round($result, 3);
    }

    /**
     * [extractDMS description]
     * @param [string] $str       [description]
     */
    public static function extractDMS($str){
        return self::DMSParse($str);
    }

    /**
     * [DECtoDMS description]
     * @param [string] $dev       [description]
     */
    public static function DECtoDMS($dec){
    // Converts decimal longitude / latitude to DMS
    // ( Degrees / minutes / seconds ) 

    // This is the piece of code which may appear to 
    // be inefficient, but to avoid issues with floating
    // point math we extract the integer part and the float
    // part by using a string function.

        $vars = explode(".",$dec);
        $deg = $vars[0];
        $tempma = "0.".$vars[1];

        $tempma = $tempma * 3600;
        $min = floor($tempma / 60);
        $sec = $tempma - ($min*60);

        return array("deg"=>$deg,"min"=>$min,"sec"=>$sec);
    }
}

