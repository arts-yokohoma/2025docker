from sqlalchemy.orm import Session
from app.models.role_model import Role
from app.entity.role_entity import RoleCreate, RoleUpdate


class RoleRepository:
    def __init__(self, db, logger):
        self.db: Session = db
        self.logger = logger

    def _get_role_by_id(self, role_type: int):
        try:
            return self.db.query(Role).filter(Role.type == role_type).first()
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_role_by_name(self, name: str, type: int):
        try:
            return self.db.query(Role).filter((Role.name == name) | (Role.type == type)).first()
        except Exception as e:
            self.logger.exception(e)
            raise

    def _create_role(self, role: RoleCreate):
        try:
            new_role = Role(name=role.name, type=role.type)
            self.db.add(new_role)
            self.db.commit()
            self.db.refresh(new_role)
            return new_role
        except Exception as e:
            self.logger.exception(e)
            raise

    def _update_role(self, role: Role, role_data: RoleUpdate):
        try:
            if role_data.name:
                role.name = role_data.name
            if role_data.type:
                role.type = role_data.type
            self.db.commit()
            self.db.refresh(role)
            return role
        except Exception as e:
            self.logger.exception(e)
            raise

    def _delete_role(self, role: Role):
        try:
            self.db.delete(role)
            self.db.commit()
        except Exception as e:
            self.logger.exception(e)
            raise

    def _list_roles(self):
        try:
            return self.db.query(Role).all()
        except Exception as e:
            self.logger.exception(e)
            raise
