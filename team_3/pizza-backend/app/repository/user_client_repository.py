from sqlalchemy.orm import Session
from app.models.user_model import User
from app.entity.user_entity import UserCreate, UserUpdate


class UserRepository:
    def __init__(self, db, logger):
        self.db: Session = db
        self.logger = logger

    def _get_by_username(self, username: str, email: str | None = None):
        try:
            tmp_filter = (User.user_name == username) | (
                User.email == email if email else None
            )
            return self.db.query(User).filter(tmp_filter).first()
        except Exception as e:
            self.logger.exception(e)
            raise

    # def _create_user(self, user: UserCreate):
    def _create_user(self, user: UserCreate, hashed_pw):
        try:
            db_user = User(
                name=user.name,
                user_name=user.user_name,
                phone=user.phone,
                email=user.email,
                password=hashed_pw,
            )
            self.db.add(db_user)
            self.db.commit()
            self.db.refresh(db_user)
            return db_user
        except Exception as e:
            self.logger.exception(e)
            raise

    def _create_update_user(self, user: User, new_user:UserUpdate, hashed_pw):
        try:
            user.is_deleted = False
            user.name = new_user.name
            user.email = new_user.email
            user.phone = new_user.phone
            user.password = hashed_pw
            self.db.add(user)
            self.db.commit()
            self.db.refresh(user)
            return user
        except Exception as e:
            self.logger.exception(e)
            raise

    def _get_users(self):
        try:
            return self.db.query(User).filter(User.is_deleted == False).all()
        except Exception as e:
            self.logger.exception()
            raise

    def _get_user_by_id(self, user_id: int):
        try:
            return (
                self.db.query(User)
                .filter(User.id == user_id, User.is_deleted == False)
                .first()
            )
        except Exception as e:
            self.logger.exception(e)
            raise

    def _change_password(self, user: User, new_password: str):
        try:
            user.reset_code = None
            user.reset_code_sent_at = None
            user.password = new_password
            self.db.commit()
            self.db.refresh(user)
        except Exception as e:
            self.logger.exception(e)
            raise

    def _delete_user(self, user: User):
        try:
            user.is_deleted = True
            self.db.commit()
            self.db.refresh(user)
        except Exception as e:
            self.logger.exception(e)
            raise
    
    def set_reset_code(self, user: User, reset_code: str, jst_time_now):
        try:
            user.reset_code = reset_code
            user.reset_code_sent_at = jst_time_now
            self.db.add(user)
            self.db.commit()
            self.db.refresh(user)
        except Exception as e:
            self.logger.exception(e)
            raise