What a package is it?
---
Well, this is the package that allow us do not worry about filters in GET requests.

We won't need to check what parameters exist and have they valid type or not anymore. For example url is `www.example.com/foo?filter[users_id]=string` but we defined user.id as int. This package will check typization, make validation on base of `Symfony\Component\Validator\Constraints` (with consideration of group of validation), return suitable response and cache it as well.

All of this is happened under the hood, we do not need to handle it explicitly.

It wraps `Doctrine\ORM\QueryBuilder` and `Symfony\Component\Validator\Validator\ValidatorInterface` \
and throws  `InvalidArgumentException` or `Symfony\Component\Validator\Exception\ValidatorException`
until pass throw validation was made. It happens before any request to DB.

Filter does not make any request to DB by design. It just apply filter and we always can take our `Doctrine\ORM\QueryBuilder` and continue our work with it as we want.

Let’s look at some details below.

[one of the simple usage example](../simple-example.md)

---
# How to use it?

For example we have DB like this.
<p align="center">
    <img src="https://i.imgur.com/hyuigzE.png"/>
</p>

and we want to have possibility to filter all aggregate of  User. For this we need to do two things:
+ To Add `Atlance\HttpDoctrineOrmFilter\Filter`. You can do it like this:
```php
use Atlance\HttpDoctrineOrmFilter\Filter;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    private $filter;

    public function setFilter(Filter $filter): self
    {
        $this->filter = $filter;

        return $this;
    }
}
```
+ To prepare request (as example we have request for some statistic on amount of users filtered for some parameters)

```php
use Atlance\HttpDoctrineOrmFilter\Query\Configuration;
use Atlance\HttpDoctrineOrmFilter\Filter;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class UserRepository extends EntityRepository
{
    private $filter;

    public function setFilter(Filter $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function findByConditions(array $conditions = [])
    {
        $qb = $this->filter->createQueryBuilder()
            ->select('COUNT(DISTINCT(users.id))')
            ->from(User::class, 'users')
            ->leftJoin('users.cards', 'cards', Join::WITH)
            ->leftJoin('users.phones', 'phones', Join::WITH)
            ->leftJoin(Passport::class, 'passport', Join::WITH, 'users.id = passport.user')
        ;

        return $this->filter->apply($qb, new Configuration($conditions))->getSingleScalarResult();
    }
}
```
All aliases of tables in request can be applied in filtering. To use it we need to know how to make HTTP request. \
For example:
- `from(User::class, 'users')` - alias `users`
- `leftJoin('users.cards', 'cards', Join::WITH)` - alias `cards` \
next by use of “_” we add property \
example: `created_at` … as result we have something like this - `users_created_at`

| expr | sql equivalent |
| ----------- | ---------- |
| eq | = |
| neq | <> |
| gt | > |
| gte | >= |
| lt | < |
| lte | =< |
| in | IN() |
| not_in | NOT IN() |
| is_null | IS NULL |
| is_not_null | IS NOT NULL |
| like | LIKE |
| not_like | NOT LIKE() |
| ilike | ILIKE() |
| between | BETWEEN() |

Values that was passed to this functions can be multiple.

In `$conditions = []` we have all `HTTP` query (`?filter[expr][table_column]=any`)

```php
use Symfony\Component\HttpFoundation\Request;

$request->query->all();
```

`HTTP query in form of: ?filter[expr][table_column]=any`

If we take DB described above and prepared request in service, than we have (in example of `expr` `eq`): \
`HTTP query: ?filter[eq][users_id]=1|2|3&filter[eq][cards_available]=1&filter[eq][cards_balance]=24760.21` \
It’s like `SQL` query:
```sql
SELECT COUNT(DISTINCT (u0_.id)) AS sclr_0 FROM users u0_
    LEFT JOIN users_cards u2_ ON u0_.id = u2_.user_id
    LEFT JOIN banking_cards b1_ ON b1_.id = u2_.card_id
    LEFT JOIN phones p3_ ON u0_.id = p3_.user_id
    LEFT JOIN passports p4_ ON (u0_.id = p4_.user_id)
WHERE (u0_.id = 1 OR u0_.id = 2 OR u0_.id = 3) AND b1_.available = 1 AND b1_.balance = 24760.21
```
We can make any request to DB by using `expr`. There is only restriction by length of HTTP request. \
There also is `ORDER BY`, but we should use it in a way like ...`&order[cards_expires_at]=asc`.

Another example of using pagination:

```php
use Atlance\HttpDoctrineOrmFilter\Query\Configuration;
use Atlance\HttpDoctrineOrmFilter\Filter;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Knp\Component\Pager\Pagination\PaginationInterface;

class UserRepository extends EntityRepository
{
    private $filter;
    private $paginator;

    public function setFilter(Filter $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function setPaginator(PaginationInterface $paginator): self
    {
        $this->paginator = $paginator;

        return $this;
    }

    public function fetch(array $conditions, int $page, int $size): PaginationInterface
    {
        $qb = $this->filter->createQueryBuilder()
            ->select('users')
            ->from(User::class, 'users')
            ->leftJoin('users.cards', 'cards', Join::WITH)
            ->leftJoin('users.phones', 'phones', Join::WITH)
            ->leftJoin(Passport::class, 'passport', Join::WITH, 'users.id = passport.user')
        ;

        return $this->paginator->paginate($this->filter->apply($qb, new Configuration($conditions))->getQuery(), $page, $size);
    }
}
```
---
## What is about validation?
For example we have User looks like this:
```php
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* @ORM\Table(name="users")
  */
class User
{
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
}
```

It will also validate on the base of `Symfony\Component\Validator\Constraints` during `HTTP` request.
Filter allows to make group of validations.
`Filter::setValidationGroups`.

---

## What is about cache?

Interface of `Doctrine\Common\Cache\Cache` will pass to constructor of filter.