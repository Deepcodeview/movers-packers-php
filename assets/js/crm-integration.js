/**
 * Centralized CRM Integration JavaScript Client.
 * Routes page-specific UI rendering and binds backend microservices.
 */
const GATEWAY_URL = 'http://localhost:8000';

async function apiCall(endpoint, options = {}) {
    const token = localStorage.getItem('auth_token');
    const tenantSlug = localStorage.getItem('tenant_slug') || 'PUBLIC';

    const headers = {
        'Content-Type': 'application/json',
        'X-Tenant-Id': tenantSlug,
        ...options.headers
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const response = await fetch(`${GATEWAY_URL}${endpoint}`, {
        ...options,
        headers
    });

    if (response.status === 401) {
        localStorage.clear();
        window.location.href = 'login.html';
        throw new Error('Session expired');
    }

    return response;
}

// Route page loads
document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname.toLowerCase();

    if (path.includes('contacts.html')) {
        initContactsPage();
    } else if (path.includes('deals.html')) {
        initDealsPage();
    } else if (path.includes('departments.html')) {
        initDepartmentsPage();
    } else if (path.includes('file-manager.html')) {
        initFileManagerPage();
    } else if (path.includes('subscription.html') || path.includes('membership-plans.html')) {
        initSubscriptionPage();
    } else if (path.includes('campaign.html') || path.includes('email-campaign.html')) {
        initCampaignsPage();
    } else if (path.includes('index.html') || path.includes('dashboard.html')) {
        initDashboardStats();
    }
});

// ============================================================================
// 1. DASHBOARD OVERVIEW STATS
// ============================================================================
async function initDashboardStats() {
    // Instantly override template placeholder values with loading states
    const contactsStat = document.getElementById('dashboard-contacts-count') || document.querySelector('.contacts-count, .leads-count, #contacts-total');
    if (contactsStat) contactsStat.textContent = '...';

    const dealsStat = document.getElementById('dashboard-deals-count');
    if (dealsStat) dealsStat.textContent = '...';

    const revenueStat = document.getElementById('dashboard-revenue-count');
    if (revenueStat) revenueStat.textContent = '...';

    try {
        const crmRes = await apiCall('/api/v1/crm/contacts');
        const contacts = await crmRes.json();
        
        // Dynamic overrides
        const contactsStat = document.getElementById('dashboard-contacts-count') || document.querySelector('.contacts-count, .leads-count, #contacts-total');
        if (contactsStat) {
            contactsStat.textContent = contacts.length;
        }

        const dealsRes = await apiCall('/api/v1/crm/deals');
        const deals = await dealsRes.json();
        const dealsStat = document.getElementById('dashboard-deals-count');
        if (dealsStat) {
            dealsStat.textContent = deals.length;
        }

        try {
            const billingRes = await apiCall('/api/v1/billing/transactions');
            const transactions = await billingRes.json();
            const totalRevenue = transactions
                .filter(t => t.paymentStatus === 'SUCCESS')
                .reduce((sum, t) => sum + t.amount, 0.0);
            
            const revenueStat = document.getElementById('dashboard-revenue-count');
            if (revenueStat) {
                revenueStat.textContent = '$' + totalRevenue.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            }
        } catch (billingErr) {
            console.error("Failed to load revenue stats", billingErr);
        }
    } catch (err) {
        console.error("Dashboard statistics loading failed", err);
    }
}

// ============================================================================
// 2. CONTACTS SERVICE BINDINGS
// ============================================================================
async function initContactsPage() {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Loading contacts from service...</td></tr>';

    async function loadContacts() {
        try {
            const response = await apiCall('/api/v1/crm/contacts');
            const contacts = await response.json();
            
            tbody.innerHTML = ''; // Clear mockup data
            if (contacts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No active contacts found for this tenant workspace.</td></tr>';
                return;
            }

            contacts.forEach(c => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${c.id}</td>
                    <td><h2 class="table-avatar"><a href="javascript:void(0);">${c.firstName} ${c.lastName}</a></h2></td>
                    <td>${c.email}</td>
                    <td>${c.phone || 'N/A'}</td>
                    <td><span class="badge bg-success-light">Active</span></td>
                `;
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error("Failed loading contacts registry", err);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load contacts. Please verify backend is running.</td></tr>';
        }
    }

    loadContacts();

    // Hook Create Modal submission form
    const form = document.querySelector('#addContactForm, form.add-contact-form');
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const payload = {
                firstName: form.querySelector('#firstName, [name="first_name"]').value,
                lastName: form.querySelector('#lastName, [name="last_name"]').value,
                email: form.querySelector('#email, [name="email"]').value,
                phone: form.querySelector('#phone, [name="phone"]').value
            };

            try {
                const res = await apiCall('/api/v1/crm/contacts', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    alert('Contact created successfully!');
                    loadContacts();
                    form.reset();
                }
            } catch (err) {
                alert('Add contact failed');
            }
        });
    }
}

// ============================================================================
// 3. PIPELINE DEALS BINDINGS
// ============================================================================
async function initDealsPage() {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Loading deals from pipeline...</td></tr>';

    async function loadDeals() {
        try {
            const response = await apiCall('/api/v1/crm/deals');
            const deals = await response.json();

            tbody.innerHTML = '';
            if (deals.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No active deals registered.</td></tr>';
                return;
            }

            deals.forEach(d => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${d.id}</td>
                    <td><strong>${d.name}</strong></td>
                    <td>$${d.value.toFixed(2)}</td>
                    <td><span class="badge bg-warning">${d.stage}</span></td>
                    <td>${d.probability || 'N/A'}</td>
                `;
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error("Failed loading deals pipeline", err);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load deals. Please verify backend is running.</td></tr>';
        }
    }

    loadDeals();

    // Hook Create Deal form submission
    const form = document.getElementById('addDealForm');
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const payload = {
                name: form.querySelector('#dealName').value,
                value: parseFloat(form.querySelector('#dealValue').value) || 0.0,
                stage: form.querySelector('#dealStage').value || 'Open',
                probability: '50%',
                contactId: 1
            };

            try {
                const res = await apiCall('/api/v1/crm/deals', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    alert('Deal created successfully!');
                    loadDeals();
                    form.reset();
                    const offcanvasEl = document.querySelector('#offcanvas_add');
                    if (offcanvasEl) {
                        const bootstrapOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                        if (bootstrapOffcanvas) {
                            bootstrapOffcanvas.hide();
                        } else {
                            const closeBtn = offcanvasEl.querySelector('.btn-close');
                            if (closeBtn) closeBtn.click();
                        }
                    }
                } else {
                    alert('Failed to save deal on backend');
                }
            } catch (err) {
                alert('Add deal connection error');
            }
        });
    }
}

// ============================================================================
// 4. HR DEPARTMENTS BINDINGS (Soft deletes)
// ============================================================================
async function initDepartmentsPage() {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Loading departments...</td></tr>';

    async function loadDepts() {
        try {
            const response = await apiCall('/api/v1/hr/departments');
            const depts = await response.json();

            tbody.innerHTML = '';
            if (depts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No departments registered.</td></tr>';
                return;
            }

            depts.forEach(d => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${d.id}</td>
                    <td><strong>${d.name}</strong></td>
                    <td><span class="badge bg-outline-info">Tenant: ${d.tenantId}</span></td>
                    <td class="text-end">
                        <button class="btn btn-danger btn-sm delete-dept" data-id="${d.id}">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Bind click handlers to soft deletes
            document.querySelectorAll('.delete-dept').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const id = this.getAttribute('data-id');
                    if (confirm('Delete this department? (Will execute soft-delete mapping)')) {
                        try {
                            const res = await apiCall(`/api/v1/hr/departments/${id}`, { method: 'DELETE' });
                            if (res.ok) {
                                alert('Department deleted!');
                                loadDepts();
                            }
                        } catch (err) {
                            alert('Delete action failed');
                        }
                    }
                });
            });
        } catch (err) {
            console.error("Failed loading departments registry", err);
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Failed to load departments. Please verify backend is running.</td></tr>';
        }
    }

    loadDepts();

    const form = document.querySelector('#addDeptForm, form.add-dept');
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const nameInput = form.querySelector('[name="name"], #deptName');
            try {
                const res = await apiCall('/api/v1/hr/departments', {
                    method: 'POST',
                    body: JSON.stringify({ name: nameInput.value })
                });
                if (res.ok) {
                    alert('Department created!');
                    loadDepts();
                    nameInput.value = '';
                }
            } catch (err) {
                alert('Creation failed');
            }
        });
    }
}

// ============================================================================
// 5. DOCUMENTS STORAGE STRATEGY BINDINGS
// ============================================================================
async function initFileManagerPage() {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Loading files...</td></tr>';

    async function loadFiles() {
        try {
            const response = await apiCall('/api/v1/docs/list');
            const files = await response.json();

            tbody.innerHTML = '';
            if (files.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No documents registered.</td></tr>';
                return;
            }

            files.forEach(f => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${f.id}</td>
                    <td><strong>${f.fileName}</strong></td>
                    <td>${f.contentType}</td>
                    <td>${(f.fileSize / 1024).toFixed(1)} KB</td>
                    <td class="text-end">
                        <a href="${GATEWAY_URL}/api/v1/docs/download/${f.id}" class="btn btn-primary btn-sm text-white" target="_blank">Download</a>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error("Failed loading documents list", err);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load documents. Please verify backend is running.</td></tr>';
        }
    }

    loadFiles();

    // Hook multipart file upload trigger
    const uploadInput = document.querySelector('#fileUpload, #doc-file-input');
    if (uploadInput) {
        uploadInput.addEventListener('change', async function () {
            if (this.files.length === 0) return;
            const file = this.files[0];
            const formData = new FormData();
            formData.append('file', file);

            const token = localStorage.getItem('auth_token');
            const tenantSlug = localStorage.getItem('tenant_slug') || 'PUBLIC';

            try {
                const response = await fetch(`${GATEWAY_URL}/api/v1/docs/upload`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'X-Tenant-Id': tenantSlug
                    },
                    body: formData
                });
                
                if (response.ok) {
                    alert('File uploaded successfully!');
                    loadFiles();
                } else {
                    alert('Upload failed. Limit is 10MB.');
                }
            } catch (err) {
                console.error(err);
                alert('Connection upload failed');
            }
        });
    }
}

// ============================================================================
// 6. STRIPE BILLING & PLAN UPGRADES
// ============================================================================
async function initSubscriptionPage() {
    try {
        const response = await apiCall('/api/v1/billing/subscriptions');
        const plan = await response.json();

        const tierBadge = document.querySelector('#active-plan-badge, .current-plan-name');
        if (tierBadge) {
            tierBadge.textContent = "Current Active Tier: " + plan.planName;
        }

        // Bind Razorpay checkout hooks
        document.querySelectorAll('.choose-plan-btn').forEach(btn => {
            btn.addEventListener('click', async function (e) {
                e.preventDefault();
                const planName = this.getAttribute('data-plan');

                if (confirm(`Do you want to purchase the ${planName} Plan?`)) {
                    try {
                        const orderRes = await apiCall('/api/v1/billing/subscriptions/order', {
                            method: 'POST',
                            body: JSON.stringify({ planName })
                        });

                        if (!orderRes.ok) {
                            const errText = await orderRes.text();
                            throw new Error(errText || 'Order creation failed');
                        }

                        const orderData = await orderRes.json();

                        const options = {
                            key: orderData.keyId,
                            amount: orderData.amount,
                            currency: orderData.currency,
                            name: "CRMS SaaS Portal",
                            description: `${planName} Subscription Plan`,
                            order_id: orderData.razorpayOrderId,
                            handler: async function (response) {
                                try {
                                    const verifyRes = await apiCall('/api/v1/billing/subscriptions/verify', {
                                        method: 'POST',
                                        body: JSON.stringify({
                                            razorpayOrderId: response.razorpay_order_id,
                                            razorpayPaymentId: response.razorpay_payment_id,
                                            razorpaySignature: response.razorpay_signature,
                                            planName: orderData.planName
                                        })
                                    });

                                    if (verifyRes.ok) {
                                        alert(`Subscription activated successfully! Plan: ${planName}`);
                                        window.location.reload();
                                    } else {
                                        alert('Signature verification failed. Payment validation could not be completed.');
                                    }
                                } catch (verifyErr) {
                                    alert('Payment verification failed. Please contact support.');
                                }
                            },
                            prefill: {
                                name: localStorage.getItem('user_email') || 'User',
                                email: localStorage.getItem('user_email') || 'user@example.com'
                            },
                            theme: {
                                color: "#3B82F6"
                            }
                        };

                        const rzp = new Razorpay(options);
                        rzp.open();

                    } catch (err) {
                        console.error(err);
                        alert('Subscription order failed: ' + err.message);
                    }
                }
            });
        });
    } catch (err) {
        console.error("Failed loading subscription details", err);
    }
}

// ============================================================================
// 7. MARKETING CAMPAIGNS (Bulk Scheduled)
// ============================================================================
async function initCampaignsPage() {
    // Hooks bulk trigger actions
    const sendButton = document.querySelector('#trigger-campaign, .send-bulk-btn');
    if (sendButton) {
        sendButton.addEventListener('click', async function () {
            if (confirm('Trigger asynchronous marketing dispatch to all leads?')) {
                try {
                    const res = await apiCall('/api/v1/campaigns/1/send', {
                        method: 'POST',
                        body: JSON.stringify({
                            recipients: ["user1@lead.com", "buyer2@lead.com", "partner3@lead.com"]
                        })
                    });
                    if (res.ok) {
                        alert('Async dispatches sent! Verification details logged in background.');
                    }
                } catch (err) {
                    alert('Campaign trigger failed');
                }
            }
        });
    }
}
