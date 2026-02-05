from pydantic import BaseModel
from typing import List, Optional

class HolidayBase(BaseModel):
    date: str

class HolidayCreate(HolidayBase):
    pass

class HolidayUpdate(BaseModel):
    date: str | None = None

class HolidayOut(HolidayBase):
    id: int
    model_config = {"from_attributes": True}
    
class HolidayDeleteRequest(BaseModel):
    ids: List[int]