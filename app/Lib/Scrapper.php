<?php
/**
 * Load external lib
 */
App::uses('simple_html_dom', 'Lib/SimpleHTMLDom');

/**
 * Class Scrapper
 */
Class Scrapper {

/**
 * [$dom description]
 * @var [type]
 */
    private $dom;

/**
 * [$endpoint description]
 * @var [type]
 */
    private $endpoint;

/**
 * [$endpointTokens description]
 * @var array
 */
    private $endpointTokens = [];
    
/**
 * [__construct description]
 * @param [type] $endpoint       [description]
 * @param [type] $endpointTokens [description]
 */
    public function __construct($endpoint, $endpointTokens) {
        $this->endpoint = $endpoint;
        $this->endpointTokens = $endpointTokens;
        $this->dom = new simple_html_dom;
    }
    
/**
 * [getScrappingUrl description]
 * @param  [type] $params [description]
 * @return [type]         [description]
 */
    public function getScrappingUrl($params) {
        return str_replace($this->endpointTokens, $params, $this->endpoint);
    }

/**
 * [getContent description]
 * @param  [type]  $endpoint   [description]
 * @param  boolean $persistent [description]
 * @return [type]              [description]
 */
    public function getContent($endpoint, $persistent = false) {
        $content = null;
        do {
            $content = file_get_contents($endpoint); 
        } while (!$content AND $persistent === true);
        
        return $content;
    }
/**
 * [domLoad description]
 * @param  [type] $content [description]
 * @return [type]          [description]
 */
    public function domLoad($content) {
        if ($this->dom) {
            $this->dom->load($content, true, true);
        }
    }

/**
 * [findInDom description]
 * @param  [type] $selector [description]
 * @return [type]           [description]
 */
    public function findInDom($selector) {
        return $this->dom->find($selector);
    }
}

