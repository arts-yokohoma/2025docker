from sqlalchemy import Column, Integer, String, ForeignKey, DateTime
from sqlalchemy.orm import relationship
from app.infras.external.db.postgres_connection import Base
from app.common.timer import Timer


class Schedule(Base):
    __tablename__ = "schedule"

    id = Column(Integer, primary_key=True, index=True)

    user_id = Column(Integer, ForeignKey("user_admin.id"), nullable=False)
    user = relationship("UserAdmin")

    target_date = Column(String, nullable=False)
    start_time = Column(String, nullable=False)
    end_time = Column(String, nullable=False)
    from_break_time = Column(String, nullable=True)
    to_break_time = Column(String, nullable=True)
    position = Column(String, nullable=True)
    actual_start_time = Column(String, nullable=True)
    actual_end_time = Column(String, nullable=True)
    create_by = Column(String, nullable=False)
    create_time = Column(DateTime(timezone=True), default=Timer.get_jst_now_from_utc())
    update_time = Column(
        DateTime(timezone=True),
        default=Timer.get_jst_now_from_utc(),
        onupdate=Timer.get_jst_now_from_utc(),
    )
