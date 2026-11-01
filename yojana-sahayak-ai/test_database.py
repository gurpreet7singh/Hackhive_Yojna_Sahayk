from database.mysql_connection import get_db_connection


connection = get_db_connection()

if connection:
    print("Python MySQL Connection Successful!")

    cursor = connection.cursor()

    cursor.execute("SHOW TABLES")

    tables = cursor.fetchall()

    print("\nTables in yojana_sahayak database:")

    for table in tables:
        print(table[0])

    cursor.close()
    connection.close()

else:
    print("Database connection failed.")