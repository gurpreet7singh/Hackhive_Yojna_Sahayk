
from google import genai
from dotenv import load_dotenv
import os

load_dotenv()

api_key = os.getenv("GEMINI_API_KEY", "").strip()

client = genai.Client(api_key=api_key)

print("Checking available Gemini models...\n")

for model in client.models.list():
    if "generateContent" in model.supported_actions:
        print(model.name)
