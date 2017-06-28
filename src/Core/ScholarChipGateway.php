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
class ScholarChipGateway implements ScholarChip/*extends ScholarchipBase*/{
  /*private $order_seq = null;
  private $student_seq = null;
  private $callback_url;*/

  /*
    Create a new Transaction.  You must pass an order_id which is
    unique across your ScholarChip login credentials.  If the same
    login information is used across several apps it is recommended
    you include the appname in the order_id (e.g. 'appname_order_id')
  */
  /*public function __construct($order_id, $check=false, $callback_url=null){
    //hopefully the user agent trick solves the weird timeout
    $this->soap_client = new SoapClient(SCHOLAR_CHIP_WSDL_URL, array("user_agent"=>""));
    $this->callback_url = $callback_url ? $callback_url :SCHOLAR_CHIP_CALLBACK_URL;
    $this->order_id = SCHOLAR_CHIP_ORDER_PREFIX."_".$order_id;
    $this->RefreshToken();
    //This if blocked was used furing debugging. Feel free to ignore
    if($check)
    {
      $result = $this->soap_client->DoesOrderExist(array("sToken"=>$this->token,"sOrderID"=>$this->order_id))->DoesOrderExistResult;
      if($this->contains_error($result))
      {
        throw new ScholarchipException($result);
      }
      else
        $this->order_seq = $result;
    }
    else
      $this->CreateOrder();
  } */    

    private $soapClient;
    private $config;

    private $token;
    private $orderSeqId;

    private $debug = true;

    /**
     * API result value indicating a valid token.
     */
    const VALID_TOKEN = '1';

    /**
     * API result value indicating a successful line item add.
     */
    const ADD_SUCCESSFUL = '1';


    const PAYMENT_ENDPOINT = '/payment';


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

    public function createOrder($orderId, $callbackUrl)
    {
        //TODO what if order already exists for this
        if(strlen($orderId) > 32) {
            throw new ScholarChipException('The order id cannot exceed 32 characters!');
        }

        $this->refreshToken();
        $this->orderSeqId = $this->soapClient->CreateOrder(array(
                                    'sToken' => $this->token,
                                    'sOrderID' => $orderId,
                                ))->CreateOrderResult;
        if($this->debug) {
            var_dump('Seq Order ID: ' . $this->orderSeqId);
        }
        $this->checkForError($this->orderSeqId, 'Unable to create order');

        $result = $this->soapClient->SetOrderCallbackURL(array(
                                'sToken' => $this->token,
                                'sSeqidOrder' => $this->orderSeqId,
                                'sCallBackURL' => $callbackUrl,
                            ))->SetOrderCallbackURLResult;
        if($this->debug) {
            var_dump('SetOrderCallbackURLResult: ' . $result);
        }
        $this->checkForError($result, 'Unable to set callback URL');
        return $this->orderSeqId;
    }

    public function getOrderStatus($orderId)
    {
        $this->refreshToken();
        $seqOrderId = $this->fetchOrderSeqId($orderId);
        $result = $this->soapClient->GetOrderStatus(array(
                        'sToken' => $this->token,
                        'sSeqidOrder' => $seqOrderId,
                    ))->GetOrderStatusResult;
        if($this->debug) {
            var_dump('Order status: ' . $result);
        }
        $this->checkForError($result, 'Unable to fetch order status');
        return $result;
    }

    /*public function GetOrderURL(){
    $this->RefreshToken();
    $result = $this->soap_client->GetOrderURL(array("sToken"=>$this->token,"sSeqidOrder"=>$this->order_seq))->GetOrderURLResult;
    if($this->contains_error($result)){
      throw new ScholarchipException("Coudl not fetch the order URL: " . $result);
    }
    return $result;
  }*/

    public function getOrderUrl($orderId)
    {
        $this->refreshToken();
        if($this->orderSeqId == null) {
            $this->orderSeqId = $this->fetchOrderSeqId($orderId);
        }
        $orderUrl = $this->soapClient->GetOrderURL(array(
                        'sToken' => $this->token,
                        'sSeqidOrder' => $this->orderSeqId,
                    ))->GetOrderURLResult;
        $this->checkForError($orderUrl, 'Unable to fetch order URL');

        if($this->debug) {
            var_dump('orderURL: ' . $orderUrl);
        }
        return $orderUrl;
    }


    
 /**
     Adds an item to the pending Transaction.  You can add multiple
  items.  These will show up as quantity 1 for the amount you
  specify.  To complete the order redirect the user to the URL
  returned from GetOrderUrl().   Line item id is optional
  */
  /*public function AddLineItem($amount,$description,$line_item_id=null){
    $this->RefreshToken();
    $result = $this->soap_client->AddLineItemToOrder(array("sToken"=>$this->token,"sSeqidOrder"=>$this->order_seq,"sLineItemID"=>$line_item_id, 
                                                           "sDescription"=>$description, "sPrice"=>$amount, "sEAGL"=>GL_ACCOUNT_NUMBER))->AddLineItemToOrderResult;
    if($this->contains_error($result)){
      echo "GL: ".GL_ACCOUNT_NUMBER;
      throw new ScholarchipException("Could not add line item to order: ".  $result);
    }

    return True;
  }*/


     
    /**
     * [addItem description]
     * @param [type] $orderId     [description]
     * @param [type] $amount      [description]
     * @param [type] $description [description]
     * @param [type] $itemId      If for some reason you wish to assign your
     *                            own ID to an item you may do so.
     */
    public function addItem($orderId, $amount, $description, $itemId = null)
    {
        $this->refreshToken();
        if($this->orderSeqId == null) {
            $this->orderSeqId = $this->fetchOrderSeqId($orderId);
        }
        $result = $this->soapClient->AddLineItemToOrder(array(
                        'sToken' => $this->token,
                        'sSeqidOrder' => $this->orderSeqId,
                        'sLineItemID' => $itemId,
                        'sDescription' => $description,
                        'sPrice' => $amount,
                        'sEAGL' => $this->config['gl'],
                    ))->AddLineItemToOrderResult;
        if($this->debug) {
            var_dump('AddLineItemToOrder result: ' . $result);
        }
        $this->checkForError($result, 'Unable to add item to order');
        return $result == self::ADD_SUCCESSFUL;
    }

    public function availableAPIFunctions()
    {
        return $this->soapClient->__getFunctions();
    }

    private function fetchOrderSeqId($orderId)
    {
        $result = $this->soapClient->DoesOrderExist(array(
                            'sToken' => $this->token,
                            'sOrderID' => $orderId,
                        ))->DoesOrderExistResult;
        // If order doesn't exist the error returned from 
        // ScholarChip is 'ERROR: Order does not exist'
        if($this->debug) {
            var_dump('Fetching order seq id for: ' . $orderId);
        }
        $this->checkForError($result, '');
        return $result;
    }

    private function refreshToken()
    {
        $this->isValidToken();
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

    private function isValidToken()
    {
        if($this->token != null) {
            $result = $this->soapClient->IsValidToken($this->token)
                        ->IsValidTokenResult;
            if ($this->debug) {
                var_dump('IsValidToken result: ' . $result);
            }
            $this->checkForError($result, 'Could not validate token');
            return $result === self::VALID_TOKEN;
        } 
        return false;
    }

    /**
     * According to ScholarChip API documentation, if an error
     * occurred, the result will output a string that contains
     * the word 'ERROR' along with human readable message of
     * what went wrong. 
     *
     * 
     * @param  [type] $message [description]
     * @return [type]          [description]
     */
    private function checkForError($message, $prependMessage)
    {
        if(strpos($message, 'ERROR') !== false) {
            throw new ScholarChipException($prependMessage . ': ' . $message);
        }
    }

 /* private function DoesStudentExist($ruid=null){
    if($this->student_seq == null){
      $result = $this->soap_client->DoesStudentExist(array("sToken"=>$this->token, "sStudentID"=>$ruid))->DoesStudentExistResult;
      if($this->contains_error($result)){
        return False;
      }else{
        $this->student_seq = $result;
      }
    }
    return True;
  }

  public function SetStudent($ruid,$first_name,$last_name,$email_address){
    $this->RefreshToken();
    if(!$this->DoesStudentExist($ruid)){
      $student_seq = $this->soap_client->CreateStudent(array("sToken"=>$this->token, "sStudentID"=>$ruid,
                                                             "sFirstName"=>$first_name, "sLastName"=>$last_name,
                                                             "sEmailAddress"=>$email_address))->CreateStudentResult;
      if($this->contains_error($student_seq)){
    throw new ScholarchipException("Unable to create student: " . $student_seq);
      }
      $this->student_seq = $student_seq;

    }
    $this->AddOrderToStudent();
    return $this->student_seq;
  }

  private function CreateOrder(){
    if($this->order_seq == null){ 
      $order_seq = $this->soap_client->CreateOrder(array("sToken"=>$this->token,"sOrderID"=> $this->order_id))->CreateOrderResult;
      if($this->contains_error($order_seq)){
        throw new ScholarchipException("Could not create order: " . $order_seq);
      }
      $this->order_seq = $order_seq;

      $result = $this->soap_client->SetOrderCallbackURL(array("sToken"=>$this->token,"sSeqidOrder"=>$this->order_seq,
                                                    "sCallBackURL"=>$this->callback_url))->SetOrderCallbackURLResult;
      if($this->contains_error($result)){
        throw new ScholarchipException("Could not set callback URL: " . $result);
      }
    }
    return $this->order_seq;
  }

  private function AddOrderToStudent(){
    $result = $this->soap_client->AddOrderToStudent(array("sToken" => $this->token, "sSeqidStudent"=>$this->student_seq,
                                                          "sSeqidOrder"=>$this->order_seq))->AddOrderToStudentResult;
    if($this->contains_error($result)){
      throw new ScholarchipException("Could not add order ($this->order_seq) student ($this->student_seq)". $result);
    }

    return;
  }*/

  /**
     Adds an item to the pending Transaction.  You can add multiple
  items.  These will show up as quantity 1 for the amount you
  specify.  To complete the order redirect the user to the URL
  returned from GetOrderUrl().   Line item id is optional
  */
  /*public function AddLineItem($amount,$description,$line_item_id=null){
    $this->RefreshToken();
    $result = $this->soap_client->AddLineItemToOrder(array("sToken"=>$this->token,"sSeqidOrder"=>$this->order_seq,"sLineItemID"=>$line_item_id, 
                                                           "sDescription"=>$description, "sPrice"=>$amount, "sEAGL"=>GL_ACCOUNT_NUMBER))->AddLineItemToOrderResult;
    if($this->contains_error($result)){
      echo "GL: ".GL_ACCOUNT_NUMBER;
      throw new ScholarchipException("Could not add line item to order: ".  $result);
    }

    return True;
  }*/

  /*
   *    Returns the URL that the user must be redirect to for completion of the purchase.
   */
  /*public function GetOrderURL(){
    $this->RefreshToken();
    $result = $this->soap_client->GetOrderURL(array("sToken"=>$this->token,"sSeqidOrder"=>$this->order_seq))->GetOrderURLResult;
    if($this->contains_error($result)){
      throw new ScholarchipException("Coudl not fetch the order URL: " . $result);
    }
    return $result;
  }*/

  /*
   *    Returns the ScholarChip unique sequence ID for the order.
   */
  /*public function GetOrderSeqID()
  {
    return $this->order_seq;
  }*/

  /*
   Returns array for read only Access to Transaction internals for debugging.
  */
  /*public function GetDebugInformation(){
    return get_object_vars($this);
  }*/
}
