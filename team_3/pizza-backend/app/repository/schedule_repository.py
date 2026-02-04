from sqlalchemy.orm import Session
from app.models.schedule_model import Schedule
from app.entity.schedule_entity import ScheduleCreate, ScheduleDeleteRequest, ScheduleUpdate
import logging
from app.models.user_admin_model import UserAdmin


class ScheduleRepository:
    def __init__(self, logger: logging.Logger, db: Session):
        self.logger = logger
        self.db = db

    def _create_schedule(
        self, schedule_data: ScheduleCreate, create_by: str
    ) -> Schedule:
        try:
            schedule_dict = schedule_data.dict()
            schedule_dict["create_by"] = create_by
            schedule = Schedule(**schedule_dict)
            self.db.add(schedule)
            self.db.commit()
            self.db.refresh(schedule)
            return schedule
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_schedules(self):
        try:
            return self.db.query(Schedule).all()
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_schedule_by_id(self, schedule_id: int):
        try:
            return self.db.query(Schedule).filter(Schedule.id == schedule_id).first()
        except Exception as e:
            self.logger.exception(e)
            raise
    
    def _get_schedule_by_user_id(self, user_id: int, target_date):
        try:
            return self.db.query(Schedule).filter((Schedule.user_id == user_id) & (Schedule.target_date == target_date)).first()
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_user_by_id(self, user_id: int):
        try:
            return (
                self.db.query(UserAdmin)
                .filter(UserAdmin.id == user_id, UserAdmin.is_deleted == False)
                .first()
            )
        except Exception as e:
            self.logger.exception(e)
            raise

    def _update_schedule(
        self, schedule_id: int, schedule_data: ScheduleUpdate
    ) -> Schedule:
        try:
            schedule = self._get_schedule_by_id(schedule_id)
            if not schedule:
                self.logger.warning(f"Schedule id={schedule_id} not found")
                return None

            updated = False
            for field, value in schedule_data.dict(exclude_unset=True).items():
                setattr(schedule, field, value)
                updated = True

            if updated:
                self.db.commit()
                self.db.refresh(schedule)
            return schedule
        except Exception as e:
            self.logger.exception(e)
            raise
    
    def _update_actual_schedule(self, schedule: Schedule) -> Schedule:
        try:
            self.db.commit()
            self.db.refresh(schedule)
            return schedule
        except Exception as e:
            self.logger.exception(e)
            raise

    def _delete_schedules(self, schedule_ids: ScheduleDeleteRequest):
        try:
            self.db.query(Schedule).filter(Schedule.id.in_(schedule_ids)).delete(
                synchronize_session=False
            )
            self.db.commit()
        except Exception as e:
            self.logger.exception(e)
            raise
