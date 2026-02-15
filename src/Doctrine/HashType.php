<?php

namespace RandomPHP\Hashing\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use RandomPHP\Hashing\Hash;
use RandomPHP\Hashing\Interface\HashInterface;

final class HashType extends Type
{
    public const string NAME = 'hash';

    /**
     * @inheritDoc
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $length = $column['length'] ?? 255;

        return $platform->getStringTypeDeclarationSQL([
            'length' => $length,
            'fixed'  => false,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HashInterface
    {
        if ($value === null || $value instanceof HashInterface) {
            return $value;
        }

        if (!\is_string($value)) {
            throw ValueNotConvertible::new(
                $value,
                self::class
            );
        }

        try {
            return Hash::make($value);
        } catch (\Throwable $throwable) {
            throw InvalidFormat::new(
                value: $value,
                toType: self::class,
                expectedFormat: null,
                previous: $throwable
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof HashInterface) {
            return $value->toString();
        }

        if (\is_string($value)) {
            try {
                return Hash::make($value)->toString();
            } catch (\Throwable $throwable) {
                throw InvalidFormat::new(
                    value: $value,
                    toType: self::class,
                    expectedFormat: null,
                    previous: $throwable
                );
            }
        }

        throw InvalidType::new(
            $value,
            self::class,
            ['null', 'string', HashInterface::class]
        );
    }

    /**
     * @inheritDoc
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return [self::NAME];
    }
}