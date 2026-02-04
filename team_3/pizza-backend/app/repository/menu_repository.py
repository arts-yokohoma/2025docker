# app/repository/menu_repository.py
from sqlalchemy.orm import Session
from app.models.menu_model import Menu
from app.models.size_model import Size
from app.entity.menu_entity import MenuCreate, MenuUpdate
from app.entity.size_entity import SizeBase
import logging

class MenuRepository:
    def __init__(self, logger: logging.Logger, db: Session):
        self.logger = logger
        self.db = db

    def _create_menu(self, menu_data: MenuCreate) -> Menu:
        try:
            menu = Menu(
                name=menu_data.name,
                description=menu_data.description,
                image_url=menu_data.image_url,
                category_id =menu_data.category_id,
                is_liked = menu_data.is_liked
            )
            self.db.add(menu)
            self.db.commit()
            self.db.refresh(menu)

            if menu_data.sizes:
                for sizes in menu_data.sizes:
                    sizes = Size(
                        menu_id=menu.id,
                        size=sizes.size,
                        price=sizes.price,
                        description=sizes.description
                    )
                    self.db.add(sizes)
                self.db.commit()
            return menu
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_menus(self):
        try:
            return self.db.query(Menu).all()
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_menu_by_id(self, menu_id: int):
        try:
            return self.db.query(Menu).filter(Menu.id == menu_id).first()
        except Exception as e:
            self.logger.exception(e)
            raise

    def _update_menu(self, menu_id: int, menu_data: MenuUpdate) -> Menu:
        try:
            menu = self._get_menu_by_id(menu_id)
            if not menu:
                self.logger.warning(f"Menu id={menu_id} not found")
                return None

            updated = False
            for field in ['name', 'description', 'image_url', 'is_liked']:
                value = getattr(menu_data, field)
                if value is not None:
                    setattr(menu, field, value)
                    updated = True
                    
            if menu_data.category_id != menu.category_id:
                menu.category_id = menu_data.category_id
                updated = True

            if menu_data.sizes is not None:
                self.db.query(Size).filter(Size.menu_id == menu.id).delete()
                for size in menu_data.sizes:
                    new_size = Size(
                        menu_id=menu.id,
                        size=size.size,
                        price=size.price or menu.price,
                        description=size.description or menu.description,
                    )
                    self.db.add(new_size)
                updated = True
            if updated:
                self.db.commit()
                self.db.refresh(menu)
                self.logger.info(f"Menu id={menu.id} updated successfully")
            else:
                self.logger.info(f"No changes detected for menu id={menu.id}")
            return menu
        except Exception as e:
            self.logger.exception(e)
            raise

    def _delete_menus(self, menu_ids: list[int]):
        try:
            self.db.query(Size).filter(Size.menu_id.in_(menu_ids)).delete(synchronize_session=False)
            self.db.query(Menu).filter(Menu.id.in_(menu_ids)).delete(synchronize_session=False)
            self.db.commit()
        except Exception as e :
            self.logger.exception(e)
            raise