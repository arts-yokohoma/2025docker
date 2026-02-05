# app/entity/order_entity.py
from pydantic import BaseModel, EmailStr
from typing import List, Optional
from datetime import datetime
from enum import Enum

class OrderStatusEnum(int, Enum):
    PENDING = 1
    PROCESSING = 2
    COMPLETED = 3
    CANCEL = 4

class OrderItemCreate(BaseModel):
    size: str
    menu_id: int
    menu_name: str
    quantity: int
    price: int

class OrderItemRead(OrderItemCreate):
    id: int
    model_config = {
        "from_attributes": True 
    }


class OrderCreate(BaseModel):
    customer_name: str
    customer_id: Optional[int] = None
    phone: str
    address: str
    email: EmailStr
    total_price: int
    order_date: Optional[str] = None
    note: Optional[str] = None
    items: List[OrderItemCreate]


class OrderOut(OrderCreate):
    id: int
    status: Optional[OrderStatusEnum]
    order_code: str
    create_time: datetime
    update_time: datetime
    items: List[OrderItemRead]

    model_config = {
        "from_attributes": True 
    }

class RevenueOut(BaseModel):
    month: str
    priceTotal: int    

class OrderUpdate(BaseModel):
    status: Optional[OrderStatusEnum]