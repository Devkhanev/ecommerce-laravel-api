<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createPayment(Request $request)
    {

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        //        if ($order->user_id !== auth()->id()) {
        //            abort(403);
        //        }

        if ($order->status === 'paid') {
            return back()->with('error', 'Заказ уже оплачен');
        }

        $lastPayment = $order->payments()->latest()->first();
        if ($lastPayment && $lastPayment->status === 'pending') {
            try {
                $apiPayment = $this->getPaymentFromAPI($lastPayment->yookassa_payment_id);
                if (! empty($apiPayment['confirmation']['confirmation_url'])) {
                    return redirect($apiPayment['confirmation']['confirmation_url']);
                }
            } catch (\Exception $e) {
                Log::error('Failed to get existing payment confirmation URL', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $idempotenceKey = "order_{$order->id}_".uniqid();

            $response = Http::timeout(10)
                ->withBasicAuth(
                    config('services.yookassa.shop_id'),
                    config('services.yookassa.secret_key')
                )
                ->withHeaders([
                    'Idempotence-Key' => $idempotenceKey,
                ])
                ->post('https://api.yookassa.ru/v3/payments', [
                    'amount' => [
                        'value' => number_format($order->total_price, 2, '.', ''),
                        'currency' => 'RUB',
                    ],
                    'capture' => true,
                    'description' => 'Order #'.$order->id,
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => route('orders.show', $order->id),
                    ],
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'user_id' => (string) auth()->id(),
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Payment creation failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return back()->with('error', 'Ошибка создания платежа');
            }

            $paymentData = $response->json();

            DB::transaction(function () use ($order, $paymentData) {
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $order->total_price,
                    'status' => 'pending',
                    'payment_method' => 'yookassa',
                    'yookassa_payment_id' => $paymentData['id'],
                    'description' => 'Order #'.$order->id,
                ]);

                $order->update([
                    'status' => 'pending_payment',
                ]);
            });

            Log::info('Payment created successfully', [
                'order_id' => $order->id,
                'payment_id' => $paymentData['id'],
            ]);

            return redirect($paymentData['confirmation']['confirmation_url']);

        } catch (\Exception $e) {
            Log::error('Payment exception', ['error' => $e->getMessage()]);

            return back()->with('error', 'Произошла ошибка при обработке платежа');
        }
    }

    private function syncPaymentAndOrderStatuses(Payment $payment, array $apiPaymentData): void // Синхронизация статусов платежа и заказа
    {
        $yooKassaStatus = $apiPaymentData['status'] ?? null;

        if (! $yooKassaStatus) {
            Log::warning('Payment status is missing from API response', [
                'payment_id' => $payment->yookassa_payment_id,
            ]);

            return;
        }

        // Успешная оплата
        if ($yooKassaStatus === 'succeeded' && $payment->status !== 'succeeded') {
            $payment->update([
                'status' => 'succeeded',
                'paid_at' => now(),
            ]);

            $payment->order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            Log::info('Payment marked as succeeded', [
                'order_id' => $payment->order_id,
                'payment_id' => $payment->yookassa_payment_id,
            ]);
        } // Отменённая оплата
        elseif ($yooKassaStatus === 'canceled' && $payment->status !== 'canceled') {
            $payment->update([
                'status' => 'canceled',
            ]);

            $payment->order->update([
                'status' => 'canceled',
            ]);

            Log::info('Payment marked as canceled', [
                'order_id' => $payment->order_id,
                'payment_id' => $payment->yookassa_payment_id,
            ]);
        }
    }

    public function checkPaymentStatus(Order $order) // Проверка статуса оплаты
    {
        try {
            $payment = $order->payments()->latest()->first();

            if (! $payment || ! $payment->yookassa_payment_id) {
                return response()->json(['status' => $order->status]);
            }

            $apiPayment = $this->getPaymentFromAPI($payment->yookassa_payment_id);

            $this->syncPaymentAndOrderStatuses($payment, $apiPayment);
            $order = $order->fresh();

            return response()->json(['status' => $order->fresh()->status, 'payment_status' => $payment->fresh()->status]);

        } catch (\Exception $e) {
            Log::error('Check payment status error', ['error' => $e->getMessage()]);

            return response()->json(['status' => $order->status ?? 'unknown']);
        }
    }

    public function handleWebhook(Request $request)
    {

        try {
            $data = $request->json()->all();
            $event = $data['event'] ?? null;
            $webhookPayment = $data['object'] ?? [];

            if (! $event || empty($webhookPayment)) {
                return response()->json(['status' => 'ok']);
            }

            $paymentId = $webhookPayment['id'] ?? null;
            if (! $paymentId) {
                return response()->json(['status' => 'ok']);
            }

            $payment = Payment::where('yookassa_payment_id', $paymentId)->first();

            if (! $payment) {
                return response()->json(['status' => 'ok']);
            }

            try {
                $apiPayment = $this->getPaymentFromAPI($paymentId);
                if (($apiPayment['status'] ?? null) !== ($webhookPayment['status'] ?? null)) {
                    $webhookPayment = $apiPayment;
                }
            } catch (\Exception $e) {
                Log::error('Failed to verify payment status via API', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->syncPaymentAndOrderStatuses($payment, $webhookPayment);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function getPaymentFromAPI($paymentId)
    {
        $response = Http::timeout(10)->withBasicAuth(
            config('services.yookassa.shop_id'),
            config('services.yookassa.secret_key')
        )->get("https://api.yookassa.ru/v3/payments/{$paymentId}");

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch payment from API. Status: '.$response->status());
        }

        return $response->json();
    }
}
