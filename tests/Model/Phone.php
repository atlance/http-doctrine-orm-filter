<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Model;

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
     */
    private $number;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Atlance\HttpDoctrineOrmFilter\Test\Model\User", inversedBy="phones", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;
}
