from sqlalchemy.orm import Session
from app.models.holiday_model import Holiday
from app.entity.holiday_entity import HolidayCreate, HolidayUpdate
import logging


class HolidayRepository:
    def __init__(self, logger: logging.Logger, db: Session):
        self.logger = logger
        self.db = db

    def _create_holiday(self, holiday_data: HolidayCreate) -> Holiday:
        try:
            holiday = Holiday(date=holiday_data.date)
            self.db.add(holiday)
            self.db.commit()
            self.db.refresh(holiday)
            self.logger.info(f"Created holiday id={holiday.id}")
            return holiday
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_holidays(self):
        try:
            return self.db.query(Holiday).all()
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_holiday_by_id(self, holiday_id: int):
        try:
            return self.db.query(Holiday).filter(Holiday.id == holiday_id).first()
        except Exception as e:
            self.logger.exception(e)
            raise
    
    def _get_holiday_by_date(self, holiday_data: HolidayCreate):
        try:
            exists = (
                self.db.query(Holiday)
                .filter(Holiday.date == holiday_data.date)
                .first()
                )
            return exists
        except Exception as e:
            self.logger.exception(e)
            raise

    def _update_holiday(self, holiday_id: int, holiday_data: HolidayUpdate) -> Holiday:
        try:
            holiday = self._get_holiday_by_id(holiday_id)
            if not holiday:
                self.logger.warning(f"Holiday id={holiday_id} not found")
                return None

            updated = False
            if holiday_data.date is not None:
                holiday.date = holiday_data.date
                updated = True

            if updated:
                self.db.commit()
                self.db.refresh(holiday)
                self.logger.info(f"holiday id={holiday.id} updated successfully")
            else:
                self.logger.info(f"No changes detected for holiday id={holiday.id}")
            return holiday
        except Exception as e:
            self.logger.exception(e)
            raise

    def _delete_holiday(self, category_is: list[int]) -> Holiday:
        try:
            self.db.query(Holiday).filter(Holiday.id.in_(category_is)).delete(synchronize_session=False)
            self.db.commit()
        except Exception as e:
            self.logger.exception(e)
            raise
