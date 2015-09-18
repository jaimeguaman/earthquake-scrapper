<?php
/**
 * Load libs
 */
App::uses('Scrapper', 'Lib');
App::uses('DatesUtils', 'Lib');

/**
 * UsgsController class
 */
Class UsgsController extends Controller {

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

        $endpoint = 'http://earthquake.usgs.gov/fdsnws/event/1/query.geojson?starttime=%DATEMIN%%2000:00:00&minmagnitude=2&endtime=%DATEMAX%%2023:59:59&orderby=time';
        $endpointTokens = [
            '%DATEMIN%',
            '%DATEMAX%',
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
            $datemin = $year . '-' . $month . '-' . $day;
            $datemax = $year . '-' . $month . '-' . $day;
            $self->doScrapping($self->scrapper->getScrappingUrl([$datemin, $datemax]));
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

        $this->doScrapping($this->scrapper->getScrappingUrl( array( date('Y-m-d',$currentUTCTimestamp) , date('Y-m-d',$currentUTCTimestamp))) );
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
        $content = json_decode($content);

        foreach ($content->features as $earthquakeData){
            if (empty($earthquakeData)) {
                Debugger::dump('***NO HAY DATOS****');
                continue;
            }

            $dateUTC = DatesUtils::toReadableDate($earthquakeData->properties->time / 1000);
            $dateTs = $earthquakeData->properties->time;
            $dateSQL = DatesUtils::toSQLDate($dateUTC);

            $eventData = [
                'lat' => $earthquakeData->geometry->coordinates[1],
                'lon' => $earthquakeData->geometry->coordinates[0],
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
                $metadatum = [
                    'event_id' => $event['Event']['id'],
                    'agency_id' => 3,
                    'lat' => $eventData['lat'],
                    'lon' => $eventData['lon'],
                    'ts' => $dateSQL,
                    'depth' => $earthquakeData->geometry->coordinates[2],
                    'magnitude' => floatval($earthquakeData->properties->mag),
                    'geo_reference' => $earthquakeData->properties->place
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







    }

}