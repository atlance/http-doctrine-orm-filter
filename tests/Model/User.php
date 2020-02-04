<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Test\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="Atlance\HttpDoctrineFilter\Test\Repository\UserRepository")
 */
class User
{
    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Atlance\HttpDoctrineFilter\Test\Model\BankingCard", fetch="EAGER", cascade={"persist"})
     *
     * @ORM\JoinTable(
     *     name="users_cards",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="card_id", referencedColumnName="id")}
     * )
     */
    private $cards;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(name="created_at", type="datetime")
     *
     * @Assert\DateTime(format="Y-m-d H:i:s", groups={"tests"})
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="email", length=180, unique=true, nullable=true)
     *
     * @Assert\Email(groups={"tests"})
     * @Assert\Length(min=10, max=50, groups={"tests"})
     */
    private $email;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Assert\Type(type="integer", groups={"tests"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="name", length=180, unique=true)
     *
     * @Assert\Type(type="string", groups={"tests"})
     */
    private $name;

    /**
     * @var Passport
     *
     * @ORM\OneToOne(targetEntity="Atlance\HttpDoctrineFilter\Test\Model\Passport", fetch="EAGER", mappedBy="user", cascade={"persist"})
     */
    private $passport;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Atlance\HttpDoctrineFilter\Test\Model\Phone", mappedBy="user", fetch="EAGER", cascade={"persist"})
     */
    private $phones;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
        $this->phones = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getCards(): ArrayCollection
    {
        return $this->cards;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPassport(): Passport
    {
        return $this->passport;
    }

    public function getPhones(): Collection
    {
        return $this->phones;
    }

    public function setCards($cards): self
    {
        $this->cards = $cards;

        return $this;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setPassport(Passport $passport): self
    {
        $this->passport = $passport;

        return $this;
    }

    public function setPhones(Collection $phones): self
    {
        $this->phones = $phones;

        return $this;
    }
}
