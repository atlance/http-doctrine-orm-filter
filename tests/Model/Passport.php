<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Test\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="passports")
 * @ORM\Entity
 */
class Passport
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
     * @ORM\Column(type="string", name="sn", length=180, unique=true)
     */
    private $serialNumber;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="Atlance\HttpDoctrineFilter\Test\Model\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSn(): string
    {
        return $this->serialNumber;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setSn(string $sn): void
    {
        $this->serialNumber = $sn;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
