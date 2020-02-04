<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\BankingCard;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table('banking_cards')]
#[ORM\Entity]
class BankingCard
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Assert\Type(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(name: 'available', type: Types::BOOLEAN)]
    private bool $available;

    #[ORM\Column(name: 'balance', type: Types::DECIMAL, precision: 10, scale: 2)]
    private float $balance;

    #[ORM\Column(name: 'bank_name', type: Types::STRING)]
    #[Assert\Type(type: Types::STRING)]
    #[Assert\Length(max: 255)]
    private string $bankName;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Assert\DateTime(format: 'Y-m-d H:i:s')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_MUTABLE)]
    #[Assert\DateTime(format: 'Y-m-d H:i:s')]
    private \DateTime $expiresAt;
}
