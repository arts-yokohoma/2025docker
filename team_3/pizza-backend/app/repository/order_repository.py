# app/repository/order_repository.py
from sqlalchemy.orm import Session
from app.entity.order_entity import OrderCreate, OrderUpdate
from app.models.order_model import Order, OrderItem
from app.common.timer import Timer
from sqlalchemy import func, cast, Integer, Date

class OrderRepository:
    def __init__(self, logger, db: Session):
        self.logger = logger
        self.db = db

    def _generate_order_code_unique(self, prefix: str = "ORD") -> str:
        today_str = Timer.strf_datetime_to_yyyymmdd(Timer.get_jst_now_from_utc())
        last_order = (
            self.db.query(Order)
            .filter(Order.order_code.like(f"{prefix}{today_str}-%"))
            .order_by(Order.order_code.desc())
            .first()
        )
        if last_order:
            last_number = int(last_order.order_code.split("-")[-1])
            next_number = last_number + 1
        else:
            next_number = 1
        return f"{prefix}{today_str}-{next_number:04d}"

    def _create_order(self, order_data: OrderCreate, status= 1) -> Order:
        order_code = self._generate_order_code_unique()
        order = Order(
            order_code=order_code,
            customer_name=order_data.customer_name,
            order_date = order_data.order_date,
            customer_id=order_data.customer_id,
            address = order_data.address,
            phone=order_data.phone,
            email=order_data.email,
            total_price=order_data.total_price,
            note=order_data.note,
        )
        order.status = status
        self.db.add(order)
        self.db.commit()
        self.db.refresh(order)

        # Tạo OrderItem
        for item_data in order_data.items:
            item = OrderItem(
                order_id=order.id,
                size=item_data.size,
                menu_id=item_data.menu_id,
                menu_name=item_data.menu_name,
                quantity=item_data.quantity,
                price=item_data.price,
            )
            self.db.add(item)
        self.db.commit()
        self.db.refresh(order)
        return order

    def _get_orders(self):
        return self.db.query(Order).all()
    
    def _get_orders_user(self, user_id: int):
        return self.db.query(Order).filter(Order.customer_id == user_id).all()

    def _get_orders_revenue(self, year: int):
        order_date_as_date = cast(Order.order_date, Date)

        rows = (
            self.db.query(
                cast(func.extract("month", order_date_as_date), Integer).label("month"),
                func.coalesce(func.sum(Order.total_price), 0).label("priceTotal"),
            )
            .filter(
                Order.status == 3,
                func.extract("year", order_date_as_date) == year,
            )
            .group_by("month")
            .order_by("month")
            .all()
        )
        month_to_total = {int(m): int(total) for m, total in rows}
        return [{"month": str(m), "priceTotal": month_to_total.get(m, 0)} for m in range(1, 13)]

    def _get_order_by_id(self, order_id: int):
        return self.db.query(Order).filter(Order.id == order_id).first()

    def _update_order(self, order_id: int, order_data: OrderUpdate):
        order = self._get_order_by_id(order_id)
        if not order:
            return None
        for key, value in order_data.model_dump(exclude_unset=True).items():
            setattr(order, key, value)
        self.db.commit()
        self.db.refresh(order)
        return order