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

/**
 * [checkForExists description]
 * @param  [type] $eventData  [description]
 * @param  [type] $dateBounds [description]
 * @return [type]             [description]
 */
    public function checkForExists($eventData, $dateBounds) {
        $return = [
            'exists' => false,
            'Event' => null
        ];

        $events = $this->find('all', [
            'conditions' => [
                'Event.ts >=' => $dateBounds['start'] . ' 00:00:00',
                'Event.ts <=' => $dateBounds['end'] . ' 23:59:59'
            ],
            'recursive' => 0
        ]);
        foreach ($events as $event) {
            $existingEventDate = $event['Event']['ts'];
            $newEventDate = $eventData['ts'];
            $timeDifference = strtotime($existingEventDate) - strtotime($newEventDate);

            if (
                ($timeDifference <= -1 && $timeDifference >= -10) || 
                ($timeDifference <= 10 && $timeDifference >= 1)  ||
                $existingEventDate == $newEventDate
            ) {
                $return = [
                    'exists' => true,
                    'Event' => $event['Event']
                ];
                break;
            }
        }

        return $return;        
    }

}