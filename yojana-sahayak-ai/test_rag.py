
from services.rag_service import generate_rag_answer


print("=" * 60)
print("YOJANA SAHAYAK - RAG TEST")
print("=" * 60)


# Test 1: English question
question_english = (
    "What is PM-KISAN and how much financial "
    "assistance do farmers receive?"
)

print("\nTEST 1: ENGLISH")
print("-" * 60)

result_english = generate_rag_answer(
    question=question_english,
    language="English"
)

print("\nAnswer:")
print(result_english["answer"])

print("\nSources:")
for source in result_english["sources"]:
    print(
        f"- {source['document']} | "
        f"Page {source['page_number']}"
    )


# Test 2: Hindi question
question_hindi = (
    "पीएम किसान योजना क्या है और किसानों को "
    "कितनी आर्थिक सहायता मिलती है?"
)

print("\n\nTEST 2: HINDI")
print("-" * 60)

result_hindi = generate_rag_answer(
    question=question_hindi,
    language="Hindi"
)

print("\nउत्तर:")
print(result_hindi["answer"])

print("\nस्रोत:")
for source in result_hindi["sources"]:
    print(
        f"- {source['document']} | "
        f"Page {source['page_number']}"
    )


print("\n" + "=" * 60)
print("RAG TEST COMPLETED")
print("=" * 60)
