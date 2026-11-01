from sentence_transformers import SentenceTransformer

print("Loading multilingual embedding model...")

model = SentenceTransformer(
    "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
)

print("Model loaded successfully!")

sentences = [
    "What is PM-KISAN?",
    "पीएम किसान योजना क्या है?"
]

embeddings = model.encode(sentences)

print("\nEmbeddings generated successfully!")
print("Number of sentences:", len(embeddings))
print("Embedding dimensions:", len(embeddings[0]))