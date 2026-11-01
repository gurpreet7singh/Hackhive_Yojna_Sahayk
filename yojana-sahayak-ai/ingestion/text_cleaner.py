import re


def clean_text(text):

    if not text:
        return ""

    # Replace multiple spaces with a single space
    text = re.sub(r"[ \t]+", " ", text)

    # Remove excessive blank lines
    text = re.sub(r"\n\s*\n+", "\n\n", text)

    # Remove spaces at the beginning and end of lines
    text = "\n".join(
        line.strip() for line in text.splitlines()
    )

    # Remove unwanted control characters
    text = re.sub(r"[\x00-\x08\x0B\x0C\x0E-\x1F]", "", text)

    return text.strip()