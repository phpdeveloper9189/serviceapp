# Service Delivery App (Full MVP Core)

Implemented a complete **domain-level app core** for Admin, User, and Service Partner workflows.

## What is included

- **Admin module**
  - Provider onboarding and status management
  - Category, service, and coupon setup
  - Manual booking assignment
  - Revenue reporting
- **User module**
  - User signup
  - Booking creation with coupon-based discount calculation
  - User booking history
- **Service Partner module**
  - Accept / start / complete assigned jobs
  - Earnings accumulation from completed jobs

## Main entrypoint

Use `ServiceDeliveryApp` from `app.main`.

```python
from app.main import ServiceDeliveryApp

app = ServiceDeliveryApp()
user = app.signup_user("Asha", "8888888888")
```

## Run tests

```bash
pytest -q
```
