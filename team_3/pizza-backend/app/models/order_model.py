from sqlalchemy import Column, Integer, String, DateTime, ForeignKey
from app.infras.external.db.postgres_connection import Base
from app.common.timer import Timer
from sqlalchemy.orm import relationship

class Order(Base):
    __tablename__ = "orders"

    id = Column(Integer, primary_key=True, index=True)
    order_code = Column(String(255), nullable=False, unique=True, index=True)
    customer_name = Column(String(255), nullable=False)
    customer_id = Column(Integer, nullable=True)
    phone = Column(String(255), nullable=False)
    email = Column(String(255), nullable=False)
    address = Column(String(255), nullable=False)
    total_price = Column(Integer, nullable=False)
    order_date = Column(String(255), nullable=False)
    note = Column(String(255), nullable=True)
    status = Column(Integer, nullable=True, default=1)
    create_time = Column(DateTime, default=Timer.get_jst_now_from_utc, nullable=False)
    update_time = Column(DateTime, default=Timer.get_jst_now_from_utc, onupdate=Timer.get_jst_now_from_utc, nullable=False)

    items = relationship("OrderItem", back_populates="order", cascade="all, delete-orphan")

class OrderItem(Base):
    __tablename__ = "order_items"

    id = Column(Integer, primary_key=True, index=True)
    order_id = Column(Integer, ForeignKey("orders.id"), nullable=False)
    menu_id = Column(Integer, nullable=False)
    menu_name = Column(String(255), nullable=False)
    quantity = Column(Integer, default=1)
    price = Column(Integer, nullable=False)

    size = Column(String(10), nullable=False)

    order = relationship("Order", back_populates="items")