const API_BASE = '../api.php';

let latestProviderId = null;
let latestBookingId = null;

function show(data) {
  document.getElementById('output').textContent = JSON.stringify(data, null, 2);
}

async function callApi(action, payload = null, query = '') {
  const url = `${API_BASE}?action=${action}${query}`;
  const options = payload
    ? {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      }
    : { method: 'GET' };

  const response = await fetch(url, options);
  return response.json();
}

async function seedDemo() {
  const category = await callApi('add_category', { name: 'Cleaning' });
  const categoryId = category.category.id;

  await callApi('add_service', {
    category_id: categoryId,
    name: 'Deep Clean',
    price: 1000,
  });

  await callApi('add_coupon', { code: 'SAVE10', discount_percent: 10 });

  const provider = await callApi('add_provider', {
    name: 'Ravi',
    phone: '9999999999',
  });
  latestProviderId = provider.provider.id;

  await callApi('approve_provider', { provider_id: latestProviderId });
  show({ message: 'Seed data ready', latestProviderId });
}

async function createBooking(event) {
  event.preventDefault();
  const formData = new FormData(event.target);

  const user = await callApi('signup_user', {
    name: formData.get('name'),
    phone: formData.get('phone'),
  });

  const home = await callApi('home_data');
  const serviceId = home.featured_services[0]?.id;

  const booking = await callApi('create_booking', {
    user_id: user.user.id,
    service_id: serviceId,
    address: formData.get('address'),
    slot: formData.get('slot'),
    coupon_code: 'SAVE10',
  });

  latestBookingId = booking.booking.id;

  if (latestProviderId) {
    await callApi('assign_booking', {
      booking_id: latestBookingId,
      provider_id: latestProviderId,
    });
  }

  show({ user, booking, latestProviderId, latestBookingId });
}

async function partnerUpdate(status) {
  if (!latestProviderId || !latestBookingId) {
    show({ error: 'Create data and booking first.' });
    return;
  }

  const updated = await callApi('partner_update_status', {
    booking_id: latestBookingId,
    provider_id: latestProviderId,
    status,
  });

  show(updated);
}

async function loadRevenue() {
  const report = await callApi('revenue_report');
  show(report);
}

document.getElementById('seedBtn').addEventListener('click', seedDemo);
document.getElementById('bookingForm').addEventListener('submit', createBooking);
document.getElementById('acceptBtn').addEventListener('click', () => partnerUpdate('accepted'));
document.getElementById('startBtn').addEventListener('click', () => partnerUpdate('started'));
document.getElementById('completeBtn').addEventListener('click', () => partnerUpdate('completed'));
document.getElementById('reportBtn').addEventListener('click', loadRevenue);
