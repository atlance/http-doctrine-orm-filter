
Get started:

```
composer require atlance/http-doctrine-orm-filter
```

Register as service:

```yaml
# config/services.yaml
services:
# > Cache -------------------------------------------------------------------
    memcached:
        class: \Memcached
        arguments: ['memcached']
        calls: [[ addServer, ['memcached', 11211]]]
# < Cache -------------------------------------------------------------------

# > Http Doctrine Filter ----------------------------------------------------
    filter.cache_adapter:
        class: Symfony\Component\Cache\Adapter\MemcachedAdapter
        arguments: ['@memcached', 'orm_filter_namespace']

    filter.cache:
        class: Doctrine\Common\Cache\Psr6\DoctrineProvider
        factory: ['Doctrine\Common\Cache\Psr6\DoctrineProvider', 'wrap']
        arguments: ['@filter.cache_adapter']

    Atlance\HttpDoctrineOrmFilter\Filter:
        arguments: ['@validator', '@filter.cache']
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
use Atlance\HttpDoctrineOrmFilter\Dto\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Knp\Component\Pager\Pagination\PaginationInterface;

final class AnyFetcher
{
    public function __construct(
        private EntityManagerInterface $em,
        private Filter $filter,
        private PaginatorInterface $paginator
    ) {
    }

    public function list(array $conditions, int $page, int $size): PaginationInterface
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(['any'])
            ->from(AnyEntity::class, 'any')
            ->leftJoin(FooEntity::class, 'foo', Join::WITH)
            ->leftJoin(BarEntity::class, 'bar', Join::WITH)
            ->leftJoin(BazEntity::class, 'baz', Join::WITH)
        ;

        $this->filter->apply($qb, new Configuration($conditions));

        return $this->paginator->paginate($qb, $page, $size);
    }
}
```

Use case:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\AnyFetcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/list', name: self::class)]
class ListController extends AbstractController
{
    public function __invoke(Request $request, AnyFetcher $fetcher): Response
        {
            return $this->render('any-template.html.twig', [
                'items' => $fetcher->list(
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

[other here](../tests/Acceptance/FilterTest.php)
