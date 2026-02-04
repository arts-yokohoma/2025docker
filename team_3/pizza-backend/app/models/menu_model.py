from sqlalchemy import Column, Integer, String, DateTime, ForeignKey, Boolean, Text
from sqlalchemy.orm import relationship
from app.infras.external.db.postgres_connection import Base
from app.common.timer import Timer

class Menu(Base):
    __tablename__ = "menu"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(255), nullable=False)
    description = Column(String(255), nullable=True)
    image_url = Column(Text, nullable=True)
    is_liked = Column(Boolean, default=False)

    category_id = Column(Integer, ForeignKey("category.id"), nullable=False)

    category = relationship("Category", back_populates="menus")

    sizes = relationship("Size", back_populates="menu", cascade="all, delete-orphan")

    create_time = Column(DateTime, default=Timer.get_jst_now_from_utc(), nullable=False)
    update_time = Column(DateTime, default=Timer.get_jst_now_from_utc(), onupdate=Timer.get_jst_now_from_utc(), nullable=False)

from app.models.category_model import Category
from app.models.size_model import Size