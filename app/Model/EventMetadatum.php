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
        
        public function checkForExists($earthquakeData,$dateBounds,$eventId){
            $ret=null;
            $earthquakes=$this->find('all',array(
                'conditions'=>array(
                    'EventMetadatum.ts >=' => $dateBounds['start'] . ' 00:00:00',
                    'EventMetadatum.ts <=' => $dateBounds['end'] . ' 23:59:59',
                    'EventMetadatum.event_id' => $eventId
                ) 
            ));
            
            foreach ($earthquakes as $earthquake){
                Debugger::log('***FECHA EXISTENTE: ***' . $earthquake['EventMetadatum']['ts']);
                Debugger::log('***FECHA NUEVO: ***' . $earthquakeData['ts']);
                $existingEarthquakeDate=$earthquake['EventMetadatum']['ts'];
                $newEarthquakeDate=$earthquakeData['ts'];
                if ($existingEarthquakeDate == $newEarthquakeDate){
                    if ($earthquake['EventMetadatum']['magnitude'] == $earthquakeData['magnitude'] OR
                        $earthquake['EventMetadatum']['lat'] == $earthquakeData['lat'] OR
                        $earthquake['EventMetadatum']['lon'] == $earthquakeData['lon']){
                        $ret=array(
                            'exists'=>true
                        );  
                        break;
                    }
                    
                }else{
                        $ret=array(
                            'exists'=>false
                        );   
                }
                
            }
            
            return $ret;
            
        }
}
