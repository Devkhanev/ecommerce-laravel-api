<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Cart;

class OrderController extends Controller
{

    public function store(Request $request)
    {

        try{
            $user = auth()->user();
            $cart = $user->cart;

            if (!$cart || $cart->cartItems->isEmpty()) {
                return response()->json(['message' => 'Cart is empty'], 400);
            }

            $total = $cart->cartItems->sum(function ($item) {
                return $item->product->price * $item->quantity;
            });

            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $total,
                'status' => 'pending'
            ]);

            foreach ($cart->cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price_at_purchase' => $cartItem->product->price,
                ]);
            }

            $cart->cartItems()->delete();

            return response()->json([
                'message' => 'Order created',
                'order' => $order->load('orderItems.product')
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

    }

    public function index(){

        try {
            $user = auth()->user();
            $orders = $user->orders()->with('orderItems.product')->get();

            return response()->json([
                'message' => 'Orders retrieved successfully',
                'orders' => $orders
            ] ,200);

        }catch (\Exception $e){
            return response()->json([
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ] ,500);
        }

    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $order = $user->orders()->with('orderItems.product')->find($id);

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found',
                ], 404);
            }

            return response()->json([
                'message' => 'Order retrieved successfully',
                'order' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
