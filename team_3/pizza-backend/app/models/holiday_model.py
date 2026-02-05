from sqlalchemy import Column, Integer, String
from app.infras.external.db.postgres_connection import Base

class Holiday(Base):
    __tablename__ = "holiday"

    id = Column(Integer, primary_key=True, index=True)
    date = Column(String, nullable=False)
    