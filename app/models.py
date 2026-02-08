from dataclasses import dataclass
from enum import Enum
from typing import Optional


class ProviderStatus(str, Enum):
    PENDING = "pending"
    APPROVED = "approved"
    SUSPENDED = "suspended"


class BookingStatus(str, Enum):
    REQUESTED = "requested"
    ASSIGNED = "assigned"
    ACCEPTED = "accepted"
    STARTED = "started"
    COMPLETED = "completed"
    CANCELLED = "cancelled"


@dataclass
class User:
    id: int
    name: str
    phone: str
    blocked: bool = False
    wallet_balance: float = 0.0


@dataclass
class Provider:
    id: int
    name: str
    phone: str
    status: ProviderStatus = ProviderStatus.PENDING
    category: Optional[str] = None
    docs_verified: bool = False
    availability: bool = True
    earnings: float = 0.0


@dataclass
class Category:
    id: int
    name: str
    enabled: bool = True


@dataclass
class Service:
    id: int
    category_id: int
    name: str
    price: float
    enabled: bool = True


@dataclass
class Coupon:
    code: str
    discount_percent: int
    enabled: bool = True


@dataclass
class Booking:
    id: int
    user_id: int
    service_id: int
    address: str
    slot: str
    status: BookingStatus = BookingStatus.REQUESTED
    provider_id: Optional[int] = None
    price: float = 0.0
    coupon_code: Optional[str] = None
