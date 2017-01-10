<?php

class Adjustments
{
    public static function makeChargeAdjustment($invoice, $invoiceItem, $remainingBalance)
    {
        $dateToday = new DateTime('now', new DateTimeZone('Australia/Sydney'));
        $dateToday = $dateToday->format('Y-m-d');

        $chargeAdjustment = new Zuora_InvoiceItemAdjustment();
        $chargeAdjustment->AccountingCode = $invoiceItem->AccountingCode;
        $chargeAdjustment->AdjustmentDate = $dateToday;
        $chargeAdjustment->Amount = ($remainingBalance < $invoiceItem->ChargeAmount) ? $remainingBalance : $invoiceItem->ChargeAmount;
        $chargeAdjustment->Comment = "Automated Debt Write Off - $dateToday";
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

    public static function makeTaxAdjustment($invoice, $invoiceItem, $taxationItemID)
    {
        $dateToday = new DateTime('now', new DateTimeZone('Australia/Sydney'));
        $dateToday = $dateToday->format('Y-m-d');

        $taxAdjustment = new Zuora_InvoiceItemAdjustment();
        $taxAdjustment->AccountingCode = $invoiceItem->TaxCode;
        $taxAdjustment->AdjustmentDate = $dateToday;
        $taxAdjustment->Amount = $invoiceItem->TaxAmount;
        $taxAdjustment->Comment = "Automated Debt Write Off - $dateToday";
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
