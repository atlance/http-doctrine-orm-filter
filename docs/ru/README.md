Что это вообще за пакет?  
Пакет позваляет не думать о фильтрах в http запросах.  

Нам не нужно будет проверять какие там существуют/отсутствуют параметры, нормальное ли значение у параметров, например  
`www.example.com/foo?filter[users_id]=string` а у нас `user.id` это `int`, неговоря уже о том что этот пакет не только проверяет допустимость типизации, но и валидирует на основании `Symfony\Component\Validator\Constraints` (учитывая группы валидации) и возвращает адекватный ответ, помимо этого кэширует всё это дело.

И всё это в динамике - не описывая какие-то конкретные сущности, случаи, не придумывая велосипед с названием параметров и тд.  

По сути он оборачивает `Doctrine\ORM\QueryBuilder` и `Symfony\Component\Validator\Validator\ValidatorInterface` \
и пока всех всё не устроит выкидывает или `InvalidArgumentException` или `Symfony\Component\Validator\Exception\ValidatorException` и всё это до запроса в БД. \
Фильтр нарочно не делает сам запрос в БД, он просто применяет фильтр и мы всегда можем получить обратно свой `Doctrine\ORM\QueryBuilder` - продолжить с ним работу до формирования итогового запроса.

В тестах можно более подробно посмотреть все эти процессы.

[Один из возможных примеров использования](../simple-example.md)

---
# Как этим пользоваться?

Допустим у нас есть такая база данных
<p align="center">
    <img src="https://i.imgur.com/hyuigzE.png"/>
</p>

и мы хотим иметь возможность фильтровать по всему агрегату `User`, соответственно в нашем репозиории (любом другом сервисе) мы должны сделать 2 вещи:
+ Подключить сам фильтр `Atlance\HttpDoctrineOrmFilter\Filter`. Например так:
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

+ Подготовить сам запрос (например у нас какая-то статистика на количество пользователей по фильтру):
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
        // Готовим запрос.
        $qb = $this->filter->createQueryBuilder()
            ->select('COUNT(DISTINCT(users.id))')
            ->from(User::class, 'users')
            ->leftJoin('users.cards', 'cards', Join::WITH)
            ->leftJoin('users.phones', 'phones', Join::WITH)
            ->leftJoin(Passport::class, 'passport', Join::WITH, 'users.id = passport.user')
        ;
        // Применяем фильтр.
        $this->filter->apply($qb, new QueryConfiguration($conditions));
        // Отдаем результат.
        return $qb->getQuery()->getSingleScalarResult();
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
/** @var Request */
$request->query->all(); // или $request->request->all()
```

В общем, массив с ключами и значениями.

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
        // Готовим запрос.
        $qb = $this->filter->createQueryBuilder()
            ->select('users')
            ->from(User::class, 'users')
            ->leftJoin('users.cards', 'cards', Join::WITH)
            ->leftJoin('users.phones', 'phones', Join::WITH)
            ->leftJoin(Passport::class, 'passport', Join::WITH, 'users.id = passport.user')
        ;
        // Применяем фильтр.
        $this->filter->apply($qb, new QueryConfiguration($conditions));
        // Отдаем результат.
        return $this->paginator->paginate($qb->getQuery(), $page, $size);
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
     * @Assert\Type(type="integer")
     */
    private $id;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(name="created_at", type="datetime")
     *
     * @Assert\DateTime(format="Y-m-d H:i:s")
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="email", length=180, unique=true, nullable=true)
     *
     * @Assert\Email()
     * @Assert\Length(min=10, max=50)
     */
    private $email;
}
```
То, при HTTP запросе он будет ещё и проверять на допустимость на основании `Symfony\Component\Validator\Constraints`.

---

## А что там на счет кеширования?

На конструктор фильтра передаётся  `Doctrine\Common\Cache\Cache` интерфейс.