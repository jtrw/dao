#!/bin/bash
# --------------------------------------
# Creating databases
filename=/tmp/initdb/create.mysql.sql
database="$MYSQL_DATABASE"
host="$MYSQL_HOST"


#sleep 5;
#echo "Creating "$database" database..."
#mysql \
#--user='root' \
#--password="${MYSQL_ROOT_PASSWORD}" \
#--execute "DROP DATABASE IF EXISTS $database; CREATE DATABASE $database;"
#echo "Done!"

# Importing dumps
echo "Importing "$database" database..."
mysql \
--host=$host \
--user='root' \
--password="${MYSQL_ROOT_PASSWORD}" \
 $database < $filename
echo "Done!"