<?php
/**
 * 
 */
Class DatesUtils {

/**
 * [rangeLoop description]
 * @param  [type] $startDate [description]
 * @param  [type] $endDate   [description]
 * @param  [type] $callback  [description]
 * @return [type]            [description]
 */
    public static function rangeLoop($startDate,$endDate,$callback) {
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        $date = $startDate;

    	while ($date <= $endDate) {
            $day = date('d', $date);
            $month = date('m', $date);
            $year = date('Y', $date);

            $callback($day, $month, $year);
            $date = strtotime("+1 day", $date);
    	} 
    }

/**
 * [toTimeStamp description]
 * @param  [type] $date [description]
 * @return [type]       [description]
 */
    public static function toTimeStamp($date) {
        return strtotime(str_replace('/', '-', $date));
    }

/**
 * [toReadableDate description]
 * @param  [type] $timestamp [description]
 * @return [type]            [description]
 */
    public static function toReadableDate($timestamp) {
        return date('d-m-Y H:i:s',$timestamp);
    }

/**
 * [toSQLDate description]
 * @param  [type] $date [description]
 * @return [type]       [description]
 */
    public static function toSQLDate($date) {
        return date("Y-m-d H:i:s", strtotime(str_replace('/', '-', $date)));
    }
}