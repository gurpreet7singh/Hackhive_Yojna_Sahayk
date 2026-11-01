from vector_store.chroma_client import get_collection

collection = get_collection()

# Remove sample records
collection.delete(ids=["test_1", "test_2", "test_3"])

print("Test records removed.")
print("Remaining records:", collection.count())