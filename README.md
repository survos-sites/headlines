# MultiLingual Artwork from MOMA

Primarily a testing ground for translations and pixie-bundle

## Layout

```yaml
survos_pixie:
  # directories are relative to the project dir if not prefixed with /

  # location of the .pixie files.  Add to .gitignore to prevent checkin if in the repo path
  directory: pixie
  # location of the .config.yaml files
  config_directory: config/packages/pixie
  # data is defined in the .conf files
```

## Developer Installation

First, install the survos packages, since pixie is a bundle

```bash
git clone git@github.com:survos/survos ../survos
```

Install the app in the same directory as survos.

```bash
  git clone git@github.com:survos-sites/headlines.git && cd headlines
../survos/link . 
composer install
symfony check:req
bin/console doctrine:schema:update --force --complete
symfony server:start -d
symfony open:local --path /pixie/pixie
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
