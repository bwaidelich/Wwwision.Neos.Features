<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\FeatureImplementation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Neos\Features\Model\Feature\EmptyFeatureOptions;
use Wwwision\Neos\Features\Model\FeatureImplementation\NoopFeature;

#[CoversClass(NoopFeature::class)]
final class NoopFeatureTest extends TestCase
{
    public function test_optionsClassName_is_EmptyFeatureOptions(): void
    {
        self::assertSame(EmptyFeatureOptions::class, NoopFeature::optionsClassName());
    }

    public function test_activate_reports_success(): void
    {
        self::assertTrue((new NoopFeature())->activate(new EmptyFeatureOptions())->success);
    }

    public function test_deactivate_reports_success(): void
    {
        self::assertTrue((new NoopFeature())->deactivate()->success);
    }
}
