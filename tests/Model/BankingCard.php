<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="banking_cards")
 * @ORM\Entity
 */
class BankingCard
{
    /**
     * @var bool
     *
     * @ORM\Column(name="available", type="boolean")
     */
    private $available;

    /**
     * @var float
     *
     * @ORM\Column(name="balance", type="decimal", precision=10, scale=2)
     */
    private $balance;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="bank_name")
     */
    private $bankName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires_at", type="datetime")
     *
     * @Assert\DateTime(format="Y-m-d H:i:s", groups={"tests"})
     */
    private $expiresAt;
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
}
