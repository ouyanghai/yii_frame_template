<?php

class CMemLogRoute extends CLogRoute
{

    public $host = 'localhost';
    public $port = 11211;
    public $name = 'system_log';
    
    public $_levels = array( 'error', 'warning' );
    public $_filters = array( 'clientScript', 'favicon.ico', '无法解析请求' );

    public function processLogs( $logs ){

        $cache = new Storage( 'queue' );

        /*
        $cache = new Memcache();

        if( !$cache->connect( $this->host, $this->port ) ){
            return;
        } */

        foreach( $logs as $log ){
            
            if( !empty( $this->_filters ) ){
                foreach( $this->_filters as $pattern ){
                    
                    if( preg_match( "#{$pattern}#", $log[0] ) ) {
                        continue 2;
                    }
                }
            }
            
            if ( strlen( $log[0] ) > 2048 ) {
                $log[0] = substr( $log[0], 0, 2048 );
            }
            
            if ( !in_array( $log[1], $this->_levels ) ) {
                continue;
            }
            
            // $log[] = $_SERVER['SERVER_ADDR'];
            
            if ( isset( $_SERVER["SERVER_ADDR"] ) ) {
                $log[] = $_SERVER['SERVER_ADDR'];
            } else {
                $log[] = 'cli';
            }

            $cache->set( $this->name, $log );
        }

        $cache->close();
    }
}