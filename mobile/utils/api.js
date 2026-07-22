import AsyncStorage from '@react-native-async-storage/async-storage';

const API_URL = 'https://manage.deepsde.in/api.php';

// Helper to make API requests
async function request(action, method = 'GET', body = null) {
  try {
    const url = `${API_URL}?action=${action}`;
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
      },
    };

    if (body) {
      options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);
    const json = await response.json();

    if (!response.ok || !json.success) {
      throw new Error(json.error || 'Something went wrong');
    }

    return json;
  } catch (error) {
    console.error(`API Error (${action}):`, error);
    throw error;
  }
}

// User Authentication
export async function login(username, password) {
  const result = await request('login', 'POST', { username, password });
  if (result.user) {
    await AsyncStorage.setItem('user_profile', JSON.stringify(result.user));
  }
  return result;
}

export async function getLoggedUser() {
  const user = await AsyncStorage.getItem('user_profile');
  return user ? JSON.parse(user) : null;
}

export async function logout() {
  await AsyncStorage.removeItem('user_profile');
}

// Dashboard Stats
export async function getDashboard() {
  return await request('dashboard', 'GET');
}

// Customers CRUD
export async function getCustomers() {
  return await request('customers_list', 'GET');
}

export async function addCustomer(data) {
  return await request('customer_add', 'POST', data);
}

// Quotations CRUD
export async function getQuotations() {
  return await request('quotations_list', 'GET');
}

export async function addQuotation(data) {
  return await request('quotation_add', 'POST', data);
}

// Invoices CRUD
export async function getInvoices() {
  return await request('invoices_list', 'GET');
}

export async function addInvoice(data) {
  return await request('invoice_add', 'POST', data);
}

// Lorry Receipts CRUD
export async function getLorryReceipts() {
  return await request('lr_list', 'GET');
}

export async function addLorryReceipt(data) {
  return await request('lr_add', 'POST', data);
}

// Payments CRUD
export async function getPayments() {
  return await request('payments_list', 'GET');
}

export async function addPayment(data) {
  return await request('payment_add', 'POST', data);
}

export async function getGstAudit(startDate, endDate) {
  return await request(`gst_audit&start_date=${startDate}&end_date=${endDate}`, 'GET');
}

export async function getAuditLogs() {
  return await request('audit_logs', 'GET');
}
