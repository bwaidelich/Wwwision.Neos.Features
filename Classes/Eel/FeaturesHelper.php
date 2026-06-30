<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Eel;

use Neos\Eel\ProtectedContextAwareInterface;
use Wwwision\Types\Schema\OptionalSchema;
use Wwwision\Types\Schema\Schema;
use Wwwision\Types\Schema\ShapeSchema;

final readonly class FeaturesHelper implements ProtectedContextAwareInterface
{
    public function isOptionalSchema(Schema $schema): bool
    {
        return $schema instanceof OptionalSchema;
    }

    public function unwrapSchema(Schema $schema): Schema
    {
        return $schema instanceof OptionalSchema ? $schema->wrapped : $schema;
    }

    public function getDefaultValue(ShapeSchema $schema, string $propertyName): mixed
    {
        return $schema->hasDefaultValue($propertyName) ? $schema->defaultValue($propertyName) : null;
    }

    public function allowsCallOfMethod($methodName): true
    {
        return true;
    }
}
