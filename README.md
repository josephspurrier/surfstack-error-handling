SurfStack Error Handling in PHP
========================

Single class that registers a shutdown function and an error handler to provide
detailed error logging in both the $_SESSION superglobal and an HTML file.

The class handles both fatal and non-fatal errors. Non-fatal errors will allow
the page to load. Fatal errors will try to reload the page once more and it the
error still occurs, it will try to access the parent page. If that page also
throws an error, an HTTP 500 page will display.

In your main application file (index.php), follow these instructions.

```php
<?php

// Use this php.ini directive for both PHP default handler
// and for SurfStack Error Handler
ini_set('error_reporting', E_ALL);

// Use this php.ini directive for only PHP default handler
ini_set('display_errors', 0);

// Use this php.ini directive for only PHP default handler
ini_set('log_errors', 1);

// Use this php.ini directive for both PHP default handler
// and for SurfStack Error handler
// SurfStack Error Handler will create an additional file with extension .html
// if blLogHTMLErrors = true
ini_set('error_log', dirname($_SERVER['DOCUMENT_ROOT']).'/private/log/'.date('Y-m-d', time()).'_error.log');

// Start a session to prevent excessive loops with fatal errors
session_start();

// Create an instance of the Error Handler
$eHandler = new SurfStack\ErrorHandling\ErrorHandler();

// Register the error handler and shutdown function
// You can also register them individually
$eHandler->register();

// Use the default PHP handler for output to screen and output to file after
// this class finishes
// This includes writing to the screen and to the file specified by the php.ini
// directive: error_log
$eHandler->blDefaultHandler = true;

// Log more detailed information to HTML file alongside error_log to *.html
// Ignores the log_errors php.ini directive
$eHandler->blLogHTMLErrors = true;

// If true,  log SESSION, GET, POST, COOKIE, SERVER, ENV
// If false, log SESSION, GET, POST
$eHandler->blLogAllGlobals = false;

// Output the error code on the screen to notify of an error
// Easy to spot. Ex. Error code: 8
// Ignores the display_errors php.ini directive
$eHandler->blOutputErrorCode = true;

// Output a notice error
$foo[1];

// You can also test with this
// Output a depreciated error
//split(":", 'bar:rab', 2);

// You can also test with this
// Output a loop error (fatal error)
//fakeFunction();

```

The above configuration will output:
Error code: 8

There will be a text error log (2014-03-05_error.log) with this information:
[05-Mar-2014 06:31:01 US/Eastern] PHP Notice:  Undefined variable: foo in ...\localhost\public\index.php on line 71
[05-Mar-2014 06:31:01 US/Eastern] PHP Stack trace:
[05-Mar-2014 06:31:01 US/Eastern] PHP   1. {main}() ...\localhost\public\index.php:0

There will also be an HTML error log (2014-03-05_error.log.html) with this information:
Website Error
NOTICE: Undefined variable: foo
...\localhost\public\index.php on line 71
Stack trace:
1 index.php : SurfStack\ErrorHandling\ErrorHandler->errorHandler() on line 71

Additional Information
Remote Address: ::1
Browser: Mozilla/5.0 (Windows NT 6.1; WOW64)
Query:
Method: GET
PHP File: /index.php
PHP Script: /index.php
URI: /
Protocol: HTTP/1.1

Session
Array
(
)

Get
Array
(
)

Post
Array
(
)


Generated: Mar 05, 2014 at 06:31 AM

Advanced Functionality
----------------------

The class can be extended to replace any method, but the main ones are:
* showUserError
* showAdminError

The showAdminError will only be called when the global function isAdmin() returns
true. If the function doesn't exist or returns false, the showUserError function
will be called. You'll need to create an isAdmin() function yourself if you
want to use the functionality.

The class requires a session to be started prior to handling any errors.
The class creates and uses the following session variables:
* $_SESSION['error'] - contains the latest error message
* $_SESSION['errorBacklog'] - contains list of errors during a fatal error redirect
* $_SESSION['errorLoop'] - tracks fatal error redirects

These session variables are also available for you to use in your application
for logging or display purposes. A practical application would be to create
a global function that you can can call after the error occurs and before a
page renders.

```php
<?php
function logAndGetError()
{
    $eMessage = '';
    
    // If an error occurred
    if (isset($_SESSION['error']))
    {
        // Log the error through Monolog to a file or database
        $this->logger->error($_SESSION['error']);

        // Only retrieve the error message for an admin
        if (isAdmin())
        {
            // Get the error
            $eMessage = $_SESSION['error'];
        }
        
        // Clear the error
        unset($_SESSION['error']);
        
        // Clear the error backlog
        if (isset($_SESSION['errorBacklog']))
        {
            unset($_SESSION['errorBacklog']);
        }
    }
    
    // Return the message
    return $eMessage;
}
```
