#!/bin/bash
# Eseguito dall'immagine MySQL solo alla prima inizializzazione del volume mysql_data.
# Crea il database dei test e concede i permessi all'utente applicativo.
set -euo pipefail

# Nome fisso: deve coincidere con DB_DATABASE in backend/phpunit.xml.
TEST_DATABASE="orarium_test"
# In GRANT il carattere _ è un jolly: va escapato con \_ per non concedere anche altri database.
GRANT_DATABASE="$(printf '%s' "${TEST_DATABASE}" | sed 's/_/\\_/g')"

mysql --user=root --password="${MYSQL_ROOT_PASSWORD}" <<EOSQL
CREATE DATABASE IF NOT EXISTS \`${TEST_DATABASE}\`;
GRANT ALL PRIVILEGES ON \`${GRANT_DATABASE}\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
EOSQL
