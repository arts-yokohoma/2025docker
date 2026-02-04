from pydantic import BaseModel, field_serializer
from typing import Optional, List
from datetime import datetime

class UserBase(BaseModel):
    id: int
    name: str
    model_config = {"from_attributes": True}


class ScheduleBase(BaseModel):
    user_id: int
    target_date: str
    start_time: str
    end_time: str
    from_break_time: Optional[str] = None
    to_break_time: Optional[str] = None
    position: Optional[str] = None
    actual_start_time: Optional[str] = None
    actual_end_time: Optional[str] = None

class ScheduleCreate(ScheduleBase):
    pass

class ScheduleUpdate(BaseModel):
    user_id: int
    target_date: Optional[str]
    start_time: Optional[str]
    end_time: Optional[str]
    from_break_time: Optional[str] = None
    to_break_time: Optional[str] = None
    position: Optional[str]
    actual_start_time: Optional[str]
    actual_end_time: Optional[str]

class ScheduleOut(ScheduleBase):
    id: int
    user: Optional[UserBase]
    create_time: datetime
    update_time: datetime
    create_by: str

    model_config = {"from_attributes": True}
    @field_serializer("create_time", "update_time")
    def serialize_datetimes(self, value: Optional[datetime], _info):
        return value.strftime("%Y-%m-%dT%H:%M:%S") if value else None


class ScheduleActionUpdate(BaseModel):
    user_id: int
    type: int
    target_date: str


class ScheduleDeleteRequest(BaseModel):
    ids: List[int]