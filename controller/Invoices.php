<?php

class Invoices
{
    public static $instance;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get Zuora Invoices from CSV File
     *
     * @param $file string
     *
     * @return array
     */
    public function getInvoiceIDsFromCSV($file)
    {
        $row = 1;
        $invoices = [];

        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                # skip headers & final row in csv file
                if ($row == 1 or $row == count(file($file, FILE_SKIP_EMPTY_LINES))) {
                    $row++;
                    continue;
                }
                $row++;

                # WHICH COLUMN ARE THE INVOICE IDs IN - SET IN ENV?!
                array_push($invoices, $data[getenv('CSVINVCOL')]);

            }
            fclose($handle);
        }

        return $invoices;
    }

    /**
     * @param $table string
     * @param $invoice object
     * @return string query
     */
    public function allFields($table, $invoice)
    {

        switch ($table) {
            case 'InvoiceItem':
                return "select  AccountingCode, 
                                AppliedToInvoiceItemId,  
                                ChargeAmount, 
                                ChargeDate, 
                                ChargeDescription, 
                                ChargeId, 
                                ChargeName, 
                                ChargeNumber, 
                                ChargeType, 
                                CreatedById, 
                                CreatedDate, 
                                Id, 
                                InvoiceId, 
                                ProcessingType, 
                                ProductDescription, 
                                ProductId, 
                                ProductName,
                                Quantity,
                                RatePlanChargeId,
                                RevRecCode,
                                RevRecStartDate,
                                RevRecTriggerCondition,
                                ServiceEndDate,
                                ServiceStartDate,
                                SKU,
                                SubscriptionId,
                                SubscriptionNumber,
                                TaxAmount,
                                TaxCode,
                                TaxExemptAmount,
                                TaxMode,
                                UnitPrice,
                                UOM
                                from InvoiceItem
                                where InvoiceId = '$invoice->Id'";
            case 'Invoice':
                return "select  AccountID,
                                AdjustmentAmount,
                                Amount,
                                AmountWithoutTax,
                                Balance,
                                Comments,
                                CreatedById,
                                CreatedDate,
                                CreditBalanceAdjustmentAmount,
                                DueDate,
                                Id,
                                IncludesOneTime,
                                IncludesRecurring,
                                IncludesUsage,
                                InvoiceDate,
                                InvoiceNumber,
                                LastEmailSentDate,
                                PaymentAmount,
                                PostedBy,
                                PostedDate,
                                RefundAmount,
                                Source,
                                SourceId,
                                Status,
                                TargetDate,
                                TaxAmount,
                                TaxExemptAmount,
                                TransferredToAccounting,
                                UpdatedDate
                                from Invoice
                                where InvoiceNumber = '$invoice->InvoiceNumber'";
            case 'InvoiceItemAdjustment':
                return "select  AccountID,
                                AccountingCode,
                                AdjustmentDate,
                                AdjustmentNumber,
                                Amount,
                                CancelledById,
                                CancelledDate,
                                Comment,
                                CreatedById,
                                CreatedDate,
                                Id,
                                InvoiceId,
                                InvoiceItemName,
                                InvoiceNumber,
                                ReasonCode,
                                ReferenceId,
                                ServiceEndDate,
                                ServiceStartDate,
                                SourceId,
                                SourceType,
                                Status,
                                TransferredToAccounting,
                                Type,
                                UpdatedDate
                                from InvoiceItemAdjustment
                                where InvoiceNumber = '$invoice->InvoiceNumber' AND InvoiceId = '$invoice->Id'";
            default:
                return null;
        }
    }

    public function balance($invoice)
    {
        return "SELECT Balance
                FROM Invoice
                WHERE InvoiceNumber = '$invoice->InvoiceNumber'";
    }

    public function invoiceItemId($invoiceItemId)
    {
        return "select  AccountingCode, 
                                AppliedToInvoiceItemId,  
                                ChargeAmount, 
                                ChargeDate, 
                                ChargeDescription, 
                                ChargeId, 
                                ChargeName, 
                                ChargeNumber, 
                                ChargeType, 
                                CreatedById, 
                                CreatedDate, 
                                Id, 
                                InvoiceId, 
                                ProcessingType, 
                                ProductDescription, 
                                ProductId, 
                                ProductName,
                                Quantity,
                                RatePlanChargeId,
                                RevRecCode,
                                RevRecStartDate,
                                RevRecTriggerCondition,
                                ServiceEndDate,
                                ServiceStartDate,
                                SKU,
                                SubscriptionId,
                                SubscriptionNumber,
                                TaxAmount,
                                TaxCode,
                                TaxExemptAmount,
                                TaxMode,
                                UnitPrice,
                                UOM
                                from InvoiceItem
                                where Id = '$invoiceItemId'";
    }
}
