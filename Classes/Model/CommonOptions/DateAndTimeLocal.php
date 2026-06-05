<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonOptions;

use DateTimeImmutable;
use DateTimeInterface;
use Webmozart\Assert\Assert;
use Wwwision\Types\Attributes\StringBased;

use function Wwwision\Types\instantiate;

#[StringBased(pattern: '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$', extensions: ['x-feature-editor' => 'dateTimeLocal'])]
final readonly class DateAndTimeLocal
{
    private const string FORMAT = 'Y-m-d\TH:i';

    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        return instantiate(self::class, $value);
    }

    public static function fromPhpDateTime(DateTimeInterface $dateTime): self
    {
        if ($dateTime instanceof DateTimeImmutable) {
            $dateTime = $dateTime->setTimezone(new \DateTimeZone('UTC'));
        } elseif ($dateTime instanceof \DateTime) {
            $dateTime = $dateTime->setTimezone(new \DateTimeZone('UTC'));
        }
        return instantiate(self::class, $dateTime->format(self::FORMAT));
    }

    public function toPhpDateTime(): DateTimeImmutable
    {
        $result = DateTimeImmutable::createFromFormat(self::FORMAT, $this->value);
        Assert::isInstanceOf($result, DateTimeImmutable::class);
        return $result;
    }
}
