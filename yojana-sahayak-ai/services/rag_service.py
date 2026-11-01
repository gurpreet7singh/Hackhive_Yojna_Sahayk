
from services.retrieval_service import search_documents
from google import genai
from dotenv import load_dotenv
import os


# Load environment variables
load_dotenv(
    dotenv_path=r"C:\xampp\yojana-sahayak-ai\.env",
    override=True
)

API_KEY = os.getenv("GEMINI_API_KEY", "").strip()

if not API_KEY:
    raise ValueError("Gemini API key not found in .env file")


# Initialize Gemini client
client = genai.Client(api_key=API_KEY)

MODEL_NAME = "models/gemini-3.6-flash"


def generate_rag_answer(question, language="English", top_k=5):

    # Step 1: Retrieve relevant document chunks
    retrieved_chunks = search_documents(
        query=question,
        top_k=top_k
    )

    # Handle empty retrieval
    if not retrieved_chunks:
        return {
            "answer": (
                "The official documents I have do not contain "
                "enough information to answer this question."
            ),
            "sources": []
        }

    # Step 2: Prepare context from retrieved documents
    context_parts = []
    sources = []

    for chunk in retrieved_chunks:

        text = chunk["text"]
        metadata = chunk["metadata"]

        document_name = metadata.get(
            "source",
            "Official Government Document"
        )

        page_number = metadata.get("page_number", "Unknown")

        context_parts.append(
            f"""
Source: {document_name}
Page: {page_number}

Content:
{text}
"""
        )

        # Collect source details for citations
        source = {
            "document": document_name,
            "page_number": page_number,
            "chunk_id": chunk["chunk_id"]
        }

        if source not in sources:
            sources.append(source)

    context = "\n\n".join(context_parts)

    # Step 3: Create a strict grounding prompt
    prompt = f"""
You are Yojana Sahayak, an AI assistant that helps citizens
understand Indian government welfare schemes.

STRICT RULES:

1. Answer ONLY using the information provided in the context.
2. Do not invent facts, eligibility conditions, benefits,
   dates, amounts, or government rules.
3. If the context does not contain enough information,
   clearly say that the available official documents
   do not provide sufficient information.
4. Do not make assumptions about a person's eligibility.
5. Explain the answer in simple, citizen-friendly language.
6. Answer in {language}.
7. Use numbered points or bullet points when helpful.
8. Mention relevant document names and page numbers
   in your answer using this format:
   [Source: Document Name, Page X]

USER QUESTION:
{question}

RETRIEVED OFFICIAL DOCUMENT CONTEXT:
{context}

Now provide a grounded answer in {language}.
"""

    # Step 4: Generate answer using Gemini with retry logic
    import time

    answer = None

    for attempt in range(3):
        try:
            response = client.models.generate_content(
                model=MODEL_NAME,
                contents=prompt
            )

            answer = response.text
            break

        except Exception as error:
            print(f"Gemini attempt {attempt + 1} failed: {error}")

            if attempt < 2:
                print("Retrying in 5 seconds...")
                time.sleep(5)

    if answer is None:
        answer = (
            "Sorry, our AI service is temporarily busy. "
            "Please try again in a few moments."
        )

    # Step 5: Return answer and source metadata
    return {
        "answer": answer,
        "sources": sources
    }

    # Step 5: Return answer and source metadata
    return {
        "answer": answer,
        "sources": sources
    }
