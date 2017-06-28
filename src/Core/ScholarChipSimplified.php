<?php

namespace Itacs\ScholarChip\Core;

use Itacs\ScholarChip\Exceptions\ScholarChipException;
use Itacs\ScholarChip\ScholarChip;
use SoapClient;



/**
 * Creates a transaction to begin the process of sending a user to the
 * ScholarChip web interface.
 * The following global defines are assumed to exist:
 
 * SCHOLAR_CHIP_WSDL_URL - The service URL
 * SCHOLAR_CHIP_USER_NAME - Your departmental ScholarChip user
 * SCHOLAR_CHIP_PASSWORD - departmental password for the account
 * GL_ACCOUNT_NUMBER - General Ledger account money should be
 * deposited in
 * SCHOLAR_CHIP_CALLBACK_URL - URL which users should be redirec to
 * upon completion of the payment process at the ScholarChip
 * storefront.

 * Steps for use:
 * 1) $transaction = Transaction($order_id);
 * 2) $transaction->SetStudent($ruid);
 * (optional, if no student is set, the order will progress as normal.)
 * 3) $transaction->AddLineItem($amount, $description); (Can be called
 * multiple times. $amount is a dollar value.    $description is
 * allowed to be up to  64 characters explaining the purchase.)
 * 4) Redirect the user to the url: $transaction->GetOrderUrl();
 * 
 * This library will throw a ScholarchipException when any step fails
 * and should contain an explanatory string, along with teh
 * ScholarChip error message.
 * Once an exception is thrown the transaction should be aborted.  Do
 * not attempt to recover the existing transaction.
 *
 * @throws ScholarchipException 
 */
// used to be ScholarchipTransaction
class ScholarChipSimplified implements ScholarChip {

    private $scholarchip;

    public function __construct(ScholarChipGateway $scholarchip)
    {
        $this->scholarchip = $scholarchip;
    }

    public function createOrder($orderId, $amount, $description)
    {
        $seqOrderId = $this->scholarchip
                            ->createOrder($orderId, 
                                          config('app.url') . '/payment');

        $this->scholarchip->addItem($orderId, $amount, $description);
        return $seqOrderId;
    }

    public function redirectToPayment($orderId)
    {
        // set session
        // 
        $redirectUrl = $this->scholarchip->getOrderUrl($orderId);
        header('Location: ' . $redirectUrl);
        die();
    }
}
