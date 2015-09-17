<?php
App::uses('AppModel', 'Model');
/**
 * Agency Model
 *
 * @property EventMetadatum $EventMetadatum
 */
class Agency extends AppModel {


/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'agency';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'name';


	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'EventMetadatum' => array(
			'className' => 'EventMetadatum',
			'foreignKey' => 'agency_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

}
