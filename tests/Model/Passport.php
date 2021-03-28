<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Model;

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
     * @ORM\OneToOne(targetEntity="Atlance\HttpDoctrineOrmFilter\Test\Model\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;
}
