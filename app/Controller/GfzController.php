<?php
/**
 * Load libs
 */
App::uses('Scrapper', 'Lib');
App::uses('DatesUtils', 'Lib');
App::uses('Coordinates', 'Lib');

Class GfzController extends Controller {
/**
 * Load models
 * @var array
 */
    public $uses = array(
        'Agency',
        'Event',
        'EventMetadatum'
    );
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
    public function beforeFilter(){
        $endpoint = 'http://geofon.gfz-potsdam.de/eqinfo/list.php?datemin=%DATEMIN%';
        $endpoint .= '&datemax=%DATEMAX%&latmin=&latmax=&lonmin=&lonmax=&magmin=2&fmt';
        $endpoint .= '=html&nmax=200';
        
        $endpointTokens = array(
            '%DATEMIN%',
            '%DATEMAX%'
        );

        $this->scrapper = new Scrapper($endpoint, $endpointTokens);
    }

/**
 * [getFromDateRange description]
 * @param  [type] $startDate [description]
 * @param  [type] $endDate   [description]
 * @return [type]            [description]
 */
    public function getFromDateRange($startDate,$endDate){
        $this->dateBounds = array(
            'start'=>Date('Y-m-d', DatesUtils::toTimestamp($startDate)),
            'end'=>Date('Y-m-d', DatesUtils::toTimestamp($endDate))
        );

        $self = $this;
        DatesUtils::rangeLoop($startDate,$endDate,function($day,$month,$year) use ($self){
            $datemin = $year . '-' . $month . '-' . $day;
            $datemax = $year . '-' . $month . '-' . $day;
            $self->doScrapping($self->scrapper->getScrappingUrl(array($datemin, $datemax)));
        }); 

    }

/**
 * [getFromtoday description]
 * @return [type]       [description]
 */
    public function getFromtoday(){
        $currentUTCTimestamp = strtotime(date('Y-m-d H:i:s', time() ));
        $currentUTCDate = date('Y-m-d', $currentUTCTimestamp );

        $this->dateBounds = array(
            'start' => $currentUTCDate,
            'end' => $currentUTCDate
        );

        $this->doScrapping($this->scrapper->getScrappingUrl( array( date('Y-m-d',$currentUTCTimestamp),date('Y-m-d',$currentUTCTimestamp))) );
    }
/**
 * [doScrapping description]
 * @param  [type] $endpoint [description]
 * @return [type]           [description]
 */
    private function doScrapping($endpoint){
        $event = null;
        $earthquake = null;
        $i = 0;

        Debugger::dump('endpoint: ' . $endpoint . '    _' . $_SERVER['HTTP_USER_AGENT']);
        Debugger::dump('***INICIANDO SCRAPPING****');

        $content = $this->scrapper->getContent($endpoint);
        if ($content){
            $this->scrapper->domLoad($content);
            $tableList = $this->scrapper->findInDom('table tbody tr');
        }else{
          Debugger::dump('***ERROR, NO SE OBTUBIERON DATOS');  
        }
        
        //get each table node
        foreach ($tableList as $tableKey => $table) {
            $earthquakeData = array();
            //get each data item
            $i = 0;

            foreach ($table->find('td') as $itemKey => $tableItem) {
                $earthquakeData[$itemKey] = $tableItem->text();
                $i++;
            }

            if ($i < 8 or empty($earthquakeData)){
                continue;
            }
            $latDMSArr = Coordinates::extractDMS($earthquakeData[2]);
            $lonDMSArr = Coordinates::extractDMS($earthquakeData[3]);

            $lat = Coordinates::DMStoDEC(
                    $latDMSArr['coordinates'][0],
                    $latDMSArr['coordinates'][1],
                    0,
                    $latDMSArr['geoDir']
            );

            $lon = Coordinates::DMStoDEC(
                    $lonDMSArr['coordinates'][0],
                    $lonDMSArr['coordinates'][1],
                    0,
                    $lonDMSArr['geoDir']
            );

            $dateUTC = $earthquakeData[0];
            $dateTs = DatesUtils::toTimestamp($dateUTC);
            $dateSQL = DatesUtils::toSQLDate($dateUTC);

            $eventData=array(
                'lat' => $lat,
                'lon' => $lon,
                'ts' => $dateSQL,
                'hash' => md5($dateTs)
            );

            /*  Evitar crear eventos duplicados que muestren erroneamente más de un evento siendo que se trata del mismo
             *  pero actualizado.
             *  Esto se hace debido a que el primer informe ante un evento, puede ser preliminar
             *  y se pueden publicar actualizaciones de datos con cambios en magnitud o ubicación geográfica posteriormente.
             */

            $eventExists=$this->Event->checkForExists($eventData, $this->dateBounds);

            if ($eventExists['exists']){
                Debugger::dump('***EVENTO YA EXISTE ****');
                //echo ('evento ya existe <br>');
                $event = $eventExists;
            }else{
                Debugger::dump('***NO SE ENCONTRO EVENTO, CREANDO ****');
               $this->Event->create();
               $event = $this->Event->save($eventData);
            }

            if ($event){
                $metadatum=array(
                    'event_id' => $event['Event']['id'],
                    'agency_id' => 2,
                    'lat' => $eventData['lat'],
                    'lon' => $eventData['lon'],
                    'ts' => $dateSQL,
                    'depth' => $earthquakeData[4],
                    'magnitude' => floatval($earthquakeData[1]),
                    'geo_reference' => $earthquakeData[7],
                    /*
                        at the time I dont know magnitude type used by gfz
                        without check event specific page. I dont want to do that.
                        to be more accurate use Mw or Ml based on this
                        http://earthquake.usgs.gov/earthquakes/eventpage/terms.php#magnitude
                    */
                    'magnitude_type' => $earthquakeData[1] > 3.5 ? 'Mw' : 'Ml'
                );

                if (!$eventExists['exists']){
                       Debugger::dump('***SISMO NO EXISTE, CREANDO ****');
                  
                   $this->EventMetadatum->create();
                   $earthquake=$this->EventMetadatum->save($metadatum);
                }else{
                    $earthquakeExists = $this->EventMetadatum->checkForExists($metadatum,$this->dateBounds,$eventExists['Event']['id']);
                    if ($earthquakeExists['exists']){
                        Debugger::dump('***EVENTO EXISTE, SISMO TAMBIEN ****');
                    }else{
                        Debugger::dump('***EVENTO EXISTE, NUEVO SISMO NO. CREANDO NUEVO ASOCIADO A EVENTO****');
                        $this->EventMetadatum->create();
                        $earthquake = $this->EventMetadatum->save($metadatum);
                    }

                }

            }
        }
    }



}
