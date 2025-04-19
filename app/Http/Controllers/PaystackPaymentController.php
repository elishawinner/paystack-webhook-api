<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Unicodeveloper\Paystack\Paystack;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookLog;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PaystackPaymentController extends Controller
{
    protected $paystack;

    public function __construct()
    {
        $this->paystack = new Paystack();
        Log::info('Paystack initialized', [
            'config' => config('services.paystack')
        ]);
    }

  
//     public function redirectToGateway(Request $request)
// {
//     $validated = $request->validate([
//         'email' => 'required|email',
//         'amount' => 'required|numeric|min:100',
//     ]);

//     DB::beginTransaction();
//     Log::info('Starting payment process', ['input' => $validated]);

//     try {
//         $reference = $this->paystack->genTranxRef();
//         Log::info('Generated reference', ['reference' => $reference]);

//         // Create payment record with additional logging
//         $paymentData = [
//             'reference' => $reference,
//             'email' => $validated['email'],
//             'amount' => $validated['amount'],
//             'status' => 'pending',
//             'currency' => 'NGN',
//             'metadata' => []
//         ];

//         Log::debug('Attempting to create payment with data:', $paymentData);

//         $payment = Payment::create($paymentData);

//         if (!$payment->exists) {
//             Log::error('Payment record creation failed', ['data' => $paymentData]);
//             throw new \Exception('Payment record creation failed');
//         }

//         Log::info('Payment record created successfully', [
//             'id' => $payment->id,
//             'reference' => $payment->reference
//         ]);

//         // Prepare data for Paystack
//         $payload = [
//             'email' => $validated['email'],
//             'amount' => $validated['amount'] * 100,
//             'reference' => $reference,
//             'callback_url' => route('/api/payment/callback'), // Fixed this line
//             'metadata' => [
//                 'payment_id' => $payment->id,
//                 'custom_fields' => [
//                     [
//                         'display_name' => "Payment For",
//                         'variable_name' => "payment_for",
//                         'value' => "Service Payment"
//                     ]
//                 ]
//             ]
//         ];

//         Log::debug('Preparing Paystack payload:', $payload);

//         // Initialize payment with Paystack
//         $authorizationUrl = $this->paystack->getAuthorizationUrl($payload);
        
//         DB::commit();
//         Log::info('Payment process completed successfully', [
//             'authorization_url' => $authorizationUrl
//         ]);

//         return redirect()->away($authorizationUrl);

//     } catch (\Exception $e) {
//         DB::rollBack();
//         Log::error('Payment initiation failed', [
//             'error' => $e->getMessage(),
//             'trace' => $e->getTraceAsString(),
//             'input' => $validated
//         ]);

//         return back()->withErrors([
//             'msg' => 'Payment processing failed: ' . $e->getMessage()
//         ]);
//     }
// }
public function redirectToGateway(Request $request)
{
    $validated = $request->validate([
        'email' => 'required|email',
        'amount' => 'required|numeric|min:100',
    ]);

    DB::beginTransaction();

    try {
        $reference = $this->paystack->genTranxRef();
        Log::channel('payments')->info('Generated Reference in redirectToGateway', ['reference' => $reference]);

        $paymentData = [
            'reference' => $reference,
            'email' => $validated['email'],
            'amount' => $validated['amount'],
            'status' => 'pending',
            'currency' => 'NGN'
        ];
        Log::channel('payments')->info('Data for Payment::create', $paymentData);

        $payment = Payment::create($paymentData);

        if ($payment->exists) {
            Log::channel('payments')->info('Payment record created successfully in redirectToGateway', ['id' => $payment->id, 'reference' => $payment->reference]);
        } else {
            Log::channel('payments')->error('Failed to create payment record in redirectToGateway');
            throw new \Exception('Failed to create payment record');
        }

        $data = [
            'amount' => $validated['amount'] * 100,
            'email' => $validated['email'],
            'reference' => $reference,
            'currency' => 'NGN',
            'callback_url' => route('payment.callback'),
            'metadata' => [
                'payment_id' => $payment->id
            ]
        ];

        $authorizationUrl = $this->paystack->getAuthorizationUrl($data);

        DB::commit();

        return redirect()->away($authorizationUrl);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::channel('payments')->error('Payment initiation failed in redirectToGateway', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'input' => $validated
        ]);

        return back()->withErrors(['msg' => 'Payment processing failed: '.$e->getMessage()]);
    }
}
    public function handleGatewayCallback(Request $request)
    {
        Log::info('handleGatewayCallback executed', $request->all());

        try {
            $paymentDetails = $this->paystack->getPaymentData();
            Log::info('Payment Details from Paystack', $paymentDetails);

            if ($paymentDetails['status'] && $paymentDetails['data']['status'] === 'success') {
                Log::info('Payment verification successful, calling updatePayment', $paymentDetails['data']);
                $updatedRows = $this->updatePayment($paymentDetails['data']);
                Log::info('updatePayment completed, rows updated:', ['count' => $updatedRows]);

                return response()->json([
                    'status' => 'success',
                    'data' => $paymentDetails['data']
                ]);
            }

            Log::warning('Payment verification failed during callback', $paymentDetails);
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not verified'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Verification failed'
            ], 500);
        }
    }

    protected function updatePayment(array $data): int
    {
        $updatedRows = Payment::where('reference', $data['reference'])
            ->update([
                'status' => $data['status'],
                'paid_at' => $data['paid_at'] ?? now(),
                'metadata' => $data
            ]);

        Log::info('Payment record update attempt', [
            'reference' => $data['reference'],
            'rows_updated' => $updatedRows,
            'data_received' => $data
        ]);
        
        return $updatedRows;
    }

  

    public function handleWebhook(Request $request)
{
    Log::info('Webhook received', ['headers' => $request->headers->all()]);

    try {
        $payload = $request->getContent();
        $data = json_decode($payload, true);

        if (!$this->verifySignature($request)) {
            throw new \Exception('Invalid signature');
        }

        $log = WebhookLog::create([
            'event_type' => $data['event'],
            'reference' => $data['data']['reference'] ?? null,
            'payload' => $data,
            'is_verified' => true,
            'headers' => $request->headers->all(),
            'ip_address' => $request->ip(),
            'metadata' => [
                'customer_email' => $data['data']['customer']['email'] ?? null,
                'payment_amount' => $data['data']['amount'] ?? null,
                'payment_method' => $data['data']['channel'] ?? null,
                'source' => $data['data']['source']['type'] ?? null
            ]
        ]);

        Log::info('WebhookLog created with metadata', ['id' => $log->id]);

        if ($data['event'] === 'charge.success') {
            $this->updatePayment($data['data']);
        }

        return response()->json(['status' => 'success']);

    } catch (\Exception $e) {
        Log::error('Webhook failed', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => $e->getMessage()
        ], 400);
    }
}

    protected function verifySignature(Request $request): bool
    {
        $secret = config('services.paystack.secret_key');
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (empty($secret) || empty($signature)) {
            return false;
        }

        $computedSignature = hash_hmac('sha512', $payload, $secret);
        return hash_equals($computedSignature, $signature);
    }
}