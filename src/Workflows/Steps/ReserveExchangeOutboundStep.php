<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryItem;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryLevel;
use JeffersonGoncalves\Commerce\Inventory\Services\InventoryService;
use JeffersonGoncalves\Commerce\Order\Models\OrderExchange;
use JeffersonGoncalves\Commerce\Product\Models\ProductVariant;

class ReserveExchangeOutboundStep extends Step
{
    /**
     * @return array<int, array{level_id: string, quantity: int}>
     */
    public function handle(WorkflowContext $context): array
    {
        /** @var OrderExchange $exchange */
        $exchange = $context->result('create_exchange');
        $exchange->load('items');

        $service = new InventoryService;
        $reservations = [];

        foreach ($exchange->items->where('type', 'outbound') as $item) {
            $variant = $item->variant_id !== null ? ProductVariant::query()->find($item->variant_id) : null;

            if ($variant === null || $variant->sku === null) {
                continue;
            }

            $inventoryItem = InventoryItem::query()->where('sku', $variant->sku)->first();
            $level = $inventoryItem?->levels()->first();

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

        foreach ($result as $entry) {
            /** @var InventoryLevel|null $level */
            $level = InventoryLevel::query()->find($entry['level_id']);
            $level?->update(['reserved_quantity' => $level->reserved_quantity - $entry['quantity']]);
        }
    }

    public function name(): string
    {
        return 'reserve_exchange_outbound';
    }
}
