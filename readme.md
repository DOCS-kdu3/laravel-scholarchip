# ScholarChip Client Package
This is a PHP package wrapper around ScholarChip API.

## Requirements
* >= PHP 7
* php7.0-soap `sudo apt-get install php7.0-soap`

## Installation
Add the following to your `composer.json` file.

```
"require": {
    "itacs/scholarchip": "~1.0"
},
"repositories": [
    {
        "type": "vcs",
        "url": "git@gitlab.docs.rutgers.edu:itacs/laravel-scholarchip.git"
    }
],
```

Add the ScholarChipServiceProvider in `config\app.php` of your application:

```
Itacs\ScholarChip\ScholarChipServiceProvider::class
```

Run 'composer update', but note that you have to be signed into WatchGuard and your
dev-box needs to be configured to authenticate with the Github server via SSH-keys.
For more information about how to set this up refer to [this guide](https://help.github.com/articles/adding-a-new-ssh-key-to-your-github-account/).


## Sample Laravel Use
This is a rough overview of how to interact with the package.

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

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Itacs\ScholarChip\Exceptions\OrderExistsException;
use Itacs\ScholarChip\ScholarChip;

class HelloController extends Controller
{
    // using a random key for session data in order to
    // hide meaning of this value in case user decides 
    // to browse session data
    const ORDER_KEY = 'sk3x1zJ5yv';

    const REDIRECT_URL = '/thank-you';

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
            $seqOrderId = '';
            $orderCreated = false;
            $numRetries = 3;
            while(!$orderCreated && $numRetries > 0) {
                try {
                    $seqOrderId = $this->scholarchip
                                ->createOrder($orderId, 
                                              $request->fullUrl());
                    $orderCreated = true;
                } catch (Exception $e) {
                    // catching multiple exceptions is supported only
                    // as of PHP 7.1: https://wiki.php.net/rfc/multiple-catch
                    if($e instanceof OrderExistsException) {
                        $orderStatus = '';
                        try {
                            $orderStatus = $this->scholarchip->getOrderStatus($orderId);
                            if($orderStatus == ScholarChip::PROCESSED) {
                                // redirect user saying that order has been already
                                // processed and they're all good
                            } else {
                                // update the $orderId somehow, to generate
                                // new order
                                $numRetries--;
                            }
                        } catch (Exception $nestedE) {
                            // log error and redirect user telling them something bad
                            // happened
                        }
                    } else {
                        // log error and redirect user telling them something bad
                        // happened
                    }
                }
            }

            $redirectUrl = '';
            try {
                // add order item
                $this->scholarchip->addItem($orderId, $amount, $description);

                // get URL to redirect user to
                $redirectUrl = $this->scholarchip->getOrderUrl($orderId);
            } catch (Exception $e) {
                // log error and redirect user telling them something bad
                // happened
            }

            //
            // Here update your database with payment order information.
            //

            // store orderID
            $request->session()->put(self::ORDER_KEY, $orderId);

            // redirect user to ScholarChip to complete payment
            //header('Location: ' . $redirectUrl);
            die();
        }

        // someone must have stumbled upon this page by mistake
        return redirect('/');
    }

    public function returnedFromPayment(Request $request)
    {

        if ($request->session()->has(self::ORDER_KEY)) {
            // User came back from payment, redirect them
            // to where they need to go.

            // fetch orderId stored in session
            $sessionOrderId = $request->session()->get(self::ORDER_KEY);

            // remove orderId from session
            $request->session()->forget(self::ORDER_KEY);

            $orderStatus = '';
            try {
                // fetch order status
                $orderStatus = $this->scholarchip->getOrderStatus($sessionOrderId);   
            } catch (Exception $e) {
                // something bad happened, log it and maybe tell user
                // that payment 'tentively' accepted.
            }

            // do whatever bookeeping you need

            // redirect user
            return redirect(self::REDIRECT_URL);
        }

        // someone must have stumbled upon this page by mistake
        return redirect('/');
    }
}
```