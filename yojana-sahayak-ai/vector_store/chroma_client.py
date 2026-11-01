import chromadb
from dotenv import load_dotenv
import os

load_dotenv()

CHROMA_DB_PATH = os.getenv("CHROMA_DB_PATH", "./vector_store/chroma_db")

client = chromadb.PersistentClient(path=CHROMA_DB_PATH)

collection = client.get_or_create_collection(
    name="government_schemes",
    metadata={"hnsw:space": "cosine"}
)


def get_collection():
    return collection