<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;
use App\Models\Cart;

class CartController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            $cart = $user->cart()->with('cartItems.product')->first();


            if(!$cart){
                    $cart = Cart::create(['user_id' => $user->id]);
            }

            return response()->json([
                'message' => 'Cart retrieved successfully',
                'cart' => $cart,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ],500);
        }
    }

    public function store(Request $request){

        try {
            $validate = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|numeric|min:1|integer',
            ]);

            $user = auth()->user();

            $cart = $user->cart;
            if(!$cart){
                $cart = Cart::create(['user_id' => $user->id]);
            }

            $cartItem = $cart->cartItems()->where('product_id', $validate['product_id'])->first();

            if($cartItem){
                $cartItem->quantity += $validate['quantity'];
                $cartItem->save();
            }
            else{
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $validate['product_id'],
                    'quantity' => $validate['quantity'],
                ]);
            }

            return response()->json([
                'message' => 'Product added to cart successfully',
                'cartItem' => $cartItem,
            ], 201);

        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ],500);
        }
    }

    public function update(Request $request, string $id){

        try {
            $validated = $request->validate([
                'quantity' => 'required|numeric|min:1|integer',
            ]);

            $user = auth()->user();
            $cart = $user->cart;
            if (!$cart) {
                return response()->json([
                    'message' => 'Cart not found',
                ], 404);
            }
            $cartItem = $cart->cartItems()->where('product_id', $id)->first();

            if(!$cartItem){
                return response()->json([
                    'message' => 'Product not found in cart',
                ] ,404);
            }
            $cartItem->quantity = $validated['quantity'];
            $cartItem->save();

            return response()->json([
                'message' => 'Product updated successfully',
                'cartItem' => $cartItem,
            ], 200);


        }catch(\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ],500);
        }
    }

    public function destroy(string $id){
        try {
            $user = auth()->user();
            $cart = $user->cart;
            if (!$cart) {
                return response()->json([
                    'message' => 'Cart not found',
                ], 404);
            }
            $cartItem = $cart->cartItems()->where('product_id', $id)->first();
            if (!$cartItem) {
                return response()->json([
                    'message' => 'Product not found in cart',
                ], 404);
            }
            $cartItem->delete();

            return response()->json([
                'message' => 'Product deleted successfully',
            ],200);

        }catch(\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ],500);
        }
    }

}
