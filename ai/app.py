"""
FastAPI AI Service for Job Description Generation
"""
import os
from typing import Optional
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from openai import OpenAI

# Initialize FastAPI app
app = FastAPI(
    title="AI Job Portal - AI Service",
    description="AI service for generating job descriptions",
    version="1.0.0"
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure appropriately for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize OpenAI client
openai_client = None
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY")

if OPENAI_API_KEY:
    openai_client = OpenAI(api_key=OPENAI_API_KEY)


# Pydantic Models
class GenerateJobDescriptionRequest(BaseModel):
    title: str = Field(..., description="Job title")
    company_name: str = Field(..., description="Company name")
    prompts: Optional[str] = Field(None, description="Additional prompts or requirements")
    locale: str = Field(default="en", description="Locale/language code (e.g., 'en', 'es', 'fr')")


class GenerateJobDescriptionResponse(BaseModel):
    job_description: str = Field(..., description="Generated job description")
    title: str = Field(..., description="Job title")
    company_name: str = Field(..., description="Company name")
    locale: str = Field(..., description="Locale used")


class HealthResponse(BaseModel):
    status: str = Field(..., description="Service status")
    openai_configured: bool = Field(..., description="Whether OpenAI API key is configured")


# Endpoints
@app.get("/health", response_model=HealthResponse)
async def health():
    """Health check endpoint"""
    return HealthResponse(
        status="healthy",
        openai_configured=openai_client is not None
    )


@app.post("/ai/generate-job-description", response_model=GenerateJobDescriptionResponse)
async def generate_job_description(request: GenerateJobDescriptionRequest):
    """
    Generate a job description using OpenAI
    
    Accepts job title, company name, optional prompts, and locale.
    Returns a generated job description.
    """
    if not openai_client:
        raise HTTPException(
            status_code=500,
            detail="OpenAI API key not configured. Please set OPENAI_API_KEY environment variable."
        )
    
    try:
        # Build the prompt for OpenAI
        system_prompt = f"""You are an expert job description writer. Generate a professional, 
        comprehensive job description for the following position. Write in {request.locale} locale.
        
        Job Title: {request.title}
        Company: {request.company_name}
        """
        
        user_prompt = f"""Please create a detailed job description for a {request.title} position at {request.company_name}."""
        
        if request.prompts:
            user_prompt += f"\n\nAdditional requirements:\n{request.prompts}"
        
        user_prompt += "\n\nInclude sections for: Job Overview, Key Responsibilities, Required Qualifications, Preferred Qualifications, and Benefits/Compensation if applicable."
        
        # Call OpenAI API
        response = openai_client.chat.completions.create(
            model="gpt-3.5-turbo",
            messages=[
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_prompt}
            ],
            temperature=0.7,
            max_tokens=1500
        )
        
        job_description = response.choices[0].message.content.strip()
        
        return GenerateJobDescriptionResponse(
            job_description=job_description,
            title=request.title,
            company_name=request.company_name,
            locale=request.locale
        )
    
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Error generating job description: {str(e)}"
        )

