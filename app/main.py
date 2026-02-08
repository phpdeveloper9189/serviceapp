from dataclasses import dataclass, field
from typing import Dict, List

from app.models import (
    Booking,
    BookingStatus,
    Category,
    Coupon,
    Provider,
    ProviderStatus,
    Service,
    User,
)


@dataclass
class Counters:
    user: int = 1
    provider: int = 1
    category: int = 1
    service: int = 1
    booking: int = 1


@dataclass
class ServiceDeliveryApp:
    users: Dict[int, User] = field(default_factory=dict)
    providers: Dict[int, Provider] = field(default_factory=dict)
    categories: Dict[int, Category] = field(default_factory=dict)
    services: Dict[int, Service] = field(default_factory=dict)
    coupons: Dict[str, Coupon] = field(default_factory=dict)
    bookings: Dict[int, Booking] = field(default_factory=dict)
    counters: Counters = field(default_factory=Counters)

    # Admin operations
    def add_provider(self, name: str, phone: str) -> Provider:
        obj = Provider(id=self.counters.provider, name=name, phone=phone)
        self.providers[obj.id] = obj
        self.counters.provider += 1
        return obj

    def update_provider_status(self, provider_id: int, status: ProviderStatus) -> Provider:
        provider = self.providers[provider_id]
        provider.status = status
        return provider

    def verify_provider(self, provider_id: int) -> Provider:
        provider = self.providers[provider_id]
        provider.docs_verified = True
        return provider

    def add_category(self, name: str) -> Category:
        obj = Category(id=self.counters.category, name=name)
        self.categories[obj.id] = obj
        self.counters.category += 1
        return obj

    def add_service(self, category_id: int, name: str, price: float) -> Service:
        if category_id not in self.categories:
            raise KeyError("category not found")
        obj = Service(id=self.counters.service, category_id=category_id, name=name, price=price)
        self.services[obj.id] = obj
        self.counters.service += 1
        return obj

    def add_coupon(self, code: str, discount_percent: int) -> Coupon:
        coupon = Coupon(code=code.upper(), discount_percent=discount_percent)
        self.coupons[coupon.code] = coupon
        return coupon

    def assign_booking(self, booking_id: int, provider_id: int) -> Booking:
        booking = self.bookings[booking_id]
        provider = self.providers[provider_id]
        if provider.status != ProviderStatus.APPROVED:
            raise ValueError("provider must be approved")
        booking.provider_id = provider_id
        booking.status = BookingStatus.ASSIGNED
        return booking

    def revenue_report(self) -> dict:
        completed = [b for b in self.bookings.values() if b.status == BookingStatus.COMPLETED]
        return {
            "completed_bookings": len(completed),
            "total_revenue": round(sum(b.price for b in completed), 2),
        }

    # User operations
    def signup_user(self, name: str, phone: str) -> User:
        user = User(id=self.counters.user, name=name, phone=phone)
        self.users[user.id] = user
        self.counters.user += 1
        return user

    def create_booking(self, user_id: int, service_id: int, address: str, slot: str, coupon_code: str | None = None) -> Booking:
        user = self.users[user_id]
        if user.blocked:
            raise ValueError("user blocked")
        service = self.services[service_id]
        price = service.price
        if coupon_code:
            coupon = self.coupons.get(coupon_code.upper())
            if coupon and coupon.enabled:
                price = price * (100 - coupon.discount_percent) / 100

        booking = Booking(
            id=self.counters.booking,
            user_id=user_id,
            service_id=service_id,
            address=address,
            slot=slot,
            price=round(price, 2),
            coupon_code=coupon_code,
        )
        self.bookings[booking.id] = booking
        self.counters.booking += 1
        return booking

    def my_bookings(self, user_id: int) -> List[Booking]:
        return [b for b in self.bookings.values() if b.user_id == user_id]

    # Partner operations
    def partner_accept(self, provider_id: int, booking_id: int) -> Booking:
        booking = self.bookings[booking_id]
        if booking.provider_id != provider_id:
            raise ValueError("booking not assigned to provider")
        booking.status = BookingStatus.ACCEPTED
        return booking

    def partner_start(self, provider_id: int, booking_id: int) -> Booking:
        booking = self.bookings[booking_id]
        if booking.provider_id != provider_id:
            raise ValueError("booking not assigned to provider")
        booking.status = BookingStatus.STARTED
        return booking

    def partner_complete(self, provider_id: int, booking_id: int) -> Booking:
        booking = self.bookings[booking_id]
        provider = self.providers[provider_id]
        if booking.provider_id != provider_id:
            raise ValueError("booking not assigned to provider")
        booking.status = BookingStatus.COMPLETED
        provider.earnings += booking.price
        return booking
