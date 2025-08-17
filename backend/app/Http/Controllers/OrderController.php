<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CUSTOMER ENDPOINTS
    |--------------------------------------------------------------------------
    */

    /**
     * Place an order (checkout)
     */
    public function checkout(Request $request): JsonResponse
    {
        try {
            // Validatie
            $validator = Validator::make($request->all(), [
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email|max:255',
                'pickup_time' => 'required|date|after:now',
                'payment_method' => 'required|in:cash,card,ideal,paypal',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validatie fout',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();

            // Check of user cart items heeft
            $cartItems = CartItem::where('user_id', $user->id)
                                ->with('product')
                                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Je winkelwagen is leeg'
                ], 400);
            }

            // Bereken totalen
            $subtotal = $cartItems->sum(function ($item) {
                return $item->product->price * $item->quantity;
            });

            $taxRate = 0.09; // 9% BTW
            $taxAmount = $subtotal * $taxRate;
            $totalAmount = $subtotal + $taxAmount;

            // Check minimum order bedrag (€15)
            if ($totalAmount < 15.00) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimum bestelbedrag is €15,00',
                    'current_total' => number_format($totalAmount, 2),
                    'minimum_required' => '15.00'
                ], 400);
            }

            // Database transactie voor order creation
            DB::beginTransaction();

            // Maak order aan
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'pickup_time' => Carbon::parse($request->pickup_time),
                'payment_method' => $request->payment_method,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_status' => $request->payment_method === 'cash' ? 'pending' : 'pending',
                'notes' => $request->notes
            ]);

            // Maak order items aan van cart items
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->product->price,
                    'total' => $cartItem->product->price * $cartItem->quantity,
                    'notes' => $cartItem->special_instructions
                ]);
            }

            // Clear user's cart na succesvolle order
            CartItem::where('user_id', $user->id)->delete();

            DB::commit();

            // Load order with relationships voor response
            $order->load(['items.product', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Bestelling succesvol geplaatst!',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'customer_name' => $order->customer_name,
                        'customer_phone' => $order->customer_phone,
                        'customer_email' => $order->customer_email,
                        'pickup_time' => $order->pickup_time->format('d-m-Y H:i'),
                        'payment_method' => $order->payment_method,
                        'payment_status' => $order->payment_status,
                        'subtotal' => number_format($order->subtotal, 2),
                        'tax_amount' => number_format($order->tax_amount, 2),
                        'total_amount' => number_format($order->total_amount, 2),
                        'notes' => $order->notes,
                        'created_at' => $order->created_at->format('d-m-Y H:i:s'),
                        'items' => $order->items->map(function ($item) {
                            return [
                                'product_name' => $item->product->name,
                                'quantity' => $item->quantity,
                                'price' => number_format($item->price, 2),
                                'total' => number_format($item->total, 2),
                                'notes' => $item->notes
                            ];
                        })
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het plaatsen van de bestelling',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Get user's order history
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $orders = Order::where('user_id', $user->id)
                          ->with(['items.product'])
                          ->orderBy('created_at', 'desc')
                          ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->items(),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'total_pages' => $orders->lastPage(),
                        'total_orders' => $orders->total(),
                        'per_page' => $orders->perPage()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen bestelling geschiedenis',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Get specific order details
     */
    public function show($id): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $order = Order::where('user_id', $user->id)
                         ->where('id', $id)
                         ->with(['items.product'])
                         ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bestelling niet gevonden'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen bestelling details',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Cancel an order (only if pending or confirmed)
     */
    public function cancel($id): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $order = Order::where('user_id', $user->id)
                         ->where('id', $id)
                         ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bestelling niet gevonden'
                ], 404);
            }

            // Check of order nog geannuleerd kan worden
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deze bestelling kan niet meer geannuleerd worden'
                ], 400);
            }

            $order->update([
                'status' => 'cancelled'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bestelling succesvol geannuleerd',
                'data' => [
                    'order' => $order
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij annuleren bestelling',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN ENDPOINTS
    |--------------------------------------------------------------------------
    */

    /**
     * ADMIN: Get all orders with filters
     */
    public function adminIndex(Request $request): JsonResponse
    {
        try {
            $query = Order::with(['items.product', 'user']);

            // Filter op status
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // Filter op payment_status
            if ($request->has('payment_status') && $request->payment_status !== '') {
                $query->where('payment_status', $request->payment_status);
            }

            // Filter op datum
            if ($request->has('date') && $request->date !== '') {
                $query->whereDate('created_at', $request->date);
            }

            // Filter op pickup_time (vandaag, morgen, etc.)
            if ($request->has('pickup_date') && $request->pickup_date !== '') {
                $query->whereDate('pickup_time', $request->pickup_date);
            }

            // Zoeken op order number of customer naam
            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            }

            // Sorteer op created_at (nieuwste eerst)
            $query->orderBy('created_at', 'desc');

            $orders = $query->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->items(),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'total_pages' => $orders->lastPage(),
                        'total_orders' => $orders->total(),
                        'per_page' => $orders->perPage()
                    ],
                    'summary' => [
                        'total_today' => Order::whereDate('created_at', today())->count(),
                        'pending' => Order::where('status', 'pending')->count(),
                        'confirmed' => Order::where('status', 'confirmed')->count(),
                        'preparing' => Order::where('status', 'preparing')->count(),
                        'ready' => Order::where('status', 'ready')->count(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen bestellingen',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * ADMIN: Update order status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,confirmed,preparing,ready,completed,cancelled',
                'notes' => 'nullable|string|max:1000',
                'estimated_time' => 'nullable|integer|min:5|max:120' // in minuten
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validatie fout',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::with(['items.product', 'user'])->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bestelling niet gevonden'
                ], 404);
            }

            $oldStatus = $order->status;
            $newStatus = $request->status;

            // Update order
            $updateData = ['status' => $newStatus];
            
            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }

            // Voeg estimated preparation time toe als status confirmed wordt
            if ($newStatus === 'confirmed' && $request->has('estimated_time')) {
                $updateData['pickup_time'] = now()->addMinutes($request->estimated_time);
            }

            $order->update($updateData);

            // Log status change voor geschiedenis
            $statusMessage = $this->getStatusChangeMessage($oldStatus, $newStatus);

            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'data' => [
                    'order' => $order->fresh(['items.product', 'user']),
                    'status_changed' => [
                        'from' => $oldStatus,
                        'to' => $newStatus,
                        'changed_at' => now()->format('d-m-Y H:i:s')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij updaten bestelling status',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * ADMIN: Mark order as paid
     */
    public function markAsPaid(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'transaction_id' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validatie fout',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bestelling niet gevonden'
                ], 404);
            }

            $order->update([
                'payment_status' => 'paid',
                'payment_transaction_id' => $request->transaction_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bestelling gemarkeerd als betaald',
                'data' => [
                    'order' => $order->load(['items.product', 'user'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij updaten payment status',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * ADMIN: Get order statistics for dashboard
     */
    public function adminStats(): JsonResponse
    {
        try {
            $today = today();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();

            $stats = [
                'today' => [
                    'total_orders' => Order::whereDate('created_at', $today)->count(),
                    'total_revenue' => Order::whereDate('created_at', $today)
                                          ->where('payment_status', 'paid')
                                          ->sum('total_amount'),
                    'pending_orders' => Order::whereDate('created_at', $today)
                                            ->where('status', 'pending')
                                            ->count(),
                ],
                'this_week' => [
                    'total_orders' => Order::where('created_at', '>=', $thisWeek)->count(),
                    'total_revenue' => Order::where('created_at', '>=', $thisWeek)
                                           ->where('payment_status', 'paid')
                                           ->sum('total_amount'),
                ],
                'this_month' => [
                    'total_orders' => Order::where('created_at', '>=', $thisMonth)->count(),
                    'total_revenue' => Order::where('created_at', '>=', $thisMonth)
                                           ->where('payment_status', 'paid')
                                           ->sum('total_amount'),
                ],
                'status_counts' => [
                    'pending' => Order::where('status', 'pending')->count(),
                    'confirmed' => Order::where('status', 'confirmed')->count(),
                    'preparing' => Order::where('status', 'preparing')->count(),
                    'ready' => Order::where('status', 'ready')->count(),
                    'completed' => Order::where('status', 'completed')->count(),
                    'cancelled' => Order::where('status', 'cancelled')->count(),
                ],
                'recent_orders' => Order::with(['items.product', 'user'])
                                       ->latest()
                                       ->take(5)
                                       ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen statistieken',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Helper method voor status change messages
     */
    private function getStatusChangeMessage($oldStatus, $newStatus): string
    {
        return match($newStatus) {
            'confirmed' => 'Bestelling bevestigd',
            'preparing' => 'Bestelling wordt bereid',
            'ready' => 'Bestelling is klaar voor afhaal',
            'completed' => 'Bestelling voltooid',
            'cancelled' => 'Bestelling geannuleerd',
            default => 'Bestelling status bijgewerkt'
        };
    }
}