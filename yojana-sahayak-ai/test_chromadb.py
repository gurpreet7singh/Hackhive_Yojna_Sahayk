from vector_store.chroma_client import get_collection
from sentence_transformers import SentenceTransformer

print("Loading embedding model...")

model = SentenceTransformer(
    "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
)

collection = get_collection()

documents = [
    "PM-KISAN provides income support to landholding farmer families.",
    "Under PM-KISAN, eligible farmers receive financial assistance.",
    "The PM-KISAN scheme is implemented by the Ministry of Agriculture."
]

ids = ["test_1", "test_2", "test_3"]

embeddings = model.encode(documents).tolist()

collection.add(
    ids=ids,
    documents=documents,
    embeddings=embeddings,
    metadatas=[
        {"scheme": "PM-KISAN", "page": 1},
        {"scheme": "PM-KISAN", "page": 2},
        {"scheme": "PM-KISAN", "page": 3}
    ]
)

print("\nDocuments successfully stored in ChromaDB!")
print("Total records:", collection.count())