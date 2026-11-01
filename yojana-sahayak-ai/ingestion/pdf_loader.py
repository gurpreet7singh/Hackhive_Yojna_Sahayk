import pymupdf
import os


def extract_pdf_text(file_path):

    if not os.path.exists(file_path):
        raise FileNotFoundError(
            f"PDF file not found: {file_path}"
        )

    document = pymupdf.open(file_path)

    extracted_pages = []

    for page_number, page in enumerate(document, start=1):

        text = page.get_text()

        if text.strip():

            extracted_pages.append({
                "page_number": page_number,
                "text": text.strip()
            })

    total_pages = len(document)

    document.close()

    return {
        "total_pages": total_pages,
        "extracted_pages": extracted_pages
    }