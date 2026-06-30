<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryItem;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryLevel;
use JeffersonGoncalves\Commerce\Order\Models\OrderLineItem;
use JeffersonGoncalves\Commerce\Order\Models\OrderReturn;
use JeffersonGoncalves\Commerce\Product\Models\ProductVariant;

class RestockInventoryStep extends Step
{
    /**
     * @return array<int, array{level_id: string, quantity: int}>
     */
    public function handle(WorkflowContext $context): array
    {
        /** @var OrderReturn $return */
        $return = $context->result('create_return');
        $return->load('items');

        $restocked = [];

        foreach ($return->items as $returnItem) {
            $lineItem = OrderLineItem::query()->find($returnItem->order_line_item_id);
            $variant = $lineItem?->variant_id !== null ? ProductVariant::query()->find($lineItem->variant_id) : null;

            if ($variant === null || $variant->sku === null) {
                continue;
            }

            $inventoryItem = InventoryItem::query()->where('sku', $variant->sku)->first();
            $level = $inventoryItem?->levels()->first();

            if ($level === null) {
                continue;
            }

            $level->update(['stocked_quantity' => $level->stocked_quantity + $returnItem->quantity]);
            $restocked[] = ['level_id' => $level->id, 'quantity' => $returnItem->quantity];
        }

        return $restocked;
    }

    public function compensate(mixed $result, WorkflowContext $context): void
    {
        if (! is_array($result)) {
            return;
        }

        foreach ($result as $entry) {
            /** @var InventoryLevel|null $level */
            $level = InventoryLevel::query()->find($entry['level_id']);
            $level?->update(['stocked_quantity' => $level->stocked_quantity - $entry['quantity']]);
        }
    }

    public function name(): string
    {
        return 'restock_inventory';
    }
}
