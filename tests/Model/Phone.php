<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Test\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="phones")
 * @ORM\Entity
 */
class Phone
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="number", length=15, unique=true)
     * @Assert\Length(min=10, max=50, groups={"tests"})
     *
     */
    private $number;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Atlance\HttpDoctrineFilter\Test\Model\User", inversedBy="phones", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
