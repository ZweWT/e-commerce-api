<?php

namespace App\Services;

use App\Models\Order\CartItem;

class CartService
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get all items in cart of the user.
     *
     * @param int $userId
     * @param int $cartQuery
     * @return \Illuminate\Support\Collection
     */
    public function getItems(int $userId, array $cartQuery)
    {
        $cart = CartItem::where('user_id', $userId)
            ->with(['product', 'productModel'])
            ->paginate($cartQuery['limit']);

        $prices = $this->productService->getPrices($cart->toArray()['data']);

        $cart->getCollection()->transform(function ($item, $key) use ($prices) {
            $item->price = $prices[$key]['price'];
            $item->final_price = $prices[$key]['final_price'];

            return $item;
        });

        return $cart;
    }

    /**
     * Add item to cart of the user.
     *
     * @param int $userId
     * @param array $cartItemData
     * @return void
     */
    public function addItem(int $userId, array $cartItemData)
    {
        $cartItem = CartItem::where([
            ['user_id', $userId],
            ['product_model_id', $cartItemData['product_model_id']],
        ])
            ->first();

        if (is_null($cartItem)) {
            CartItem::create([
                ...$cartItemData,
                'user_id' => $userId,
            ]);
        } else {
            CartItem::where([
                ['user_id', $userId],
                ['product_model_id', $cartItemData['product_model_id']],
            ])
                ->update([
                    'quantity' => $cartItemData['quantity'],
                ]);
        }

        return;
    }
}
