<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ #{{ $order->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="bg-white w-full max-w-md rounded-xl shadow-lg overflow-hidden">

    <div class="bg-slate-800 p-6 text-white flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold">Заказ #{{ $order->id }}</h1>
            <p class="text-slate-400 text-sm mt-1">
                Статус: <span id="status"
                              class="uppercase font-semibold tracking-wider text-white">{{ $order->status }}</span>
            </p>
        </div>
        <div class="text-right">
            <p class="text-2xl font-bold">{{ number_format($order->total_price, 0, ',', ' ') }} ₽</p>
        </div>
    </div>

    <div class="p-6">
        <h2 class="text-gray-700 font-semibold mb-4 border-b pb-2">Состав заказа</h2>

        <ul class="space-y-3 mb-6">
            @foreach ($order->orderItems as $item)
                <li class="flex justify-between items-center text-sm">
                        <span class="text-gray-800">
                            {{ $item->product->name }}
                            <span class="text-gray-400 text-xs ml-1">x{{ $item->quantity }}</span>
                        </span>
                    <span class="font-medium text-gray-600">
                            {{ $item->price_at_purchase * $item->quantity }} ₽
                        </span>
                </li>
            @endforeach
        </ul>

        <div class="mt-6 pt-4 border-t border-gray-100">
            @if ($order->status === 'paid')
                <div class="flex items-center justify-center space-x-2 text-green-600 bg-green-50 p-3 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-medium">Заказ успешно оплачен</span>
                </div>

            @elseif ($order->status === 'canceled')
                <div class="text-center mb-3">
                        <span class="text-red-500 flex items-center justify-center gap-2 mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Платёж отменён
                        </span>
                </div>
                <form action="{{ route('payment.create') }}" method="POST">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 shadow-md">
                        Попробовать ещё раз
                    </button>
                </form>

            @elseif ($order->status === 'pending_payment')
                <div class="flex flex-col items-center justify-center text-orange-500 py-2">
                    <svg class="animate-spin h-8 w-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="font-medium animate-pulse">Ожидание подтверждения оплаты...</p>
                </div>

                <script>
                    const checkPayment = () => {
                        fetch("{{ route('payment.check', $order->id) }}")
                            .then(res => res.json())
                            .then(data => {
                                console.log('Payment status response:', data);

                                // Обновляем текст статуса в шапке
                                const statusEl = document.getElementById('status');
                                if (statusEl) statusEl.textContent = data.status;

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

            @else
                <form action="{{ route('payment.create') }}" method="POST">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 shadow-md flex justify-center items-center gap-2">
                        <span>Оплатить заказ</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

</body>
</html>
