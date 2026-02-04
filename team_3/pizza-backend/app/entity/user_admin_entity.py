from pydantic import BaseModel, EmailStr, field_serializer, constr
from typing import Optional
from datetime import datetime


class UserAdminOut(BaseModel):
    id: int
    name: str
    # user_name: str
    phone: Optional[str] = None
    email: Optional[EmailStr] = None
    role_type: int
    create_time: datetime
    update_time: Optional[datetime] = None

    model_config = {"from_attributes": True}

    @field_serializer("create_time", "update_time")
    def serialize_datetimes(self, value: datetime, _info):
        return value.strftime("%Y-%m-%dT%H:%M:%S") if value else None

class UserAdminCreate(BaseModel):
    name: str
    user_name: str
    # password: str
    role_type: int
    phone: Optional[str] = None
    email: Optional[EmailStr] = None


class UserAdminUpdate(BaseModel):
    name: Optional[str] = None
    phone: Optional[str] = None
    email: Optional[EmailStr] = None
    role_type: Optional[int] = None


class ChangePasswordSchema(BaseModel):
    old_password: constr(min_length=1)
    new_password: constr(min_length=1)


class SendCodeMail(BaseModel):
    user_name: str
    email: Optional[EmailStr]

class VerifyCode(BaseModel):
    user_name: str
    email: Optional[EmailStr]
    reset_code: str


class ChangeForgotPasswordSchema(BaseModel):
    user_name: str
    email: Optional[EmailStr]
    reset_code: str
    new_password: constr(min_length=1)