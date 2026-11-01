from database.mysql_connection import get_db_connection

from ingestion.pdf_loader import extract_pdf_text
from ingestion.text_cleaner import clean_text
from ingestion.chunker import chunk_text


# Step 1: Connect to MySQL

connection = get_db_connection()

if not connection:
    print("Database connection failed.")
    exit()


cursor = connection.cursor(dictionary=True)


# Step 2: Get PM-KISAN document

document_id = 3  # Replace with actual PM-KISAN document ID

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
    print("Document not found.")
    exit()


print("Document:", document["document_title"])


# Step 3: Extract PDF text

result = extract_pdf_text(
    document["stored_file_path"]
)

print("Pages extracted:", len(result["extracted_pages"]))


# Step 4: Clean extracted text

cleaned_pages = []

for page in result["extracted_pages"]:

    cleaned_text = clean_text(page["text"])

    cleaned_pages.append({
        "page_number": page["page_number"],
        "text": cleaned_text
    })


print("Text cleaning completed.")


# Step 5: Generate chunks

chunks = chunk_text(
    cleaned_pages,
    chunk_size=1000,
    overlap=200
)


print("\nTotal chunks generated:", len(chunks))


# Step 6: Display sample chunks

for index, chunk in enumerate(chunks[:3], start=1):

    print(f"\n--- CHUNK {index} ---")

    print("Page:", chunk["page_number"])

    print("Text:")

    print(chunk["chunk_text"][:500])