<?php

namespace Itacs\ScholarChip;

use SoapClient;
use Itacs\ScholarChip\Exceptions\OrderDoesNotExistException;
use Itacs\ScholarChip\Exceptions\OrderExistsException;
use Itacs\ScholarChip\Exceptions\ScholarChipException;

class ScholarChip
{
    /**
     * @var SoapClient
     */
    private $soapClient;

    /**
     * Configuration array containing the following fields:
     * -wsdl_url => ScholarChip endpoint URL
     * -user     => ScholarChip username
     * -password => ScholarChip password
     * -gl       => ScholarChip general ledger account (aka sEAGL)
     * 
     * @var array
     */
    private $config;

    /**
     * Token obtained from ScholarChip API. This value will
     * hold it so that an additional GetToken call to the
     * API can be avoided in subsequent API calls.
     * @var [type]
     */
    private $token;

    /**
     * Flag to output debug information.
     * @var boolean
     */
    private $debug = false;

    /**
     * API result value indicating a valid token.
     */
    const VALID_TOKEN = '1';

    /**
     * API result value indicating a successful line item add.
     */
    const ITEM_ADD_SUCCESSFUL = '1';

    /**
     * API result value indicating a successful callback set.
     */
    const CALLBACK_SET_SUCCESSFUL = '1';


    /**
     * Order statuses as defined by ScholarChip API documentation.
     */
    const CANCELLED = "Cancelled";
    const EXPIRED = "Expired";
    const INITIALIZED = "Initialized";
    const INVALID = "Invalid";
    const PENDING = "Pending";
    const PROCESSED = "Processed";
    const FAILED = "Failed";


    public function __construct(SoapClient $soapClient, array $config)
    {
        $this->soapClient = $soapClient;
        $this->config = $config;
    }

    /**
     * Creates new order in ScholarChip system.
     *
     * You must pass an order_id which is unique across your ScholarChip 
     * login credentials. If the same login information is used across 
     * several apps it is recommended you include the appname in the 
     * order_id (e.g. 'appname_order_id'. Another option is to prepend
     * a timestamp to order_id.
     * 
     * @param  mixed $orderId
     * @param  string $callbackUrl URL to which ScholarChip should redirect
     *                             to after user completes payment.
     *                             
     * @return string              SeqidOrder that identifies order in the
     *                             ScholarChip system is returned, it may
     *                             be beneficial for client application to
     *                             store this value on their end to have an
     *                             additional piece of information to match
     *                             client data to ScholarChip order.
     *
     * @throws ScholarChipException thrown upon API error.
     * @throws OrderDoesNotExistException
     */
    public function createOrder($orderId, $callbackUrl)
    {
        if(strlen($orderId) > 32) {
            throw new ScholarChipException('The order id cannot exceed 32 characters!');
        }

        $this->refreshToken();
        $orderExists = $this->getOrderSeqId($orderId);
        if(!empty($orderExists)) {
            throw new OrderExistsException(sprintf('\'%s\' => \'%s\'', 
                                            $orderId, $orderExists));
        }
        $orderSeqId = $this->soapClient->CreateOrder(array(
                                    'sToken' => $this->token,
                                    'sOrderID' => $orderId,
                                ))->CreateOrderResult;
        if($this->debug) {
            var_dump('Seq Order ID: ' . $orderSeqId);
        }
        $this->checkForError($orderSeqId, 'Unable to create order');

        $result = $this->soapClient->SetOrderCallbackURL(array(
                                'sToken' => $this->token,
                                'sSeqidOrder' => $orderSeqId,
                                'sCallBackURL' => $callbackUrl,
                            ))->SetOrderCallbackURLResult;
        if($this->debug) {
            var_dump('SetOrderCallbackURLResult: ' . $result);
        }
        if($result != self::CALLBACK_SET_SUCCESSFUL) {
            $this->checkForError($result, 'Unable to set callback URL');
        }
        return $orderSeqId;
    }

    /**
     * Given an orderId, the status of order is returned.
     *
     * @param  mixed $orderId
     * @return string
     *
     * @throws ScholarChipException thrown upon API error.
     * @throws OrderDoesNotExistException
     */
    public function getOrderStatus($orderId)
    {
        $this->refreshToken();
        $this->checkIfOrderExists($orderId);
        $orderSeqId = $this->getOrderSeqId($orderId);
        $result = $this->soapClient->GetOrderStatus(array(
                        'sToken' => $this->token,
                        'sSeqidOrder' => $orderSeqId,
                    ))->GetOrderStatusResult;
        if($this->debug) {
            var_dump('Order status: ' . $result);
        }
        $this->checkForError($result, 'Unable to fetch order status');
        return $result;
    }

    /**
     * Given an orderId, the order URL is returned.
     * 
     * @param  mixed $orderId
     * @return string
     *
     * @throws ScholarChipException thrown upon API error.
     * @throws OrderDoesNotExistException
     */
    public function getOrderUrl($orderId)
    {
        $this->refreshToken();
        $this->checkIfOrderExists($orderId);
        $orderSeqId = $this->getOrderSeqId($orderId);
        $orderUrl = $this->soapClient->GetOrderURL(array(
                        'sToken' => $this->token,
                        'sSeqidOrder' => $orderSeqId,
                    ))->GetOrderURLResult;
        $this->checkForError($orderUrl, 'Unable to fetch order URL');

        if($this->debug) {
            var_dump('orderURL: ' . $orderUrl);
        }
        return $orderUrl;
    }
     
    /**
     * Adds an item to an existing order,
     *
     * You can add multiple items. These will show up as quantity 1 for 
     * the amount you specify. To complete the order redirect the user to
     * the URL returned from getOrderUrl().
     * 
     * @param mixed  $orderId
     * @param mixed  $amount
     * @param string $description Limited to 64 characters, if longer string
     *                            is passed the first 64 characters will 
     *                            be used
     *                            
     * @param mixed  $itemId      If for some reason you wish to assign your
     *                            own ID to an item you may do so.
     * @return boolean
     *
     * @throws ScholarChipException thrown upon API error.
     * @throws OrderDoesNotExistException
     */
    public function addItem($orderId, $amount, $description, $itemId = null)
    {
        $this->refreshToken();
        $this->checkIfOrderExists($orderId);
        $orderSeqId = $this->getOrderSeqId($orderId);
        $result = $this->soapClient->AddLineItemToOrder(array(
                        'sToken' => $this->token,
                        'sSeqidOrder' => $orderSeqId,
                        'sLineItemID' => $itemId,
                        'sDescription' => $description,
                        'sPrice' => $amount,
                        'sEAGL' => $this->config['gl'],
                    ))->AddLineItemToOrderResult;
        if($this->debug) {
            var_dump('AddLineItemToOrder result: ' . $result);
        }
        if($result != self::ITEM_ADD_SUCCESSFUL) {
            $this->checkForError($result, 'Unable to add item to order');
        }
        return true;
    }

    /**
     * Returns a list of all available API functions/calls.
     * @return array
     */
    public function availableAPIFunctions()
    {
        return $this->soapClient->__getFunctions();
    }

    /**
     * Fetches orderSeqId given orderId
     * 
     * @param  mixed $orderId
     * @return string
     */
    private function getOrderSeqId($orderId)
    {
        $result = $this->soapClient->DoesOrderExist(array(
                            'sToken' => $this->token,
                            'sOrderID' => $orderId,
                        ))->DoesOrderExistResult;
        if($this->debug) {
            var_dump('Fetching order seq id for: ' . $orderId);
            var_dump('OrderseqId result ' . $result);
        }
        if(strpos($result, 'ERROR: Order does not exist') !== false) {
            return '';
        }
        $this->checkForError($result, '');
        return $result;
    }

    /**
     * Refreshes the API token.
     * @return string
     */
    private function refreshToken()
    {
        if($this->token == null || !$this->isValidToken()) {
            $this->token = $this->soapClient
                                ->GetToken(array(
                                    'sUserid' => $this->config['user'],
                                    'sPassword' => $this->config['password']))
                                ->GetTokenResult;
            if($this->debug) {
                var_dump('Token value: ' . $this->token);
            }
            $msg = 'Unable to acquire ScholarChip token';
            $this->checkForError($this->token, $msg);
        }
        return $this->token;
    }

    /**
     * Validates whether the currently stored token is still valid.
     * @return boolean
     */
    private function isValidToken()
    {
        if($this->token != null) {
            $result = $this->soapClient->IsValidToken([
                            'sToken' => $this->token
                            ])->IsValidTokenResult;
            if ($this->debug) {
                var_dump('IsValidToken result: ' . $result);
            }
            $this->checkForError($result, 'Could not validate token');
            return $result === self::VALID_TOKEN;
        } 
        return false;
    }

    /**
     * Checks whether passed message contains error and throws
     * exception if that is the case.
     * 
     * According to ScholarChip API documentation, if an error
     * occurred, the result will output a string that contains
     * the word 'ERROR' along with human readable message of
     * what went wrong. 
     *
     * @param  string $message        Message potentially containing 
     *                                error message
     *                                
     * @param  string $prependMessage Text to be added at begining of 
     *                                message in the exception thrown
     */
    private function checkForError($message, $prependMessage)
    {
        if(strpos($message, 'ERROR') !== false) {
            throw new ScholarChipException($prependMessage . ': ' . $message);
        }
    }

    private function checkIfOrderExists($orderId)
    {
        $orderExists = $this->getOrderSeqId($orderId);
        if(empty($orderExists)) {
            $msg = sprintf('OrderId \'%s\' does NOT exist!', $orderId);
            throw new OrderDoesNotExistException($msg);
        }
    }
}
