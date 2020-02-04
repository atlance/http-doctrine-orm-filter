
Get started:

```
composer req atlance/http-doctrine-filter
```

Register as service (use `symfony/expression-language` for create qb):

```yaml
# config/services.yaml

Atlance\HttpDoctrineFilter\Filter:
    arguments:
        - '@=service("doctrine.orm.entity_manager").createQueryBuilder()'
        - '@validator'

```

Entity validation annotations:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="any")
 * @ORM\Entity(repositoryClass="App\Repository\AnyRepository")
 */
class Any
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @Assert\Type(type="integer", groups={"any"})
     */
    private $id;
}
```

DI Repository(autowiring) and prepare the join aliases before filtering:
```php
<?php

declare(strict_types=1);

namespace App\Repository;

use Atlance\HttpDoctrineFilter\Filter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;


class AnyRepository extends ServiceEntityRepository
{
    /** @var Filter */
    private $httpDoctrineFilter;

    /**
     * {@inheritdoc}
     */
    public function __construct(ManagerRegistry $registry, Filter $filter)
    {
        parent::__construct($registry, AnyEntity::class);
        $this->httpDoctrineFilter = $filter;
    }
    
        /**
         * @param array $conditions
         *
         * @return array
         */
        public function findByConditions(array $conditions = [])
        {
            $qb = $this->_em->createQueryBuilder();
            $qb->select(['any'])
                ->from(AnyEntity::class, 'any')
                ->leftJoin('any.foo', 'foo', Join::WITH)
                ->leftJoin('any.bar', 'bar', Join::WITH)
                ->leftJoin('any.baz', 'baz', Join::WITH);
            
            return $this->httpDoctrineFilter->setOrmQueryBuilder($qb)
                ->setValidationGroups(['any'])
                ->selectBy($conditions['filter'] ?? [])
                ->orderBy($conditions['order'] ?? [])
                ->getOrmQueryBuilder()
                ->getQuery()
                ->getResult();
        }
}
```

Use case:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Atlance\HttpDoctrineFilter\Dto\HttpDoctrineFilterRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AnyController extends AbstractController
{
    /**
     * @Route("/any", name="any", methods={"GET"})
     */
    public function filterAction(Request $request, AnyRepository $repository): Response
        {
            return $this->render('any-template.html.twig', [
                'items' => $repository->findByConditions(
                    (new HttpDoctrineFilterRequest($request->query->all()))->toArray()
                ),
            ]);
        }
}
```

request: `http://localhost/any?filter[eq][any_id]=1|2&order[any_id]=asc`

where: \
`filter` - Filter::selectBy argument. \
`[eq]` - qb query alias. \
`[any` - join/from table dql alias. \
`id]` - table column name. \
`order` - Filter::orderBy argument    

this http request equivalent sql: `WHERE any.id = 1 OR any.id = 2 ORDER BY any.id ASC`

[other here](./../tests/Acceptance/FilterTest.php)