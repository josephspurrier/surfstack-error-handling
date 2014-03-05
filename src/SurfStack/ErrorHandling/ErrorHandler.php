<?php

/**
 * This file is part of the SurfStack package.
 *
 * @package SurfStack
 * @copyright Copyright (C) Joseph Spurrier. All rights reserved.
 * @author Joseph Spurrier (http://josephspurrier.com)
 * @license http://www.apache.org/licenses/LICENSE-2.0.html
 */

namespace SurfStack\ErrorHandling;

/**
 * Error Handler
 *
 * Handles shutdowns and errors with detailed logging. Requires SESSION to catch
 * redirect loops.
 */
class ErrorHandler
{
    /**
     * Use the default PHP handler for output to screen and output to file
     * after this class finishes
     * @var boolean
     */
    public $blDefaultHandler = false;
    
    /**
     * Log HTML to the same folder location as php.ini directive: error_log
     * @var boolean
     */
    public $blLogHTMLErrors = false;
    
    /**
     * If false, log SESSION, GET, POST
     * If true, log SESSION, GET, POST, COOKIE, SERVER, ENV
     * @var boolean
     */
    public $blLogAllGlobals = false;
    
    /**
     * Output the error code to the page
     * @var boolean
     */
    public $blOutputErrorCode = false;
    
    /**
     * Register the error and shutdown handlers
     */
    public function register()
    {
        register_shutdown_function(array($this, 'shutdownHandler'));
        set_error_handler(array($this, 'errorHandler'));
    }
    
    /**
     * Register the error handler
     */
    public function registerErrorHandler()
    {
        set_error_handler(array($this, 'errorHandler'));
    }
    
    /**
     * Register the shutdown function
     */
    public function registerShutdownFunction()
    {
        register_shutdown_function(array($this, 'shutdownHandler'));
    }
    
    /**
     * Handle shutdowns
     */
    public function shutdownHandler()
    {
        // Load the last error message
        $error = error_get_last();
        
        // If an error exists
        if ($error != NULL)
        {
            $handle = false;
            
            // Only handle FATAL errors
            switch ($error['type'])
            {
                // E_ERROR
            	case 1:
            	    $handle = true;
            	    break;
        	    // E_WARNING
        	    case 2:
        	        $handle = false;
        	        break;
        	    // E_PARSE
            	case 4:
            	    $handle = true;
            	    break;
        	    // E_NOTICE
        	    case 8:
        	        $handle = false;
        	        break;
        	    // E_CORE_ERROR
            	case 16:
            	    $handle = true;
            	    break;
        	    // E_CORE_WARNING
            	case 32:
            	    $handle = true;
            	    break;
        	    // E_COMPILE_ERROR
            	case 64:
            	    $handle = true;
            	    break;
        	    // E_COMPILE_WARNING
            	case 128:
            	    $handle = true;
            	    break;
        	    // E_USER_ERROR
        	    case 256:
        	        $handle = false;
        	        break;
    	        // E_USER_WARNING
    	        case 512:
    	            $handle = false;
    	            break;
	            // E_USER_NOTICE
	            case 1024:
	                $handle = false;
	                break;
        	    // E_STRICT
            	case 2048:
            	    $handle = true;
            	    break;
        	    // E_RECOVERABLE_ERROR
        	    case 4096:
        	        $handle = false;
        	        break;
    	        // E_DEPRECATED
    	        case 8192:
    	            $handle = false;
    	            break;
	            // E_USER_DEPRECATED
	            case 16384:
	                $handle = false;
	                break;
                // E_ALL
                case 32767:
                    $handle = false;
                    break;
            	default:
            	    break;
            }
            // If a FATAL error occurred
            if ($handle)
            {
                // Handle and redirect
                $this->errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
            }
            // Else a non-fatal error occurred
            else
            {
                // Do nothing
            }
        }
        // Else an error does not exist and the script finishes or exit() or die()
        else
        {
            // A successful pageload will clear up the errorLoop variable
            if (isset($_SESSION['errorLoop'])) unset($_SESSION['errorLoop']);
        }
    }
    
    /**
     * Handle errors
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return boolean
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // If statement starts with @ or the error is not set to report
        if (!($errno & error_reporting()))
        {
            // If false, log to error_log. If true, don't log to error_log.
            return !$this->blDefaultHandler;
        }
    
        // Create a new Exception
        $lasterror = new \ErrorException(
            htmlentities($errstr, ENT_QUOTES, "UTF-8"),
            $errno,
            0,
            htmlentities($errfile, ENT_QUOTES, "UTF-8"),
            $errline
        );
    
        // Handle the exception
        $this->handleError($lasterror);

        // If false, log to error_log. If true, don't log to error_log.
        return !$this->blDefaultHandler;
    }

    /**
     * Format and log the errors
     * @param \ErrorException $e
     */
    private function handleError(\ErrorException $e)
    {
        if ($this->blOutputErrorCode)
        {
            // Output the error code to the page
            echo 'Error code: '.$e->getCode();
        }

        // If a redirect needs to occurr
        $blFatalRedirect = false;
        
        switch ($e->getCode())
        {
        	case 1:
        	    $econstant = "FATAL";
        	    $edescription = "Fatal run-time error. Execution of the script is halted.";
        	    $blFatalRedirect = true;
        	    break;
        	case 2:
        	    $econstant = "WARNING";
        	    $edescription = "Non-fatal run-time error. Execution of the script is not halted.";
        	    break;
        	case 4:
        	    $econstant = "PARSE";
        	    $edescription = "Compile-time parse errors. Parse errors should only be generated by the parser.";
        	    // These errors occurs when there is a syntax error (like when line doesn't end with a semicolon)
        	    $blFatalRedirect = true;
        	    break;
        	case 8:
        	    $econstant = "NOTICE";
        	    $edescription = "Run-time notice. The script found something that might be an error, but could also happen when running a script normally.";
        	    break;
        	case 16:
        	    $econstant = "CORE_ERROR";
        	    $edescription = "Fatal errors that occur during PHP's initial startup.";
        	    $blFatalRedirect = true;
        	    break;
        	case 32:
        	    $econstant = "CORE_WARNING";
        	    $edescription = "Warnings (non-fatal errors) that occur during PHP's initial startup.";
        	    break;
        	case 64:
        	    $econstant = "COMPILE_ERROR";
        	    $edescription = "Fatal compile-time errors.";
        	    $blFatalRedirect = true;
        	    break;
        	case 128:
        	    $econstant = "COMPILE_WARNING";
        	    $edescription = "Compile-time warnings (non-fatal errors).";
        	    break;
        	case 256:
        	    $econstant = "USER_ERROR";
        	    $edescription = "Fatal user-generated error. This is like an E_ERROR set by the programmer using the PHP function trigger_error().";
        	    $blFatalRedirect = true;
        	    break;
        	case 512:
        	    $econstant = "USER_WARNING";
        	    $edescription = "Non-fatal user-generated warning. This is like an E_WARNING set by the programmer using the PHP function trigger_error().";
        	    break;
        	case 1024:
        	    $econstant = "USER_NOTICE";
        	    $edescription = "User-generated notice. This is like an E_NOTICE set by the programmer using the PHP function trigger_error().";
        	    break;
        	case 2048:
        	    $econstant = "STRICT";
        	    $edescription = "Suggested changes to code which will ensure the best interoperability and forward compatibility of your code..";
        	    break;
        	case 4096:
        	    $econstant = "RECOVERABLE_ERROR";
        	    $edescription = "Catchable fatal error. This is like an E_ERROR but can be caught by a user defined handle (see also set_err or_handler()).";
        	    // These errors occurs when you try to pass the wrong type of variable to a method (like passing an int instead of a FileItemType)
        	    $blFatalRedirect = true;
        	    break;
        	case 8191:
        	    $econstant = "DEPRECATED";
        	    $edescription = "Run-time notices. Warnings about code that will not work in future versions.";
        	    break;
        	case 16384:
        	    $econstant = "USER_DEPRECATED";
        	    $edescription = "User-generated warning message.";
        	    break;
        	case 32767:
        	    $econstant = "ALL";
        	    $edescription = "All errors and warnings, as supported, except of level E_STRICT prior to PHP 5.4.0.";
        	    break;
        	default:
        	    $econstant = "ERROR";
        	    $edescription = "Problem with code.";
        }

        // Save the error information
        $_SESSION['error'] = $this->generateDetailedLog($e, $econstant, $edescription);
        
        // If blLogHTMLErrors
        if ($this->blLogHTMLErrors)
        {
            // Log the HTML error
            $errorFile = ini_get('error_log').'.html';
            file_put_contents($errorFile, $_SESSION['error'].'<hr><br />', FILE_APPEND);
        }

        // If there is a fatal error, redirect immediately
        if ($blFatalRedirect)
        {
            // If there are 2 fatal errors in a row, redirect to a page_not_found
            if (isset($_SESSION['errorLoop']) && $_SESSION['errorLoop']>2)
            {
                if (function_exists('isAdmin') && isAdmin())
                {
                    $this->showAdminError();
                }
                else
                {
                    $this->showUserError();
                }
                
                // This prevents the errors from piling up
                $this->clearErrorBacklog();
            }
            // Else if there is 1 fatal error, redirect to the parent page
            else if (isset($_SESSION['errorLoop']))
            {
                $_SESSION['errorLoop'] += 1;
                header("location: ".$_SERVER['REQUEST_URI']);
            }
            // Else this is the first fatal error, redirect to the same page
            else
            {
                $_SESSION['errorLoop'] = 1;
                header("location: ".dirname($_SERVER['REQUEST_URI']));
            }
            exit;
        }
        
        // This prevents the errors from piling up
        $this->clearErrorBacklog();
    }
    
    /**
     * Generate the detailed log
     * @param \ErrorException $e
     * @param int $econstant
     * @param string $edescription
     * @return string
     */
    private function generateDetailedLog(\ErrorException $e, $econstant, $edescription)
    {
        // Holds the error
        $errorout = '';
        
        // If the Backlog is set
        if (isset($_SESSION['errorBacklog']))
        {
            // Append only previous error message heading
            $errorout .= $_SESSION['errorBacklog'];
        }
        
        // Show the basic error message
        $errorout .= "<b>Website Error</b>";
        $errorout .= "<br />";
        
        // Format the basic error information
        $errorout .= "<b>".$econstant.":</b> ".$e->getMessage();
        $errorout .= "<br />";
        $errorout .= '<b>'.$e->getFile().'</b> on line <b>'.$e->getLine().'</b>';
        $errorout .= "<br />";
        
        // Save the current error message (without all the crap below)
        $_SESSION['errorBacklog'] = $errorout;
        
        if (strstr($errorout, 'Stack trace:') === false)
        {
            // Show every step to get to the error message
            $errorout .= $this->getStackTrace();
        }
        
        // Save the current error message (without all the crap below)
        //$_SESSION['errorBacklog'] = htmlentities($errorout, ENT_QUOTES, "UTF-8");
        $_SESSION['errorBacklog'] = $errorout;
        
        // Additional information
        $errorout .= PHP_EOL.'<b>Additional Information</b>'.PHP_EOL;
        
        $errorout .= "<b>Remote Address:</b> ".(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'NA').PHP_EOL;
        $errorout .= "<b>Browser:</b> ".(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'NA').PHP_EOL;
        if (isset($_SERVER['HTTP_REFERER'])) $errorout .= "<b>Previous Page:</b> ".$_SERVER['HTTP_REFERER'].PHP_EOL;
        $errorout .= "<b>Query:</b> ".$_SERVER['QUERY_STRING'].PHP_EOL;
        $errorout .= "<b>Method:</b> ".$_SERVER['REQUEST_METHOD'].PHP_EOL;
        $errorout .= "<b>PHP File:</b> ".$_SERVER['PHP_SELF'].PHP_EOL;
        $errorout .= "<b>PHP Script:</b> ".$_SERVER['SCRIPT_NAME'].PHP_EOL;
        $errorout .= "<b>URI:</b> ".$_SERVER['REQUEST_URI'].PHP_EOL;
        $errorout .= "<b>Protocol:</b> ".$_SERVER['SERVER_PROTOCOL'].PHP_EOL;
        $errorout .= PHP_EOL;
        
        // These didn't output anything for me
        /*$errorout .= $php_errormsg;
         $errorout .= $HTTP_RAW_POST_DATA;
        $errorout .= $http_response_header;
        $errorout .= $argc;
        $errorout .= $argv;*/
        
        // Get the super global information
        $errorout .= $this->getSuperglobals(true);
        
        // Remove the characters that javascript doesn't like
        $errorout = str_replace("'","", $errorout);
        $errorout = str_replace("\"","", $errorout);
        $errorout = str_replace("\n","<br />", $errorout);
        
        // Store the date in this format: Mar 05, 2014 at 02:39 AM
        $date = date_format(date_create(), 'M d, Y \a\t h:i A');
        
        // Save the good information for the server
        return $errorout."<br /><b>Generated:</b> $date<br /><br />";
    }
    
    /**
     * Return string of clean stack trace
     * @return string
     */
    private function getStackTrace()
    {
        // Get the backtrace
        $trace = array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        
        // Output heading
        $output = 'Stack trace:'.PHP_EOL;
        
        // Start a 1
        $i = 1;
        
        // Loop through stacktrace
        foreach($trace as $node)
        {
            // Get the stacktrace values
            $file = (isset($node['file']) ? basename($node['file']).' : ' : '');
            $class = (isset($node['class']) ? $node['class'] : '');
            $function = (isset($node['function']) ? $node['function'] : '');
            $type = (isset($node['type']) ? $node['type'] : '');
            $line = (isset($node['line']) ? ' on line '.$node['line'] : '');
            
            // Don't include this file in the stack trace
            if (!$file || basename($node['file']) != basename(__FILE__))
            {
                // Format the values
                $output .= "#$i $file$class$type$function()$line".PHP_EOL;
            }

            // Increment the counter
            $i++;
        }
        
        // Return the string
        return $output;
    }

    /**
     * Clear the error backlog from the $_SESSION global
     */
    private function clearErrorBacklog()
    {        
        // Clear the error backlog
        if (isset($_SESSION['errorBacklog']))
        {
            unset($_SESSION['errorBacklog']);
        }
    }
    
    /**
     * Return an HTML representation of the global variables
     * @param int $startTime Script start time
     * @return string
     */
    public function getVerboseOutput($startTime)
    {
        $output = '';
        
        $loadTime = round(microtime(true) - $startTime,4);
        
        $output .= PHP_EOL.PHP_EOL.'<b>Load Time:</b> '.$loadTime.' seconds'.PHP_EOL.PHP_EOL;
        $output .= $this->getSuperglobals();
        
        return nl2br($output);
    }
    
    /**
     * Return an HTML representation of the superglobals
     * @param string $blStripSessionErrors
     * @return string
     */
    private function getSuperglobals($blStripSessionErrors = false)
    {
        $output = '';
        
        if (isset($_SESSION))
        {
            if ($blStripSessionErrors)
            {
                $output .= '<b>Session</b>'.$this->getBufferedArray($this->stripSessionErrors($_SESSION)).PHP_EOL;
            }
            else 
            {
                $output .= '<b>Session</b>'.$this->getBufferedArray($_SESSION).PHP_EOL;
            }
            
        }
        $output .='<b>Get</b>'.$this->getBufferedArray($_GET).PHP_EOL;
        $output .='<b>Post</b>'.$this->getBufferedArray($_POST).PHP_EOL;
        
        if ($this->blLogAllGlobals)
        {
            $output .='<b>Cookie</b>'.$this->getBufferedArray($_COOKIE).PHP_EOL;
            $output .='<b>Server</b>'.$this->getBufferedArray($_SERVER).PHP_EOL;
            $output .='<b>Env</b>'.$this->getBufferedArray($_ENV).PHP_EOL;
        }
        
        return $output;
    }
    
    /**
     * Return an array without the error or errorBacklog
     * @param array $ar
     * @return array
     */
    private function stripSessionErrors($ar)
    {
        $new = $ar;
        if (isset($new['error'])) unset($new['error']);
        if (isset($new['errorBacklog'])) unset($new['errorBacklog']);
        return $new;
    }

    /**
     * Return an escaped string representation of an array that is suitable for HTML output
     * @param array $out
     * @return string
     */
    private function getBufferedArray($out)
    {
        // Output the array in a pretty format
        $output = '<pre>';
        ob_start();
        print_r($out);
        $output .= htmlentities(ob_get_contents(), ENT_QUOTES, "UTF-8");
        ob_end_clean();
        $output .= "</pre>";
        return $output;
    }

    /**
     * Extensible method called when an error occurs for non-admin
     */
    public function showUserError()
    {
        // Delete all other text
        ob_end_clean();
        
        // Show error message
        echo 'Error: Page loop occurred';
        header('HTTP/1.0 500 Internal Server Error');
    }
    
    /**
     * Extensible method called when an error occurs for admin
     */
    public function showAdminError()
    {
        // Show the admin error
        echo nl2br(PHP_EOL).nl2br(PHP_EOL).'Admin Error Debug Enabled: Page loop occurred'.nl2br(PHP_EOL);
        echo $this->getBufferedArray(error_get_last());
    }
}












