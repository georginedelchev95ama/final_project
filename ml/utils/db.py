import os
import mysql.connector


def get_db():
    return mysql.connector.connect(
        host=os.environ.get('MYSQL_ADDON_HOST', 'localhost'),
        database=os.environ.get('MYSQL_ADDON_DB', ''),
        user=os.environ.get('MYSQL_ADDON_USER', ''),
        password=os.environ.get('MYSQL_ADDON_PASSWORD', ''),
        port=int(os.environ.get('MYSQL_ADDON_PORT', 3306)),
        connection_timeout=5,
    )
