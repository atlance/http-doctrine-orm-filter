[![Build Status](https://scrutinizer-ci.com/g/atlance/http-doctrine-filter/badges/build.png?)](https://scrutinizer-ci.com/g/atlance/http-doctrine-filter/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/atlance/http-doctrine-filter/badges/quality-score.png?)](https://scrutinizer-ci.com/g/atlance/http-doctrine-filter/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/atlance/http-doctrine-filter/badges/code-intelligence.svg?)](https://scrutinizer-ci.com/code-intelligence)
![GitHub](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg?style=flat)
[![Maintainability](https://api.codeclimate.com/v1/badges/bf0278a75df2cb127350/maintainability)](https://codeclimate.com/github/atlance/http-doctrine-filter/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/bf0278a75df2cb127350/test_coverage)](https://codeclimate.com/github/atlance/http-doctrine-filter/test_coverage)
![Psalm coverage](https://shepherd.dev/github/atlance/http-doctrine-filter/coverage.svg)
<p align="center">
    <img src="http://i.piccy.info/i9/fcbc05cfe68c7b45f7f36f77e1addff5/1579918903/199482/1358755/ezgif_3_038bb81d8352.jpg" alt="atlance/http-doctrine-filter" />
</p>

---
Что это вообще за пакет? \
Нууу ... Этот пакет позваляет не думать о фильтрах в `GET` запросах.

Нам не нужно будет проверять какие там существуют/отсутствуют параметры, нормальное ли значение у параметров, например 
`www.example.com/foo?filter[users_id]=string` а у нас `user.id` это `int`, неговоря уже о том что этот пакет не только проверяет допустимость типизации, но и валидирует на основании `Symfony\Component\Validator\Constraints` (учитывая группы валидации) и возвращает адекватный ответ, помимо этого кэширует всё это дело.

И всё это в динамике - не описывая какие-то конкретные сущности, случаи, не придумывая велосипед с названием параметров и тд.

По сути он оборачивает `Doctrine\ORM\QueryBuilder` и `Symfony\Component\Validator\Validator\ValidatorInterface` \
и пока всех всё не устроит выкидывает или `InvalidArgumentException` или `Symfony\Component\Validator\Exception\ValidatorException` и всё это до запроса в БД. \
Фильтр нарочно не делает сам запрос в БД, он просто применяет фильтр и мы всегда можем получить обратно свой `Doctrine\ORM\QueryBuilder` - продолжить с ним работу до формирования итогового запроса.

В тестах можно более подробно посмотреть все эти процессы.

---
# Как этим пользоваться?

Допустим у нас есть такая база данных
<p align="center">
    <img src="http://i.piccy.info/i9/9b1b8c13e1c512fe0822e0e7bd44c463/1599819842/37931/1395885/db.png" alt="atlance/http-doctrine-filter" />
</p>

и мы хотим иметь возможность фильтровать по всему агрегату `User`, соответственно в нашем репозиории (любом другом сервисе) мы должны сделать 2 вещи:
+ Подключить сам фильтр `Atlance\HttpDoctrineFilter\Filter`. Например так:
```php
use Atlance\HttpDoctrineFilter\Filter;
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

+ Подготовить сам запрос (например у нас какая-то статистика на количество пользователей по фильтру):
```php
use Atlance\HttpDoctrineFilter\Dto\QueryConfiguration;
use Atlance\HttpDoctrineFilter\Filter;
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

        return $this->filter->apply($qb, new QueryConfiguration($conditions))->getSingleScalarResult();
    }
}
```

под "подготовить" речь идёт о `join`-ах, т.е. всё что в `join`-ах и естественно в `from` будет иметь возможность учавствовать в фильтрации на предмет следующих функций:

| expr | sql equivalent |
| ----------- | ---------- |
| `eq` | `=` |
| `neq` | `<>` |
| `gt` | `>` |
| `gte` | `>=` |
| `lt` | `<` |
| `lte` | `=<` |
| `in` | `IN()` |
| `not_in` | `NOT IN()` |
| `is_null` | `IS NULL` |
| `is_not_null` | `IS NOT NULL` |
| `like` | `LIKE` |
| `not_like` | `NOT LIKE()` |
| `ilike` | `ILIKE()` |
| `between` | `BETWEEN()` |

Значения переданные в эти функции могут быть множественными.

Возникает вопрос:
так что же передавать и в какой форме в этот самый `array $conditions = []`

Ответ:
```php
use Symfony\Component\HttpFoundation\Request;

$request->query->all()
```

`HTTP query` в формате: `?filter[expr][table_column]=any`

Если взять изложенную выше конструкцию базы и подготовленного запроса в сервисе, то у нас на примере `expr eq` получается: \
`HTTP query`: `?filter[eq][users_id]=1|2|3&filter[eq][cards_available]=1&filter[eq][cards_balance]=24760.21` \
Эквивалентно `SQL`
```sql
SELECT COUNT(DISTINCT (u0_.id)) AS sclr_0 FROM users u0_
    LEFT JOIN users_cards u2_ ON u0_.id = u2_.user_id
    LEFT JOIN banking_cards b1_ ON b1_.id = u2_.card_id
    LEFT JOIN phones p3_ ON u0_.id = p3_.user_id
    LEFT JOIN passports p4_ ON (u0_.id = p4_.user_id)
WHERE (u0_.id = 1 OR u0_.id = 2 OR u0_.id = 3) AND b1_.available = 1 AND b1_.balance = 24760.21
```

Т.е. по сути мы не ограничены в составлении запросов к БД через представленные `expr`, за исключением максимально допустимой длинны `HTTP` запроса `:)`

Кроме этого существует и `ORDER BY`, но уже не через `filter[expr]` а просто как `...&order[cards_expires_at]=asc` например.

Ещё один рабочий вариант с пагинацией:
```php
use Atlance\HttpDoctrineFilter\Dto\QueryConfiguration;
use Atlance\HttpDoctrineFilter\Filter;
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

        return $this->paginator->paginate($this->filter->apply($qb, new QueryConfiguration($conditions)), $page, $size);
    }
}
```
---
## А что там на счет валидации?

Допустим наш `User` выглядит частично так:
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
То, при HTTP запросе он будет ещё и проверять на допустимость на основании `Symfony\Component\Validator\Constraints`.
Так же фильтр располагает возможностью передавать группы валидаций.

---

## А что там на счет кеширования?

На конструктор фильтра передаётся  `Doctrine\Common\Cache\CacheProvider` интерфейс. \
В дефолтном состоянии это `Doctrine\Common\Cache\ArrayCache`.