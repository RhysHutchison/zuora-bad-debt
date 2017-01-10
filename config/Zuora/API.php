<?php

/**
 *    Copyright (c) 2010 Zuora, Inc.
 *
 *    Permission is hereby granted, free of charge, to any person obtaining a copy of
 *    this software and associated documentation files (the "Software"), to use copy,
 *    modify, merge, publish the Software and to distribute, and sublicense copies of
 *    the Software, provided no fee is charged for the Software.  In addition the
 *    rights specified above are conditioned upon the following:
 *
 *    The above copyright notice and this permission notice shall be included in all
 *    copies or substantial portions of the Software.
 *
 *    Zuora, Inc. or any other trademarks of Zuora, Inc.  may not be used to endorse
 *    or promote products derived from this Software without specific prior written
 *    permission from Zuora, Inc.
 *
 *    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *    FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL
 *    ZUORA, INC. BE LIABLE FOR ANY DIRECT, INDIRECT OR CONSEQUENTIAL DAMAGES
 *    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *    ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
class API
{
    /**
     * Zuora API Instance
     *
     * @var API
     */
    protected static $_instance = null;

    protected static $_config = null;

    /**
     * Soap Client
     *
     * @var SoapClient
     */
    protected $_client;

    /**
     * @var SoapHeader
     */
    protected $_header;

    protected $_endpoint = null;

    protected static $_classmap = [
        'zObject' => 'Zuora_Object',
        'ID' => 'Zuora_ID',
        'Invoice' => 'Zuora_Invoice',
        'InvoiceAdjustment' => 'Zuora_InvoiceAdjustment',
        'InvoiceItemAdjustment' => 'Zuora_InvoiceItemAdjustment',
        'InvoiceItem' => 'Zuora_InvoiceItem',
        'InvoicePayment' => 'Zuora_InvoicePayment',
        'InvoiceData' => 'Zuora_InvoiceData',
        'Payment' => 'Zuora_Payment',
        'TaxationItem' => 'Zuora_TaxationItem'
    ];

    protected function __construct($config)
    {
        self::$_config = $config;

        $this->_client = new SoapClient(self::$_config->wsdl, [
            'soap_version' => SOAP_1_1,
            'trace' => 1,
            'classmap' => self::$_classmap
        ]);
    }

    /**
     * Singleton Instance of Zuora API
     *
     * @param $config
     *
     * @return API
     */
    public static function getInstance($config)
    {
        if (null === self::$_instance || $config != self::$_config) {
            self::$_instance = new self($config);
        }

        return self::$_instance;
    }

    /**
     * Login to Zuora and create a session
     *
     * @param $username
     * @param $password
     *
     * @return boolean
     *
     * @throws Fault
     */
    public function login($username, $password)
    {
        if ($this->_endpoint) {
            $this->setLocation($this->_endpoint);
        }

        try {
            $result = $this->_client->login(['username' => $username, 'password' => $password]);
            $session = $result->result->Session;
            $serverUrl = $result->result->ServerUrl;
        } catch (SoapFault $e) {
            throw new Fault('ERROR in ' . __METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        $this->addSessionToHeader($session);

        $this->_client->__setLocation($serverUrl);

        $this->saveSessionToEnv($session, $serverUrl);

        return true;
    }

    /**
     * Save the session ID to the .env
     *
     * @param $session
     * @param $serverUrl
     */
    private function saveSessionToEnv($session, $serverUrl)
    {
        $path = '.env';

        // save the session to API_SESSION & API_SESSION_TIMESTAMP in the .env file
        if (file_exists($path)) {

            $date = new DateTime('now', new DateTimeZone('Australia/Sydney'));
            $timestamp = $date->getTimestamp();

            if (getenv('API_SESSION')) {
                // if api session exists, replace it and set new timestamp
                file_put_contents($path, str_replace(getenv('API_SESSION'), $session, file_get_contents($path)));
                file_put_contents($path, str_replace(getenv('API_SESSION_TIMESTAMP'), $timestamp, file_get_contents($path)));
            } else {
                // create a new API_SESSION & TIMESTAMP
                file_put_contents($path, "\nAPI_SESSION = $session", FILE_APPEND);
                file_put_contents($path, "\nAPI_SESSION_TIMESTAMP = $timestamp", FILE_APPEND);
            }
        }

        print "\n= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =";
        print "\n API Session created for $serverUrl";
    }

    /**
     * Save errors to .errors
     *
     * @param $errors
     */
    public function saveErrorsToFile($errors)
    {
        $path = '.errors';
        $i = 0;

        if (file_exists($path)) {
            foreach ($errors as $error) {

                $invoice = $error['InvoiceNumber'];
                $type = $error['Type'];
                $invoiceId = $error['InvoiceId'];
                $invoiceItemId = $error['InvoiceItemId'];
                $code = $error['Code'];
                $message = $error['Message'];

                file_put_contents($path, "[$i] => [\n    [Invoice] => $invoice\n    [Type] => $type\n    [InvoiceID] => $invoiceId\n    [InvoiceItemID] => $invoiceItemId\n    [Code] => $code\n    [Message] => $message\n ]\n", FILE_APPEND);
                $i++;
            }
        }
    }


    public function setQueryOptions($batchSize)
    {
        $header = new SoapHeader(
            (getenv('SANDBOX')) ? 'https://apisandbox-api.zuora.com' : 'https://api.zuora.com',
            'QueryOptions', [
                'batchSize' => $batchSize
            ]
        );
        $this->addHeader($header);
    }

    /**
     * Sets the location of the Web service to use
     *
     * @param $endpoint
     */
    public function setLocation($endpoint)
    {
        $this->_endpoint = $endpoint;
        $this->_client->__setLocation($this->_endpoint);
    }

    /**
     * Add header to the headers array
     *
     * @param $hdr
     */
    public function addHeader($hdr)
    {
        if (!$this->_header) {
            $this->_header = [];
        }

        $this->_header[] = $hdr;
    }

    /**
     * Add session to the headers array
     *
     * @param $session
     */
    public function addSessionToHeader($session)
    {
        $header = new SoapHeader(
            'http://api.zuora.com/',
            'SessionHeader', [
                'session' => $session
            ]
        );

        if (!$this->_header) {
            $this->_header = [];
        }

        $this->_header[] = $header;
    }

    /**
     * Execute create() API call.
     *
     * @param $zObjects array
     * @return object
     *
     * @throws Fault
     */
    public function create($zObjects)
    {
        if (count($zObjects) > 50) {
            throw new Fault('ERROR in ' . __METHOD__ . ': only supports up to 50 objects');
        }
        $soapVars = [];
        $type = 'Zuora_Object';

        foreach ($zObjects as $zObject) {
            if ($zObject instanceof $type) {
                $type = get_class($zObject);
                $soapVars[] = $zObject->getSoapVar();
            } else {
                throw new Fault('ERROR in ' . __METHOD__ . ': all objects must be of the same type');
            }
        }

        $create = [
            "zObjects" => $soapVars
        ];

        try {
            $result = $this->_client->__soapCall("create", $create, null, $this->_header);
        } catch (SoapFault $e) {
            throw new Fault('ERROR in ' . __METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute create() API call for a single object.
     *
     * @param $zObject Zuora_Object
     * @return object
     *
     * @throws Fault
     */
    public function createSingle($zObject)
    {
        $soapVars = [];
        $type = 'Zuora_Object';

        if ($zObject instanceof $type) {
            $type = get_class($zObject);
            $soapVars[] = $zObject->getSoapVar();
        } else {
            throw new Fault('ERROR in ' . __METHOD__ . ': all objects must be of the same type');
        }

        $create = [
            "zObjects" => $soapVars
        ];

        try {
            $result = $this->_client->__soapCall("create", $create, null, $this->_header);
        } catch (SoapFault $e) {
            throw new Fault('ERROR in ' . __METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute delete() API call.
     *
     * @return object
     *
     * @throws Fault
     */
    public function delete($type, $ids)
    {
        $delete = array(
            "type" => $type,
            "ids" => $ids,
        );
        $deleteWrapper = [
            "delete" => $delete
        ];

        try {
            $result = $this->_client->__soapCall("delete", $deleteWrapper, null, $this->_header);
        } catch (SoapFault $e) {
            throw new Fault('ERROR in ' . __METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }
        return $result;
    }

    /**
     * Execute generate() API call for a single object.
     *
     * @param $zObject Zuora_Object
     * @return object
     *
     * @throws Fault
     */
    public function generateSingle($zObject)
    {
        $soapVars = [];
        $type = 'Zuora_Object';

        if ($zObject instanceof $type) {
            $type = get_class($zObject);
            $soapVars[] = $zObject->getSoapVar();
        } else {
            throw new Fault('ERROR in ' . __METHOD__ . ': all objects must be of the same type');
        }

        $generate = [
            "zObjects" => $soapVars
        ];

        try {
            $result = $this->_client->__soapCall("generate", $generate, null, $this->_header);
        } catch (SoapFault $e) {
            throw new Fault('ERROR in ' . __METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute update() API call for a single object.
     *
     * @param $zObject Zuora_Object
     * @return object
     *
     * @throws Fault
     */
    public function updateSingle($zObject)
    {
        $soapVars = [];
        $type = 'Zuora_Object';

        if ($zObject instanceof $type) {
            $type = get_class($zObject);
            $soapVars[] = $zObject->getSoapVar();
        } else {
            throw new Fault('ERROR in ' . __METHOD__ . ': all objects must be of the same type');
        }

        $update = [
            "zObjects" => $soapVars
        ];

        try {
            $result = $this->_client->__soapCall("update", $update, null, $this->_header);
        } catch (SoapFault $e) {
            throw new Fault('ERROR in ' . __METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute update() API call.
     *
     * @param $zObjects array
     *
     * @throws Fault
     *
     * @return object
     */
    public function update($zObjects)
    {
        if (count($zObjects) > 50) {
            throw new Fault('Error in ' . __METHOD__ . ': only supports up to 50 objects');
        }

        $soapVars = [];

        $type = 'Zuora_Object';

        foreach ($zObjects as $zObject) {
            if ($zObject instanceof $type) {
                $type = get_class($zObject);
                $soapVars[] = $zObject->getSoapVar();
            } else {
                throw new Fault('Error in ' . __METHOD__ . ': all objects must be of the same type');
            }
        }

        $update = [
            'zObjects' => $soapVars
        ];

        try {
            $result = $this->_client->__soapCall('update', $update, null, $this->_header);
        } catch (SoapFault $e) {
            throw new Fault('ERROR in ' . __METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute the query() API call.
     *
     * @param $zql
     *
     * @return object
     *
     * @throws Fault
     */
    public function query($zql)
    {
        $query = ['queryString' => $zql];
        $queryWrapper = ['query' => $query];

        try {
            $result = $this->_client->__soapCall("query", $queryWrapper, null, $this->_header);
        } catch (SoapFault $e) {
            throw new Fault('ERROR in ' . __METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Query a single row for the result set
     *
     * @param $instance API
     * @param $query string
     *
     * @return object || null
     */
    public static function querySingle($instance, $query)
    {
        $result = $instance->query($query);
        $records = $result->result->records;

        if ($result->result->size == 1) {
            $items = [$records];
        } else {
            $items = $records;

            if (!$items || !count($items)) {
                return null;
            }
        }

        return $items[0];
    }

    /**
     * @param $instance API
     * @param $query string
     *
     * @return array
     */
    public function queryAll($instance, $query)
    {
        $moreCount = 0;
        $recordsArray = [];
        $totalStart = time();

        $start = time();
        $result = $instance->query($query);
        $end = time();
        $elapsed = $end - $start;

        $done = $result->result->done;
        $size = $result->result->size;
        $records = $result->result->records;

        if ($size == 0) {
        } else if ($size == 1) {
            array_push($recordsArray, $records);
        } else {

            $locator = $result->result->queryLocator;
            $newRecords = $result->result->records;
            $recordsArray = array_merge($recordsArray, $newRecords);

            while (!$done && $locator && $moreCount == 0) {
                $start = time();
                $result = $instance->queryMore($locator);
                $end = time();
                $elapsed = $end - $start;

                $done = $result->result->done;
                $size = $result->result->size;
                $locator = $result->result->queryLocator;
                $newRecords = $result->result->records;
                $count = count($newRecords);

                if ($count == 1) {
                    array_push($recordsArray, $newRecords);
                } else {
                    $recordsArray = array_merge($recordsArray, $newRecords);
                }
            }
        }

        $totalEnd = time();
        $totalElapsed = $totalEnd - $totalStart;

        echo $totalElapsed;

        return $recordsArray;
    }

    /**
     * Execute queryMore() API call.
     *
     * @param $zql
     *
     * @return mixed
     *
     * @throws Fault
     */
    public function queryMore($zql)
    {
        $query = ["queryLocator" => $zql];
        $queryWrapper = ["queryMore" => $query];

        try {
            $result = $this->_client->__soapCall('queryMore', $queryWrapper, null, $this->_header);
        } catch (SoapFault $e) {
            throw new Fault('ERROR in ' . __METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }
}
