<?php

namespace App\Services;

use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalPaymentService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->baseUrl = config('services.paypal.mode', 'sandbox') === 'live'
            ? 'https://api.paypal.com'
            : 'https://api.sandbox.paypal.com';

        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
    }

    /**
     * Get access token from PayPal
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];
                return $this->accessToken;
            }

            throw new Exception('Failed to get PayPal access token: ' . $response->body());

        } catch (Exception $e) {
            Log::error('PayPal access token error', ['error' => $e->getMessage()]);
            throw new Exception('Error obtaining PayPal access token: ' . $e->getMessage());
        }
    }

    /**
     * Create a PayPal order
     */
    public function createOrder(Order $order): array
    {
        try {
            $accessToken = $this->getAccessToken();

            $orderData = $this->buildOrderData($order);

            Log::info('Creating PayPal order', [
                'order_id' => $order->id,
                'total_amount' => $order->total_amount,
                'order_number' => $order->order_number
            ]);

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ])
                ->post("{$this->baseUrl}/v2/checkout/orders", $orderData);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('PayPal order created successfully', [
                    'order_id' => $order->id,
                    'paypal_order_id' => $data['id']
                ]);

                return [
                    'success' => true,
                    'paypal_order_id' => $data['id'],
                    'approval_url' => $this->getApprovalUrl($data['links'] ?? []),
                    'response' => $data
                ];
            }

            $error = $response->json();
            Log::error('PayPal order creation failed', [
                'order_id' => $order->id,
                'error' => $error,
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => 'Error creating PayPal order',
                'details' => $error
            ];

        } catch (Exception $e) {
            Log::error('Unexpected error creating PayPal order', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Capture a PayPal order
     */
    public function captureOrder(string $paypalOrderId, Order $order): array
    {
        try {
            $accessToken = $this->getAccessToken();

            Log::info('Capturing PayPal order', [
                'order_id' => $order->id,
                'paypal_order_id' => $paypalOrderId
            ]);

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/v2/checkout/orders/{$paypalOrderId}/capture");

            if ($response->successful()) {
                $data = $response->json();
                $captureId = $data['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

                Log::info('PayPal order captured successfully', [
                    'order_id' => $order->id,
                    'paypal_order_id' => $paypalOrderId,
                    'capture_id' => $captureId
                ]);

                return [
                    'success' => true,
                    'status' => $data['status'],
                    'capture_id' => $captureId,
                    'response' => $data
                ];
            }

            $error = $response->json();
            Log::error('PayPal order capture failed', [
                'order_id' => $order->id,
                'paypal_order_id' => $paypalOrderId,
                'error' => $error,
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => 'Error capturing PayPal order',
                'details' => $error
            ];

        } catch (Exception $e) {
            Log::error('Unexpected error capturing PayPal order', [
                'order_id' => $order->id,
                'paypal_order_id' => $paypalOrderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get PayPal order details
     */
    public function getOrderDetails(string $paypalOrderId): array
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/v2/checkout/orders/{$paypalOrderId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'response' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Error getting PayPal order details',
                'details' => $response->json()
            ];

        } catch (Exception $e) {
            Log::error('Error getting PayPal order details', [
                'paypal_order_id' => $paypalOrderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process payment (create and capture order)
     */
    public function processPayment(Order $order, string $paypalOrderId = null): array
    {
        try {
            // If no PayPal order ID provided, create one
            if (!$paypalOrderId) {
                $createResult = $this->createOrder($order);
                if (!$createResult['success']) {
                    return $createResult;
                }
                $paypalOrderId = $createResult['paypal_order_id'];
            }

            // Capture the order
            $captureResult = $this->captureOrder($paypalOrderId, $order);

            if ($captureResult['success']) {
                // Update order payment status
                app(OrderService::class)->updatePaymentStatus(
                    $order->id,
                    Order::PAYMENT_STATUS_PAID,
                    $captureResult['capture_id'],
                    'paypal'
                );

                Log::info('Payment processed successfully', [
                    'order_id' => $order->id,
                    'paypal_order_id' => $paypalOrderId,
                    'capture_id' => $captureResult['capture_id']
                ]);
            } else {
                // Update order payment status to failed
                app(OrderService::class)->updatePaymentStatus(
                    $order->id,
                    Order::PAYMENT_STATUS_FAILED,
                    $paypalOrderId,
                    'paypal'
                );

                Log::warning('Payment processing failed', [
                    'order_id' => $order->id,
                    'paypal_order_id' => $paypalOrderId,
                    'error' => $captureResult['error']
                ]);
            }

            return $captureResult;

        } catch (Exception $e) {
            Log::error('Unexpected error processing payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            // Update order payment status to failed
            if (isset($paypalOrderId)) {
                app(OrderService::class)->updatePaymentStatus(
                    $order->id,
                    Order::PAYMENT_STATUS_FAILED,
                    $paypalOrderId,
                    'paypal'
                );
            }

            return [
                'success' => false,
                'error' => 'Unexpected error processing payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Simulate a successful payment for testing
     */
    public function simulateSuccessfulPayment(Order $order): array
    {
        try {
            // Simulate a successful PayPal transaction
            $paypalOrderId = 'SIMULATED_' . strtoupper(uniqid());
            $captureId = 'CAPTURE_' . strtoupper(uniqid());

            // Update order payment status
            app(OrderService::class)->updatePaymentStatus(
                $order->id,
                Order::PAYMENT_STATUS_PAID,
                $paypalOrderId,
                'paypal'
            );

            Log::info('Simulated successful payment', [
                'order_id' => $order->id,
                'simulated_paypal_order_id' => $paypalOrderId,
                'simulated_capture_id' => $captureId
            ]);

            return [
                'success' => true,
                'simulated' => true,
                'paypal_order_id' => $paypalOrderId,
                'capture_id' => $captureId,
                'message' => 'Payment simulated successfully'
            ];

        } catch (Exception $e) {
            Log::error('Error simulating successful payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error simulating payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Simulate a failed payment for testing
     */
    public function simulateFailedPayment(Order $order): array
    {
        try {
            // Simulate a failed PayPal transaction
            $paypalOrderId = 'FAILED_' . strtoupper(uniqid());

            // Update order payment status
            app(OrderService::class)->updatePaymentStatus(
                $order->id,
                Order::PAYMENT_STATUS_FAILED,
                $paypalOrderId,
                'paypal'
            );

            Log::info('Simulated failed payment', [
                'order_id' => $order->id,
                'simulated_paypal_order_id' => $paypalOrderId
            ]);

            return [
                'success' => false,
                'simulated' => true,
                'paypal_order_id' => $paypalOrderId,
                'error' => 'Payment declined - simulated failure',
                'message' => 'Payment simulation failed as expected'
            ];

        } catch (Exception $e) {
            Log::error('Error simulating failed payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error simulating failed payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build PayPal order data
     */
    private function buildOrderData(Order $order): array
    {
        $order->load(['orderItems', 'shippingAddress', 'billingAddress']);

        $items = [];
        foreach ($order->orderItems as $item) {
            $items[] = [
                'name' => $item->product_name,
                'unit_amount' => [
                    'currency_code' => 'MXN',
                    'value' => number_format($item->price_per_unit, 2, '.', '')
                ],
                'quantity' => (string) $item->quantity,
            ];
        }

        return [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $order->order_number,
                    'amount' => [
                        'currency_code' => 'MXN',
                        'value' => number_format($order->total_amount, 2, '.', ''),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => 'MXN',
                                'value' => number_format($order->subtotal_amount, 2, '.', '')
                            ],
                            'shipping' => [
                                'currency_code' => 'MXN',
                                'value' => number_format($order->shipping_cost, 2, '.', '')
                            ]
                        ]
                    ],
                    'items' => $items,
                    'shipping' => [
                        'name' => [
                            'full_name' => $order->shippingAddress->full_name ?? 'Customer'
                        ],
                        'address' => [
                            'address_line_1' => $order->shippingAddress->street ?? '',
                            'address_line_2' => $order->shippingAddress->apartment ?? '',
                            'admin_area_2' => $order->shippingAddress->city ?? '',
                            'admin_area_1' => $order->shippingAddress->state ?? '',
                            'postal_code' => $order->shippingAddress->postal_code ?? '',
                            'country_code' => 'MX'
                        ]
                    ]
                ]
            ],
            'application_context' => [
                'brand_name' => 'CronosMatic',
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
                'return_url' => route('orders.payment.success'),
                'cancel_url' => route('orders.payment.cancel')
            ]
        ];
    }

    /**
     * Extract approval URL from PayPal response links
     */
    private function getApprovalUrl(array $links): ?string
    {
        foreach ($links as $link) {
            if (isset($link['rel']) && $link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }
}
