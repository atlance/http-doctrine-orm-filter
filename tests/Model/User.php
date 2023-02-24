<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="Atlance\HttpDoctrineOrmFilter\Test\Repository\UserRepository")
 */
class User
{
    /**
     * @ORM\ManyToMany(targetEntity="Atlance\HttpDoctrineOrmFilter\Test\Model\BankingCard", fetch="EAGER", cascade={"persist"})
     *
     * @ORM\JoinTable(
     *     name="users_cards",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="card_id", referencedColumnName="id")}
     * )
     */
    private Collection $cards;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     *
     * @Assert\DateTime(format="Y-m-d H:i:s")
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="string", name="email", length=180, unique=true, nullable=true)
     *
     * @Assert\Email()
     * @Assert\Length(min=10, max=50)
     */
    private string $email;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Assert\Type(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", name="name", length=180, unique=true)
     *
     * @Assert\Type(type="string")
     */
    private string $name;

    /**
     * @ORM\OneToOne(targetEntity="Atlance\HttpDoctrineOrmFilter\Test\Model\Passport", fetch="EAGER", mappedBy="user", cascade={"persist"})
     */
    private Passport $passport;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Atlance\HttpDoctrineOrmFilter\Test\Model\Phone", mappedBy="user", fetch="EAGER", cascade={"persist"})
     */
    private $phones;
}
