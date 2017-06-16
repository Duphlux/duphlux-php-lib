<?php
namespace duphlux;

Abstract class ApiRequest
{

    /** GET method for request */
    CONST METHOD_GET = 'GET';

    /** POST method for request */
    CONST METHOD_POST = 'POST';

    /** PUT method for request */
    CONST METHOD_PUT = 'PUT';

    /** DELETE method for request */
    CONST METHOD_DELETE = 'DELETE';

    protected $config, $baseUrl;

    public $beforeSend,$afterSend;

    protected $verify_peer, $operationUrl;

    /**
     * The current operation being performed
     * @var string|int Current operation id
     */
    private $operation;

    /**
     * @var bool
     */
    public $hasError = false;

    protected $error,$message,$status,$response, $data;

    /**
     * @var string the request method
     */
    private $request_method = self::METHOD_GET;

    /**
     * @var array the request options
     */
    protected $requestOptions = array();

    /**
     * @return mixed the request configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     *  gets the the value of verify_peer which is used in setting CURLOPT_SSL_VERIFYPEER
     */
    private function verifyPeer()
    {
        return !($this->verify_peer === false);
    }

    /**
     * set the request headers
     * @param $header array An array of headers to be used in the request
     */
    abstract public function setHeader($header);

    /**
     * get the request headers
     * @return array Should return an array of headers to be used in the request
     */
    abstract public function getHeader();

    /**
     * sets the response status of the request
     * @param $status string the status of the request
     */
    protected function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * gets the response status of the request
     * @return string|int|boolean return the status of the request
     */
    public final function getStatus()
    {
        return $this->status;
    }

    /**
     * sets the request error
     * @param $error string|array the request error
     */
    protected function setError($error)
    {
        $this->error = $error;

        if (!empty($error))
            $this->hasError = true;
    }

    /**
     * sets the response message
     * @param $message string the response message
     */
    protected function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * sets the response message
     * @return string the response message
     */
    public final function getMessage()
    {
        return $this->message;
    }

    /**
     * gets the response errors in case of error
     * @return mixed the response error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * calls the aftersend callback after any request is made
     * @param $parameter mixed the callback parameter
     * @return void performs the callback operation
     */
    protected function afterSend($parameter = null)
    {
        $parameter = $parameter?:$this;
        if (!empty($this->afterSend))
        {
            if (!is_callable($this->afterSend))
                throw new \InvalidArgumentException('beforeSend property must be a callback');
            else
                call_user_func($this->afterSend,$parameter);
        }
    }

    /**
     * executes the beforesend callback before any request is made
     * @param $parameter mixed the callback parameter
     * @return void
     */
    protected function beforeSend($parameter = null)
    {
        $parameter = $parameter?:$this;
        if (!empty($this->beforeSend))
        {
            if (!is_callable($this->beforeSend))
                throw new \InvalidArgumentException('beforeSend property must be a callback');
            else
                call_user_func($this->beforeSend,$parameter);
        }
    }

    /**
     * sets the request method
     * @param $method the request method
     * @return void
     */
    protected function setRequestMethod($method)
    {
        $this->request_method = $method;
    }

    /**
     * gets the current request method
     * @return string the request method
     */
    public function getRequestMethod()
    {
        return $this->request_method;
    }

    /**
     * sets the request response
     * @param $response mixed the request response
     * @return void
     */
    protected function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * sets the request operation url
     * @param $operation mixed the current operation
     * @return void
     */
    protected function setOperationUrl($operation)
    {
        $this->operationUrl = $this->baseUrl.$this->getConfig()[$operation]['endpoint'];
    }

    /**
     * sets the request response
     * @param $operation the current operation
     * @return void
     */
    protected function setOperation($operation)
    {
        $this->operation = $operation;
    }

    /**
     * gets the current request operation
     * @return void
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * get the current api request endpoint
     * @return void
     */
    public function getOperationUrl()
    {
        return $this->operationUrl;
    }

    /**
     * sets the request parameters/data
     * @param $options array|int|string the request parameters/data
     * @return self
     */
    public function setRequestOptions($options = null)
    {
        if (!empty($options))
        {
            if (is_array($options))
                $this->requestOptions = $options + $this->requestOptions;
            else
                $this->requestOptions = $options;
        }

        return $this;
    }

    /**
     * gets the request parameters
     * @return string|int|array the request parameters
     */
    public function getRequestOptions()
    {
        return $this->requestOptions;
    }

    /**
     * gets the request response
     * @param $key string specific response value
     * @return string|int|array the request response
     */
    public function getResponse($key = null)
    {
        return $key?$this->response[$key]:$this->response;
    }

    /**
     * set the response data value
     * @param $data string|int|array the response data
     * @void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * gets the request response data value
     * @param $key string specific response data value
     * @return string|int|array the response data or a speicified data value
     */
    public function getData($key = null)
    {
        return $key?$this->data[$key]:$this->data;
    }

    /**
     * sets-up and sends the api request
     * @param $operation string|int the current operation
     * @param $method string the request method to be used for the operation
     * @return array the response information
     */
    protected function sendRequest($operation,$method = self::METHOD_GET)
    {
        $ch = curl_init();
        $this->setOperationUrl($operation);
        $this->setRequestMethod($method);

        curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $this->getOperationUrl(),
                CURLOPT_HTTPHEADER=>$this->getHeader(),
                CURLOPT_SSL_VERIFYPEER => $this->verifyPeer(),
            )
        );

        $this->beforeSend();

        $method = $this->getRequestMethod();
        if ($method== self::METHOD_GET)
        {
            curl_setopt($ch,CURLOPT_POST, false );
        }
        elseif ($method == self::METHOD_POST)
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->getRequestOptions()));
        }
        elseif ($method == self::METHOD_PUT)
        {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD_PUT);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getRequestOptions());
        }
        elseif ($method == self::METHOD_DELETE)
        {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD_DELETE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getRequestOptions());
        }

        $response = json_decode(curl_exec($ch),true);

        if (curl_error($ch))
        {
            $this->hasError = true;
            $this->error = curl_error($ch);
        }
        else
        {
            $this->setResponse($response);
        }

        $this->afterSend();

        curl_close($ch);

        return $this->response;
    }
}