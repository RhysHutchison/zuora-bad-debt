<?php

class TaxationItem
{

    public static $instance;
    public $dateToday;

    public function __construct()
    {
        $date = getenv('WRITE_OFF_DATE') ? getenv('WRITE_OFF_DATE') : 'now';
        $dateToday = new DateTime($date, new DateTimeZone('Australia/Sydney'));
        $this->dateToday = $dateToday->format('Y-m-d');
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $crud String
     * @param $id String
     * @return string query
     */
    public function crud($crud, $id)
    {
        switch ($crud) {
            case 'read':
                return "SELECT AccountingCode,CreatedById,CreatedDate,ExemptAmount,InvoiceId,InvoiceItemId,Jurisdiction,Id,Name,TaxAmount,TaxCode,TaxDate,TaxRate,TaxRateDescription, TaxRateType
                        FROM TaxationItem
                        WHERE InvoiceId = '$id'";
            case 'delete':
                return "DELETE FROM TaxationItem
                        WHERE InvoiceId = '$id'";
            default:
                return null;
        }
    }

    public function getTaxationItemByInvoiceItemId($invoiceItemId)
    {
        return "SELECT AccountingCode,CreatedById,CreatedDate,ExemptAmount,InvoiceId,InvoiceItemId,Jurisdiction,Id,Name,TaxAmount,TaxCode,TaxDate,TaxRate,TaxRateDescription, TaxRateType
                        FROM TaxationItem
                        WHERE InvoiceItemId = '$invoiceItemId'";
    }

    public function makeTaxationItem($invoiceItem)
    {
        $taxationItem = new Zuora_TaxationItem();
        $taxationItem->AccountingCode = $invoiceItem->TaxCode;
        $taxationItem->InvoiceItemId = $invoiceItem->Id;
        $taxationItem->Jurisdiction = $invoiceItem->Id;
        $taxationItem->Name = $invoiceItem->TaxCode;
        $taxationItem->TaxAmount = $invoiceItem->TaxAmount;
        $taxationItem->TaxDate = $this->dateToday;
        $taxationItem->TaxRate = 0.1;
        $taxationItem->TaxRateType = 'Percentage';

        return $taxationItem;
    }
}