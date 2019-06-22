<?php

namespace App\Utils;

use Exception;

class UsageException extends Exception {}


class Util
{

    // returns the CLI params, exactly as entered by user.
    public static function getCliParams()
    {
        $paramsArray = array(
            'user:',
            'pass:',
            'url:',
            'method:',
            'params:',
            'outfile:',
            'format:',
            'logfile:',
            'loglevel:',
            'httpfile:',
            'version',
            'highlight:',
            'help',
        );

        $params = getopt( '', $paramsArray);

        // simulate getopt optind param for older versions of php.
        $optind = count($params) + 1;
        $argv = $GLOBALS['argv'];
        
        $params['url'] = @$argv[$optind];
        $params['method'] = @$argv[$optind+1];
        $params['params'] = @$argv[$optind+2];        

        
        return $params;
    }

    /* processes and sanitizes the CLI params. adds defaults
     * and ensure each value is set.
     */
    public static function processCliParams()
    {

        $params = static::getCliParams();

        $success = 0;   // 0 == success.

        if( isset($params['version'])) {
            static::printVersion();
            return [$params, 2];
        }

        if( isset($params['help'])) {
            static::printHelp();
            return [$params, 2];
        }        
        
        $params['format'] = @$params['format'] ?: 'jsonpretty';
        $params['user'] = @$params['user'];
        $params['pass'] = @$params['pass'];
        $params['url'] = @$params['url'];        
        $params['highlight'] = @$params['highlight'] == 'off' ? false : true;
                
        $params['method'] = @$params['method'];
        $params['httpfile'] = @$params['httpfile'];
        
        $params['params'] = json_decode($params['params'], true);
        if(json_last_error() != JSON_ERROR_NONE) {
            throw new UsageException("Invalid JSON in parameters.  " . json_last_error_msg() );
        }

        if(!$params['url']) {
            throw new UsageException("Destination URL not provided");
        }        
        
        // TODO
        if(@$params['logfile']) {
            mylogger()->set_log_file( $params['logfile'] );
            mylogger()->echo_log = false;
        }

        $loglevel = @$params['loglevel'] ?: 'specialinfo';
        MyLogger::getInstance()->set_log_level_by_name( $loglevel );

        return [$params, $success];
    }
    
    /**
     * prints program version text
     */
    public static function printVersion()
    {
        $versionFile = __DIR__ . '/../VERSION';

        $version = @file_get_contents($versionFile);
        echo $version ?: 'version unknown' . "\n";
    }


    /* prints CLI help text
     */
    public static function printHelp()
    {

        $levels = MyLogger::getInstance()->get_level_map();
        $loglevels = implode(',', array_values( $levels ));

        $buf = <<< END

   jsonrpc-cli.php [options] <url> <method> [params]

   This script makes a request to a jsonrpc server.
   
   params should be provided in json format.  eg:
     "6" or "6,7", or "[6,7]", or '{color: "red", size: "small"}'

   Options:
   
    --user <user>        username for http basic auth
    --pass <pass>        password for http basic auth

    --outfile=<path>     specify output file path.
    
    --format=<format>    [ raw|json|jsonpretty|json5|printr|vardump|serialize|all ]
                           
                         default=jsonpretty
                         
                         raw will print response exactly as received from
                         server (after http chunk decoding) even if invalid json.
    
                         if 'all' is specified then a file will be created
                         for each format with appropriate extension.
                         only works when outfile is specified.
                         
    --highlight=<flag>   [ 'on' | 'off' ]   default = on.
                           highlights output if possible, depending on --format.

    --logfile=<path>    path to logfile. if not present logs to stdout.
    --loglevel=<level>  $loglevels
                          default = info
                          
    --httpfile=<path>   writes raw http request, headers, and response to a file.


END;

        fprintf( STDOUT, $buf );

    }
        
    
}
