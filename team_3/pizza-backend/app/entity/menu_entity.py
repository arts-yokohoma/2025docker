# app/entity/menu_entity.py
from pydantic import BaseModel
from typing import List, Optional
from datetime import datetime
from app.entity.size_entity import SizeBase, SiseOut, SizeUpdate
from app.entity.category_entity import CategoryOut

class MenuBase(BaseModel):
    name: str
    description: Optional[str]
    image_url: Optional[str]
    is_liked: bool

class MenuCreate(MenuBase):
    category_id: int
    sizes: Optional[List[SizeBase]] = []

class MenuOut(MenuBase):
    id: int
    create_time: datetime
    update_time: datetime
    category: CategoryOut
    sizes: List[SiseOut] = []

    model_config = {"from_attributes": True}

class MenuUpdate(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    image_url: Optional[str] = None
    is_liked : bool
    category_id: Optional[int] = None
    sizes: Optional[List[SizeUpdate]] = None

class MenuDeleteRequest(BaseModel):
    ids: List[int]