<?php

App::uses('ScrapperController', 'Controller');


Class GucController extends ScrapperController {

    public $uses=array(
        'Agency',
        'Event',
        'EventMetadatum'
    );
    
    public function beforeFilter(){
        parent::beforeFilter();
        
        //TO-DO: hacer esto con un metodo con frefijo por país ;)
       date_default_timezone_set('Chile/Continental');
    }
    
    public function getFromDateRange($startDate,$endDate){
        $dateBounds=array(
            'start'=>Date('Y-m-d',DatesUtils::toTimestamp($startDate)),
            'end'=>Date('Y-m-d',DatesUtils::toTimestamp($endDate))
        );

        $this->dateBounds=$dateBounds;
        
        $self=$this;
        DatesUtils::rangeLoop($startDate,$endDate,function($day,$month,$year) use ($self){
            
            $self->doScrapping($self->getScrappingUrl(array($year,$month,$day)));
        }); 
        die;
       
    }
    public function getFromtoday($mode='verbose'){
        $currentUTCTimestamp=strtotime(date('Y-m-d H:i:s',time() ) . ' + 3 hours');
        $currentUTCDate=date('Y-m-d',$currentUTCTimestamp );
        

        $dateBounds=array(
            'start'=>$currentUTCDate,
            'end'=>$currentUTCDate
        );
        $timeBounds=array(
            'start'=>date('Y-m-d H:i:s',$currentUTCTimestamp ),
            'end'=>date('Y-m-d H:i:s',$currentUTCTimestamp )
        );


        $this->dateBounds=$dateBounds;
        $this->timeBounds=$timeBounds;
        $this->doScrapping($this->getScrappingUrl( array( date('Y',$currentUTCTimestamp),date('m',$currentUTCTimestamp),date('d',$currentUTCTimestamp))) );
        die;
    }
    public function doScrapping($endpoint){
        $event=null;
        $earthquake=null;
        Debugger::dump('endpoint: ' . $endpoint . '    _' . $_SERVER['HTTP_USER_AGENT']);
        Debugger::dump('***INICIANDO SCRAPPING****');

        
       
        $content=$this->getContent($endpoint);
        if ($content){
            $this->domLoad($content);
            $tableList = $this->dom->find('table tbody tr');
        }else{
          Debugger::dump('***ERROR, NO SE OBTUBIERON DATOS');  
          die('NO HAY DATOS: ' . $endpoint);
        }
        
        //get each table node
        foreach ($tableList as $key => $table) {
            $earthquakeData=array();
            //get each data item
            foreach ($table->find('td') as $key => $tableItem) {
                $earthquakeData[$key]=$tableItem->text();
            }

            //ignore invalid items
            if ($earthquakeData){
                $dateUTC=$earthquakeData[1];
                $dateTs=DatesUtils::toTimestamp($dateUTC);
                $dateSQL=DatesUtils::toSQLDate($dateUTC);

                $eventData=array(
                    'lat'=>$earthquakeData[2],
                    'lon'=>$earthquakeData[3],
                    'ts'=>$dateSQL,
                    'hash'=>md5($dateTs)
                );

                /*  Evitar crear eventos duplicados que muestren erroneamente más de un evento siendo que se trata del mismo
                 *  pero actualizado.
                 *  Esto se hace debido a que el primer informe ante un evento, puede ser preliminar
                 *  y se pueden publicar actualizaciones de datos con cambios en magnitud o ubicación geográfica posteriormente.
                 */

                $eventExists=$this->Event->checkForExists($eventData,$this->dateBounds);
                if ($eventExists['exists']){
                    Debugger::dump('***EVENTO YA EXISTE ****');
                  //echo ('evento ya existe <br>');
                    $event=$eventExists;
                }else{
                    Debugger::dump('***NO SE ENCONTRO EVENTO, CREANDO ****');
                   $this->Event->create();
                   $event=$this->Event->save($eventData);
                }

                if ($event){
                    $metadatum=array(
                        'event_id'=>$event['Event']['id'],
                        'agency_id'=>1,
                        'lat'=>$eventData['lat'],
                        'lon'=>$eventData['lon'],
                        'ts'=>$dateSQL,
                        'depth'=>$earthquakeData[4],
                        'magnitude'=>floatval($earthquakeData[5]),
                        'geo_reference'=>$earthquakeData[6]
                    );

                    if (!$eventExists['exists']){
                           Debugger::dump('***EVENTO NO EXISTE, TEMBLOR TAMPOCO ****');
                      
                       $this->EventMetadatum->create();
                       $earthquake=$this->EventMetadatum->save($metadatum);
                    }else{
                        $earthquakeExists=$this->EventMetadatum->checkForExists($metadatum,$this->dateBounds,$eventExists['Event']['id']);
                        if ($earthquakeExists['exists']){
                            Debugger::dump('***EVENTO EXISTE, TEMBLOR TAMBIEN ****');
                           //echo ('evento existe, temblor también <br>');
                        }else{
                            Debugger::dump('***EVENTO EXISTE, TEMBLOR NO CREANDO NUEVO ASOCIADO A EVENTO****');
                            //echo ('evento ya existe,temblor no, creando nuevo sismo asociado a evento <br>');
                            $this->EventMetadatum->create();
                            $earthquake=$this->EventMetadatum->save($metadatum);
                        }

                    }

                }

            }else{
                Debugger::dump('***NO HAY DATOSSS****');
            }
        }
    }



}
