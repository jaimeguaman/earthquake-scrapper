<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('DatesUtils', 'Lib');
App::uses('simple_html_dom', 'Lib/SimpleHTMLDom');
/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class ScrapperController extends Controller {
    public $components = array('DebugKit.Toolbar');
    public $uses=array(
        'Agency',
        'Event',
        'EventMetadatum'
    );
    public $dateBounds=null;
    
    protected $dom=null;
    protected $endpoint='http://sismologia.cl/events/listados/%YEAR%/%MONTH%/%YEAR%%MONTH%%DAY%.html';
    protected $endpointTokens=array(
        '%YEAR%',
        '%MONTH%',
        '%DAY%'
    );
    
    
    public function beforeFilter(){
        $this->dom=new simple_html_dom;
        date_default_timezone_set('UTC');
    }
    
    public function getScrappingUrl($params){
        return str_replace($this->endpointTokens,$params,$this->endpoint);
    }
    
    public function getContent($endpoint,$persistent=false){
        $content=null;
        do {
           $content = file_get_contents($endpoint); 
        } while (!$content AND $persistent===true);
        return $content;
    }
    
    public function domLoad($content){
        if ($this->dom){
            $this->dom->load($content, true, true);
        }
    }
         
}
