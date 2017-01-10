<?php

class Payments
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
                return "SELECT AccountId,Amount,AuthTransactionId,CancelledOn,Comment,Id, PaymentNumber
                        FROM Payment
                        WHERE AccountId = '$id'";
            case 'delete':
                return "DELETE FROM Payment
                        WHERE InvoiceId = '$id'";
            default:
                return null;
        }
    }
}
