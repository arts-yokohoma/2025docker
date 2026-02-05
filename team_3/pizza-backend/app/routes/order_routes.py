from fastapi import APIRouter, Depends, Request, Query
from sqlalchemy.orm import Session
from typing import List
import logging

from app.repository.order_repository import OrderRepository
from app.entity.order_entity import OrderCreate, OrderOut, OrderStatusEnum, OrderUpdate, OrderItemRead, RevenueOut
from app.infras.external.db.postgres_connection import postgre_connection
from app.utils.response import response
from app.utils.middleware import get_auth_dependency
from app.common.constant import CONFIRM_ORDER_PRICE, ORDER_STATUS
from app.common.mail_template import ORDER_MAIL_TEMPLATE, UPDATE_STATUS_TEMPLATE
from app.infras.external.slow_api import SlowApiLimiter
from app.infras.external.mail_service.send_mail import SendMail


router = APIRouter(tags=["Order"])
logger = logging.getLogger()
limiter = SlowApiLimiter().get_limiter()


def get_order_repo(db: Session = Depends(postgre_connection)) -> OrderRepository:
    return OrderRepository(logger=logger, db=db)


@router.post("/create", response_model=OrderOut)
@limiter.limit("5/minute", SlowApiLimiter.user_body_key_func)
def create_order(req: OrderCreate, request: Request, repo: OrderRepository = Depends(get_order_repo)):
    try:
        status = 1
        if req.total_price <= CONFIRM_ORDER_PRICE:
            status = 2
        order = repo._create_order(req, status)
        order_items = [OrderItemRead.from_orm(item).model_dump(mode="json") for item in order.items]
        order_items_html = "".join(
            f"""
            <li>
                <strong>{item['menu_name']} - Size: {item['size']}</strong> × {item['quantity']} 
                <span style="float:right;">{item['price'] * item['quantity']:,} 円</span>
            </li>
            """ for item in order_items
        )
        mailer = SendMail()
        mailer._send(
            to_mail=order.email,
            mail_content=ORDER_MAIL_TEMPLATE,
            customer_name=order.customer_name,
            order_code=order.order_code,
            address=order.address,
            order_items=order_items_html,
            total_price=order.total_price,
            order_status=ORDER_STATUS[order.status],
        )
        return response(data=OrderOut.from_orm(order).model_dump(mode="json"), code=201)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.get("/", response_model=List[OrderOut])
def list_orders(
    repo: OrderRepository = Depends(get_order_repo),
    payload=Depends(get_auth_dependency([1, 2, 5])),
):
    try:
        if (
            isinstance(payload, dict)
            and "code" in payload
            and payload["code"] in (401, 403)
        ):
            return payload
        orders = repo._get_orders()
        return response(
            data=[OrderOut.from_orm(order).model_dump(mode="json") for order in orders]
        )
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.get("/my-order/{user_id}", response_model=List[OrderOut])
def list_orders(
    user_id: int,
    repo: OrderRepository = Depends(get_order_repo),
    payload=Depends(get_auth_dependency([])),
):
    try:
        if (
            isinstance(payload, dict)
            and "code" in payload
            and payload["code"] in (401, 403)
        ):
            return payload
        orders = repo._get_orders_user(user_id)
        return response(
            data=[OrderOut.from_orm(order).model_dump(mode="json") for order in orders]
        )
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.get("/revenue", response_model=List[RevenueOut])
def list_orders(
    year: int = Query(..., ge=2000, le=2100),
    repo: OrderRepository = Depends(get_order_repo),
    payload=Depends(get_auth_dependency([1])),
):
    try:
        if (
            isinstance(payload, dict)
            and "code" in payload
            and payload["code"] in (401, 403)
        ):
            return payload
        data = repo._get_orders_revenue(year)
        return response(data=data)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.post("/update/{order_id}", response_model=OrderOut)
def update_order(
    order_id: int,
    order_data: OrderUpdate,
    repo: OrderRepository = Depends(get_order_repo),
    payload=Depends(get_auth_dependency([1, 2, 5])),
):
    try:
        if (
            isinstance(payload, dict)
            and "code" in payload
            and payload["code"] in (401, 403)
        ):
            return payload
        logger.info("order_data", order_data)
        logger.info("order_data", order_data.status)
        if not OrderStatusEnum(order_data.status):
            return response(message="状態不正", code=400)
        order = repo._update_order(order_id, order_data)
        if not order:
            return response(message="オーダー存在ない", code=404)
        mailer = SendMail()
        mailer._send(
            to_mail=order.email,
            mail_content=UPDATE_STATUS_TEMPLATE,
            customer_name=order.customer_name,
            order_code=order.order_code,
            order_status=ORDER_STATUS[order.status],
        )
        return response(data=OrderOut.from_orm(order).model_dump(mode="json"), code=200)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)
