from vector_store.chroma_client import get_collection
from services.embedding_service import generate_query_embedding


def search_documents(query, top_k=5):

    collection = get_collection()

    query_embedding = generate_query_embedding(query)

    results = collection.query(
        query_embeddings=[query_embedding],
        n_results=top_k,
        include=["documents", "metadatas", "distances"]
    )

    retrieved_chunks = []

    for i in range(len(results["ids"][0])):

        retrieved_chunks.append({
            "chunk_id": results["ids"][0][i],
            "text": results["documents"][0][i],
            "metadata": results["metadatas"][0][i],
            "distance": results["distances"][0][i]
        })

    # Sort by closest semantic match
    retrieved_chunks.sort(key=lambda x: x["distance"])

    return retrieved_chunks