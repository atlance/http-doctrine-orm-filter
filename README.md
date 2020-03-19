[![Build Status](https://scrutinizer-ci.com/g/atlance/http-doctrine-filter/badges/build.png?b=master)](https://scrutinizer-ci.com/g/atlance/http-doctrine-filter/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/atlance/http-doctrine-filter/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/atlance/http-doctrine-filter/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/atlance/http-doctrine-filter/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)
![GitHub](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg?style=flat)
[![Maintainability](https://api.codeclimate.com/v1/badges/bf0278a75df2cb127350/maintainability)](https://codeclimate.com/github/atlance/http-doctrine-filter/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/bf0278a75df2cb127350/test_coverage)](https://codeclimate.com/github/atlance/http-doctrine-filter/test_coverage)
![Psalm coverage](https://shepherd.dev/github/atlance/http-doctrine-filter/coverage.svg)
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
[use case](./docs/simple-example.md)

<p align="center">
    <img src="http://i.piccy.info/i9/fcbc05cfe68c7b45f7f36f77e1addff5/1579918903/199482/1358755/ezgif_3_038bb81d8352.jpg" alt="atlance/http-doctrine-filter" />
</p>