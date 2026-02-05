from sqlalchemy import Column, Integer, String, DateTime, ForeignKey
from sqlalchemy.orm import relationship
from app.infras.external.db.postgres_connection import Base
from app.common.timer import Timer

class Size(Base):
    __tablename__ = "size"

    id = Column(Integer, primary_key=True, index=True)
    menu_id = Column(Integer, ForeignKey("menu.id"), nullable=False)
    size = Column(String(255), nullable=True)
    price = Column(Integer, nullable=True)
    description = Column(String(255), nullable=False)
    create_time = Column(DateTime, default=Timer.get_jst_now_from_utc(), nullable=False)
    update_time = Column(DateTime, default=Timer.get_jst_now_from_utc(), onupdate=Timer.get_jst_now_from_utc(), nullable=False)

    menu = relationship("Menu", back_populates="sizes", passive_deletes=True)