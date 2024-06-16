# MultiLingual Headlines

Display headlines from NewsAPI in multiple language

Primarily a testing ground for translations.


## Developer Installation

```bash
git clone git@github.com:survos-sites/voxitour.git && cd voxitour
composer install
symfony check:req
bin/console doctrine:schema:update --force --complete
symfony server:start -d
# search for Microsoft in en and es feeds
bin/console app:load-data Microsoft --language=en,es 
symfony open:local
```

## Additional Bundles

* survos/scraper-bundle cache json calls to the API
* doctrine behaviours - store translations in related database

## In dev the database is sqlite, on production it is postgres.


