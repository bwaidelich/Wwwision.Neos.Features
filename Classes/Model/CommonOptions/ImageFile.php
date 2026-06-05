<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonOptions;

use Wwwision\Types\Attributes\StringBased;

use function Wwwision\Types\instantiate;

#[StringBased(extensions: ['x-feature-editor' => 'fileUpload', 'x-feature-editor-options' => ['accept' => ['image/*']]])]
final readonly class ImageFile
{
    private function __construct(
        public string $tmpPath,
    ) {}

    public static function fromString(string $tmpPath): self
    {
        return instantiate(self::class, $tmpPath);
    }

    public function copyFileTo(string $targetPath): void
    {
        copy($this->tmpPath, $targetPath);
    }
}
