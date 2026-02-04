from sqlalchemy import Column, Integer, String, Boolean, DateTime
from app.infras.external.db.postgres_connection import Base
from app.common.timer import Timer
from passlib.hash import argon2
from app.common.constant import PASSWORD_DEFAULT

class UserAdmin(Base):
    __tablename__ = "user_admin"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String, nullable=False)
    user_name = Column(String, unique=True, nullable=False)
    phone = Column(String, nullable=True)
    email = Column(String, unique=True, nullable=True)
    is_deleted = Column(Boolean, default=False)
    role_type = Column(Integer, nullable=False)
    create_time = Column(DateTime(timezone=True), default=Timer.get_jst_now_from_utc())
    update_time = Column(DateTime(timezone=True), default=Timer.get_jst_now_from_utc(), onupdate=Timer.get_jst_now_from_utc())
    password = Column(String, nullable=False, default=argon2.hash(PASSWORD_DEFAULT))

    reset_code = Column(String, nullable=True)
    reset_code_sent_at = Column(DateTime(timezone=True), nullable=True)
