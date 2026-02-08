from app.main import ServiceDeliveryApp
from app.models import BookingStatus, ProviderStatus


def test_full_workflow():
    app = ServiceDeliveryApp()

    # Admin setup
    cat = app.add_category("Cleaning")
    svc = app.add_service(cat.id, "Deep Clean", 1000)
    app.add_coupon("SAVE10", 10)

    provider = app.add_provider("Ravi", "9999999999")
    app.verify_provider(provider.id)
    app.update_provider_status(provider.id, ProviderStatus.APPROVED)

    # User flow
    user = app.signup_user("Asha", "8888888888")
    booking = app.create_booking(
        user_id=user.id,
        service_id=svc.id,
        address="123 Main St",
        slot="2026-02-06T10:00:00",
        coupon_code="SAVE10",
    )
    assert booking.price == 900.0
    assert booking.status == BookingStatus.REQUESTED

    # Admin assigns
    app.assign_booking(booking.id, provider.id)
    assert app.bookings[booking.id].status == BookingStatus.ASSIGNED

    # Partner lifecycle
    app.partner_accept(provider.id, booking.id)
    app.partner_start(provider.id, booking.id)
    app.partner_complete(provider.id, booking.id)
    assert app.bookings[booking.id].status == BookingStatus.COMPLETED

    # Reports
    report = app.revenue_report()
    assert report["completed_bookings"] == 1
    assert report["total_revenue"] == 900.0
    assert app.providers[provider.id].earnings == 900.0


def test_provider_must_be_approved_before_assignment():
    app = ServiceDeliveryApp()
    cat = app.add_category("Plumbing")
    svc = app.add_service(cat.id, "Leak Fix", 500)
    user = app.signup_user("Nina", "7777777777")
    provider = app.add_provider("Sam", "6666666666")
    booking = app.create_booking(user.id, svc.id, "Lane 4", "2026-02-06T12:00:00")

    try:
        app.assign_booking(booking.id, provider.id)
        assert False, "Expected ValueError for unapproved provider"
    except ValueError as exc:
        assert "approved" in str(exc)
