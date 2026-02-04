from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from typing import List
import logging
from app.infras.external.db.postgres_connection import postgre_connection
from app.entity.schedule_entity import ScheduleActionUpdate, ScheduleDeleteRequest, ScheduleCreate, ScheduleUpdate, ScheduleOut
from app.repository.schedule_repository import ScheduleRepository
from app.utils.middleware import get_auth_dependency
from app.utils.response import response
from app.common.timer import Timer

router = APIRouter(tags=["Schedule"])
logger = logging.getLogger()

def get_schedule_repo(db: Session = Depends(postgre_connection)) -> ScheduleRepository:
    return ScheduleRepository(logger=logger, db=db)

@router.post("/create", response_model=ScheduleOut)
def create_schedule(
    req: ScheduleCreate,
    repo: ScheduleRepository = Depends(get_schedule_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        is_exsit = repo._get_schedule_by_user_id(req.user_id, req.target_date)
        if is_exsit:
            return response(message=f"{req.target_date} 作成しました。")
        
        tmp_user_id = payload.get("user_id")
        create_by = repo._get_user_by_id(tmp_user_id).name
        schedule = repo._create_schedule(req, create_by)

        return response(data=ScheduleOut.from_orm(schedule).model_dump(mode="json"), code=201)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)

@router.get("/", response_model=List[ScheduleOut])
def list_schedules(
    repo: ScheduleRepository = Depends(get_schedule_repo),
):
    try:
        schedules = repo._get_schedules()
        return response(data=[ScheduleOut.from_orm(s).model_dump(mode="json") for s in schedules])
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.post("/update/{schedule_id}", response_model=ScheduleOut)
def update_schedule(
    schedule_id: int,
    schedule_data: ScheduleUpdate,
    repo: ScheduleRepository = Depends(get_schedule_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        schedule = repo._update_schedule(schedule_id, schedule_data)
        if not schedule:
            return response(message="スケジュールが存在しません", code=404)
        return response(data=ScheduleOut.from_orm(schedule).model_dump(mode="json"), code=200)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.post("/update", response_model=ScheduleOut)
def update_schedule(
    schedule_data: ScheduleActionUpdate,
    repo: ScheduleRepository = Depends(get_schedule_repo),
    payload=Depends(get_auth_dependency([1,2,4])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        tmp_user_id = schedule_data.user_id
        tmp_target_date = schedule_data.target_date
        jst_datetime_now = Timer.strf_datetime_to_yyyymmddhhmmss(Timer.get_jst_now_from_utc())
        tmp_action = schedule_data.type

        tmp_schedule = repo._get_schedule_by_user_id(user_id=tmp_user_id, target_date=tmp_target_date)
        if not tmp_schedule:
            return response(code=400, message="このユーザーはスケジュールが存在しません")
        if tmp_action == 1 and not tmp_schedule.actual_start_time:
            tmp_schedule.actual_start_time = jst_datetime_now
            repo._update_actual_schedule(tmp_schedule)
        if tmp_action == 2:
            if not tmp_schedule.from_break_time:
                tmp_schedule.from_break_time = jst_datetime_now
            else:
                tmp_schedule.to_break_time = jst_datetime_now
            repo._update_actual_schedule(tmp_schedule)
        if tmp_action == 3 and not tmp_schedule.actual_end_time:
            tmp_schedule.actual_end_time = jst_datetime_now
            repo._update_actual_schedule(tmp_schedule)
        return response(data={"message": "完成"})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)

@router.post("/delete")
def delete_schedules(
    req: ScheduleDeleteRequest,
    repo: ScheduleRepository = Depends(get_schedule_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        if not req.ids:
            return response(message="不正リクエスト", code=400)
        repo._delete_schedules(req.ids)
        return response(data={"message": "success"})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)
