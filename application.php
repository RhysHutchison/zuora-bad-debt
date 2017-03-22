<?php

use Dotenv\Dotenv;

class application
{
    /**
     * @var API $instance
     */
    protected $_instance;
    protected $errors = [];

    public function __construct()
    {
        $dotenv = new Dotenv(__DIR__);
        $dotenv->load();

        $_config = new stdClass();

        $this->colourGreen = "\033[1;32m";
        $this->colourRed = "\033[1;31m";
        $this->colourBlack = "\033[0m \n";

        $_config->wsdl = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . getenv('WSDL');

        $this->_instance = API::getInstance($_config);

        if (getenv('SANDBOX_ENVIRONMENT') == 'FALSE') {
            $endpoint = getenv('PRODUCTION_ENDPOINT');
        } else {
            $endpoint = getenv('SANDBOX_ENDPOINT');
        }

        $this->_instance->setLocation($endpoint);

        $this->getSession();

        $this->getInvoices();

        // dump errors after completion
        if (!empty($this->errors)) {
            $this->_instance->saveErrorsToFile($this->errors);
            print_r($this->errors);
        }
    }

    /**
     * Create new API Session
     *
     * @param $valid bool
     * @return bool
     */
    private function getSession($valid = null)
    {
        $session = getenv('API_SESSION');

        if (getenv('SANDBOX_ENVIRONMENT') == 'FALSE') {
            $this->verifyEnvironment();
            $endpoint = getenv('PRODUCTION_ENDPOINT');
        } else {
            $endpoint = getenv('SANDBOX_ENDPOINT');
        }

        if ($session) {
            // check if session is valid
            // unix timestamp of when session was created
            $sessionStart = new DateTime('@' . getenv('API_SESSION_TIMESTAMP'), new DateTimeZone('Australia/Sydney'));
            $sessionTimestamp = $sessionStart->getTimestamp();

            // session id is valid for 8hrs (unix 28800)
            $sessionValidUntil = $sessionTimestamp + 28800;

            $dt = new DateTime('now', new DateTimeZone('Australia/Sydney'));
            $nowTimeStamp = $dt->getTimestamp();

            $valid = ($nowTimeStamp < $sessionValidUntil) ? true : false;
        }

        if ($valid) {
            // if session is valid, continue with existing session id
            $this->_instance->addSessionToHeader($session);

            echo "\n= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =";
            echo "\n API Session already exists for $endpoint";

        } else {
            if (!is_null($valid)) {
                echo "\n= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =";
                echo "\n API Session Expired, creating a new one for $endpoint";
            }

            // if api session doesn't exist or is invalid, create new session

            $password = (!getenv('SANDBOX_ENVIRONMENT') || getenv('SANDBOX_ENVIRONMENT') == 'TRUE') ?
                getenv('SANDBOX_PASSWORD') : getenv('PRODUCTION_PASSWORD');
            $this->_instance->login(getenv('USERNAME'), $password);
        }

        return true;
    }

    /**
     * Get Zuora Invoices from CSVFile in .env
     *
     */
    private function getInvoices()
    {
        $invoices = Invoices::getInvoiceIDsFromCSV(getenv('CSVFILE'));

        foreach ($invoices as $row => $invoiceId) {
            $invoice = (object)null;
            $invoice->InvoiceNumber = $invoiceId;

            $this->handleInvoice($this->_instance->query(Invoices::allFields('Invoice', $invoice)), $invoiceId);
        }
    }

    private function handleInvoice($result, $invoiceNumber)
    {
        $invoice = $result->result->records;
        $size = $result->result->size;

        if ($size > 0) {
            if ($invoice->Balance > 0) {
                // if query was successful and returned the $invoice object
                $this->handleInvoiceItems($invoice);
            } else {
                echo "\n= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =\n";
                echo "#$invoice->InvoiceNumber does not have an outstanding balance.\n";
            }
        } else {
            // notify console if $invoice can not be found
            echo "Invoice #$invoiceNumber can not be found. \n";
        }
    }

    private function handleInvoiceItems($invoice)
    {
        // $invoice status must be posted to create adjustments
        if ($invoice->Status == 'Posted') {
            // get all $invoiceItems within this $invoice in Zuora
            $invoiceItemResults = $this->_instance->query(Invoices::allFields('InvoiceItem', $invoice));

            // count & array of $invoiceItems returned from the query above
            $invoiceItemSize = $invoiceItemResults->result->size;
            $invoiceItemRecords = $invoiceItemResults->result->records;

            // get all existing $invoiceAdjustments for aforementioned $invoice
            $itemAdjustmentResults = $this->_instance->query(Invoices::allFields('InvoiceItemAdjustment', $invoice));

            $itemAdjustmentSize = $itemAdjustmentResults->result->size;
            $itemAdjustmentRecords = $itemAdjustmentResults->result->records;

            echo "\n= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =";
            echo "\n Invoice ID = $invoice->Id";
            echo "\n AccountId = $invoice->AccountId";
            echo "\n #$invoice->InvoiceNumber has $invoiceItemSize invoice items with a total outstanding balance of $invoice->Balance.";
            echo "\n #$invoice->InvoiceNumber has $itemAdjustmentSize existing item adjustments (InvoiceItem + GST = 2 Adjustments).";
            echo "\n= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =\n";

            if ($invoiceItemRecords) {
                // sort the array by ASC chargeAmounts for invoiceItems
                usort($invoiceItemRecords, function ($a, $b) {
                    return strcmp($a->ChargeAmount, $b->ChargeAmount);
                });

                foreach ($invoiceItemRecords as $invoiceItem) {

                    $line = "Invoice Item ID: $invoiceItem->Id    |   Name: $invoiceItem->ChargeName    |   Amount: $invoiceItem->ChargeAmount      |      Tax: $invoiceItem->TaxAmount";
                    $match = null;

                    //if there is itemAdjustments
                    if ($itemAdjustmentRecords) {

                        foreach ($itemAdjustmentRecords as $itemAdjustment) {
                            // if the itemAdjustment -> invoice id matches the invoiceItem -> invoiceId
                            if ($itemAdjustment->InvoiceId == $invoiceItem->InvoiceId) {
                                // Assert that the itemAdjustment matches either the charge or the tax
                                if ($itemAdjustment->Amount == $invoiceItem->TaxAmount || $itemAdjustment->Amount == $invoiceItem->ChargeAmount AND $itemAdjustment->AccountingCode == $invoiceItem->TaxCode || $itemAdjustment->AccountingCode == $invoiceItem->AccountingCode) {
                                    // set the line adjustment as being credited and show as green in the console
                                    $match = true;
                                }
                            }
                        }
                    }

                    if ($match) {
                        echo $this->colourGreen . $line . $this->colourBlack;
                    } else {
                        // get the updated remaining balance
                        $result = $this->_instance->query(Invoices::balance($invoice));
                        $remainingBalance = $result->result->records->Balance;

                        if ($remainingBalance > 0) {
                            if ($invoiceItem->ChargeAmount > 0 || $invoiceItem->TaxAmount > 0) {
                                echo $this->colourRed . $line . $this->colourBlack;

                                // get taxationItem for the invoiceItem that has tax applied to it
                                if ($invoiceItem->TaxAmount > 0) {

                                    // get the updated remaining balance
                                    $result = $this->_instance->query(Invoices::balance($invoice));
                                    $remainingBalance = $result->result->records->Balance;

                                    if ($remainingBalance > 0) {
                                        $result = $this->_instance->query(TaxationItem::getTaxationItemByInvoiceItemId($invoiceItem->Id));
                                        $taxationItemID = $result->result->records->Id;


                                        if ($taxationItemID) {
                                            // create new tax adjustment using $taxationItem->ID
                                            $result = $this->_instance->createSingle(Adjustments::makeTaxAdjustment($invoice, $invoiceItem, $taxationItemID));

                                            if ($result->result->Success) {
                                                echo "$this->colourGreen" . "Tax Adjustment Created For Invoice Item ID #$invoiceItem->Id. $this->colourBlack";
                                            } else {
                                                // if creation of taxAdjustment failed
                                                $taxErrors = $result->result->Errors;

                                                // save the $taxError to $errors array
                                                array_push($this->errors, [
                                                    'Type' => 'TaxAdjustmentError',
                                                    'InvoiceId' => $invoice->Id,
                                                    'InvoiceNumber' => $invoice->InvoiceNumber,
                                                    'InvoiceItemId' => $invoiceItem->Id,
                                                    'Code' => $taxErrors->Code,
                                                    'Message' => $taxErrors->Message,
                                                ]);

                                                echo "$this->colourRed" . "Tax Adjustment can not be Created For Invoice Item ID #$invoiceItem->Id. See Error Log.$this->colourBlack";
                                            }

                                        } else {
                                            // if taxationItem can not be found, throw error to array
                                            array_push($this->errors, [
                                                'Type' => 'TaxAdjustmentError',
                                                'InvoiceId' => $invoice->Id,
                                                'InvoiceNumber' => $invoice->InvoiceNumber,
                                                'InvoiceItemId' => $invoiceItem->Id,
                                                'Code' => 'TaxationItem Not Found',
                                                'Message' => 'Unable to retrieve required Taxation Item ID required to adjust Invoice Item',
                                            ]);
                                        }
                                    }
                                }

                                // if chargeAmount exists create charge adjustment for the $invoice
                                if ($invoiceItem->ChargeAmount > 0) {

                                    // get the updated remaining balance
                                    $result = $this->_instance->query(Invoices::balance($invoice));
                                    $remainingBalance = $result->result->records->Balance;

                                    if ($remainingBalance > 0) {
                                        $result = $this->_instance->createSingle(Adjustments::makeChargeAdjustment($invoice, $invoiceItem, $remainingBalance));

                                        // if creation of chargeAdjustment was successful
                                        if ($result->result->Success) {
                                            echo "$this->colourGreen" . "Charge Adjustment Created For Invoice Item ID #$invoiceItem->Id. $this->colourBlack";
                                        } else {
                                            // if creation of chargeAdjustment failed
                                            $chargeErrors = $result->result->Errors;

                                            // save the $chargeError to $errors array
                                            array_push($this->errors, [
                                                'Type' => 'ChargeAdjustmentError',
                                                'InvoiceId' => $invoice->Id,
                                                'InvoiceNumber' => $invoice->InvoiceNumber,
                                                'InvoiceItemId' => $invoiceItem->Id,
                                                'Code' => $chargeErrors->Code,
                                                'Message' => $chargeErrors->Message,
                                            ]);

                                            echo "$this->colourRed" . "Charge Adjustment can not be created For Invoice Item ID #$invoiceItem->Id. See Error Log.$this->colourBlack";
                                        }
                                    }
                                }
                            }
                        } else {
                            return false;
                        }
                    }
                }
                echo "= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =\n";
            } else {
                echo "\n= = = = = = = = = = = NO INVOICE ITEMS FOUND = = = = = = = = = = = = = = = = = = = =\n";
                echo "#$invoice->InvoiceNumber does not require adjusting.\n";
            }
        } else if ($invoice->Status == 'Cancelled') {
            echo "= = = = = = = = = = = INVOICE CANCELLED = = = = = = = = = = = = = = = = = = = =\n";
            echo "#$invoice->InvoiceNumber has previously been cancelled.\n";
        } else {
            echo "\n= = = = = = = = = = = INVOICE NOT POSTED = = = = = = = = = = = = = = = = = = = =\n";
            echo "#$invoice->InvoiceNumber is required to be posted before adjustments can be made.\n";
        }
    }

    private function verifyEnvironment()
    {

        echo "\n= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =";
        echo "\nZuora Production Environment is in use. Press CTRL-C to abort, Enter to continue.";
        echo "\n= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =";

        $fp = fopen("php://stdin", "r");
        fgets($fp);
    }
}
