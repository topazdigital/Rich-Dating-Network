#!/bin/bash

MYSQL_DATADIR=/home/runner/mysql_data
MYSQL_RUN=/home/runner/mysql_run
MYSQL_LOGS=/home/runner/mysql_logs
MYSQL_CNF=/home/runner/mysql.cnf
APP_DIR=/home/runner/workspace

mkdir -p "$MYSQL_RUN" "$MYSQL_LOGS"

cat > "$MYSQL_CNF" << 'CONFEOF'
[mysqld]
datadir=/home/runner/mysql_data
socket=/home/runner/mysql_run/mysql.sock
pid-file=/home/runner/mysql_run/mysql.pid
log-error=/home/runner/mysql_logs/error.log
port=3306
bind-address=127.0.0.1
skip-name-resolve
innodb_use_native_aio=OFF
[client]
socket=/home/runner/mysql_run/mysql.sock
user=root
password=
CONFEOF

mysql_ready() {
    mysql --defaults-file="$MYSQL_CNF" -u root --connect-timeout=2 -e "SELECT 1" > /dev/null 2>&1
}

rm -f "$MYSQL_RUN/mysql.sock" "$MYSQL_RUN/mysql.pid"

echo "[start.sh] Starting MariaDB..."
mysqld --defaults-file="$MYSQL_CNF" 2>>"$MYSQL_LOGS/error.log" &

for i in $(seq 1 20); do
    if mysql_ready; then
        echo "[start.sh] MariaDB ready in ${i}s"
        break
    fi
    sleep 1
done

if ! mysql_ready; then
    echo "[start.sh] Normal start failed, trying skip-grant-tables mode..."
    kill $(cat "$MYSQL_RUN/mysql.pid" 2>/dev/null) 2>/dev/null
    sleep 2
    rm -f "$MYSQL_RUN/mysql.sock" "$MYSQL_RUN/mysql.pid"
    mysqld --defaults-file="$MYSQL_CNF" --skip-grant-tables 2>>"$MYSQL_LOGS/error.log" &
    sleep 6
fi

if mysql_ready; then
    echo "[start.sh] MariaDB is ready!"
    mysql --defaults-file="$MYSQL_CNF" -u root -e "CREATE DATABASE IF NOT EXISTS dating_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" > /dev/null 2>&1 || true
    TABLE_COUNT=$(mysql --defaults-file="$MYSQL_CNF" -u root dating_app -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='dating_app';" -s --skip-column-names 2>/dev/null || echo "0")
    if [ "$TABLE_COUNT" = "0" ] || [ -z "$TABLE_COUNT" ]; then
        echo "[start.sh] Loading app schema..."
        mysql --defaults-file="$MYSQL_CNF" -u root < "$APP_DIR/setup_db.sql" > /dev/null 2>&1 && echo "[start.sh] Schema loaded." || echo "[start.sh] Schema had some warnings"
    else
        echo "[start.sh] App tables exist (${TABLE_COUNT} tables). Skipping schema."
    fi
else
    echo "[start.sh] WARNING: MariaDB not responding, PHP server starting anyway..."
fi

echo "[start.sh] Starting PHP web server on 0.0.0.0:5000..."
cd "$APP_DIR"
exec php -S 0.0.0.0:5000 -t "$APP_DIR" "$APP_DIR/router.php" -d log_errors=1 -d error_log=/home/runner/php_errors.log -d error_reporting=32767
