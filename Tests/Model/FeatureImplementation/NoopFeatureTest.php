<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\FeatureImplementation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Neos\Features\Model\CommonFeatures\YamlConfigurationFile;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;
use Wwwision\Neos\Features\Model\FeatureImplementation\NoopFeature;
use Wwwision\Neos\Features\Model\FeatureImplementation\OptionlessFeatureImplementation;

#[CoversClass(NoopFeature::class)]
final class NoopFeatureTest extends TestCase
{
    private function context(): FeatureContext
    {
        return new FeatureContext(new YamlConfigurationFile('/dev/null'), new YamlConfigurationFile('/dev/null'));
    }

    public function test_is_an_optionless_feature_implementation(): void
    {
        self::assertInstanceOf(OptionlessFeatureImplementation::class, new NoopFeature());
    }

    public function test_activate_reports_success(): void
    {
        self::assertTrue((new NoopFeature())->activate($this->context())->success);
    }

    public function test_deactivate_reports_success(): void
    {
        self::assertTrue((new NoopFeature())->deactivate($this->context())->success);
    }
}
