from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from typing import List
import logging
from app.infras.external.db.postgres_connection import postgre_connection
from app.entity.holiday_entity import (
    HolidayCreate,
    HolidayDeleteRequest,
    HolidayUpdate,
    HolidayOut,
)
from app.repository.holiday_repository import HolidayRepository
from app.utils.middleware import get_auth_dependency
from app.utils.response import response

router = APIRouter(tags=["Holiday"])
logger = logging.getLogger()


def get_category_repo(db: Session = Depends(postgre_connection)) -> HolidayRepository:
    return HolidayRepository(logger=logger, db=db)


@router.post("/create", response_model=HolidayOut)
def create_category(
    holiday_data: HolidayCreate,
    repo: HolidayRepository = Depends(get_category_repo),
    payload=Depends(get_auth_dependency([1])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        exists = repo._get_holiday_by_date(holiday_data)
        if exists:
            return response(message="既に存在します", code=400)
        category = repo._create_holiday(holiday_data)
        return response(
            data=HolidayOut.from_orm(category).model_dump(mode="json"), code=201
        )
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.get("/", response_model=List[HolidayOut])
def list_categories(
    repo: HolidayRepository = Depends(get_category_repo),
    # payload=Depends(get_auth_dependency([])),
):
    try:
        result = repo._get_holidays()
        categories = [
            HolidayOut.from_orm(cat).model_dump(mode="json") for cat in result
        ]
        return response(data=categories)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.put("/update/{category_id}", response_model=HolidayOut)
def update_category(
    category_id: int,
    category_data: HolidayUpdate,
    repo: HolidayRepository = Depends(get_category_repo),
    payload=Depends(get_auth_dependency([1])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        category = repo._update_holiday(category_id, category_data)
        if not category:
            return response(message="Category not found", code=404)
        return response(
            data=HolidayOut.from_orm(category).model_dump(mode="json"), code=200
        )
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.post("/delete", response_model=HolidayOut)
def delete_category(
    req: HolidayDeleteRequest,
    repo: HolidayRepository = Depends(get_category_repo),
    payload=Depends(get_auth_dependency([1])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        if not req.ids:
            return response(message="不正リクエスト", code=400)
        repo._delete_holiday(req.ids)
        return response(data={"message": "success"})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)
