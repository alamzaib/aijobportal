"""
FastAPI AI Service for Job Description Generation
"""
import os
import json
import httpx
import numpy as np
from typing import Optional, List, Dict, Any
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


class AnalyzeCvRequest(BaseModel):
    s3_url: Optional[str] = Field(None, description="S3 URL of the CV/resume file")
    raw_text: Optional[str] = Field(None, description="Raw text content of the CV/resume")


class ExperienceItem(BaseModel):
    company: str = Field(..., description="Company name")
    title: str = Field(..., description="Job title")
    from_date: str = Field(..., description="Start date")
    to_date: str = Field(..., description="End date (or 'Present' if current)")
    summary: str = Field(..., description="Job description/summary")


class EducationItem(BaseModel):
    institution: str = Field(..., description="School/University name")
    degree: Optional[str] = Field(None, description="Degree obtained")
    field: Optional[str] = Field(None, description="Field of study")
    year: Optional[str] = Field(None, description="Graduation year")


class AnalyzeCvResponse(BaseModel):
    name: str = Field(..., description="Full name")
    email: str = Field(..., description="Email address")
    phone: Optional[str] = Field(None, description="Phone number")
    skills: List[str] = Field(default_factory=list, description="List of skills")
    experiences: List[ExperienceItem] = Field(default_factory=list, description="Work experience")
    education: List[EducationItem] = Field(default_factory=list, description="Education history")


class MatchRequest(BaseModel):
    job_id: str = Field(..., description="Job ID")
    job_description: str = Field(..., description="Job description text")
    candidate_resume_parsed_json: Dict[str, Any] = Field(..., description="Parsed resume JSON from analyze-cv endpoint")


class MatchedSkill(BaseModel):
    skill: str = Field(..., description="Skill name")
    relevance: float = Field(..., description="Relevance score (0-1)")


class MatchResponse(BaseModel):
    match_score: float = Field(..., description="Match score from 0-100")
    top_skills: List[MatchedSkill] = Field(..., description="Top 3 matched skills with explanations")
    explanation: str = Field(..., description="Short explanation of the match")


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


@app.post("/ai/analyze-cv", response_model=AnalyzeCvResponse)
async def analyze_cv(request: AnalyzeCvRequest):
    """
    Analyze a CV/resume and extract structured information.
    
    Accepts either an S3 URL or raw text content.
    Returns standardized JSON with name, email, phone, skills, experiences, and education.
    """
    if not openai_client:
        raise HTTPException(
            status_code=500,
            detail="OpenAI API key not configured. Please set OPENAI_API_KEY environment variable."
        )
    
    if not request.s3_url and not request.raw_text:
        raise HTTPException(
            status_code=400,
            detail="Either 's3_url' or 'raw_text' must be provided"
        )
    
    try:
        # Get CV content
        cv_content = ""
        
        if request.raw_text:
            # Use raw text directly
            cv_content = request.raw_text
        elif request.s3_url:
            # For MVP, we'll try to fetch the file from S3 URL
            # Note: This assumes the URL is publicly accessible or we have proper auth
            # In production, you'd want to use proper S3 SDK with credentials
            try:
                async with httpx.AsyncClient(timeout=30.0) as client:
                    response = await client.get(request.s3_url)
                    response.raise_for_status()
                    # For PDF files, we'd need to extract text
                    # For MVP, assume it's a text file or we'll use OpenAI vision API
                    # For now, let's use a simple approach: try to decode as text
                    cv_content = response.text
            except Exception as e:
                # If direct fetch fails, we can use OpenAI vision API for PDFs
                # For MVP, we'll raise an error and suggest using raw_text
                raise HTTPException(
                    status_code=400,
                    detail=f"Could not fetch CV from S3 URL. Error: {str(e)}. Please provide raw_text instead."
                )
        
        if not cv_content.strip():
            raise HTTPException(
                status_code=400,
                detail="CV content is empty"
            )
        
        # Build the prompt for OpenAI
        system_prompt = """You are an expert CV/resume parser. Extract structured information from the provided CV/resume text.
        
Return a JSON object with the following structure:
{
    "name": "Full Name",
    "email": "email@example.com",
    "phone": "phone number or null",
    "skills": ["skill1", "skill2", ...],
    "experiences": [
        {
            "company": "Company Name",
            "title": "Job Title",
            "from": "Start Date (YYYY-MM or YYYY)",
            "to": "End Date (YYYY-MM or YYYY or 'Present')",
            "summary": "Job description and key achievements"
        }
    ],
    "education": [
        {
            "institution": "School/University Name",
            "degree": "Degree Type (e.g., Bachelor's, Master's)",
            "field": "Field of Study",
            "year": "Graduation Year"
        }
    ]
}

Extract all available information. If a field is not found, use null or empty array/list as appropriate.
For dates, use consistent format (preferably YYYY-MM or YYYY).
Return ONLY valid JSON, no additional text or explanation."""
        
        user_prompt = f"""Please parse the following CV/resume and extract the structured information:\n\n{cv_content}"""
        
        # Call OpenAI API
        # Try to use a model that supports JSON mode (gpt-3.5-turbo-1106 or newer)
        # Fallback to regular model if needed
        result_text = ""
        try:
            # Try with JSON mode first (requires gpt-3.5-turbo-1106 or newer)
            response = openai_client.chat.completions.create(
                model="gpt-3.5-turbo-1106",
                messages=[
                    {"role": "system", "content": system_prompt},
                    {"role": "user", "content": user_prompt}
                ],
                temperature=0.3,  # Lower temperature for more consistent parsing
                response_format={"type": "json_object"}  # Force JSON response
            )
            result_text = response.choices[0].message.content.strip()
        except Exception as e:
            # Fallback to regular model if JSON mode not supported
            # Extract JSON from text response if needed
            response = openai_client.chat.completions.create(
                model="gpt-3.5-turbo",
                messages=[
                    {"role": "system", "content": system_prompt + "\n\nIMPORTANT: Return ONLY valid JSON, no additional text or explanation."},
                    {"role": "user", "content": user_prompt}
                ],
                temperature=0.3
            )
            result_text = response.choices[0].message.content.strip()
            
            # Try to extract JSON from markdown code blocks if present
            import re
            json_match = re.search(r'```(?:json)?\s*(\{.*?\})\s*```', result_text, re.DOTALL)
            if json_match:
                result_text = json_match.group(1)
            else:
                # Try to find JSON object in the text
                json_match = re.search(r'\{.*\}', result_text, re.DOTALL)
                if json_match:
                    result_text = json_match.group(0)
        
        # Parse the JSON response
        parsed_data = json.loads(result_text)
        
        # Transform to match our response model
        # Handle experiences
        experiences = []
        for exp in parsed_data.get("experiences", []):
            experiences.append(ExperienceItem(
                company=exp.get("company", ""),
                title=exp.get("title", ""),
                from_date=exp.get("from", ""),
                to_date=exp.get("to", ""),
                summary=exp.get("summary", "")
            ))
        
        # Handle education
        education = []
        for edu in parsed_data.get("education", []):
            education.append(EducationItem(
                institution=edu.get("institution", ""),
                degree=edu.get("degree"),
                field=edu.get("field"),
                year=edu.get("year")
            ))
        
        return AnalyzeCvResponse(
            name=parsed_data.get("name", ""),
            email=parsed_data.get("email", ""),
            phone=parsed_data.get("phone"),
            skills=parsed_data.get("skills", []),
            experiences=experiences,
            education=education
        )
    
    except json.JSONDecodeError as e:
        raise HTTPException(
            status_code=500,
            detail=f"Failed to parse OpenAI response as JSON: {str(e)}"
        )
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Error analyzing CV: {str(e)}"
        )


def cosine_similarity(vec1: List[float], vec2: List[float]) -> float:
    """Calculate cosine similarity between two vectors."""
    vec1 = np.array(vec1)
    vec2 = np.array(vec2)
    
    dot_product = np.dot(vec1, vec2)
    norm1 = np.linalg.norm(vec1)
    norm2 = np.linalg.norm(vec2)
    
    if norm1 == 0 or norm2 == 0:
        return 0.0
    
    return float(dot_product / (norm1 * norm2))


def get_embedding(text: str, client: OpenAI) -> List[float]:
    """Get embedding for text using OpenAI."""
    try:
        response = client.embeddings.create(
            model="text-embedding-3-small",  # Using the smaller, cheaper model
            input=text
        )
        return response.data[0].embedding
    except Exception as e:
        raise Exception(f"Failed to get embedding: {str(e)}")


@app.post("/ai/match", response_model=MatchResponse)
async def match_candidate(request: MatchRequest):
    """
    Match a candidate's resume against a job description using OpenAI embeddings.
    
    Accepts job_id, job_description, and candidate_resume_parsed_json.
    Returns match_score (0-100) and explanation of top 3 matched skills.
    """
    if not openai_client:
        raise HTTPException(
            status_code=500,
            detail="OpenAI API key not configured. Please set OPENAI_API_KEY environment variable."
        )
    
    try:
        # Validate job description
        if not request.job_description or not request.job_description.strip():
            raise HTTPException(
                status_code=400,
                detail="Job description cannot be empty"
            )
        
        # Extract candidate information from parsed JSON
        candidate_skills = request.candidate_resume_parsed_json.get("skills", [])
        candidate_experiences = request.candidate_resume_parsed_json.get("experiences", [])
        candidate_education = request.candidate_resume_parsed_json.get("education", [])
        
        # Build candidate summary text
        candidate_text_parts = []
        
        # Add skills
        if candidate_skills:
            candidate_text_parts.append("Skills: " + ", ".join(candidate_skills))
        
        # Add experience summaries
        if candidate_experiences:
            exp_texts = []
            for exp in candidate_experiences:
                exp_text = f"{exp.get('title', '')} at {exp.get('company', '')}"
                if exp.get('summary'):
                    exp_text += f": {exp.get('summary', '')}"
                exp_texts.append(exp_text)
            candidate_text_parts.append("Experience: " + ". ".join(exp_texts))
        
        # Add education
        if candidate_education:
            edu_texts = []
            for edu in candidate_education:
                edu_parts = []
                if edu.get('degree'):
                    edu_parts.append(edu.get('degree'))
                if edu.get('field'):
                    edu_parts.append(edu.get('field'))
                if edu.get('institution'):
                    edu_parts.append(f"from {edu.get('institution')}")
                if edu_parts:
                    edu_texts.append(", ".join(edu_parts))
            if edu_texts:
                candidate_text_parts.append("Education: " + ". ".join(edu_texts))
        
        candidate_text = ". ".join(candidate_text_parts)
        
        if not candidate_text.strip():
            raise HTTPException(
                status_code=400,
                detail="Candidate resume must have at least skills, experience, or education information"
            )
        
        # Get embeddings
        job_embedding = get_embedding(request.job_description, openai_client)
        candidate_embedding = get_embedding(candidate_text, openai_client)
        
        # Calculate cosine similarity
        similarity = cosine_similarity(job_embedding, candidate_embedding)
        
        # Convert similarity (-1 to 1) to match score (0 to 100)
        # Normalize: similarity of 1.0 = 100, similarity of 0.0 = 50, similarity of -1.0 = 0
        match_score = max(0, min(100, (similarity + 1) * 50))
        
        # Find top matched skills by comparing individual skill embeddings with job description
        top_skills = []
        if candidate_skills:
            skill_scores = []
            for skill in candidate_skills[:20]:  # Limit to first 20 skills for performance
                try:
                    skill_embedding = get_embedding(skill, openai_client)
                    skill_similarity = cosine_similarity(job_embedding, skill_embedding)
                    skill_scores.append({
                        'skill': skill,
                        'relevance': max(0, min(1, (skill_similarity + 1) / 2))  # Normalize to 0-1
                    })
                except Exception as e:
                    # Skip skills that fail to embed
                    continue
            
            # Sort by relevance and take top 3
            skill_scores.sort(key=lambda x: x['relevance'], reverse=True)
            top_skills = skill_scores[:3]
        
        # Generate explanation
        if top_skills:
            skill_names = [s['skill'] for s in top_skills]
            explanation = f"Top matched skills: {', '.join(skill_names)}. "
        else:
            explanation = "Match based on overall profile similarity. "
        
        explanation += f"Overall match score: {match_score:.1f}/100 based on semantic similarity between job requirements and candidate profile."
        
        return MatchResponse(
            match_score=round(match_score, 2),
            top_skills=[MatchedSkill(skill=s['skill'], relevance=round(s['relevance'], 3)) for s in top_skills],
            explanation=explanation
        )
    
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Error matching candidate: {str(e)}"
        )

