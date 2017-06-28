<?php

namespace Tests;

use Itacs\ScholarChip\Exceptions\OrderDoesNotExistException;
use Itacs\ScholarChip\Exceptions\OrderExistsException;
use Itacs\ScholarChip\Exceptions\ScholarChipException;
use Itacs\ScholarChip\ScholarChip;
use Mockery;
use PHPUnit\Framework\TestCase;
use SoapClient;



class ScholarChipTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->soapClientMock = Mockery::mock(SoapClient::class);
        $this->config = array(
                           'wsdl_url' => 'test-url',
                           'user' => 'test-user',
                           'password' => 'test-pass',
                           'gl' => '01-34032',
                        );
        $this->scholarchip = new ScholarChip($this->soapClientMock, 
                                             $this->config);
    }

    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testAvailableAPIFunctions()
    {
        $expectedResult = 123;
        $this->soapClientMock->shouldReceive('__getFunctions')
            ->andReturn($expectedResult);

        $result = $this->scholarchip->availableAPIFunctions();
        $this->assertEquals($expectedResult, $result);
    }

    public function testAddItem()
    {
        $token = 123;
        $seqIdOrder = 567;
        $orderId = 34567;
        $lineItemId = 456;
        $description = 'Hey, hi, hello!';
        $price = 12345.45;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);

        $this->mockGetOrderSeqId($token, $orderId, $seqIdOrder);

        $this->soapClientMock->shouldReceive('AddLineItemToOrder')
            ->with(['sToken' => $token, 
                    'sSeqidOrder' => $seqIdOrder,
                    'sLineItemID' => $lineItemId,
                    'sDescription' => $description,
                    'sPrice' => $price,
                    'sEAGL' => $this->config['gl']])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->AddLineItemToOrderResult = 
            ScholarChip::ITEM_ADD_SUCCESSFUL;

        $result = $this->scholarchip->addItem($orderId, $price, 
                                              $description, $lineItemId);
        $this->assertTrue($result);
    }

    public function testAddItemOrderDoesNotExistException()
    {
        $token = 123;
        $orderId = 34567;
        $lineItemId = 456;
        $description = 'Hey, hi, hello!';
        $price = 12345.45;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);

        $this->mockGetOrderSeqId($token, $orderId, '');

        $this->expectException(OrderDoesNotExistException::class);
        $result = $this->scholarchip->addItem($orderId, $price, 
                                              $description, $lineItemId);
    }

    public function testAddItemThrowsException()
    {
        $token = 123;
        $seqIdOrder = 567;
        $orderId = 34567;
        $lineItemId = 456;
        $description = 'Hey, hi, hello!';
        $price = 12345.45;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);

        $this->mockGetOrderSeqId($token, $orderId, $seqIdOrder);

        $this->soapClientMock->shouldReceive('AddLineItemToOrder')
            ->with(['sToken' => $token, 
                    'sSeqidOrder' => $seqIdOrder,
                    'sLineItemID' => $lineItemId,
                    'sDescription' => $description,
                    'sPrice' => $price,
                    'sEAGL' => $this->config['gl']])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->AddLineItemToOrderResult = 
            'ERROR: What is life?';

        $this->expectException(ScholarChipException::class);
        $result = $this->scholarchip->addItem($orderId, $price, 
                                              $description, $lineItemId);
    }

    public function testGetOrderUrl()
    {
        $token = 123;
        $seqIdOrder = 567;
        $orderUrl = 'http://hey.man';
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);

        $this->mockGetOrderSeqId($token, $orderId, $seqIdOrder);

        $this->soapClientMock->shouldReceive('GetOrderURL')
            ->with(['sToken' => $token, 'sSeqidOrder' => $seqIdOrder])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->GetOrderURLResult = $orderUrl;

        $result = $this->scholarchip->getOrderUrl($orderId);
        $this->assertEquals($orderUrl, $result);
    }

    public function testGetOrderUrlOrderDoesNotExistException()
    {
        $token = 123;
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);

        $this->mockGetOrderSeqId($token, $orderId, '');

        $this->expectException(OrderDoesNotExistException::class);
        $result = $this->scholarchip->getOrderUrl($orderId);
    }

    public function testGetOrderUrlThrowsException()
    {
        $token = 123;
        $seqIdOrder = 567;
        $orderUrl = 'ERROR: I dont like you';
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);

        $this->mockGetOrderSeqId($token, $orderId, $seqIdOrder);

        $this->soapClientMock->shouldReceive('GetOrderURL')
            ->with(['sToken' => $token, 'sSeqidOrder' => $seqIdOrder])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->GetOrderURLResult = $orderUrl;

        $this->expectException(ScholarChipException::class);
        $result = $this->scholarchip->getOrderUrl($orderId);
    }

    public function testRefreshTokenThrowsExceptionThroughGetOrderUrl()
    {
        // since refreshToken is private, can't access it directly,
        // but can test it by calling a method that uses it.
        $token = 'ERROR: I dont like you';
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);

        $this->expectException(ScholarChipException::class);
        $result = $this->scholarchip->getOrderUrl($orderId);
    }

    public function testIsValidTokenThrowsExceptionThroughGetOrderUrl()
    {
        $token = 123;
        $seqIdOrder = 567;
        $orderUrl = 'http://hey.man';
        $orderId = 34567;

        // Execute getOrderUrl once succesfully so that the internal
        // token variable get's set
        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);
        $this->mockGetOrderSeqId($token, $orderId, $seqIdOrder);
        $this->soapClientMock->shouldReceive('GetOrderURL')
            ->with(['sToken' => $token, 'sSeqidOrder' => $seqIdOrder])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->GetOrderURLResult = $orderUrl;
        $result = $this->scholarchip->getOrderUrl($orderId);
        $this->assertEquals($orderUrl, $result);
        // Finish getOrderUrl successfully
        
        // Upon second call to getOrderUrl, isValidToken will be invoked
        // and hence can be mocked to return error as below.
        $this->soapClientMock->shouldReceive('IsValidToken')
            ->with(['sToken' => $token])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->IsValidTokenResult = 'ERROR: Your token is ugly';

        $this->expectException(ScholarChipException::class);
        $result = $this->scholarchip->getOrderUrl($orderId);
    }

    public function testRefreshTokenThroughGetOrderUrl()
    {
        // This test will ensure that refreshToken returns the 'cached'
        // token stored in token member variable 'aka $this->token'

        $token = 123;
        $seqIdOrder = 567;
        $orderUrl = 'http://hey.man';
        $orderId = 34567;

        // Execute getOrderUrl once succesfully so that the internal
        // token variable get's set
        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);
        $this->mockGetOrderSeqId($token, $orderId, $seqIdOrder);
        $this->soapClientMock->shouldReceive('GetOrderURL')
            ->with(['sToken' => $token, 'sSeqidOrder' => $seqIdOrder])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->GetOrderURLResult = $orderUrl;
        $result = $this->scholarchip->getOrderUrl($orderId);
        $this->assertEquals($orderUrl, $result);
        // Finish getOrderUrl successfully
        
        // Upon second call to getOrderUrl, isValidToken will be invoked
        // so make it return true
        $this->soapClientMock->shouldReceive('IsValidToken')
            ->with(['sToken' => $token])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->IsValidTokenResult = ScholarChip::VALID_TOKEN;
        $result = $this->scholarchip->getOrderUrl($orderId);

        // Now the next call(s) to getOrderUrl should return proper token
        $result = $this->scholarchip->getOrderUrl($orderId);
        $this->assertEquals($orderUrl, $result);
    }

    public function testGetOrderStatus()
    {
        $token = 123;
        $seqIdOrder = 567;
        $orderStatus = 'OMG this order makes my body shake!';
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);

        $this->mockGetOrderSeqId($token, $orderId, $seqIdOrder);

        $this->soapClientMock->shouldReceive('GetOrderStatus')
            ->with(['sToken' => $token, 'sSeqidOrder' => $seqIdOrder])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->GetOrderStatusResult = $orderStatus;

        $result = $this->scholarchip->getOrderStatus($orderId);
        $this->assertEquals($orderStatus, $result);
    }

    public function testGetOrderStatusOrderDoesNotExist()
    {
        $token = 123;
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);

        $this->mockGetOrderSeqId($token, $orderId, '');

        $this->expectException(OrderDoesNotExistException::class);
        $result = $this->scholarchip->getOrderStatus($orderId);
    }

    public function testGetOrderStatusThrowsException()
    {
        $token = 123;
        $seqIdOrder = 567;
        $orderStatus = 'ERROR: Chicken';
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);

        $this->mockGetOrderSeqId($token, $orderId, $seqIdOrder);

        $this->soapClientMock->shouldReceive('GetOrderStatus')
            ->with(['sToken' => $token, 'sSeqidOrder' => $seqIdOrder])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->GetOrderStatusResult = $orderStatus;

        $this->expectException(ScholarChipException::class);
        $result = $this->scholarchip->getOrderStatus($orderId);
    }

    public function testCreateOrderOrderIDTooLong()
    {
        $orderId = '';
        for($i = 0; $i < 50; $i++) {
            $orderId .= 'a';
        }
        $this->expectException(ScholarChipException::class);
        $result = $this->scholarchip->createOrder($orderId, null);
    }

    public function testCreateOrder()
    {
        $token = 123;
        $seqIdOrder = 567;
        $callbackUrl = 'call me at 1-800-000-0000';
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);
        // mock call that checks if order exists
        $this->mockGetOrderSeqId($token, $orderId, '');

        $this->soapClientMock->shouldReceive('CreateOrder')
            ->with(['sToken' => $token, 'sOrderID' => $orderId])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->CreateOrderResult = $seqIdOrder;

        $this->soapClientMock->shouldReceive('SetOrderCallbackURL')
            ->with(['sToken' => $token, 
                    'sSeqidOrder' => $seqIdOrder,
                    'sCallBackURL' => $callbackUrl])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->SetOrderCallbackURLResult = 
            ScholarChip::CALLBACK_SET_SUCCESSFUL;

        $result = $this->scholarchip->createOrder($orderId, $callbackUrl);
        $this->assertEquals($seqIdOrder, $result);
    }

    public function testCreateOrderOrderExistsException()
    {
        $token = 123;
        $seqIdOrder = 567;
        $callbackUrl = 'call me at 1-800-000-0000';
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);
        $this->mockGetOrderSeqId($token, $orderId, $seqIdOrder);

        $this->expectException(OrderExistsException::class);
        $result = $this->scholarchip->createOrder($orderId, $callbackUrl);
    }

    public function testCreateOrderThrowsException()
    {
        $token = 123;
        $seqIdOrder = 'ERROR: I love writing assembly!';
        $callbackUrl = 'call me at 1-800-000-0000';
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);
        // mock call that checks if order exists
        $this->mockGetOrderSeqId($token, $orderId, '');

        $this->soapClientMock->shouldReceive('CreateOrder')
            ->with(['sToken' => $token, 'sOrderID' => $orderId])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->CreateOrderResult = $seqIdOrder;

        $this->expectException(ScholarChipException::class);
        $result = $this->scholarchip->createOrder($orderId, $callbackUrl);
    }

    public function testCreateOrderSetOrderCallbackURLThrowsException()
    {
        $token = 123;
        $seqIdOrder = 567;
        $callbackUrl = 'call me at 1-800-000-0000';
        $orderId = 34567;

        $this->mockRefreshToken($this->config['user'],
                                $this->config['password'],
                                $token);
        // mock call that checks if order exists
        $this->mockGetOrderSeqId($token, $orderId, '');

        $this->soapClientMock->shouldReceive('CreateOrder')
            ->with(['sToken' => $token, 'sOrderID' => $orderId])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->CreateOrderResult = $seqIdOrder;

        $this->soapClientMock->shouldReceive('SetOrderCallbackURL')
            ->with(['sToken' => $token, 
                    'sSeqidOrder' => $seqIdOrder,
                    'sCallBackURL' => $callbackUrl])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->SetOrderCallbackURLResult = 
            'ERROR: Paypal > ScholarChip';

        $this->expectException(ScholarChipException::class);
        $result = $this->scholarchip->createOrder($orderId, $callbackUrl);
    }

    private function mockRefreshToken($user, $password, $returnToken)
    {
        $this->soapClientMock->shouldReceive('GetToken')
            ->with(['sUserid' => $user, 
                    'sPassword' => $password])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->GetTokenResult = $returnToken;
    }

    private function mockGetOrderSeqId($token, $orderId, $returnOrderSeqId)
    {
        $this->soapClientMock->shouldReceive('DoesOrderExist')
            ->with(['sToken' => $token, 
                    'sOrderID' => $orderId])
            ->andReturn($this->soapClientMock);
        $this->soapClientMock->DoesOrderExistResult = $returnOrderSeqId;
    }
}