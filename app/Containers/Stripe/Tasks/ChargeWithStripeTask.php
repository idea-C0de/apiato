<?php

namespace App\Containers\Stripe\Tasks;

use App\Containers\Payment\Contracts\ChargeableInterface;
use App\Containers\Payment\Contracts\PaymentChargerTaskInterface;
use App\Containers\Payment\Models\AbstractPaymentGatewayAccount;
use App\Containers\Stripe\Exceptions\StripeAccountNotFoundException;
use App\Containers\Stripe\Exceptions\StripeApiErrorException;
use App\Ship\Parents\Tasks\Task;
use Cartalyst\Stripe\Stripe;
use Exception;
use Illuminate\Support\Facades\Config;

/**
 * Class ChargeWithStripeTask.
 *
 * @author Mahmoud Zalt <mahmoud@zalt.me>
 */
class ChargeWithStripeTask extends Task implements PaymentChargerTaskInterface
{

    private $stripe;

    /**
     * StripeApi constructor.
     *
     * @param \Cartalyst\Stripe\Stripe $stripe
     */
    public function __construct(Stripe $stripe)
    {
        $this->stripe = $stripe->make(Config::get('services.stripe.secret'), Config::get('services.stripe.version'));
    }

    /**
     * @param ChargeableInterface           $user
     * @param AbstractPaymentGatewayAccount $account
     * @param float                         $amount
     * @param string                        $currency
     *
     * @return array|null
     * @throws StripeAccountNotFoundException
     * @throws StripeApiErrorException
     */
    public function run(ChargeableInterface $user, AbstractPaymentGatewayAccount $account, $amount, $currency = 'USD')
    {
        $valid = $account->checkIfPaymentDataIsSet(['customer_id', 'card_id', 'card_funding', 'card_last_digits', 'card_fingerprint']);

        if($valid == false) {
            throw new StripeAccountNotFoundException('We could not find your credit card information. 
            For security reasons, we do not store your credit card information on our server. 
            So please login to our Web App and enter your credit card information directly into Stripe, 
            then try to purchase the credits again. 
            Thanks.');
        }

        try {
            $response = $this->stripe->charges()->create([
                'customer' => $account->customer_id,
                'currency' => $currency,
                'amount'   => $amount,
            ]);

        } catch (Exception $e) {
            throw (new StripeApiErrorException('Stripe API error (chargeCustomer)'))->debug($e->getMessage(), true);
        }

        if ($response['status'] != 'succeeded') {
            throw new StripeApiErrorException('Stripe response status not succeeded (chargeCustomer)');
        }

        if ($response['paid'] !== true) {
            return null;
        }

        // this data will be stored on the pivot table (user credits)
        return [
            'payment_method' => 'stripe',
            'description'    => $response['id'] // the charge id
        ];
    }

}
