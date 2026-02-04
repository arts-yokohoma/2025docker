from sqlalchemy import Column, Integer, String
from app.infras.external.db.postgres_connection import Base

class Role(Base):
    __tablename__ = "roles"

    id = Column(Integer, primary_key=True, index=True)
    type = Column(Integer, unique=True, nullable=False)
    name = Column(String, unique=True, nullable=False)
