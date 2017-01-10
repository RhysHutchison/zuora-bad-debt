<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitfe73335f2c1bee0434f194c58f267d64
{
    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'Dotenv\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Dotenv\\' => 
        array (
            0 => __DIR__ . '/..' . '/vlucas/phpdotenv/src',
        ),
    );

    public static $classMap = array (
        'API' => __DIR__ . '/../..' . '/config/Zuora/API.php',
        'Adjustments' => __DIR__ . '/../..' . '/controller/Adjustments.php',
        'Fault' => __DIR__ . '/../..' . '/config/Zuora/Fault.php',
        'Invoices' => __DIR__ . '/../..' . '/controller/Invoices.php',
        'Payments' => __DIR__ . '/../..' . '/controller/Payments.php',
        'TaxationItem' => __DIR__ . '/../..' . '/controller/TaxationItem.php',
        'Zuora_Account' => __DIR__ . '/../..' . '/config/Zuora/Account.php',
        'Zuora_Amendment' => __DIR__ . '/../..' . '/config/Zuora/Amendment.php',
        'Zuora_Contact' => __DIR__ . '/../..' . '/config/Zuora/Contact.php',
        'Zuora_Error' => __DIR__ . '/../..' . '/config/Zuora/Error.php',
        'Zuora_Invoice' => __DIR__ . '/../..' . '/config/Zuora/Invoice.php',
        'Zuora_InvoiceAdjustment' => __DIR__ . '/../..' . '/config/Zuora/InvoiceAdjustment.php',
        'Zuora_InvoiceItem' => __DIR__ . '/../..' . '/config/Zuora/InvoiceItem.php',
        'Zuora_InvoiceItemAdjustment' => __DIR__ . '/../..' . '/config/Zuora/InvoiceItemAdjustment.php',
        'Zuora_InvoicePayment' => __DIR__ . '/../..' . '/config/Zuora/InvoicePayment.php',
        'Zuora_Object' => __DIR__ . '/../..' . '/config/Zuora/Object.php',
        'Zuora_Payment' => __DIR__ . '/../..' . '/config/Zuora/Payment.php',
        'Zuora_PaymentMethod' => __DIR__ . '/../..' . '/config/Zuora/PaymentMethod.php',
        'Zuora_Product' => __DIR__ . '/../..' . '/config/Zuora/Product.php',
        'Zuora_ProductRatePlan' => __DIR__ . '/../..' . '/config/Zuora/ProductRatePlan.php',
        'Zuora_ProductRatePlanCharge' => __DIR__ . '/../..' . '/config/Zuora/ProductRatePlanCharge.php',
        'Zuora_ProductRatePlanChargeTier' => __DIR__ . '/../..' . '/config/Zuora/ProductRatePlanChargeTier.php',
        'Zuora_RatePlan' => __DIR__ . '/../..' . '/config/Zuora/RatePlan.php',
        'Zuora_RatePlanCharge' => __DIR__ . '/../..' . '/config/Zuora/RatePlanCharge.php',
        'Zuora_RatePlanChargeData' => __DIR__ . '/../..' . '/config/Zuora/RatePlanChargeData.php',
        'Zuora_RatePlanChargeTier' => __DIR__ . '/../..' . '/config/Zuora/RatePlanChargeTier.php',
        'Zuora_RatePlanData' => __DIR__ . '/../..' . '/config/Zuora/RatePlanData.php',
        'Zuora_SubscribeOptions' => __DIR__ . '/../..' . '/config/Zuora/SubscribeOptions.php',
        'Zuora_SubscribeRequest' => __DIR__ . '/../..' . '/config/Zuora/SubscribeRequest.php',
        'Zuora_SubscribeResult' => __DIR__ . '/../..' . '/config/Zuora/SubscribeResult.php',
        'Zuora_Subscription' => __DIR__ . '/../..' . '/config/Zuora/Subscription.php',
        'Zuora_SubscriptionData' => __DIR__ . '/../..' . '/config/Zuora/SubscriptionData.php',
        'Zuora_TaxationItem' => __DIR__ . '/../..' . '/config/Zuora/TaxationItem.php',
        'Zuora_Usage' => __DIR__ . '/../..' . '/config/Zuora/Usage.php',
        'application' => __DIR__ . '/../..' . '/application.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfe73335f2c1bee0434f194c58f267d64::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfe73335f2c1bee0434f194c58f267d64::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitfe73335f2c1bee0434f194c58f267d64::$classMap;

        }, null, ClassLoader::class);
    }
}
