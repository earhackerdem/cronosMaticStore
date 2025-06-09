<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentReturnController extends Controller
{
    /**
     * Handle successful payment return from PayPal
     */
    public function success(Request $request)
    {
        Log::info('PayPal payment success return', [
            'query_params' => $request->query(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);

        // Extract PayPal parameters
        $paypalOrderId = $request->query('token');
        $payerId = $request->query('PayerID');

        return Inertia::render('Payment/Success', [
            'paypal_order_id' => $paypalOrderId,
            'payer_id' => $payerId,
            'message' => 'Payment completed successfully!'
        ]);
    }

    /**
     * Handle cancelled payment return from PayPal
     */
    public function cancel(Request $request)
    {
        Log::info('PayPal payment cancel return', [
            'query_params' => $request->query(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);

        // Extract PayPal parameters
        $paypalOrderId = $request->query('token');

        return Inertia::render('Payment/Cancel', [
            'paypal_order_id' => $paypalOrderId,
            'message' => 'Payment was cancelled. You can try again or choose a different payment method.'
        ]);
    }
}
