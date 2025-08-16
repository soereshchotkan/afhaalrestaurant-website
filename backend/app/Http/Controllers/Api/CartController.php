<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Get current user's cart
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $cartItems = $user->cartItems()->with('product.category')->get();
        
        $subtotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });
        
        // Bereken BTW (9% in Nederland voor eten)
        $tax = $subtotal * 0.09;
        $total = $subtotal + $tax;
        
        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cartItems,
                'summary' => [
                    'subtotal' => round($subtotal, 2),
                    'tax' => round($tax, 2),
                    'total' => round($total, 2),
                    'item_count' => $cartItems->sum('quantity')
                ]
            ]
        ]);
    }
    
    /**
     * Add product to cart
     */
    public function addToCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:10',
            'special_instructions' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = auth()->user();
        $product = Product::find($request->product_id);
        
        // Check if product is available
        if (!$product->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Product is niet beschikbaar'
            ], 400);
        }
        
        // Check if item already in cart
        $cartItem = CartItem::where('user_id', $user->id)
                           ->where('product_id', $product->id)
                           ->first();
        
        if ($cartItem) {
            // Update quantity
            $cartItem->quantity += $request->quantity;
            $cartItem->special_instructions = $request->special_instructions ?? $cartItem->special_instructions;
            $cartItem->save();
            
            $message = 'Cart item bijgewerkt';
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price,
                'special_instructions' => $request->special_instructions
            ]);
            
            $message = 'Product toegevoegd aan winkelwagen';
        }
        
        $cartItem->load('product');
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $cartItem,
            'cart_count' => $user->cartItems()->sum('quantity')
        ]);
    }
    
    /**
     * Update cart item quantity
     */
    public function updateQuantity(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1|max:10'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = auth()->user();
        $cartItem = CartItem::where('user_id', $user->id)
                           ->where('id', $id)
                           ->first();
        
        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item niet gevonden'
            ], 404);
        }
        
        $cartItem->quantity = $request->quantity;
        $cartItem->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Aantal bijgewerkt',
            'data' => $cartItem->load('product')
        ]);
    }
    
    /**
     * Update special instructions
     */
    public function updateInstructions(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'special_instructions' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = auth()->user();
        $cartItem = CartItem::where('user_id', $user->id)
                           ->where('id', $id)
                           ->first();
        
        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item niet gevonden'
            ], 404);
        }
        
        $cartItem->special_instructions = $request->special_instructions;
        $cartItem->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Speciale instructies bijgewerkt',
            'data' => $cartItem
        ]);
    }
    
    /**
     * Remove item from cart
     */
    public function removeItem($id): JsonResponse
    {
        $user = auth()->user();
        $cartItem = CartItem::where('user_id', $user->id)
                           ->where('id', $id)
                           ->first();
        
        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item niet gevonden'
            ], 404);
        }
        
        $cartItem->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Item verwijderd uit winkelwagen',
            'cart_count' => $user->cartItems()->sum('quantity')
        ]);
    }
    
    /**
     * Clear entire cart
     */
    public function clearCart(): JsonResponse
    {
        $user = auth()->user();
        $deleted = $user->cartItems()->delete();
        
        return response()->json([
            'success' => true,
            'message' => "Winkelwagen geleegd ({$deleted} items verwijderd)",
            'data' => [
                'items' => [],
                'summary' => [
                    'subtotal' => 0,
                    'tax' => 0,
                    'total' => 0,
                    'item_count' => 0
                ]
            ]
        ]);
    }
    
    /**
     * Check if cart has minimum order amount
     */
    public function validateCart(): JsonResponse
    {
        $user = auth()->user();
        $cartItems = $user->cartItems()->with('product')->get();
        
        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Winkelwagen is leeg',
                'data' => ['valid' => false]
            ]);
        }
        
        $subtotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });
        
        $minimumOrder = 15.00; // Minimum bestelbedrag
        
        if ($subtotal < $minimumOrder) {
            return response()->json([
                'success' => false,
                'message' => "Minimum bestelbedrag is €{$minimumOrder}",
                'data' => [
                    'valid' => false,
                    'current_total' => $subtotal,
                    'minimum_required' => $minimumOrder,
                    'amount_needed' => round($minimumOrder - $subtotal, 2)
                ]
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Cart is geldig voor bestelling',
            'data' => [
                'valid' => true,
                'total' => $subtotal
            ]
        ]);
    }
}