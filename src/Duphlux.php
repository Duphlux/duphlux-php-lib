<?php
namespace duphlux;

class Duphlux extends ApiRequest
{
    /** Operation type to initialize transaction    */
    CONST OP_INITIALIZE_NUMBER_VERIFICATION = 1;

    /** Operation type to verify transaction    */
    CONST OP_NUMBER_VERIFICATION_STATUS = 2;

    /** Status string for a succesfull phone number verification */
    CONST VERIFICATION_STATUS_VERIFIED = "verified";

    /** Status string for a failed phone number verification */
    CONST VERIFICATION_STATUS_FAILED = "failed";

    /** Status string for a pending phone number verification */
    CONST VERIFICATION_STATUS_PENDING = "pending";

    /** The key used in storing the verification url in the response payload data */
    CONST DATA_NUMBER_VERIFICATION_URL_KEY = 'verification_url';

    /** The key used in storing the expiry timestamp in the response payload data */
    CONST DATA_VERIFICATION_EXPIRY_DATE_KEY = 'expires_at';

    /** The key used in storing the transaction reference in the response payload data */
    CONST DATA_TRANSACTION_REFERENCE_KEY = 'transaction_reference';

    /** The key used in storing the verification status value in the verify status method response payload data */
    CONST DATA_VERIFICATION_STATUS_KEY = 'verification_status';


    /**
     * Test environmet
     */
    CONST ENV_TEST = 'TEST';

    /**
     * Live environment
     */
    CONST ENV_LIVE = 'LIVE';

    /** @var  array
     *  Array containing the response payload information from duphlux
     */
    private $payload;

    /** @var string sets the environment for the request to LIVE when test is false */
    private $environment = self::ENV_LIVE;

    /** @var boolean set to false to allow unsecured connection to the paystack api */
    public $verify_peer = false;

    /** @var  string live access token  used in live environment */
    public static $live_access_token;

    /** @var  string test access token used in test environment */
    public static $test_access_token;

    //private $phone_number, $timeout, $transaction_reference, $redirect_url;

    /** @var  string stores the access token being used for the current operation */
    private $api_token;

    /** @var  string Duphlux API default base url*/
    private $defaultBaseUrl = "https://duphlux.com/webservice/authe";

    private static $reference;

    private $header,$authHeader;

    /**
     * Duphlux constructor.
     * @param string $access_token
     * @param string $environment
     */
    public function __construct($access_token,$environment = self::ENV_LIVE)
    {
        $this->setBaseUrl();
        $this->setEnvironment($environment);
        $this->setApiToken($access_token);
    }

    protected $config = [
        self::OP_INITIALIZE_NUMBER_VERIFICATION => [
            'endpoint' => '/verify.json',
        ],
        self::OP_NUMBER_VERIFICATION_STATUS => [
            'endpoint' => '/status.json',
        ],
    ];

    /**
     * @var array contains the required parameters for each duphlux operation
     */
    private $requiredOptions = [
        self::OP_INITIALIZE_NUMBER_VERIFICATION => ['phone_number','transaction_reference','redirect_url'],
        self::OP_NUMBER_VERIFICATION_STATUS => ['transaction_reference']
    ];

    /**
     * Validates the options to make sure that all required parameters are present
     * @param $options the options parameters passed for the operation
     * @param $operation the operation to be performed
     * @throws \InvalidArgumentException
     */
    private function validateOptions($options,$operation)
    {
        $options_keys = is_array($options)?array_keys($options): (array) $options;

        foreach ($this->requiredOptions[$operation] as $option)
        {
            if (!in_array($option,$options_keys) || empty(trim($options[$option])))
            {
                throw new \InvalidArgumentException("$option is Required for this operation");
            }
        }
    }

    /**
     * executes the beforeSend callback before a request is made
     */
    protected function beforeSend($parameter = null)
    {
        parent::beforeSend($this);
    }

    /**
     * executes the afterSend callback after a request is made
     */
    protected function afterSend($parameter = null)
    {
        parent::afterSend($this);
    }

    /**
     * Sets the requeest environment (live or test)
     * @param $environment the request environment
     */
    private function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Gets the request environment (live or test)
     * @return string the current environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Gets the request token
     * @return string the request token
     */
    public function getApiToken()
    {
        return $this->api_token;
    }

    /**
     * @param null $token
     */
    public function setApiToken($token = null)
    {
        if (!empty($token))
            $this->api_token = $token;
        elseif (strtolower($this->environment) == strtolower(self::ENV_LIVE))
            $this->api_token = self::$live_access_token;
        else
            $this->api_token = self::$test_access_token;

        $this->setHeader();
    }

    /**
     * Sets the request header
     * @param array $headers the request headers
     */
    public function setHeader($headers = array())
    {
        $this->authHeader = array('token: '.$this->getApiToken(),'Content-type: application/json','Cache-Control: no-cache');
        $this->header = array_merge($this->authHeader,$headers);
    }

    /**
     * Get the request headers
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Set duphlux base url
     * @param null $url duphlux base url
     */
    private function setBaseUrl($url = null)
    {
        if ($url == null)
        {
            if (!isset($this->baseUrl) || empty($this->baseUrl))
                $this->baseUrl = $this->defaultBaseUrl;
        }
        else
        {
            $this->baseUrl = $url;
        }
    }

    /**
     * Get duphlux base url
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Sets the api response
     * @param $response the response data gotten from a request
     */
    protected function setResponse($response)
    {
        parent::setResponse($response);

        $this->payload = $this->response['PayLoad'];

        $this->setStatus($this->payload['status']);
        $this->setError($this->payload['errors']);
        $this->setData($this->payload['data']);

        if (!$this->status)
        {
            $this->hasError = true;
        }
    }

    /**
     * @param $options array the authentication request parameters
     * Method can be chained with redirect to allow redirect to the duphlux response verification url
     * @return $this
     */
    public function authenticate($options)
    {
        $this->setOperation(self::OP_INITIALIZE_NUMBER_VERIFICATION);
        $this->validateOptions($options,$this->getOperation());

        $this->setRequestOptions($options);
        $this->sendRequest($this->getOperation(),self::METHOD_POST);

        return $this;
    }

    /**
     * Redirects to duphlux verification url after an authenticate request is made
     * @throws \Exception
     */
    public function redirect()
    {
        $this->checkOperation(self::OP_INITIALIZE_NUMBER_VERIFICATION);
        $verification_url = $this->data[self::DATA_NUMBER_VERIFICATION_URL_KEY];

        if ($verification_url)
        {
            header("Location: ".$this->data[self::DATA_NUMBER_VERIFICATION_URL_KEY]);
            exit();
        }
    }

    /**
     * verifies the status of a duphlux authentication request whether it is verified, pending or failed
     * Method can be chained with isVerified, isPending or isFailed
     * @param $reference the authentication request reference that is to be verified
     * @return $this
     */
    public function checkStatus($reference)
    {
        $this->setOperation(self::OP_NUMBER_VERIFICATION_STATUS);
        $options['transaction_reference'] = $reference;

        $this->validateOptions($options,$this->getOperation());
        $this->setRequestOptions($options);
        $this->sendRequest($this->getOperation(),self::METHOD_POST);

        return $this;
    }

    /**
     * checks if a previous verification request is verified
     * @return bool
     * @throws \Exception
     */
    public function isVerified()
    {
        $this->checkOperation(self::OP_NUMBER_VERIFICATION_STATUS);
        return ($this->getData(self::DATA_VERIFICATION_STATUS_KEY) == self::VERIFICATION_STATUS_VERIFIED);
    }

    /**
     * checks if a previous verification request is pending
     * @return bool
     * @throws \Exception
     */
    public function isPending()
    {
        $this->checkOperation(self::OP_NUMBER_VERIFICATION_STATUS);
        return ($this->getData(self::DATA_VERIFICATION_STATUS_KEY) == self::VERIFICATION_STATUS_PENDING);
    }

    /**
     * checks if a previous verification request failed
     * @return bool
     * @throws \Exception
     */
    public function isFailed()
    {
        $this->checkOperation(self::OP_NUMBER_VERIFICATION_STATUS);
        return ($this->getData(self::DATA_VERIFICATION_STATUS_KEY) == self::VERIFICATION_STATUS_FAILED);
    }

    /**
     * Checks to confirm the current operation is allowed or not
     * @param $operation
     * @param null|string $message
     * @throws \Exception
     */
    private function checkOperation($operation,$message = null)
    {
        if ($this->getOperation() != $operation)
        {
            if (!$message)
                $message = "Method cannot be used with the current operation";
            throw new \Exception($message);
        }
    }

    /**
     * @param int $length the total character length to generate
     * @return string the generated random characters
     */
    public static function generateRef($length = 10)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++)
        {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}