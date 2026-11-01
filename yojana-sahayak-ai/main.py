
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from services.rag_service import generate_rag_answer


# Initialize FastAPI
app = FastAPI(
    title="Yojana Sahayak API",
    description="AI-powered multilingual government scheme assistant",
    version="1.0.0"
)


# Configure CORS for PHP frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Restrict this before production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# Request model
class ChatRequest(BaseModel):
    question: str = Field(
        ...,
        min_length=2,
        max_length=1000
    )
    language: str = "English"


# Response model
class ChatResponse(BaseModel):
    answer: str
    sources: list[dict]


# Root endpoint
@app.get("/")
def home():
    return {
        "message": "Welcome to Yojana Sahayak API",
        "status": "running"
    }


# Health check endpoint
@app.get("/health")
def health_check():
    return {
        "status": "healthy",
        "service": "Yojana Sahayak AI"
    }


# Chat endpoint
@app.post("/chat", response_model=ChatResponse)
def chat(request: ChatRequest):

    result = generate_rag_answer(
        question=request.question,
        language=request.language
    )

    return ChatResponse(
        answer=result["answer"],
        sources=result["sources"]
    )
