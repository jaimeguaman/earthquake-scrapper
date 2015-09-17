<?php
App::uses('AppModel', 'Model');
/**
 * Event Model
 *
 */
class Event extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'event';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'hash';
        
        public function checkForExists($eventData,$dateBounds){
            $ret=null;
            $events=$this->find('all',array(
                'conditions'=>array(
                    'Event.ts >=' => $dateBounds['start'] . ' 00:00:00',
                    'Event.ts <=' => $dateBounds['end'] . ' 23:59:59'
                ) 
            ));
            foreach ($events as $event){
                $existingEventDate=$event['Event']['ts'];
                $newEventDate=$eventData['ts'];
                $timeDifference=strtotime($existingEventDate) - strtotime($newEventDate);
                if (($timeDifference <= -1 AND $timeDifference >= -10) OR 
                        ($timeDifference <= 10 AND $timeDifference >= 1)  OR
                        $existingEventDate == $newEventDate){
                    $ret=array(
                        'exists'=>true,
                        'Event'=>$event['Event']
                    );
                    break;
                }
            }
            if ($ret){
                return $ret;
            }else{
                return array(
                    'exists'=>false,
                    'Event'=>null
                );
            }
            
        }

}

