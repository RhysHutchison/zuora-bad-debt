<?php

class TaxationItem
{
    /**
     * @param $crud String
     * @param $id String
     * @return string query
     */
    public static function crud($crud, $id)
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

    public static function getTaxationItemByInvoiceItemId($invoiceItemId) {
        return "SELECT AccountingCode,CreatedById,CreatedDate,ExemptAmount,InvoiceId,InvoiceItemId,Jurisdiction,Id,Name,TaxAmount,TaxCode,TaxDate,TaxRate,TaxRateDescription, TaxRateType
                        FROM TaxationItem
                        WHERE InvoiceItemId = '$invoiceItemId'";
    }

    public static function makeTaxationItem($invoiceItem)
    {
        $dateToday = new DateTime('now', new DateTimeZone('Australia/Sydney'));
        $dateToday = $dateToday->format('Y-m-d');

        $taxationItem = new Zuora_TaxationItem();
        $taxationItem->AccountingCode = $invoiceItem->TaxCode;
        $taxationItem->InvoiceItemId = $invoiceItem->Id;
        $taxationItem->Jurisdiction = $invoiceItem->Id;
        $taxationItem->Name = $invoiceItem->TaxCode;
        $taxationItem->TaxAmount = $invoiceItem->TaxAmount;
        $taxationItem->TaxDate = $dateToday;
        $taxationItem->TaxRate = 0.1;
        $taxationItem->TaxRateType = 'Percentage';

        return $taxationItem;
    }
}