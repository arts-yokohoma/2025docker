from pydantic import BaseModel
from typing import Optional

class RoleCreate(BaseModel):
    name: str
    type: int

class RoleUpdate(BaseModel):
    name: Optional[str] = None
    type: type

class RoleOut(BaseModel):
    id: int
    name: str
    type: int

    model_config = {"from_attributes": True}
