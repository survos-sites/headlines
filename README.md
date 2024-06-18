# MultiLingual Artwork from MOMA

Primarily a testing ground for translations and key-value-bundle

## Developer Installation

```bash
git clone git@github.com:survos-sites/artwork.git && cd artwork
composer install
symfony check:req
wget "https://github.com/MuseumofModernArt/collection/raw/main/Artists.csv"  
wget "https://github.com/MuseumofModernArt/collection/raw/main/Artworks.csv"  
bin/console doctrine:schema:update --force --complete
symfony server:start -d
# search for Microsoft in en and es feeds
symfony open:local
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
