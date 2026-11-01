
from google import genai
from dotenv import load_dotenv
import os

load_dotenv()

API_KEY = os.getenv("GEMINI_API_KEY")

if not API_KEY:
    print("ERROR: API key not found!")
    exit()

client = genai.Client(api_key=API_KEY)

response = client.models.generate_content(
    model="gemini-3.8-flash",
    contents="Explain PM-KISAN in two simple sentences."
)

print("\nGemini Response:")
print(response.text)
