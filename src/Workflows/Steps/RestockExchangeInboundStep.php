<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryItem;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryLevel;
use JeffersonGoncalves\Commerce\Order\Models\OrderExchange;
use JeffersonGoncalves\Commerce\Order\Models\OrderLineItem;
use JeffersonGoncalves\Commerce\Product\Models\ProductVariant;

class RestockExchangeInboundStep extends Step
{
    /**
     * @return array<int, array{level_id: string, quantity: int}>
     */
    public function handle(WorkflowContext $context): array
    {
        /** @var OrderExchange $exchange */
        $exchange = $context->result('create_exchange');
        $exchange->load('items');

        $restocked = [];

        foreach ($exchange->items->where('type', 'inbound') as $item) {
            $lineItem = OrderLineItem::query()->find($item->order_line_item_id);
            $variant = $lineItem?->variant_id !== null ? ProductVariant::query()->find($lineItem->variant_id) : null;

            if ($variant === null || $variant->sku === null) {
                continue;
            }

            $level = InventoryItem::query()->where('sku', $variant->sku)->first()?->levels()->first();

            if ($level === null) {
                continue;
            }

            $level->update(['stocked_quantity' => $level->stocked_quantity + $item->quantity]);
            $restocked[] = ['level_id' => $level->id, 'quantity' => $item->quantity];
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
        return 'restock_exchange_inbound';
    }
}
