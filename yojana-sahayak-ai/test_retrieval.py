from services.retrieval_service import search_documents


questions = [
    "What is PM-KISAN and what benefits does it provide?",
    "Who is eligible to receive financial assistance under this scheme?",
    "पीएम किसान योजना के तहत किसानों को कितनी सहायता मिलती है?",
    "किन किसानों को इस योजना का लाभ नहीं मिल सकता?"
]

for question in questions:

    print("\n" + "=" * 80)
    print("QUESTION:", question)
    print("=" * 80)

    results = search_documents(question, top_k=3)

    for index, result in enumerate(results, start=1):

        print(f"\nResult {index}")
        print("Page:", result["metadata"].get("page_number"))
        print("Distance:", round(result["distance"], 4))
        print("Text:")
        print(result["text"][:500])