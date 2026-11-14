#!/bin/sh
set -eu

psql \
    --username "$POSTGRES_USER" \
    --dbname "$POSTGRES_DB" \
    --set=ON_ERROR_STOP=1 \
    --set=database_name="$POSTGRES_DB" \
    --set=test_user="$COFFEE_LAB_TEST_DB_USERNAME" \
    --file /opt/coffee-lab/create_test_role.sql
