from sqlalchemy.orm import Session
from app.models.category_model import Category
from app.entity.category_entity import CategoryCreate, CategoryUpdate
import logging


class CategoryRepository:
    def __init__(self, logger: logging.Logger, db: Session):
        self.logger = logger
        self.db = db

    def _create_category(self, category_data: CategoryCreate) -> Category:
        try:
            category = Category(category_name=category_data.category_name)
            self.db.add(category)
            self.db.commit()
            self.db.refresh(category)
            self.logger.info(f"Created category id={category.id}")
            return category
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_categories(self):
        try:
            return self.db.query(Category).all()
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_category_by_id(self, category_id: int):
        try:
            return self.db.query(Category).filter(Category.id == category_id).first()
        except Exception as e:
            self.logger.exception(e)
            raise

    def _update_category(self, category_id: int, category_data: CategoryUpdate) -> Category:
        try:
            category = self._get_category_by_id(category_id)
            if not category:
                self.logger.warning(f"Category id={category_id} not found")
                return None

            updated = False
            if category_data.category_name is not None:
                category.category_name = category_data.category_name
                updated = True

            if updated:
                self.db.commit()
                self.db.refresh(category)
                self.logger.info(f"Category id={category.id} updated successfully")
            else:
                self.logger.info(f"No changes detected for category id={category.id}")
            return category
        except Exception as e:
            self.logger.exception(e)
            raise

    def _delete_category(self, category_is: list[int]) -> Category:
        try:
            self.db.query(Category).filter(Category.id.in_(category_is)).delete(synchronize_session=False)
            self.db.commit()
        except Exception as e:
            self.logger.exception(e)
            raise
