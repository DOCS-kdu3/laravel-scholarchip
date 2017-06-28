<?php

namespace Itacs\ScholarChip;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Itacs\ScholarChip\ScholarChip;

class ScholarChipController extends Controller
{
    const ORDER_KEY = 'sk3x1zJ5yv';

    public function __construct(ScholarChip $scholarchip)
    {
        $this->scholarchip = $scholarchip;
    }

    // adding a route as part of the package is not optimal
    // because it limits the flexibility of communicating with
    // the ScholarChip API. One of the issues is that you may
    // want to store seqOrderId for extra caution, then having
    // an endpoint defined by the package makes storing that
    // value cumbersome. Additionally
    // 
    // 
    // this seems to roughly work, don't include this in package
    // though
    public function index(Request $request)
    {
        $orderId = $request->input('orderId');
        $amount = $request->input('amount');
        $description = $request->input('description');
        if($request->has('test')) {
            $orderId = 123;
            $amount = 25;
            $description = 'test';
        }
        // don't take it here because then you'd have to store it
        // in session to get it later, which is stupid...
        $redirectUrl = $request->input('redirectUrl');


        if ($request->session()->has(self::ORDER_KEY)) {
            // fetch key and POST to user URL
            $sessionOrderId = $request->session()->get(self::ORDER_KEY);
            $request->session()->forget(self::ORDER_KEY);
            return 'Yo I came back from payment';
            
        } else if (!empty($orderId) && !empty($amount) && !empty($description)) {
            // redirect to /payment
            $seqOrderId = $this->scholarchip
                            ->createOrder($orderId, 
                                          config('app.url') . '/payment');
            var_dump($seqOrderId);
            $this->scholarchip->addItem($orderId, $amount, $description);
            $redirectUrl = $this->scholarchip->getOrderUrl($orderId);

            $request->session()->put(self::ORDER_KEY, $orderId);

            var_dump('Redirect to: ' . $redirectUrl);
            /*header('Location: ' . $redirectUrl);
            die();*/
        }

        return 'TODO: redirect me to main page';
    }
}
