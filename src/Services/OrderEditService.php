<?php

namespace JeffersonGoncalves\Commerce\Checkout\Services;

use JeffersonGoncalves\Commerce\Inventory\Models\InventoryItem;
use JeffersonGoncalves\Commerce\Inventory\Services\InventoryService;
use JeffersonGoncalves\Commerce\Order\Models\Order;
use JeffersonGoncalves\Commerce\Order\Models\OrderLineItem;
use JeffersonGoncalves\Commerce\Product\Models\ProductVariant;

/**
 * Post-creation order edits — add, change quantity of, or remove line items,
 * keeping inventory reservations in sync.
 */
class OrderEditService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addItem(string $orderId, array $attributes): OrderLineItem
    {
        /** @var Order $order */
        $order = Order::query()->findOrFail($orderId);

        /** @var OrderLineItem $item */
        $item = $order->items()->create($attributes);

        $this->adjust($item, $item->quantity, reserve: true);

        return $item;
    }

    public function updateItemQuantity(string $orderId, string $itemId, int $quantity): OrderLineItem
    {
        $item = $this->lineItem($orderId, $itemId);
        $delta = $quantity - $item->quantity;

        $item->update(['quantity' => $quantity]);

        if ($delta > 0) {
            $this->adjust($item, $delta, reserve: true);
        } elseif ($delta < 0) {
            $this->adjust($item, -$delta, reserve: false);
        }

        return $item;
    }

    public function removeItem(string $orderId, string $itemId): void
    {
        $item = $this->lineItem($orderId, $itemId);

        $this->adjust($item, $item->quantity, reserve: false);

        $item->delete();
    }

    private function lineItem(string $orderId, string $itemId): OrderLineItem
    {
        /** @var OrderLineItem $item */
        $item = OrderLineItem::query()->where('order_id', $orderId)->whereKey($itemId)->firstOrFail();

        return $item;
    }

    private function adjust(OrderLineItem $item, int $quantity, bool $reserve): void
    {
        if ($item->variant_id === null || $quantity <= 0) {
            return;
        }

        $variant = ProductVariant::query()->find($item->variant_id);

        if ($variant === null || $variant->sku === null) {
            return;
        }

        $inventoryItem = InventoryItem::query()->where('sku', $variant->sku)->first();
        $level = $inventoryItem?->levels()->first();

        if ($level === null) {
            return;
        }

        $service = new InventoryService;

        if ($reserve) {
            $service->reserve($inventoryItem->id, $level->location_id, $quantity);
        } else {
            $service->release($inventoryItem->id, $level->location_id, $quantity);
        }
    }
}
