<?php
App::uses('simple_html_dom', 'Lib/SimpleHTMLDom');

Class Scrapper{
    private $dom = null;
    private $endpoint = null;
    private $endpointTokens = null;
    
    public function __construct($endpoint, $endpointTokens){
        date_default_timezone_set('UTC');
        $this->endpoint = $endpoint;
        $this->endpointTokens = $endpointTokens;
        $this->dom = new simple_html_dom;
    }
    
    public function getScrappingUrl($params){
        return str_replace($this->endpointTokens,$params,$this->endpoint);
    }
    
    public function getContent($endpoint,$persistent = false){
        $content = null;
        do {
           $content = file_get_contents($endpoint); 
        } while (!$content AND $persistent === true);
        return $content;
    }
    
    public function domLoad($content){
        if ($this->dom){
            $this->dom->load($content, true, true);
        }
    }

    public function findInDom($selector){
        return $this->dom->find($selector);
    }
}

