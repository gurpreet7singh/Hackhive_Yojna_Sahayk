from sentence_transformers import SentenceTransformer

MODEL_NAME = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"

print("Loading multilingual embedding model...")
model = SentenceTransformer(MODEL_NAME)


def generate_embeddings(texts):
    embeddings = model.encode(
        texts,
        batch_size=32,
        show_progress_bar=True
    )
    return embeddings.tolist()


def generate_query_embedding(text):
    embedding = model.encode([text])
    return embedding[0].tolist()