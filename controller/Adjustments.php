<?php

class Adjustments
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

    public function makeChargeAdjustment($invoice, $invoiceItem, $remainingBalance)
    {
        $chargeAdjustment = new Zuora_InvoiceItemAdjustment();
        $chargeAdjustment->AccountingCode = $invoiceItem->AccountingCode;
        $chargeAdjustment->AdjustmentDate = $this->dateToday;
        $chargeAdjustment->Amount = ($remainingBalance < $invoiceItem->ChargeAmount) ? $remainingBalance : $invoiceItem->ChargeAmount;
        $chargeAdjustment->Comment = "Automated Debt Write Off - $this->dateToday";
        $chargeAdjustment->InvoiceId = $invoiceItem->InvoiceId;
        $chargeAdjustment->InvoiceNumber = $invoice->InvoiceNumber;
        $chargeAdjustment->ReasonCode = 'Write-off';
        $chargeAdjustment->ServiceEndDate = $invoiceItem->ServiceEndDate;
        $chargeAdjustment->ServiceStartDate = $invoiceItem->ServiceStartDate;
        $chargeAdjustment->SourceId = $invoiceItem->Id;
        $chargeAdjustment->SourceType = 'InvoiceDetail';
        $chargeAdjustment->Type = 'Credit';

        return $chargeAdjustment;
    }

    public function makeTaxAdjustment($invoice, $invoiceItem, $taxationItemID)
    {
        $taxAdjustment = new Zuora_InvoiceItemAdjustment();
        $taxAdjustment->AccountingCode = $invoiceItem->TaxCode;
        $taxAdjustment->AdjustmentDate = $this->dateToday;
        $taxAdjustment->Amount = $invoiceItem->TaxAmount;
        $taxAdjustment->Comment = "Automated Debt Write Off - $this->dateToday";
        $taxAdjustment->InvoiceId = $invoiceItem->InvoiceId;
        $taxAdjustment->InvoiceNumber = $invoice->InvoiceNumber;
        $taxAdjustment->ReasonCode = 'Write-off';
        $taxAdjustment->ServiceEndDate = $invoiceItem->ServiceEndDate;
        $taxAdjustment->ServiceStartDate = $invoiceItem->ServiceStartDate;
        $taxAdjustment->SourceId = $taxationItemID;
        $taxAdjustment->SourceType = 'Tax';
        $taxAdjustment->Type = 'Credit';

        return $taxAdjustment;
    }
}
