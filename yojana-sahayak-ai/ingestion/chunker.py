def chunk_text(
    extracted_pages,
    chunk_size=1000,
    overlap=200
):

    chunks = []

    for page in extracted_pages:

        page_number = page["page_number"]
        text = page["text"]

        if not text.strip():
            continue

        start = 0
        text_length = len(text)

        while start < text_length:

            end = start + chunk_size

            chunk = text[start:end].strip()

            if chunk:

                chunks.append({
                    "page_number": page_number,
                    "chunk_text": chunk
                })

            start += chunk_size - overlap

    return chunks