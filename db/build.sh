#!/usr/bin/env bash

mysql -h $MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < /data/www/db/migrations/1.create_checkins_table.sql