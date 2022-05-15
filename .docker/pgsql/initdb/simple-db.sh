#!/bin/bash
# --------------------------------------
# Creating databases
filename=/tmp/initdb/dump/create.pgsql.sql
database="$POSTGRESQL_DB"
host="$POSTGRESQL_HOST"
password="$POSTGRES_PASSWORD"


if psql -U postgres_user -w -lqtA | cut -d \| -f 1 | grep "dao";
then
  echo "DB "dao" already exists";
else
  PGPASSWORD=postgres_pass createdb -U postgres_user -w dao;
fi

#sleep 5;
#echo "Creating "$database" database..."
#psql \
#--user='root' \
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