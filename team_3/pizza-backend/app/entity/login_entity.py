from pydantic import BaseModel
from typing import Optional
from datetime import datetime

class LoginSchema(BaseModel):
    username: str
    password: str

class TokenResponse(BaseModel):
    access_token: str
    token_type: str = "bearer"
