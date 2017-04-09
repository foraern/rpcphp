rpcphp
===============

A simple class for making calls to RPC API's using PHP.

Getting Started
---------------
1. Include rpcphp.php into your PHP script:

    ```php
    require_once('rpc.php');
    ```
2. Initialize the connection/object:

    You can specify a host, port.

    ```php
    $myObj = new RPC('username','password','localhost','8995');
    ```

    If you wish to make an SSL connection you can set an optional CA certificate or leave blank
    
    ```php
    $myObj->setSSL('/full/path/to/mycertificate.cert');
    ````

3. Make calls to RPC as methods for your object.

    ```php
    $myObj->getinfo();
    ```

Additional Info
---------------
* When a call fails for any reason, it will return false and put the error message in `$myObj->error`

* The HTTP status code can be found in $myObj->status and will either be a valid HTTP status code or will be 0 if cURL was unable to connect.

* The full response (not usually needed) is stored in `$myObj->response` while the raw JSON is stored in `$myObj->raw_response`
