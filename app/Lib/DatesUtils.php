<?php

Class DatesUtils{
    public static function rangeLoop($startDate,$endDate,$callback){
        
        $startDate = strtotime($startDate);
        $endDate=strtotime($endDate);
        $date=$startDate;

	while ($date <= $endDate) {
                $day=date('d',$date);
                $month=date('m',$date);
                $year=date('Y',$date);
                $callback($day,$month,$year);
                $date = strtotime("+1 day", $date);
	} 
    }
    
    public static function toTimeStamp($date){
        return strtotime(str_replace('/', '-', $date));
    }
    
    public static function toReadableDate($timestamp){
        return date('d-m-Y H:i:s',$timestamp);
    }
    
    public static function toSQLDate($date){
        return date("Y-m-d H:i:s", strtotime(str_replace('/', '-', $date)));
    }
}

