# app/script/create_admin.py
from app.infras.external.db.postgres_connection import Base, engine, SessionLocal
from app.models.user_admin_model import UserAdmin
from app.models.role_model import Role
from passlib.hash import argon2

password=argon2.hash("admin123")

def init_db():
    # Tạo tất cả bảng
    Base.metadata.create_all(bind=engine)

    with SessionLocal() as db:
        # Tạo role nếu chưa có
        if not db.query(Role).count():
            db.add_all([
                Role(id=1, name="super_admin", type = 1),
                Role(id=2, name="admin", type = 2)
            ])
            db.commit()
            print("Roles created ✅")

        # Tạo super_admin nếu chưa có
        if not db.query(UserAdmin).filter(UserAdmin.role_type == 1).first():
            super_admin = UserAdmin(
                name="Super Admin",
                user_name="superadmin",
                phone="0123456789",
                email="k247054@kccollege.ac.jp",
                password=password,  # ✅ không giới hạn 72 ký tự
                role_type=1
            )
            db.add(super_admin)
            db.commit()
            print("Super admin created ✅")
        else:
            print("Super admin already exists")

if __name__ == "__main__":
    init_db()
