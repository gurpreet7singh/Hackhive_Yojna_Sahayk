from database.mysql_connection import get_db_connection
from ingestion.pdf_loader import extract_pdf_text


# Connect to MySQL
connection = get_db_connection()

if not connection:
    print("Database connection failed.")
    exit()


cursor = connection.cursor(dictionary=True)


# Retrieve document information
document_id = 3

cursor.execute(
    """
    SELECT document_title, stored_file_path
    FROM scheme_documents
    WHERE id = %s
    """,
    (document_id,)
)

document = cursor.fetchone()

cursor.close()
connection.close()


if not document:
    print("Document not found in database.")
    exit()


print("Document:", document["document_title"])
print("\nExtracting PDF text...\n")


# Extract PDF text
result = extract_pdf_text(
    document["stored_file_path"]
)


print("Total Pages:", result["total_pages"])

print(
    "Pages with extracted text:",
    len(result["extracted_pages"])
)


# Display first page text
if result["extracted_pages"]:

    print("\n--- FIRST PAGE TEXT ---\n")

    print(
        result["extracted_pages"][0]["text"][:2000]
    )

else:

    print("No text extracted from this PDF.")