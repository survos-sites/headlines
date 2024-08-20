cd data
git clone git@github.com:WaltersArtMuseum/api-thewalters-org.git walters && cd walters
cd ..
bin/console pixie:import walters --limit 1000

