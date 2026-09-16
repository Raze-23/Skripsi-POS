<?php

namespace Tests\Unit;

use App\Models\ConsignmentReturn;
use App\Services\ConsignmentDeliveryService;
use DomainException;
use PHPUnit\Framework\TestCase;

class ConsignmentDeliveryServiceTest extends TestCase
{
    public function test_it_calculates_discounted_unit_price(): void
    {
        $this->assertSame(10_800, ConsignmentDeliveryService::discountedPrice(12_000, 10));
        $this->assertSame(8_750, ConsignmentDeliveryService::discountedPrice(10_000, 12.5));
        $this->assertSame(12_000, ConsignmentDeliveryService::discountedPrice(12_000, 0));
        $this->assertSame(12_000, ConsignmentDeliveryService::discountedPrice(12_000, null));
    }

    public function test_it_rounds_discounted_price_to_the_nearest_rupiah(): void
    {
        $this->assertSame(9_333, ConsignmentDeliveryService::discountedPrice(10_000, 6.67));
    }

    public function test_it_rejects_discount_outside_the_valid_range(): void
    {
        $this->expectException(DomainException::class);

        ConsignmentDeliveryService::discountedPrice(12_000, 101);
    }

    public function test_consignment_revenue_uses_the_saved_discounted_unit_price(): void
    {
        $return = new ConsignmentReturn(['harga_satuan' => 10_800]);

        $this->assertSame(32_400, $return->calculateRevenue(3));
    }
}
