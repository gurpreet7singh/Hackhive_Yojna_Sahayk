from database.mysql_connection import get_db_connection
from vector_store.chroma_client import get_collection
from services.embedding_service import generate_embeddings


DOCUMENT_ID = 3  # PM-KISAN guidelines document ID


print("Connecting to MySQL...")

connection = get_db_connection()

if not connection:
    print("Database connection failed!")
    exit()

cursor = connection.cursor(dictionary=True)

cursor.execute("""
    SELECT id, document_id, chunk_index, page_number, chunk_text
    FROM document_chunks
    WHERE document_id = %s
    ORDER BY chunk_index
""", (DOCUMENT_ID,))

chunks = cursor.fetchall()

cursor.close()
connection.close()

if not chunks:
    print("No chunks found for this document!")
    exit()

print(f"Retrieved {len(chunks)} chunks from MySQL.")

# Get ChromaDB collection
collection = get_collection()

# Remove previous test and document data for this document
existing = collection.get(where={"document_id": DOCUMENT_ID})

if existing["ids"]:
    collection.delete(ids=existing["ids"])
    print("Removed previous embeddings for this document.")

# Prepare data
texts = [chunk["chunk_text"] for chunk in chunks]

ids = [
    f"doc_{DOCUMENT_ID}_chunk_{chunk['chunk_index']}"
    for chunk in chunks
]

metadatas = [
    {
        "document_id": DOCUMENT_ID,
        "chunk_index": chunk["chunk_index"],
        "page_number": chunk["page_number"],
        "source": "PM-KISAN Operational Guidelines"
    }
    for chunk in chunks
]

# Generate embeddings
print("\nGenerating embeddings for PM-KISAN chunks...")
embeddings = generate_embeddings(texts)

# Store in ChromaDB
print("\nSaving embeddings to ChromaDB...")

collection.upsert(
    ids=ids,
    documents=texts,
    embeddings=embeddings,
    metadatas=metadatas
)

print("\nSUCCESS!")
print("Chunks processed:", len(chunks))
print("ChromaDB total records:", collection.count())