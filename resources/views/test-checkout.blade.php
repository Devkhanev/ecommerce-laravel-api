<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">

        <!-- Заголовок -->
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            Заказ #{{ $order->id }}
        </h1>
        <p class="text-gray-600 text-sm mb-6">Оформление оплаты</p>

        <!-- Блок статуса -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-gray-700">
                <span class="font-semibold">Сумма к оплате:</span>
            </p>
            <p class="text-4xl font-bold text-blue-600 mt-2">
                {{ $order->total_price }} ₽
            </p>
        </div>

        <!-- Статус заказа -->
        @if ($order->status === 'paid')
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">✅</span>
                    <div>
                        <p class="font-semibold text-green-900">Оплачено</p>
                        <p class="text-sm text-green-700">Заказ успешно оплачен</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('orders.show', $order->id) }}"
               class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                Вернуться к заказу
            </a>

        @elseif ($order->status === 'pending_payment')
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⏳</span>
                    <div>
                        <p class="font-semibold text-yellow-900">Ожидание оплаты</p>
                        <p class="text-sm text-yellow-700">Оплата еще не прошла</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('orders.show', $order->id) }}"
               class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                Вернуться к заказу
            </a>

        @else
            <!-- Форма оплаты -->
            <form action="{{ route('payment.create') }}" method="POST">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">

                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 text-lg shadow-md hover:shadow-lg">
                    💳 Оплатить {{ $order->total_price }} ₽
                </button>
            </form>

            <script>
                const checkPayment = () => {
                    fetch('/webhooks/check-payment/{{ $order->id }}')
                        .then(res => res.json())
                        .then(data => {
                            console.log('Payment status response:', data);
                            document.getElementById('status').textContent = data.status;
                            if (data.status === 'paid' || data.status === 'canceled') {
                                clearInterval(interval);
                                location.reload();
                            }
                        })
                        .catch(err => console.error('Fetch error:', err));
                };

                const interval = setInterval(checkPayment, 3000);
                checkPayment();
            </script>
        @endif

    </div>
</div>
</body>
</html>
