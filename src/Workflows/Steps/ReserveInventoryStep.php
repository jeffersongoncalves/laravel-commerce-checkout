<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Cart\Models\Cart;
use JeffersonGoncalves\Commerce\Cart\Models\CartLineItem;
use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryItem;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryLevel;
use JeffersonGoncalves\Commerce\Inventory\Services\InventoryService;

class ReserveInventoryStep extends Step
{
    /**
     * @return array<int, array{level_id: string, quantity: int}>
     */
    public function handle(WorkflowContext $context): array
    {
        /** @var Cart $cart */
        $cart = $context->input['cart'];

        $service = new InventoryService;
        $reservations = [];

        /** @var CartLineItem $item */
        foreach ($cart->items as $item) {
            $variant = $item->variant;

            if ($variant === null || $variant->sku === null) {
                continue;
            }

            $inventoryItem = InventoryItem::query()->where('sku', $variant->sku)->first();

            if ($inventoryItem === null) {
                continue;
            }

            /** @var InventoryLevel|null $level */
            $level = $inventoryItem->levels()->first();

            if ($level === null) {
                continue;
            }

            $service->reserve($inventoryItem->id, $level->location_id, $item->quantity);

            $reservations[] = ['level_id' => $level->id, 'quantity' => $item->quantity];
        }

        return $reservations;
    }

    public function compensate(mixed $result, WorkflowContext $context): void
    {
        if (! is_array($result)) {
            return;
        }

        foreach ($result as $reservation) {
            /** @var InventoryLevel|null $level */
            $level = InventoryLevel::query()->find($reservation['level_id']);

            if ($level !== null) {
                $level->update(['reserved_quantity' => $level->reserved_quantity - $reservation['quantity']]);
            }
        }
    }

    public function name(): string
    {
        return 'reserve_inventory';
    }
}
