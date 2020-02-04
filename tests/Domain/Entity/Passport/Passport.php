<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\Passport;

use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\User\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table('passports')]
#[ORM\Entity]
class Passport
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Assert\Type(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(name: 'sn', type: Types::STRING, length: 180, unique: true)]
    #[Assert\Length(max: 180)]
    private string $serialNumber;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'phones')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private User $user;
}
