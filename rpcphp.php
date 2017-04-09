<?php
/*
rpcphp

A simple class for making calls to RPC's API using PHP.
https://github.com/foraern/rpcphp

*/

class RPC {
    // Configuration options
    private $username;
    private $password;
    private $proto;
    private $host;
    private $port;
    private $url;
    private $CACertificate;
	
	private $method;

    // Information and debugging
    public $status;
    public $error;
    public $raw_response;
    public $response;

    private $id = 0;

    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $proto
     * @param string $url
     */
    function __construct($host = 'localhost', $port = 8500, $username = "", $password = "", $method=TRUE,  $url = null) {
        $this->username      = $username;
        $this->password      = $password;
        $this->host          = $host;
        $this->port          = $port;
        $this->url           = $url;
		$this->method		 = $method;

        // Set some defaults
        $this->proto         = 'http';
        $this->CACertificate = null;
    }

    /**
     * @param string|null $certificate
     */
    function setSSL($certificate = null) {
        $this->proto         = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }

    function __call($function, $params) {
        $this->status       = null;
        $this->error        = null;
        $this->raw_response = null;
        $this->response     = null;

        // If no parameters are passed, this will be an empty array
        $params = array_values($params);

        // The ID should be unique for each call
        $this->id++;
		
        // Build the request, it's ok that params might have any empty array
        $request = json_encode(array(
            'method' => $function,
            'params' => $params,
            'id'     => $this->id
        ));
		if($this->method==FALSE){
			foreach($params as $key=>$parm){
				$paramstring.="/".$parm;
			}
			$this->url = $function.$paramstring;
		}

        // Build the cURL session
        $ch    = curl_init("{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
		curl_setopt($ch, CURLOPT_URL, "{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		if($this->method == FALSE)
		{
			curl_setopt($ch, CURLOPT_POST, $this->method);
		}
		else
		{
			curl_setopt($ch, CURLOPT_POST, $this->method);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		}

        // This prevents users from getting the following warning when open_basedir is set:
        // Warning: curl_setopt() [function.curl-setopt]: CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
        if (ini_get('open_basedir')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, NULL);
        }

        if ($this->proto == 'https') {
            // If the CA Certificate was specified we change CURL to look for it
            if ($this->CACertificate != null) {
				curl_setopt($ch, CURLOPT_CAINFO, $this->CACertificate);
				curl_setopt($ch, CURLOPT_CAPATH, DIRNAME($this->CACertificate));
            }
            else {
                // If not we need to assume the SSL cannot be verified so we set this flag to FALSE to allow the connection
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            }
        }

        // Execute the request and decode to an array
        $this->raw_response = curl_exec($ch);
		$this->response     = json_decode($this->raw_response, TRUE);
		if(!isset($this->response['result'])){
			$this->response['result'] = json_decode($this->raw_response);
		}
        // If the status is not 200, something is wrong
        $this->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // If there was no error, this will be an empty string
        $curl_error = curl_error($ch);

        curl_close($ch);

        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }

        if ($this->response['error']) {
            // If rpcphp returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        }
        elseif ($this->status != 200) {
            // If rpcphp didn't return a nice error message, we need to make our own
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }

        if ($this->error) {
            return FALSE;
        }

        return $this->response['result'];
    }
}