<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\User;

use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\BankingCard\BankingCard;
use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\Passport\Passport;
use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\Phone\Phone;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table('users')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Assert\Type(type: Types::INTEGER)]
    private int $id;

    #[ORM\ManyToMany(targetEntity: BankingCard::class, cascade: ['persist'], fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'users_cards')]
    #[ORM\JoinColumn(name: 'user_id')]
    #[ORM\InverseJoinColumn(name: 'card_id')]
    private Collection $cards;

    #[ORM\Column(name: 'created_at', type: Types::DATE_IMMUTABLE)]
    #[Assert\DateTime(format: 'Y-m-d H:i:s')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 50, unique: true, nullable: true)]
    #[Assert\Email]
    #[Assert\Length(min: 10, max: 50)]
    private string $email;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 180, unique: true)]
    #[Assert\Type(type: Types::STRING)]
    private string $name;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Passport::class, cascade: ['persist'], fetch: 'EAGER')]
    private Passport $passport;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Phone::class, cascade: ['persist'], fetch: 'EAGER')]
    private Collection $phones;
}
