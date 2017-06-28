<?php

require_once("Interface.class.php");

class ScholarchipTransactionResult extends ScholarchipInterface{
  private $status = null;
  private $order_seq = null;

    const AWAITING_PAYMENT = "Awaiting Payment";
    const CANCELLED = "Cancelled";
    const EXPIRED = "Expired";
    const INITIALIZED = "Initialized";
    const INVALID = "Invalid";
    const PENDING = "Pending";
    const PROCESSED = "Processed";

  public function __construct($order_id){
    $this->order_id = SCHOLAR_CHIP_ORDER_PREFIX."_".$order_id;
    $this->soap_client = new SoapClient(SCHOLAR_CHIP_WSDL_URL, array("user_agent"=>""));
    $this->RefreshToken();
    $this->GetOrder();
    $this->GetOrderStatus();
    
  }

  private function GetOrder(){
    $result = $this->soap_client->DoesOrderExist(array("sToken"=>$this->token,"sOrderID"=>$this->order_id))->DoesOrderExistResult;
    if($this->contains_error($result)){
      throw new ScholarchipException("Could not fetch order with id: $order_id" . $result);
    }
    $this->order_seq = $result;
  }

  private function GetOrderStatus(){
    $result = $this->soap_client->GetOrderStatus(array("sToken"=>$this->token,"sSeqidOrder"=>$this->order_seq))->GetOrderStatusResult;
    if($this->contains_error($result)){
      throw new ScholarchipException("Could not get order status " . $result);
    }
    $this->status = $result;
  }

  public function GetStatus(){
    return $this->status;
  }

    public function IsProcessed(){
        return $this->status == self::PROCESSED;
    }

        public function IsFailed(){
          return $this->status == self::CANCELLED || $this->status == self::EXPIRED || $this->status == self::INVALID;
        }
}