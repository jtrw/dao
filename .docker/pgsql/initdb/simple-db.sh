#!/bin/bash
# --------------------------------------
# Creating databases
filename=/tmp/initdb/dump/create.pgsql.sql
database="$POSTGRESQL_DB"
host="$POSTGRESQL_HOST"
password="$POSTGRES_PASSWORD"


#sleep 5;
#echo "Creating "$database" database..."
#mysql \
#--user='root' \
#--password="${MYSQL_ROOT_PASSWORD}" \
#--execute "DROP DATABASE IF EXISTS $database; CREATE DATABASE $database;"
#echo "Done!"

# Importing dumps
echo "Importing "$database" database..\n"
export PGPASSWORD=$password
psql \
-h=$host \
-U "${POSTGRES_USER}" \
-d $database < $filename
echo "Done!"