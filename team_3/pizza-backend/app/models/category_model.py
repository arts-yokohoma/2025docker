from sqlalchemy import Column, Integer, String
from sqlalchemy.orm import relationship
from app.infras.external.db.postgres_connection import Base

class Category(Base):
    __tablename__ = "category"

    id = Column(Integer, primary_key=True, index=True)
    category_name = Column(String(255), nullable=False)
    menus = relationship("Menu", back_populates="category", cascade="all, delete-orphan")
