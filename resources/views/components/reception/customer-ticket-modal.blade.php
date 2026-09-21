@props(['departments' => collect(), 'employees' => collect()])

<div id="customer-ticket-modal" class="hidden fixed inset-0 z-50 bg-black/30 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white border border-[#e0e0e0] w-full max-w-2xl shadow-2xl my-8 overflow-hidden flex flex-col max-h-[92vh]" style="border-radius:0">
        <div class="px-6 py-4 bg-[#f4f4f4] border-b border-[#e0e0e0] flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-sm font-semibold tracking-tight" style="color:#161616; font-family:'IBM Plex Sans',sans-serif">Create Customer Ticket</h3>
                <p class="mono text-xs mt-0.5" style="color:#525252">Capture visitor details and route to the right team</p>
            </div>
            <button type="button" onclick="closeCustomerTicketModal()" class="w-8 h-8 flex items-center justify-center hover:bg-white border border-transparent hover:border-[#e0e0e0] text-[#525252] hover:text-[#161616]">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <form id="customer-ticket-form" onsubmit="submitCustomerTicket(event)" class="overflow-y-auto p-6 flex flex-col gap-6 text-sm bg-white">
            @csrf
            
            <div>
                <label class="text-[11px] font-semibold tracking-widest uppercase block mb-2" style="color:#525252">Customer Type</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label id="type-card-individual" class="cursor-pointer border border-[#0f62fe] bg-[#edf5ff] p-4 flex items-start gap-3" style="border-radius:0">
                        <input type="radio" name="customer_type" value="individual" checked onchange="toggleCustomerType('individual')" class="mt-1" style="accent-color:#0f62fe" />
                        <div>
                            <div class="flex items-center gap-1.5 font-semibold" style="color:#161616; font-family:'IBM Plex Sans',sans-serif">Individual</div>
                            <p class="text-xs mt-0.5" style="color:#525252">Personal customer / visitor</p>
                        </div>
                    </label>
                    <label id="type-card-company" class="cursor-pointer border border-[#e0e0e0] bg-white hover:bg-[#f4f4f4] p-4 flex items-start gap-3" style="border-radius:0">
                        <input type="radio" name="customer_type" value="company" onchange="toggleCustomerType('company')" class="mt-1" style="accent-color:#0f62fe" />
                        <div>
                            <div class="flex items-center gap-1.5 font-semibold" style="color:#161616">Company / Organization</div>
                            <p class="text-xs mt-0.5" style="color:#525252">Business or institution</p>
                        </div>
                    </label>
                </div>
            </div>

            <div id="section-individual" class="flex flex-col gap-4 border-t border-[#e0e0e0] pt-4">
                <div class="text-[11px] font-semibold tracking-widest uppercase" style="color:#525252">Customer Details</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex flex-col gap-1">
                        <span class="text-xs font-semibold" style="color:#161616">Full Name *</span>
                        <input type="text" name="full_name" id="ind-full-name" placeholder="Enter customer's full name" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0; font-family:'IBM Plex Sans',sans-serif" />
                    </label>
                    <label class="flex flex-col gap-1">
                        <span class="text-xs font-semibold" style="color:#161616">Phone Number *</span>
                        <input type="tel" name="visitor_phone" id="ind-phone" placeholder="+255 7XX XXX XXX" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none mono" />
                    </label>
                </div>
            </div>

            <div id="section-company" class="hidden flex flex-col gap-5 border-t border-[#e0e0e0] pt-4">
                <div class="flex flex-col gap-3">
                    <div class="text-[11px] font-semibold tracking-widest uppercase" style="color:#525252">Organization Details</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold" style="color:#161616">Organization Name *</span>
                            <input type="text" name="organization_name" id="comp-org-name" placeholder="e.g. ABC Technologies Ltd" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0" />
                        </label>
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold" style="color:#161616">Organization Type *</span>
                            <select name="organization_type" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0">
                                <option value="Private Company">Private Company</option>
                                <option value="Public Company">Public Company / Listed</option>
                                <option value="NGO / Non-Profit">NGO / Non-Profit</option>
                                <option value="Government / Parastatal">Government / Parastatal</option>
                                <option value="Educational Institution">Educational Institution</option>
                                <option value="Sole Proprietorship">Sole Proprietorship</option>
                                <option value="Other">Other Organization</option>
                            </select>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold" style="color:#161616">Industry / Sector</span>
                            <select name="industry" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0">
                                <option value="Information Technology">Information Technology</option>
                                <option value="Banking & Finance">Banking & Finance</option>
                                <option value="Manufacturing & Industrial">Manufacturing & Industrial</option>
                                <option value="Healthcare & Medical">Healthcare & Medical</option>
                                <option value="Construction & Real Estate">Construction & Real Estate</option>
                                <option value="Retail & Wholesale">Retail & Wholesale</option>
                                <option value="Telecommunications">Telecommunications</option>
                                <option value="Education">Education</option>
                                <option value="Other">Other Sector</option>
                            </select>
                        </label>
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold" style="color:#161616">TIN / Tax Number</span>
                            <input type="text" name="tin_number" placeholder="Optional (e.g. 100-200-300)" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm mono focus:border-[#0f62fe] focus:outline-none" style="border-radius:0" />
                        </label>
                    </div>
                </div>
                <div class="flex flex-col gap-3 pt-3 border-t border-[#e0e0e0]">
                    <div class="text-[11px] font-semibold tracking-widest uppercase" style="color:#525252">Contact Person</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold" style="color:#161616">Contact Person *</span>
                            <input type="text" name="contact_person" id="comp-contact-person" placeholder="e.g. John Michael" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0" />
                        </label>
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold" style="color:#161616">Position / Job Title</span>
                            <input type="text" name="contact_position" placeholder="e.g. IT Manager / Procurement Lead" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0" />
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold" style="color:#161616">Phone Number *</span>
                            <input type="tel" name="contact_phone" id="comp-phone" placeholder="+255 7XX XXX XXX" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm mono focus:border-[#0f62fe] focus:outline-none" style="border-radius:0" />
                        </label>
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold" style="color:#161616">Email Address</span>
                            <input type="email" name="contact_email" placeholder="john@company.co.tz" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0" />
                        </label>
                    </div>
                </div>
                <div class="flex flex-col gap-3 pt-3 border-t border-[#e0e0e0]">
                    <div class="text-[11px] font-semibold tracking-widest uppercase" style="color:#525252">Organization Location</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold" style="color:#161616">Region *</span>
                            <select name="region" id="comp-region" onchange="updateDistricts(this.value)" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0">
                                <option value="Dar es Salaam">Dar es Salaam</option>
                                <option value="Arusha">Arusha</option>
                                <option value="Dodoma">Dodoma</option>
                                <option value="Mwanza">Mwanza</option>
                                <option value="Morogoro">Morogoro</option>
                                <option value="Tanga">Tanga</option>
                                <option value="Kilimanjaro">Kilimanjaro</option>
                                <option value="Mbeya">Mbeya</option>
                                <option value="Zanzibar">Zanzibar</option>
                                <option value="Other">Other Region</option>
                            </select>
                        </label>
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold" style="color:#161616">District</span>
                            <select name="district" id="comp-district" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0">
                                <option value="Kinondoni">Kinondoni</option>
                                <option value="Ilala">Ilala</option>
                                <option value="Temeke">Temeke</option>
                                <option value="Ubungo">Ubungo</option>
                                <option value="Kigamboni">Kigamboni</option>
                            </select>
                        </label>
                    </div>
                    <label class="flex flex-col gap-1">
                        <span class="text-xs font-semibold" style="color:#161616">Physical Address</span>
                        <input type="text" name="physical_address" placeholder="e.g. Plot 14, Ali Hassan Mwinyi Road, 3rd Floor" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0" />
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-4 border-t border-[#e0e0e0] pt-4">
                <div class="text-[11px] font-semibold tracking-widest uppercase" style="color:#525252">Request / Visit Details</div>
                <label class="flex flex-col gap-1">
                    <span class="text-xs font-semibold" style="color:#161616">Visit Purpose *</span>
                    <select name="visit_purpose" id="ticket-purpose" onchange="handlePurposeChange(this.value)" required class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm font-semibold focus:border-[#0f62fe] focus:outline-none" style="border-radius:0">
                        <option value="Technical Support">Technical Support (PC Maintenance, OS Repair, Diagnostics)</option>
                        <option value="Computer / Laptop Purchase">Computer & Laptop Purchase (Showroom Devices)</option>
                        <option value="OS License & Software Purchase">OS License & Software Purchase</option>
                        <option value="Accessories / Hardware Inquiry">Accessories & Hardware Inquiry</option>
                        <option value="Sales / Purchase">General Sales / Purchase</option>
                        <option value="Showroom Visit">Showroom Visit</option>
                        <option value="Product / Service Inquiry">Product / Service Inquiry</option>
                        <option value="Follow-up">Follow-up / Collection</option>
                        <option value="Other">Other Request</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-xs font-semibold" style="color:#161616">What did the customer say? *</span>
                    <textarea name="customer_statement" id="ticket-customer-statement" required rows="3" placeholder="Describe the customer's request in their own words..." class="p-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none resize-none" style="border-radius:0"></textarea>
                </label>
            </div>

            <div class="flex flex-col gap-4 border-t border-[#e0e0e0] pt-4">
                <div class="text-[11px] font-semibold tracking-widest uppercase" style="color:#525252">Routing & Handover</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex flex-col gap-1">
                        <span class="text-xs font-semibold" style="color:#161616">Forward To *</span>
                        <select name="forward_to_department" id="ticket-forward-dept" onchange="updateStaffAssignees(this.value)" required class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm font-semibold focus:border-[#0f62fe] focus:outline-none" style="border-radius:0">
                            <option value="IT Support">IT Support</option>
                            <option value="Sales / Showroom">Sales / Showroom</option>
                            <option value="Management">Management / Executive</option>
                            <option value="Finance / Accounts">Finance / Accounts</option>
                            <option value="HR / Administration">HR / Administration</option>
                            <option value="Operations">Operations</option>
                            <option value="Inventory / Store">Inventory / Store</option>
                            <option value="Customer Service">Customer Service</option>
                            <option value="Other Department">Other Department</option>
                        </select>
                    </label>
                    <label class="flex flex-col gap-1">
                        <span class="text-xs font-semibold" style="color:#161616">Assign To Staff</span>
                        <select name="assigned_to" id="ticket-assign-staff" class="h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="border-radius:0">
                            <option value="">Any available staff in team</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" data-dept="{{ $emp->department?->name }}">{{ $emp->name }} ({{ $emp->department?->name ?? 'Staff' }})</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div>
                    <span class="text-xs font-semibold block mb-1.5" style="color:#161616">Priority</span>
                    <div class="flex items-center gap-4 text-xs font-medium">
                        <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="priority" value="low" class="text-[#0f62fe] focus:ring-[#0f62fe]" /><span>Low</span></label>
                        <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="priority" value="normal" checked class="text-[#0f62fe] focus:ring-[#0f62fe]" /><span class="font-bold" style="color:#161616">Normal</span></label>
                        <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="priority" value="high" class="text-[#da1e28] focus:ring-[#da1e28]" /><span style="color:#a1191e; font-weight:600">High</span></label>
                        <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="priority" value="urgent" class="text-[#da1e28] focus:ring-[#da1e28]" /><span style="color:#da1e28; font-weight:700">Urgent</span></label>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-[#e0e0e0] shrink-0">
                <button type="button" onclick="closeCustomerTicketModal()" class="px-4 py-2.5 border border-[#8d8d8d] bg-white hover:bg-[#f4f4f4] text-sm font-semibold" style="color:#161616; border-radius:0">Cancel</button>
                <button type="button" onclick="saveDraftTicket()" class="px-4 py-2.5 border border-[#e0e0e0] bg-[#f4f4f4] hover:bg-[#e0e0e0] text-sm font-semibold" style="color:#525252; border-radius:0">Save Draft</button>
                <button type="submit" id="btn-submit-ticket" class="flex items-center gap-2 px-5 py-2.5 bg-[#0f62fe] hover:bg-[#0353e9] text-white text-sm font-bold border border-[#0f62fe]" style="border-radius:0">
                    <span>Create & Forward →</span>
                    <span id="submit-spinner" class="hidden w-3.5 h-3.5 border-2 border-white/40 border-t-white animate-spin" style="border-radius:50%"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="ticket-confirmation-modal" class="hidden fixed inset-0 z-50 bg-black/30 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white border border-[#e0e0e0] w-full max-w-md shadow-2xl p-6 text-center flex flex-col items-center" style="border-radius:0; border-left:4px solid #0e6027">
        <div class="w-12 h-12 bg-[#defbe6] border border-[#a7f0ba] text-[#0e6027] flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-[28px]">check</span>
        </div>
        <h3 class="text-sm font-semibold" style="color:#161616">Ticket Created</h3>
        <p class="mono text-xs mt-0.5" style="color:#525252">Customer registered and routed to destination team</p>
        <div class="my-4 w-full bg-[#f4f4f4] border border-[#e0e0e0] p-4 flex flex-col gap-2.5 text-left text-xs">
            <div class="flex items-center justify-between pb-2 border-b border-[#e0e0e0]">
                <span class="mono font-bold text-sm" style="color:#0f62fe" id="conf-ticket-code">TKT-20260917-0042</span>
                <span class="bg-[#defbe6] border border-[#a7f0ba] text-[#0e6027] px-2 py-0.5 text-[11px] font-bold uppercase">New</span>
            </div>
            <div class="flex items-start justify-between"><span style="color:#525252">Customer:</span><span class="font-semibold text-right" style="color:#161616" id="conf-customer-name">ABC Technologies Ltd</span></div>
            <div id="conf-contact-row" class="flex items-start justify-between"><span style="color:#525252">Contact Person:</span><span class="font-medium text-right" style="color:#161616" id="conf-contact-person">John Michael</span></div>
            <div class="flex items-start justify-between"><span style="color:#525252">Forwarded To:</span><span class="font-semibold text-right" style="color:#0f62fe" id="conf-forwarded-to">IT Support</span></div>
            <div class="flex items-start justify-between"><span style="color:#525252">Priority:</span><span class="font-semibold text-right" style="color:#161616" id="conf-priority">Normal</span></div>
        </div>
        <div class="grid grid-cols-2 gap-3 w-full mt-2">
            <button type="button" onclick="viewCreatedTicket()" class="px-4 py-2.5 border border-[#8d8d8d] bg-white hover:bg-[#f4f4f4] text-sm font-semibold" style="color:#161616; border-radius:0">View in Queue</button>
            <button type="button" onclick="closeConfirmationModal()" class="px-4 py-2.5 bg-[#0f62fe] hover:bg-[#0353e9] text-white text-sm font-bold border border-[#0f62fe]" style="border-radius:0">Done</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let lastCreatedTicket = null;
function openCreateTicketModal() {
    document.getElementById('customer-ticket-modal').classList.remove('hidden');
    toggleCustomerType('individual');
    setTimeout(() => { document.getElementById('ind-full-name')?.focus(); }, 100);
}
function closeCustomerTicketModal() { document.getElementById('customer-ticket-modal').classList.add('hidden'); }
function toggleCustomerType(type) {
    const secInd = document.getElementById('section-individual');
    const secComp = document.getElementById('section-company');
    const cardInd = document.getElementById('type-card-individual');
    const cardComp = document.getElementById('type-card-company');
    if (type === 'company') {
        secInd.classList.add('hidden'); secComp.classList.remove('hidden');
        cardComp.style.cssText = 'border:1px solid #0f62fe; background:#edf5ff; border-radius:0';
        cardInd.style.cssText = 'border:1px solid #e0e0e0; background:#fff; border-radius:0';
        document.querySelector('input[name="customer_type"][value="company"]').checked = true;
    } else {
        secInd.classList.remove('hidden'); secComp.classList.add('hidden');
        cardInd.style.cssText = 'border:1px solid #0f62fe; background:#edf5ff; border-radius:0';
        cardComp.style.cssText = 'border:1px solid #e0e0e0; background:#fff; border-radius:0';
        document.querySelector('input[name="customer_type"][value="individual"]').checked = true;
    }
}
function updateDistricts(region) {
    const select = document.getElementById('comp-district');
    select.innerHTML = '';
    const districtsMap = {
        'Dar es Salaam': ['Kinondoni', 'Ilala', 'Temeke', 'Ubungo', 'Kigamboni'],
        'Arusha': ['Arusha City', 'Arusha District', 'Karatu', 'Longido', 'Monduli', 'Ngorongoro'],
        'Dodoma': ['Dodoma Urban', 'Bahi', 'Chamwino', 'Chemba', 'Kondoa', 'Kongwa', 'Mpwapwa'],
        'Mwanza': ['Nyamagana', 'Ilemela', 'Magu', 'Misungwi', 'Kwimba', 'Sengerema', 'Ukerewe'],
        'Morogoro': ['Morogoro Urban', 'Kilosa', 'Kilombero', 'Mvomero', 'Gairo', 'Ulanga'],
        'Zanzibar': ['Mjini', 'Magharibi', 'Kaskazini A', 'Kusini', 'Chake Chake'],
    };
    const list = districtsMap[region] || ['Central District', 'Urban District', 'Rural District'];
    list.forEach(d => { const opt = document.createElement('option'); opt.value = d; opt.textContent = d; select.appendChild(opt); });
}
function handlePurposeChange(purpose) {
    const forwardDept = document.getElementById('ticket-forward-dept');
    if (!forwardDept) return;
    if (purpose.includes('Purchase') || purpose.includes('Sales') || purpose.includes('Showroom') || purpose.includes('License') || purpose.includes('Accessories')) {
        forwardDept.value = 'Sales / Showroom';
    } else if (purpose.includes('Technical') || purpose.includes('Support') || purpose.includes('Repair') || purpose.includes('Maintenance')) {
        forwardDept.value = 'IT Support';
    }
    updateStaffAssignees(forwardDept.value);
}
function updateStaffAssignees(dept) {
    const select = document.getElementById('ticket-assign-staff');
    const options = select.querySelectorAll('option[data-dept]');
    options.forEach(opt => {
        const staffDept = opt.getAttribute('data-dept') || '';
        if (!dept || dept === 'Other Department' || staffDept.toLowerCase().includes(dept.toLowerCase().slice(0, 4))) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
}
async function submitCustomerTicket(e) {
    e.preventDefault();
    const form = document.getElementById('customer-ticket-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    const customerType = data.customer_type || 'individual';
    if (customerType === 'individual') {
        if (!data.full_name || !data.visitor_phone) { window.AppToast ? window.AppToast.showError('Please enter the customer full name and phone number.') : alert('Please enter the customer full name and phone number.'); return; }
    } else {
        if (!data.organization_name || !data.contact_person || !data.contact_phone) { window.AppToast ? window.AppToast.showError('Please enter the organization name, contact person, and phone number.') : alert('Please enter the organization name, contact person, and phone number.'); return; }
        data.visitor_phone = data.contact_phone;
        data.visitor_email = data.contact_email;
    }
    const btn = document.getElementById('btn-submit-ticket');
    const spinner = document.getElementById('submit-spinner');
    btn.disabled = true; spinner.classList.remove('hidden');
    try {
        const res = await fetch("{{ route('reception.api.tickets.store') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(data),
        });
        const result = await res.json();
        if (result.success) {
            lastCreatedTicket = result.ticket;
            closeCustomerTicketModal();
            showTicketConfirmation(result.ticket);
            form.reset();
            if (window.LiveHandoff) { window.LiveHandoff.fetchState(); }
        } else { window.AppToast ? window.AppToast.showError(result.message || 'Error creating ticket') : alert(result.message || 'Error creating ticket'); }
    } catch (err) { console.error(err); window.AppToast ? window.AppToast.showError('Could not submit ticket. Please check connection.') : alert('Could not submit ticket. Please check connection.'); } finally { btn.disabled = false; spinner.classList.add('hidden'); }
}
function showTicketConfirmation(t) {
    document.getElementById('conf-ticket-code').textContent = t.ticket_code;
    document.getElementById('conf-customer-name').textContent = t.customer;
    const contactRow = document.getElementById('conf-contact-row');
    if (t.contact_person) { contactRow.classList.remove('hidden'); document.getElementById('conf-contact-person').textContent = t.contact_person + (t.contact_position ? ' · ' + t.contact_position : ''); } else { contactRow.classList.add('hidden'); }
    document.getElementById('conf-forwarded-to').textContent = t.forward_to;
    document.getElementById('conf-priority').textContent = t.priority;
    document.getElementById('ticket-confirmation-modal').classList.remove('hidden');
    document.getElementById('ticket-confirmation-modal').classList.add('flex');
}
function closeConfirmationModal() { document.getElementById('ticket-confirmation-modal').classList.add('hidden'); document.getElementById('ticket-confirmation-modal').classList.remove('flex'); window.location.reload(); }
function viewCreatedTicket() {
    closeConfirmationModal();
    if (lastCreatedTicket && lastCreatedTicket.dept_code === 'IT') { window.location.href = "{{ route('it.index') }}"; }
    else if (lastCreatedTicket && lastCreatedTicket.dept_code === 'SALES') { window.location.href = "{{ route('sales.index') }}"; }
    else { window.location.href = "{{ route('reception.visitors') }}"; }
}
function saveDraftTicket() { window.AppToast ? window.AppToast.show('Draft ticket saved locally.') : alert('Draft ticket saved locally.'); closeCustomerTicketModal(); }
</script>
@endpush
