<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PayPalPaymentService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private PayPalPaymentService $paypalService;
    private OrderService $orderService;

    public function __construct(PayPalPaymentService $paypalService, OrderService $orderService)
    {
        $this->paypalService = $paypalService;
        $this->orderService = $orderService;
    }

    /**
     * Create a PayPal order for payment processing
     */
    public function createPayPalOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::with(['orderItems', 'shippingAddress', 'billingAddress'])
                ->findOrFail($request->order_id);

            // Verify order is in correct state for payment
            if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is not in a valid state for payment processing'
                ], 400);
            }

            $result = $this->paypalService->createOrder($order);

            if ($result['success']) {
                Log::info('PayPal order created via API', [
                    'order_id' => $order->id,
                    'paypal_order_id' => $result['paypal_order_id']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'PayPal order created successfully',
                    'data' => [
                        'paypal_order_id' => $result['paypal_order_id'],
                        'approval_url' => $result['approval_url'],
                        'order_number' => $order->order_number
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'details' => $result['details'] ?? null
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error creating PayPal order via API', [
                'order_id' => $request->order_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the PayPal order'
            ], 500);
        }
    }

    /**
     * Capture a PayPal order after user approval
     */
    public function capturePayPalOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'paypal_order_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);

            // Verify order is in correct state for capture
            if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is not in a valid state for payment capture'
                ], 400);
            }

            $result = $this->paypalService->captureOrder($request->paypal_order_id, $order);

            if ($result['success']) {
                Log::info('PayPal order captured via API', [
                    'order_id' => $order->id,
                    'paypal_order_id' => $request->paypal_order_id,
                    'capture_id' => $result['capture_id']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment captured successfully',
                    'data' => [
                        'capture_id' => $result['capture_id'],
                        'status' => $result['status'],
                        'order_number' => $order->order_number,
                        'payment_status' => Order::PAYMENT_STATUS_PAID
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'details' => $result['details'] ?? null
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error capturing PayPal order via API', [
                'order_id' => $request->order_id,
                'paypal_order_id' => $request->paypal_order_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while capturing the payment'
            ], 500);
        }
    }

    /**
     * Simulate a successful payment for testing purposes
     */
    public function simulateSuccessfulPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);

            // Verify order is in correct state for payment
            if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is not in a valid state for payment processing'
                ], 400);
            }

            $result = $this->paypalService->simulateSuccessfulPayment($order);

            if ($result['success']) {
                Log::info('Successful payment simulated via API', [
                    'order_id' => $order->id,
                    'simulated_paypal_order_id' => $result['paypal_order_id'],
                    'simulated_capture_id' => $result['capture_id']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment simulated successfully',
                    'data' => [
                        'simulated' => true,
                        'paypal_order_id' => $result['paypal_order_id'],
                        'capture_id' => $result['capture_id'],
                        'order_number' => $order->order_number,
                        'payment_status' => Order::PAYMENT_STATUS_PAID
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error simulating successful payment via API', [
                'order_id' => $request->order_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while simulating the payment'
            ], 500);
        }
    }

    /**
     * Simulate a failed payment for testing purposes
     */
    public function simulateFailedPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);

            // Verify order is in correct state for payment
            if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is not in a valid state for payment processing'
                ], 400);
            }

            $result = $this->paypalService->simulateFailedPayment($order);

            Log::info('Failed payment simulated via API', [
                'order_id' => $order->id,
                'simulated_paypal_order_id' => $result['paypal_order_id']
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment simulation failed as expected',
                'data' => [
                    'simulated' => true,
                    'paypal_order_id' => $result['paypal_order_id'],
                    'order_number' => $order->order_number,
                    'payment_status' => Order::PAYMENT_STATUS_FAILED,
                    'error' => $result['error']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error simulating failed payment via API', [
                'order_id' => $request->order_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while simulating the failed payment'
            ], 500);
        }
    }

    /**
     * Verify PayPal configuration and connectivity
     */
    public function verifyPayPalConfig(): JsonResponse
    {
        try {
            $config = [
                'mode' => config('services.paypal.mode'),
                'client_id_configured' => !empty(config('services.paypal.client_id')),
                'client_secret_configured' => !empty(config('services.paypal.client_secret')),
            ];

            // Test access token generation
            $reflection = new \ReflectionClass($this->paypalService);
            $method = $reflection->getMethod('getAccessToken');
            $method->setAccessible(true);

            try {
                $accessToken = $method->invoke($this->paypalService);
                $config['access_token_test'] = 'success';
                $config['access_token_length'] = strlen($accessToken);
            } catch (\Exception $e) {
                $config['access_token_test'] = 'failed';
                $config['access_token_error'] = $e->getMessage();
            }

            return response()->json([
                'success' => true,
                'message' => 'PayPal configuration verified',
                'config' => $config
            ]);

        } catch (\Exception $e) {
            Log::error('Error verifying PayPal configuration', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error verifying PayPal configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
