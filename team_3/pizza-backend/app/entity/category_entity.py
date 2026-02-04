from pydantic import BaseModel
from typing import List, Optional

class CategoryBase(BaseModel):
    category_name: str

class CategoryCreate(CategoryBase):
    pass

class CategoryUpdate(BaseModel):
    category_name: str | None = None

class CategoryOut(CategoryBase):
    id: int
    model_config = {"from_attributes": True}
    
class CategoryDeleteRequest(BaseModel):
    ids: List[int]