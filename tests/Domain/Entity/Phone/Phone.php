<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\Phone;

use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\User\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table('phones')]
#[ORM\Entity]
class Phone
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Assert\Type(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(name: 'number', type: Types::STRING, length: 15, unique: true)]
    #[Assert\Type(type: Types::STRING)]
    #[Assert\Length(min: 10, max: 15)]
    private string $number;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'phones')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private User $user;
}
