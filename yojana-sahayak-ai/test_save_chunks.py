from database.mysql_connection import get_db_connection
from ingestion.pdf_loader import extract_pdf_text
from ingestion.text_cleaner import clean_text
from ingestion.chunker import chunk_text


# Document ID from MySQL
document_id = 3

# Connect to database
connection = get_db_connection()

if not connection:
    print("Database connection failed!")
    exit()

cursor = connection.cursor(dictionary=True)

# Get document details
cursor.execute("""
    SELECT id, document_title, stored_file_path
    FROM scheme_documents
    WHERE id = %s
""", (document_id,))

document = cursor.fetchone()

if not document:
    print("Document not found!")
    cursor.close()
    connection.close()
    exit()

print(f"Processing: {document['document_title']}")

# Extract PDF text
extracted_data = extract_pdf_text(document["stored_file_path"])

# Clean text
cleaned_pages = []

for page in extracted_data["extracted_pages"]:
    cleaned_pages.append({
        "page_number": page["page_number"],
        "text": clean_text(page["text"])
    })

# Generate chunks
chunks = chunk_text(cleaned_pages)

print(f"Chunks generated: {len(chunks)}")

# Remove previous chunks for this document
cursor.execute("""
    DELETE FROM document_chunks
    WHERE document_id = %s
""", (document_id,))

# Insert new chunks
insert_query = """
    INSERT INTO document_chunks
    (document_id, chunk_index, page_number, chunk_text, token_count)
    VALUES (%s, %s, %s, %s, %s)
"""

for index, chunk in enumerate(chunks):
    cursor.execute(
        insert_query,
        (
            document_id,
            index,
            chunk["page_number"],
            chunk["chunk_text"],
            len(chunk["chunk_text"].split())
        )
    )

# Commit changes
connection.commit()

print(f"Successfully saved {len(chunks)} chunks to MySQL!")

cursor.close()
connection.close()