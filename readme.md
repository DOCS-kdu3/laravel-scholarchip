## Requirements
* >= PHP 7
* php7.0-soap `sudo apt-get install php7.0-soap`

## Sample Laravel Setup and Use
This is a rough overview of how to interact with the package. Note that right
now this does not deal with the exceptions that the package throws (this 
example will be modified in near future to include that).

### Route
Web middleware is needed to be able to interact with session.
```php
Route::group(['middleware' => ['web']], function () {
    Route::get('payment', 'ScholarChipController@returnedFromPayment');
    Route::post('payment', 'ScholarChipController@sendToPayment');
});
```
### Controller
```php
<?php

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Itacs\ScholarChip\ScholarChip;

class ScholarChipController extends Controller
{
    // using a random key for session data in order to
    // hide meaning of this value in case user decides 
    // to browse session data
    const ORDER_KEY = 'sk3x1zJ5yv';

    const REDIRECT_URL = '/thank-you';

    const THIS_

    public function __construct(ScholarChip $scholarchip)
    {
        $this->scholarchip = $scholarchip;
    }

    public function sendToPayment(Request $request)
    {
        // retrieve order info

        // note that 'orderID' should be unique across the entire ScholarChip
        // account. Hence prepending timestamp might be a good solution.
        $orderId = $request->input('orderId');
        $amount = $request->input('amount');
        $description = $request->input('description');

        if (!empty($orderId) && !empty($amount) && !empty($description)) {
            // Initiate new order

            // create order and redirect user back to this function
            // after payment completed
            $seqOrderId = $this->scholarchip
                            ->createOrder($orderId, 
                                          $request->fullUrl());
            // add order item
            $this->scholarchip->addItem($orderId, $amount, $description);

            // get URL to redirect user to
            $redirectUrl = $this->scholarchip->getOrderUrl($orderId);

            // store orderID
            $request->session()->put(self::ORDER_KEY, $orderId);

            // redirect user to ScholarChip to complete payment
            header('Location: ' . $redirectUrl);
            die();
        }

        // someone must have stumbled upon this page by mistake
        return redirect('/');
    }

    public function returnedFromPayment(Request $request)
    {
        // retrieve order info

        // note that 'orderID' should be unique across the entire ScholarChip
        // account. Hence prepending timestamp might be a good solution.
        $orderId = $request->input('orderId');
        $amount = $request->input('amount');
        $description = $request->input('description');

        if ($request->session()->has(self::ORDER_KEY)) {
            // User came back from payment, redirect them
            // to where they need to go.

            // fetch orderId stored in session
            $sessionOrderId = $request->session()->get(self::ORDER_KEY);

            // remove orderId from session
            $request->session()->forget(self::ORDER_KEY);

            // fetch order status
            $orderStatus = $this->scholarchip->getOrderStatus($sessionOrderId);

            // do whatever bookeeping you need

            // redirect user
            return redirect(self::REDIRECT_URL);
        }

        // someone must have stumbled upon this page by mistake
        return redirect('/');
    }
}

```