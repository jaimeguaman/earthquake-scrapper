<?php
App::uses('AppModel', 'Model');
/**
 * EventMetadatum Model
 *
 * @property Event $Event
 * @property Agency $Agency
 */
class EventMetadatum extends AppModel {

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'geo_reference';


	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Event' => array(
			'className' => 'Event',
			'foreignKey' => 'event_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Agency' => array(
			'className' => 'Agency',
			'foreignKey' => 'agency_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

/**
 * [checkForExists description]
 * @param  [type] $earthquakeData [description]
 * @param  [type] $dateBounds     [description]
 * @param  [type] $event_id        [description]
 * @return [type]                 [description]
 */
    public function checkForExists($earthquakeData, $dateBounds, $event_id){
        $return = [];
        $return['exists'] = (bool)$this->find('count', [
            'conditions' => [
                'EventMetadatum.event_id' => $event_id,
                'EventMetadatum.ts' => $earthquakeData['ts'],
                'OR' => [
                    'EventMetadatum.magnitude' => $earthquakeData['magnitude'],
                    'EventMetadatum.lat' => $earthquakeData['lat'],
                    'EventMetadatum.lon' => $earthquakeData['lon']
                ]
            ],
            'limit' => 1
        ]);
        
        return $return;
    }
}
