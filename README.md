# MultiLingual Artwork from MOMA

Primarily a testing ground for translations and key-value-bundle

## Developer Installation

```bash
git clone git@github.com:survos-sites/pixy-demo.git && cd pixy-demo
composer install
symfony check:req
bin/console doctrine:schema:update --force --complete
symfony server:start -d
# search for Microsoft in en and es feeds
symfony open:local
```

Download the data, though eventually the conf files should have this!

```
wget "https://github.com/MuseumofModernArt/collection/raw/main/Artists.csv"  
wget "https://github.com/MuseumofModernArt/collection/raw/main/Artworks.csv"  
```

## Additional Bundles

* survos/scraper-bundle cache json calls to the API
* doctrine behaviours - store translations in related database

## In dev the database is sqlite, on production it is postgres.

## Headlines

This application works will for the NewsAPI also, but the license is restrictive, so while the code is there for dev testing, the application itself only uses MOMA.



Display headlines from NewsAPI in multiple language.

```bash
bin/console app:load-data Microsoft --language=en,es 
```

## datasets

https://www.kaggle.com/competitions/petfinder-adoption-prediction/data

has lookup table for breeds, etc.
