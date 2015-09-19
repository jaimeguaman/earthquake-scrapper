<?php
/**
 * Load libs
 */
App::uses('Scrapper', 'Lib');
App::uses('DatesUtils', 'Lib');

/**
 * GucController class
 */
Class GucController extends Controller {

/**
 * Load models
 * @var array
 */
    public $uses = [
        'Agency',
        'Event',
        'EventMetadatum'
    ];

/**
 * Scrapper class
 * @var object
 */
    private $scrapper;

/**
 * [$dateBounds description]
 * @var [type]
 */
    private $dateBounds;

/**
 * [beforeFilter description]
 * @return [type] [description]
 */
    public function beforeFilter() {
        parent::beforeFilter();

        $endpoint = 'http://sismologia.cl/events/listados/%YEAR%/%MONTH%/%YEAR%%MONTH%%DAY%.html';
        $endpointTokens = [
            '%YEAR%',
            '%MONTH%',
            '%DAY%'
        ];

        $this->scrapper = new Scrapper($endpoint, $endpointTokens);
    }
    
/**
 * [getFromDateRange description]
 * @param  [type] $startDate [description]
 * @param  [type] $endDate   [description]
 * @return [type]            [description]
 */
    public function getFromDateRange($startDate, $endDate) {
        $this->dateBounds = [
            'start' => date('Y-m-d', DatesUtils::toTimestamp($startDate)),
            'end' => date('Y-m-d', DatesUtils::toTimestamp($endDate))
        ];

        $self = $this;

        DatesUtils::rangeLoop($startDate, $endDate, function($day, $month, $year) use ($self) {
            $self->doScrapping($self->scrapper->getScrappingUrl([$year, $month, $day]));
        }); 

    }

/**
 * [getFromtoday description]
 * @return [type]       [description]
 */
    public function getFromtoday() {
        $currentUTCTimestamp = time();
        $currentUTCDate = date('Y-m-d', $currentUTCTimestamp);

        $this->dateBounds = [
            'start' => $currentUTCDate,
            'end' => $currentUTCDate
        ];

        $this->doScrapping($this->scrapper->getScrappingUrl([
            date('Y', $currentUTCTimestamp),
            date('m', $currentUTCTimestamp),
            date('d', $currentUTCTimestamp)
        ]));
    }

/**
 * [doScrapping description]
 * @param  [type] $endpoint [description]
 * @return [type]           [description]
 */
    private function doScrapping($endpoint) {
        $event = null;
        $earthquake = null;

        Debugger::dump('endpoint: ' . $endpoint . '    _' . $_SERVER['HTTP_USER_AGENT']);
        Debugger::dump('***INICIANDO SCRAPPING****');

        $content = $this->scrapper->getContent($endpoint);
        if (!$content) {
            Debugger::dump('***ERROR, NO SE OBTUBIERON DATOS');
            return;
        }

        $this->scrapper->domLoad($content);
        $tableList = $this->scrapper->findInDom('table tbody tr');
        
        foreach ($tableList as $table) {
            $earthquakeData = [];

            foreach ($table->find('td') as $key => $tableItem) {
                $earthquakeData[$key] = $tableItem->text();
            }

            if (empty($earthquakeData)) {
                Debugger::dump('***NO HAY DATOS****');
                continue;
            }

            $dateUTC = $earthquakeData[1];
            $dateTs = DatesUtils::toTimestamp($dateUTC);
            $dateSQL = DatesUtils::toSQLDate($dateUTC);

            $eventData = [
                'lat' => $earthquakeData[2],
                'lon' => $earthquakeData[3],
                'ts' => $dateSQL,
                'hash' => md5($dateTs)
            ];

            /*  Evitar crear eventos duplicados que muestren erroneamente más de un evento siendo que se trata del mismo
             *  pero actualizado.
             *  Esto se hace debido a que el primer informe ante un evento, puede ser preliminar
             *  y se pueden publicar actualizaciones de datos con cambios en magnitud o ubicación geográfica posteriormente.
             */

            $eventExists = $this->Event->checkForExists($eventData, $this->dateBounds);

            if ($eventExists['exists']) {
                Debugger::dump('***EVENTO YA EXISTE ****');
                $event = $eventExists;
            }
            else {
                Debugger::dump('***NO SE ENCONTRO EVENTO, CREANDO ****');
                $this->Event->create();
                $event = $this->Event->save($eventData);
            }

            if ($event) {
                $magnitude_type = 'Mw';
                if (!strpos($earthquakeData[5], 'Mw')){
                    $magnitude_type = 'Ml';
                }

                $metadatum = [
                    'event_id' => $event['Event']['id'],
                    'agency_id' => 1,
                    'lat' => $eventData['lat'],
                    'lon' => $eventData['lon'],
                    'ts' => $dateSQL,
                    'depth' => $earthquakeData[4],
                    'magnitude' => floatval($earthquakeData[5]),
                    'geo_reference' => $earthquakeData[6],
                    'magnitude_type' => $magnitude_type
                ];

                if (!$eventExists['exists']) {
                    Debugger::dump('***EVENTO NO EXISTE, SISMO TAMPOCO ****');
                    $this->EventMetadatum->create();
                    $earthquake = $this->EventMetadatum->save($metadatum);
                }
                else {
                    $earthquakeExists = $this->EventMetadatum->checkForExists($metadatum, $this->dateBounds, $eventExists['Event']['id']);
                    if ($earthquakeExists['exists']) {
                        Debugger::dump('***EVENTO EXISTE, SISMO TAMBIEN ****');
                    }
                    else {
                        Debugger::dump('***EVENTO EXISTE, NUEVO SISMO NO. CREANDO NUEVO ASOCIADO A EVENTO****');
                        $this->EventMetadatum->create();
                        $earthquake = $this->EventMetadatum->save($metadatum);
                    }

                }

            }
        }

        return true;
    }

}