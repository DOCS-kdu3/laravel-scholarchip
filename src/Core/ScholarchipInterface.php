<?php
namespace Itacs\ScholarChip\Core;

abstract class ScholarchipInterface {
  protected $soap_client = null;
  protected $token = null;
  protected $order_id = null;

  protected function contains_error($str){
    return strpos($str,"ERROR")!==false;
  }

  protected function DoesOrderExist(){
    if($this->order_id == null){
      throw new ScholarchipException("Transaction's order_id is required to be set prior to attempting to put through a transaction.");
    }
    $result = $this->soap_client->DoesOrderExist(array("sToken"=>$this->token, "sOrderID"=>$this->order_id))->DoesOrderExistResult; 
    if($this->contains_error($result)){
      return False;
    }
    
    $this->order_seq = $result;
    return True;
  }

  protected function RefreshToken(){
    if($this->token==null || !$this->IsValidToken()){
      $this->token = $this->soap_client->GetToken(array("sUserid" =>SCHOLAR_CHIP_USER_NAME,
                            "sPassword"=>SCHOLAR_CHIP_PASSWORD))->GetTokenResult;
      if($this->contains_error($this->token)){
                new ScholarchipException("Unable to acquire ScholarChip token.  Check your username and password: " . $this->token);
      }
    }
    return $this->token;
  }
  protected function IsValidToken(){
    if($this->token != null && 
       $this->soap_client->IsValidToken($this->token)->IsValidTokenResult==="1"){
      return True;
    }
    return False;
  }

}