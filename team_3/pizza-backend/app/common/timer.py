from __future__ import annotations
from datetime import datetime, timezone, timedelta
from app.common.constant import (
    TIMEFORMAT_YYYY_MM_DD_STR,
    TIMEFORMAT_YYYYMMDD_STR,
    TIMEFORMAT_YYYYMMDDHHMMSS_STR,
    TIMEFORMAT_YYYYMMDDHHMMSSF_STR,
)

class Timer:

    @staticmethod
    def get_utc_now() -> datetime:
        utc_now: datetime = datetime.now(timezone.utc)
        return utc_now

    @staticmethod
    def get_jst_now_from_utc() -> datetime:
        utc_now: datetime = Timer.get_utc_now()
        jst_now: datetime = utc_now.astimezone(timezone(timedelta(hours=9)))
        return jst_now

    @staticmethod
    def strf_datetime_to_yyyy_mm_dd(datetime: datetime) -> str:
        return datetime.strftime(TIMEFORMAT_YYYY_MM_DD_STR)

    @staticmethod
    def strf_datetime_to_yyyymmddhhmmss(datetime: datetime) -> str:
        return datetime.strftime(TIMEFORMAT_YYYYMMDDHHMMSS_STR)

    @staticmethod
    def strf_datetime_to_yyyymmddhhmmssf(datetime: datetime) -> str:
        return datetime.strftime(TIMEFORMAT_YYYYMMDDHHMMSSF_STR)[:-3]
    
    @staticmethod
    def strf_datetime_to_yyyymmdd(datetime: datetime) -> str:
        return datetime.strftime(TIMEFORMAT_YYYYMMDD_STR)
    