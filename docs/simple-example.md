
Get started:

```
composer req atlance/http-doctrine-filter
```

Register as service:

```yaml
# config/services.yaml
services:
# > Cache -------------------------------------------------------------------
    memcached:
        class: \Memcached
        arguments:
            - 'memcached'
        calls:
            - [ addServer, ['api-memcached', 11211]]
# < Cache -------------------------------------------------------------------
# > Http Doctrine Filter ----------------------------------------------------
    Atlance\HttpDoctrineFilter\Filter:
        arguments:
            - '@doctrine.orm.entity_manager'
            - '@validator'
            - '@memcached'
# < Http Doctrine Filter ----------------------------------------------------

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

DI Service(autowiring) and prepare the join aliases before filtering:
```php
<?php

namespace App\Fetcher;

use App\Entity\AnyEntity;
use App\Entity\FooEntity;
use App\Entity\BarEntity;
use App\Entity\BazEntity;
use Atlance\HttpDoctrineFilter\Dto\QueryConfiguration;
use Doctrine\ORM\Query\Expr\Join;
use Knp\Component\Pager\Pagination\PaginationInterface;

class AnyFetcher
{
    protected $filter;
    protected $paginator;

    public function __construct(Filter $filter, PaginatorInterface $paginator)
    {
        $this->filter = $filter;
        $this->paginator = $paginator;
    }

    public function fetch(array $conditions, int $page, int $size): PaginationInterface
    {
        $qb = $this->filter->createQueryBuilder();
        $qb->select(['any'])
            ->from(AnyEntity::class, 'any')
            ->leftJoin(FooEntity::class, 'foo', Join::WITH)
            ->leftJoin(BarEntity::class, 'bar', Join::WITH)
            ->leftJoin(BazEntity::class, 'baz', Join::WITH);

        return $this->paginator->paginate($this->filter->apply($qb, new QueryConfiguration($conditions)), $page, $size);
    }
}
```

Use case:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\AnyFetcher;
use Atlance\HttpDoctrineFilter\Dto\HttpDoctrineFilterRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AnyController extends AbstractController
{
    /**
     * @Route("/any", name="any", methods={"GET"})
     */
    public function filterAction(Request $request, AnyFetcher $fetcher): Response
        {
            return $this->render('any-template.html.twig', [
                'items' => $fetcher->fetch(
                    $request->query->all(),
                    $request->query->getInt('page', 1),
                    $request->query->getInt('limit', 20)
                ),
            ]);
        }
}
```

request: `http://localhost/any?filter[eq][any_id]=1|2&order[any_id]=asc`

where: \
`filter` - Filter::select argument. \
`[eq]` - qb query alias. \
`[any` - join/from table dql alias. \
`id]` - table column name. \
`order` - Filter::order argument    

this http request equivalent sql: `WHERE any.id = 1 OR any.id = 2 ORDER BY any.id ASC`

[other here](./../tests/Acceptance/FilterTest.php)