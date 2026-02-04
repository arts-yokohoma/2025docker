from pydantic import BaseModel
from typing import List, Optional
from datetime import datetime

class SizeBase(BaseModel):
    size: Optional[str]
    price: Optional[int]
    description: str

class SizeCreate(SizeBase):
    menu_id: int

class SiseOut(SizeBase):
    id: int
    create_time: datetime
    update_time: datetime

    model_config = {"from_attributes": True}


class SizeUpdate(BaseModel):
    id: Optional[int] = None
    size: Optional[str] = None
    price: Optional[int] = None
    description: Optional[str] = None